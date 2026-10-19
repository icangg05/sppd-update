<?php

namespace App\Livewire\Concerns;

use App\Jobs\SendTelegramNotificationJob;
use App\Services\QueueHealthService;
use App\Services\TelegramUpdateHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Alur menghubungkan akun Telegram yang dipakai bersama oleh formulir pegawai
 * (UserForm) dan halaman profil (Profile).
 *
 * Bot Telegram hanya bisa mengirim ke chat_id, dan chat_id baru diketahui setelah
 * pengguna menekan Start. Alurnya: buat token → pengguna membuka
 * t.me/<bot>?start=<token> → TelegramUpdateHandler mencocokkan token & menyimpan
 * chat_id → polling di trait ini menangkap status terhubung.
 *
 * Komponen dapat menimpa hook di bawah untuk menyesuaikan konteks:
 *  - linkUserId()          : id pemilik akun (null saat pegawai belum dibuat)
 *  - linkName()            : nama pada pesan sambutan bot
 *  - onTelegramLinked()    : aksi setelah akun terhubung (mis. simpan ke DB)
 *  - onTelegramUnlinked()  : aksi setelah tautan diputus
 *  - canSendTestMessage()  : boleh mengirim pesan tes (mis. saklar notifikasi)
 */
trait InteractsWithTelegramLink
{
  // Status tautan Telegram pemilik akun — hanya diubah server.
  #[Locked]
  public bool $telegramLinked = false;
  #[Locked]
  public string $telegramChatId = '';
  #[Locked]
  public string $telegramUsername = '';

  // State modal & polling
  public bool $showLinkModal = false;
  public bool $showTestConfirm = false;
  public bool $isPolling = false;
  #[Locked]
  public string $deeplinkUrl = '';
  #[Locked]
  public string $linkToken = '';
  public bool $isVerified = false;
  public bool $isFailed = false;
  public bool $isTimedOut = false;
  public string $failedMessage = '';
  public int $pollingCount = 0;

  /** Id pemilik akun; null saat pegawai baru belum disimpan. */
  protected function linkUserId(): ?int
  {
    return null;
  }

  protected function linkName(): string
  {
    return $this->name ?: 'Pegawai';
  }

  /** Dipanggil saat akun berhasil terhubung (mis. persist ke DB). */
  protected function onTelegramLinked(string $chatId, ?string $username): void {}

  /** Dipanggil saat tautan Telegram diputus. */
  protected function onTelegramUnlinked(): void {}

  /** Isi status tautan dari data pengguna. Panggil dari mount() komponen. */
  protected function fillTelegramState(?string $chatId, ?string $username): void
  {
    $this->telegramChatId = (string) $chatId;
    $this->telegramUsername = (string) $username;
    $this->telegramLinked = filled($chatId);
  }

  /**
   * Memulihkan polling bila halaman di-refresh saat menunggu pengguna menekan Start.
   * Hanya untuk token milik akun yang sama, agar tautan pegawai lain yang dibuka
   * admin di tab lain tidak tersimpan ke akun yang salah. Panggil dari mount().
   */
  public function restoreTelegramLinkState(): void
  {
    $token = session()->get('tg_link_token');
    if (! $token || $this->telegramLinked) {
      return;
    }

    $cache = TelegramUpdateHandler::cache();
    $pending = $cache->get("tg_link:{$token}") ?? $cache->get("tg_link_status:{$token}");

    if ($pending && (int) ($pending['user_id'] ?? 0) === (int) ($this->linkUserId() ?? 0)) {
      $this->linkToken = $token;
      $this->deeplinkUrl = $this->deeplinkFor($token);
      $this->isPolling = true;
    } elseif (! $pending) {
      session()->forget('tg_link_token');
    }
  }

  public function openLinkModal(): void
  {
    if (! config('telegram.enabled') || empty(config('telegram.bot_token')) || empty(config('telegram.bot_username'))) {
      $this->toastError('Bot Telegram belum dikonfigurasi. Hubungi administrator sistem.');
      return;
    }

    // Gerbang: balasan konfirmasi & semua notifikasi dikirim lewat worker queue.
    // Bila worker mati, tolak sebelum membuka modal agar pengguna tidak menunggu sia-sia.
    if (! app(QueueHealthService::class)->isWorkerHealthy()) {
      $this->toastError($this->queueDownMessage());
      return;
    }

    $cache = TelegramUpdateHandler::cache();
    $ttl = now()->addMinutes(TelegramUpdateHandler::TTL_MINUTES);
    $userId = $this->linkUserId();

    // Hanya satu tautan yang berlaku per akun: batalkan token sebelumnya.
    if ($userId && ($prevToken = $cache->get("tg_link_user:{$userId}"))) {
      $this->forgetLinkToken($prevToken);
    }
    if ($this->linkToken !== '') {
      $this->forgetLinkToken($this->linkToken);
    }

    $this->linkToken = Str::random(32);

    $cache->put("tg_link:{$this->linkToken}", [
      'user_id' => $userId,
      'name' => $this->linkName(),
    ], $ttl);

    if ($userId) {
      $cache->put("tg_link_user:{$userId}", $this->linkToken, $ttl);
    }

    $this->deeplinkUrl = $this->deeplinkFor($this->linkToken);

    session()->put('tg_link_token', $this->linkToken);

    $this->isVerified = false;
    $this->isFailed = false;
    $this->isTimedOut = false;
    $this->failedMessage = '';
    $this->pollingCount = 0;

    $this->showLinkModal = true;
    $this->isPolling = true;
  }

