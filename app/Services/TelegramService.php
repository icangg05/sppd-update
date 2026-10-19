<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien Telegram Bot API untuk notifikasi SPPD.
 *
 * Bot hanya bisa mengirim ke chat_id yang didapat setelah pengguna menekan
 * Start (lihat TelegramUpdateHandler) — bukan ke nomor HP.
 */
class TelegramService
{
  /**
   * Kirim pesan ke chat Telegram.
   *
   * Pesan boleh memakai markup gaya WhatsApp (*tebal*, _miring_); toHtml()
   * mengubahnya ke HTML Telegram sehingga builder pesan lama tetap dipakai apa adanya.
   *
   * @param  string  $chatId   chat_id tujuan (didapat saat pengguna menghubungkan akun)
   * @param  string  $message  Isi pesan
   */
  public function send(string $chatId, string $message): bool
  {
    if (! config('telegram.enabled')) {
      return false;
    }

    if (empty(config('telegram.bot_token'))) {
      Log::warning('TelegramService: TELEGRAM_BOT_TOKEN tidak dikonfigurasi.');

      return false;
    }

    try {
      $response = $this->call('sendMessage', [
        'chat_id' => $chatId,
        'text' => $this->toHtml($message),
        'parse_mode' => 'HTML',
        'link_preview_options' => ['is_disabled' => true],
      ]);

      // Markup tidak valid (mis. tag bersilangan) → kirim ulang sebagai teks polos.
      if ($response->status() === 400 && str_contains((string) $response->json('description'), "can't parse entities")) {
        $response = $this->call('sendMessage', [
          'chat_id' => $chatId,
          'text' => $message,
          'link_preview_options' => ['is_disabled' => true],
        ]);
      }

      if ($response->successful() && $response->json('ok') === true) {
        Log::info('TelegramService: Pesan berhasil dikirim.', ['chat_id' => $chatId]);

        return true;
      }

      // 403 = pengguna memblokir bot / akun Telegram dihapus. Putuskan tautannya
      // agar UI menampilkan "belum terhubung" dan pengguna bisa menghubungkan ulang.
      if ($response->status() === 403) {
        User::where('telegram_chat_id', $chatId)->update([
          'telegram_chat_id' => null,
          'telegram_username' => null,
          'telegram_linked_at' => null,
        ]);
      }

      Log::warning('TelegramService: API mengembalikan respons non-sukses.', [
        'status' => $response->status(),
        'body' => $response->body(),
        'chat_id' => $chatId,
      ]);

      return false;
    } catch (\Throwable $e) {
      Log::error('TelegramService: Exception saat mengirim pesan.', [
        'error' => $this->redact($e->getMessage()),
        'chat_id' => $chatId,
      ]);

      return false;
    }
  }

  /**
   * Ubah markup gaya WhatsApp ke HTML Telegram. Teks di-escape lebih dulu agar
   * karakter < > & dari input pengguna (maksud perjalanan, catatan) aman.
   */
  public function toHtml(string $message): string
  {
    $html = htmlspecialchars($message, ENT_NOQUOTES, 'UTF-8');
    $html = preg_replace('/\*([^*\n]+)\*/u', '<b>$1</b>', $html);

    // _miring_ hanya bila tidak menempel huruf/angka, agar snake_case di URL tidak tersentuh.
    return preg_replace('/(?<![\p{L}\p{N}])_([^_\n]+)_(?![\p{L}\p{N}])/u', '<i>$1</i>', $html);
  }

  /**
   * Cek token bot dengan getMe.
   *
   * @return array{success: bool, username: ?string, message: string}
   */
  public function getMe(): array
  {
    if (empty(config('telegram.bot_token'))) {
      return ['success' => false, 'username' => null, 'message' => 'Token bot belum dikonfigurasi.'];
    }

    try {
      $response = $this->call('getMe', [], 10);

      if ($response->successful() && $response->json('ok') === true) {
        return [
          'success' => true,
          'username' => $response->json('result.username'),
          'message' => 'Koneksi berhasil.',
        ];
      }

      return [
        'success' => false,
        'username' => null,
        'message' => 'API menolak permintaan (HTTP '.$response->status().'): '.$response->json('description', '-'),
      ];
    } catch (\Throwable $e) {
      return ['success' => false, 'username' => null, 'message' => 'Gagal terhubung: '.$this->redact($e->getMessage())];
    }
  }

  /**
   * Daftarkan URL webhook. Telegram mengirim balik $secret pada header
   * X-Telegram-Bot-Api-Secret-Token di setiap request.
   *
   * @return array{ok?: bool, description?: string}
   */
  public function setWebhook(string $url, ?string $secret = null): array
  {
    return $this->call('setWebhook', array_filter([
      'url' => $url,
      'secret_token' => $secret ?: null,
      'allowed_updates' => ['message'],
      'drop_pending_updates' => true,
    ]))->json() ?? [];
  }

  /** @return array{ok?: bool, description?: string} */
  public function deleteWebhook(): array
  {
    return $this->call('deleteWebhook')->json() ?? [];
  }

  /** @return array{ok?: bool, result?: array<string, mixed>, description?: string} */
  public function getWebhookInfo(): array
  {
    return $this->call('getWebhookInfo')->json() ?? [];
  }

  /**
   * Ambil update baru lewat long-polling (hanya bila webhook tidak aktif).
   *
   * @return array{ok?: bool, result?: array<int, array<string, mixed>>, description?: string}
   */
  public function getUpdates(int $offset, int $timeout = 25): array
  {
    return $this->call('getUpdates', [
      'offset' => $offset,
      'timeout' => $timeout,
      'allowed_updates' => ['message'],
    ], $timeout + 10)->json() ?? [];
  }

  /**
   * Panggil method Bot API. Hanya kegagalan koneksi yang diulang; respons error
   * dikembalikan apa adanya agar pemanggil bisa membaca deskripsinya.
   */
  protected function call(string $method, array $payload = [], int $timeout = 15): Response
  {
    $url = rtrim((string) config('telegram.api_url'), '/').'/bot'.config('telegram.bot_token').'/'.$method;

    return Http::timeout($timeout)
      ->retry(2, 1000, fn ($e) => $e instanceof ConnectionException, throw: false)
      ->acceptJson()
      ->asJson()
      ->post($url, $payload);
  }

  /** Token bot ada di URL; jangan sampai ikut tercatat di log. */
  private function redact(string $text): string
  {
    $token = (string) config('telegram.bot_token');

    return $token === '' ? $text : str_replace($token, '***', $text);
  }
}
