<?php

namespace App\Services;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Memproses update masuk dari bot Telegram — dipakai bersama oleh webhook
 * (TelegramWebhookController) dan mode polling (`telegram:poll`).
 *
 * Alur verifikasi: aplikasi membuat token (InteractsWithTelegramLink) →
 * pengguna membuka t.me/<bot>?start=<token> dan menekan Start → bot menerima
 * "/start <token>" → token dicocokkan dengan cache → chat_id disimpan dan
 * polling di browser menangkap status terhubung.
 */
class TelegramUpdateHandler
{
  /** Masa berlaku token & status verifikasi di cache. */
  public const TTL_MINUTES = 15;

  /**
   * Store cache untuk token verifikasi. Sengaja "database" (bukan default "file")
   * agar terbaca lintas container: pada mode polling, update diproses container
   * scheduler sedangkan polling Livewire berjalan di container app.
   */
  public static function cache(): Repository
  {
    return Cache::store('database');
  }

  /** @param  array<string, mixed>  $update */
  public function handle(array $update): void
  {
    $message = $update['message'] ?? null;
    if (! is_array($message)) {
      return;
    }

    $chatId = $message['chat']['id'] ?? null;
    $chatType = $message['chat']['type'] ?? null;
    $from = is_array($message['from'] ?? null) ? $message['from'] : [];
    $text = trim((string) ($message['text'] ?? ''));

    if ($chatId === null || ! empty($from['is_bot'])) {
      return;
    }

    $chatId = (string) $chatId;

    if (! preg_match('/^\/start(?:@\w+)?\s+([A-Za-z0-9_-]{16,64})$/', $text, $match)) {
      // Pesan biasa / "/start" tanpa token: beri petunjuk, hanya di chat pribadi.
      if ($chatType === 'private') {
        $this->reply($chatId, "👋 *Bot Notifikasi SPPD Kendari*\n\n"
          . "Bot ini mengirim notifikasi pengajuan & persetujuan SPPD.\n\n"
          . "Untuk menghubungkan akun, buka aplikasi SPPD → *Profil Saya* → tekan *Hubungkan Telegram*, "
          . "lalu ikuti tautan yang muncul.\n\n"
          . "_Pesan otomatis, tidak perlu dibalas._");
      }

      return;
    }

    $token = $match[1];
    $cached = self::cache()->get("tg_link:{$token}");

    if (! $cached) {
      Log::info('TelegramUpdate: token verifikasi tidak ditemukan / kedaluwarsa.', ['chat_id' => $chatId]);

      if ($chatType === 'private') {
        $this->reply($chatId, "⌛ *TAUTAN KEDALUWARSA*\n\n"
          . "Tautan verifikasi sudah tidak berlaku atau sudah dipakai.\n\n"
          . "Silakan ulangi dari menu *Hubungkan Telegram* di aplikasi SPPD.");
      }

      return;
    }

    // Tautan dibuka dari grup: chat_id grup tidak boleh dipakai untuk notifikasi pribadi.
    if ($chatType !== 'private') {
      self::cache()->put("tg_link_failed:{$token}", [
        'message' => 'Tautan dibuka dari grup. Buka tautan langsung di chat pribadi dengan bot, lalu tekan Start.',
      ], now()->addMinutes(self::TTL_MINUTES));

      return;
    }

    $username = $from['username'] ?? null;

    self::cache()->put("tg_link_status:{$token}", [
      'verified' => true,
      'chat_id' => $chatId,
      'username' => $username,
      'user_id' => $cached['user_id'] ?? null,
    ], now()->addMinutes(self::TTL_MINUTES));

    // Simpan langsung ke DB bila pemilik token sudah ada (profil / edit pegawai).
    if (! empty($cached['user_id'])) {
      $user = User::find($cached['user_id']);
      if ($user) {
        $user->update([
          'telegram_chat_id' => $chatId,
          'telegram_username' => $username,
          'telegram_linked_at' => now(),
        ]);
        Log::info("TelegramUpdate: Berhasil menghubungkan Telegram user ID {$user->id}.");
      }
    }

    $name = $cached['name'] ?? 'Pegawai';
    $this->reply($chatId, "✅ *AKUN TERHUBUNG!*\n\n"
      . "Halo *{$name}*, akun Telegram Anda telah sukses terhubung dengan *Sistem SPPD Elektronik Kota Kendari*.\n\n"
      . "Anda sekarang akan menerima notifikasi perjalanan dinas secara otomatis di chat ini. Terima kasih!");

    // Token sekali pakai.
    self::cache()->forget("tg_link:{$token}");
  }

  protected function reply(string $chatId, string $message): void
  {
    SendTelegramNotificationJob::dispatch($chatId, $message);
  }
}