  public function checkLinkStatus(): void
  {
    if (! $this->isPolling) {
      return;
    }

    $this->pollingCount++;
    if ($this->pollingCount > 300) { // 5 menit dengan interval 1 dtk
      $this->isTimedOut = true;
      $this->isPolling = false;
      session()->forget('tg_link_token');
      return;
    }

    $cache = TelegramUpdateHandler::cache();

    $status = $cache->get("tg_link_status:{$this->linkToken}");
    if ($status && ! empty($status['verified'])) {
      $this->isVerified = true;
      $this->isPolling = false;
      $this->fillTelegramState((string) $status['chat_id'], $status['username'] ?? null);
      session()->forget('tg_link_token');
      $this->forgetLinkToken($this->linkToken);

      $this->onTelegramLinked($this->telegramChatId, $this->telegramUsername ?: null);
      return;
    }

    $failed = $cache->get("tg_link_failed:{$this->linkToken}");
    if ($failed) {
      $this->isFailed = true;
      $this->failedMessage = $failed['message'] ?? 'Gagal menghubungkan Telegram.';
      $this->isPolling = false;
      session()->forget('tg_link_token');
      return;
    }

    // Masih menunggu: tidak ada yang berubah di tampilan, jadi lewati render
    // agar polling tiap detik tetap ringan.
    $this->skipRender();
  }

  /** QR tautan verifikasi, agar pengguna di komputer bisa memindai dari ponsel. */
  #[Computed]
  public function linkQrSvg(): string
  {
    return $this->deeplinkUrl === ''
      ? ''
      : (string) QrCode::size(168)->margin(1)->generate($this->deeplinkUrl);
  }

  public function closeLinkModal(): void
  {
    $this->showLinkModal = false;
  }

  public function retryLink(): void
  {
    $this->closeLinkModal();
    $this->openLinkModal();
  }

  public function unlinkTelegram(): void
  {
    $this->fillTelegramState(null, null);
    $this->isVerified = false;
    $this->isPolling = false;
    session()->forget('tg_link_token');

    $this->onTelegramUnlinked();

    $this->toastSuccess('Tautan Telegram diputus. Notifikasi tidak akan dikirim sampai akun dihubungkan kembali.');
  }

  /**
   * Boleh mengirim pesan tes. Komponen menimpa ini bila punya saklar notifikasi
   * sendiri (mis. Profile: notifikasi Telegram dinonaktifkan pengguna).
   */
  protected function canSendTestMessage(): bool
  {
    return true;
  }

  /** Buka modal konfirmasi sebelum mengirim pesan tes. */
  public function confirmTestMessage(): void
  {
    if (! $this->telegramLinked) {
      $this->toastError('Akun Telegram belum terhubung.');
      return;
    }
    if (! $this->canSendTestMessage()) {
      $this->toastWarning('Notifikasi Telegram sedang nonaktif. Aktifkan dulu untuk mengirim pesan tes.');
      return;
    }
    $this->showTestConfirm = true;
  }

  public function closeTestConfirm(): void
  {
    $this->showTestConfirm = false;
  }

  /**
   * Kirim pesan tes ke akun Telegram yang terhubung untuk memastikan jalur
   * notifikasi berfungsi. Pesan dititipkan ke worker queue (jalur yang sama
   * dgn notifikasi asli), jadi worker harus hidup lebih dulu.
   */
  public function sendTestMessage(): void
  {
    // Tutup modal konfirmasi; hasil disampaikan lewat toast.
    $this->showTestConfirm = false;

    if (! $this->telegramLinked || ! $this->canSendTestMessage()) {
      $this->toastError('Pesan tes tidak dapat dikirim: akun Telegram belum terhubung atau notifikasi nonaktif.');
      return;
    }

    // Batasi 1 kirim per 30 detik per pengguna agar tidak bisa di-spam.
    $rateKey = 'tg-test:' . (Auth::id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($rateKey, 1)) {
      $this->toastWarning('Tunggu ' . RateLimiter::availableIn($rateKey) . ' detik sebelum mengirim pesan tes lagi.');
      return;
    }

    // Pesan tes diproses worker queue; bila worker mati, pesan tak akan terkirim.
    if (! app(QueueHealthService::class)->isWorkerHealthy()) {
      $this->toastError('Pesan tes gagal dikirim: layanan antrian (queue worker) '
        . 'sedang tidak berjalan. Hubungi administrator sistem dan coba lagi setelah aktif.');
      return;
    }

    $name = $this->linkName();

    $message = "🔔 *Tes Notifikasi SPPD Kendari*\n"
      . "Halo {$name}, notifikasi Telegram ke akun Anda aktif. ✅\n\n"
      . "_Pesan otomatis, tidak perlu dibalas._";

    SendTelegramNotificationJob::dispatch($this->telegramChatId, $message);
    RateLimiter::hit($rateKey, 30);

    $target = $this->telegramUsername !== '' ? '@' . $this->telegramUsername : 'akun Telegram yang terhubung';
    $this->toastSuccess("Pesan tes sedang dikirim ke {$target}. Periksa chat bot di Telegram.");
  }

  protected function queueDownMessage(): string
  {
    return 'Tidak dapat menghubungkan Telegram: layanan antrian (queue worker) sedang tidak berjalan, '
      . 'sehingga konfirmasi & notifikasi tidak dapat dikirim. Hubungi administrator '
      . 'sistem dan coba lagi setelah layanan aktif.';
  }

  private function deeplinkFor(string $token): string
  {
    return 'https://t.me/' . config('telegram.bot_username') . '?start=' . $token;
  }

  private function forgetLinkToken(string $token): void
  {
    $cache = TelegramUpdateHandler::cache();
    $cache->forget("tg_link:{$token}");
    $cache->forget("tg_link_status:{$token}");
    $cache->forget("tg_link_failed:{$token}");
  }
}
