<?php

namespace App\Livewire\Concerns;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Services\QueueHealthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Alur verifikasi nomor WhatsApp yang dipakai bersama oleh formulir pegawai
 * (UserForm) dan halaman profil (Profile).
 *
 * Komponen yang memakai trait ini WAJIB menyediakan properti publik `$phone`
 * (string) dan dapat menimpa hook di bawah untuk menyesuaikan konteks:
 *  - verificationUserId()  : id pemilik token (agar token lama bisa dibersihkan)
 *  - verificationName()    : nama yang ditampilkan pada payload verifikasi
 *  - verificationEmail()   : email pada payload verifikasi
 *  - onPhoneVerified()     : aksi setelah nomor terverifikasi (mis. simpan ke DB)
 *  - onPhoneReset()        : aksi setelah verifikasi dibatalkan/diganti
 */
trait InteractsWithPhoneVerification
{
  // State verifikasi WhatsApp
  public bool $phoneVerified = false;
  public bool $showVerifyModal = false;
  public bool $showTestConfirm = false;
  public bool $isPolling = false;
  public string $verificationNumber = '';
  public string $verificationTemplate = '';
  public string $deeplinkUrl = '';
  public string $verificationToken = '';
  public bool $isVerified = false;
  public bool $isFailed = false;
  public bool $isTimedOut = false;
  public string $failedMessage = '';
  public int $pollingCount = 0;

  /** Id pemilik token verifikasi, untuk membersihkan token lama. */
  protected function verificationUserId(): ?int
  {
    return null;
  }

  protected function verificationName(): string
  {
    return $this->name ?: 'Pegawai';
  }

  protected function verificationEmail(): string
  {
    return $this->email ?: '-';
  }

  /** Dipanggil saat nomor berhasil diverifikasi (mis. persist ke DB). */
  protected function onPhoneVerified(string $phone): void {}

  /** Dipanggil saat verifikasi dibatalkan / nomor diganti. */
  protected function onPhoneReset(): void {}

  /**
   * Memulihkan state verifikasi bila halaman di-refresh saat menunggu balasan.
   * Panggil dari mount() komponen.
   */
  public function restorePhoneVerificationState(): void
  {
    $token = session()->get('wa_verification_token');
    if ($token && ! $this->phoneVerified) {
      $cached = Cache::get("wa_verification:{$token}");
      if ($cached) {
        $this->verificationToken = $token;
        // Hanya memulihkan phone jika kosong agar input user tidak tertimpa.
        if (empty($this->phone)) {
          $this->phone = $cached['phone'];
        }
        $this->isPolling = true;
      } else {
        session()->forget('wa_verification_token');
      }
    }
  }

  public function openVerifyModal()
  {
    $this->resetErrorBag('phone');

    if (empty(trim($this->phone))) {
      $this->addError('phone', 'Masukkan nomor telepon terlebih dahulu.');
      return;
    }

    // Gerbang: verifikasi WhatsApp mencocokkan pesan masuk yang diproses worker
    // queue. Bila worker mati, balasan verifikasi tidak akan pernah diproses,
    // jadi tolak sebelum membuka modal agar pengguna tidak menunggu sia-sia.
    if (! app(QueueHealthService::class)->isWorkerHealthy()) {
      $this->toastError($this->queueDownMessage());
      return;
    }

    $this->verificationNumber = config('kirimchat.verification_number', '6281376111919');
    $this->verificationToken = 'V-' . rand(10000, 99999);
    $normalizedPhone = $this->normalizePhone($this->phone);

    $userId = $this->verificationUserId();

    if ($userId) {
      $prevToken = Cache::get("wa_verification_user:{$userId}");
      if ($prevToken) {
        $prevCached = Cache::get("wa_verification:{$prevToken}");
        if ($prevCached) {
          $prevNormalized = $this->normalizePhone($prevCached['phone']);
          Cache::forget("wa_verification_phone:{$prevNormalized}");
        }
        Cache::forget("wa_verification:{$prevToken}");
        Cache::forget("wa_verified_status:{$prevToken}");
        Cache::forget("wa_verification_failed:{$prevToken}");
      }
      Cache::put("wa_verification_user:{$userId}", $this->verificationToken, now()->addMinutes(15));
    }

    Cache::put("wa_verification:{$this->verificationToken}", [
      'phone'   => $this->phone,
      'user_id' => $userId,
      'name'    => $this->verificationName(),
      'email'   => $this->verificationEmail(),
    ], now()->addMinutes(15));

    Cache::put("wa_verification_phone:{$normalizedPhone}", $this->verificationToken, now()->addMinutes(15));

    $this->verificationTemplate = "Verifikasi WhatsApp SPPD Kendari\n" .
      "📱 *Nomor:* {$normalizedPhone}\n\n" .
      "Kirim pesan ini untuk memverifikasi nomor WhatsApp Anda.\n\n" .
      "_Sistem akan mencocokkan nomor pengirim secara otomatis. Mohon periksa status verifikasi pada browser Anda secara berkala jika belum menerima balasan._";

    $this->deeplinkUrl = 'https://wa.me/' . $this->verificationNumber . '?text=' . urlencode($this->verificationTemplate);

    session()->put('wa_verification_token', $this->verificationToken);

    $this->isVerified = false;
    $this->isFailed = false;
    $this->isTimedOut = false;
    $this->failedMessage = '';
    $this->pollingCount = 0;

    $this->showVerifyModal = true;
    $this->isPolling = true;
  }

  public function checkVerification()
  {
    if (! $this->isPolling) return;

    $this->pollingCount++;
    if ($this->pollingCount > 300) { // 5 menit dengan interval 1 dtk
      $this->isTimedOut = true;
      $this->isPolling = false;
      return;
    }

    $verified = Cache::get("wa_verified_status:{$this->verificationToken}");
    if ($verified && ! empty($verified['verified'])) {
      $this->isVerified = true;
      $this->phone = $verified['phone'];
      $this->phoneVerified = true;
      $this->isPolling = false;
      session()->forget('wa_verification_token');

      $this->onPhoneVerified($this->phone);
      return;
    }

    $failed = Cache::get("wa_verification_failed:{$this->verificationToken}");
    if ($failed) {
      $this->isFailed = true;
      $this->failedMessage = $failed['message'] ?? 'Verifikasi gagal.';
      $this->isPolling = false;
      session()->forget('wa_verification_token');
      return;
    }
  }

  public function closeVerifyModal()
  {
    $this->showVerifyModal = false;
  }

  /** Buka modal konfirmasi sebelum mengirim pesan tes. */
  public function confirmTestMessage(): void
  {
    if (! $this->phoneVerified || empty(trim($this->phone))) {
      $this->toastError('Nomor belum terverifikasi.');
      return;
    }
    $this->showTestConfirm = true;
  }

  public function closeTestConfirm(): void
  {
    $this->showTestConfirm = false;
  }

  /**
   * Kirim pesan tes ke nomor yang sudah terverifikasi untuk memastikan jalur
   * notifikasi WhatsApp ke pegawai berfungsi. Pesan dititipkan ke worker queue
   * (jalur yang sama dgn notifikasi asli), jadi worker harus hidup lebih dulu.
   */
  public function sendTestMessage()
  {
    // Tutup modal konfirmasi; hasil disampaikan lewat toast.
    $this->showTestConfirm = false;

    if (! $this->phoneVerified || empty(trim($this->phone))) {
      $this->toastError('Nomor belum terverifikasi.');
      return;
    }

    // Batasi 1 kirim per 30 detik per pengguna agar tidak bisa di-spam.
    $rateKey = 'wa-test:' . (Auth::id() ?? request()->ip());
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

    $normalizedPhone = $this->normalizePhone($this->phone);
    $name = $this->verificationName();

    $message = "🔔 *Tes Notifikasi SPPD Kendari*\n"
      . "Halo {$name}, notifikasi WhatsApp ke nomor Anda aktif. ✅\n\n"
      . "_Pesan otomatis — tidak perlu dibalas._";

    SendWhatsAppNotificationJob::dispatch($normalizedPhone, $message);
    RateLimiter::hit($rateKey, 30);

    $this->toastSuccess("Pesan tes sedang dikirim ke {$normalizedPhone}. Periksa WhatsApp nomor tersebut.");
  }

  protected function queueDownMessage(): string
  {
    return 'Verifikasi ditolak: layanan antrian (queue worker) sedang tidak berjalan, '
      . 'sehingga balasan verifikasi WhatsApp tidak dapat diproses. Hubungi administrator '
      . 'sistem dan coba lagi setelah layanan aktif.';
  }

  public function retryVerification()
  {
    $this->closeVerifyModal();
    $this->openVerifyModal();
  }

  public function resetPhoneVerification()
  {
    $this->phoneVerified = false;
    $this->phone = '';
    $this->isVerified = false;
    $this->isPolling = false;
    session()->forget('wa_verification_token');

    $this->onPhoneReset();
  }

  protected function normalizePhone(string $phone): string
  {
    if (str_contains($phone, '@')) {
      [$phone] = explode('@', $phone, 2);
    }
    $phone = preg_replace('/\D/', '', $phone);
    if (str_starts_with($phone, '0')) {
      $phone = '62' . substr($phone, 1);
    } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9 && strlen($phone) <= 13) {
      $phone = '62' . $phone;
    }
    return $phone;
  }
}
