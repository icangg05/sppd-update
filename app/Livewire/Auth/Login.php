<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
  use InteractsWithToast;

  public string $username = '';
  public string $password = '';
  public bool $remember = false;

  /** Sisa detik penguncian (0 = tidak terkunci). Dipakai untuk hitung mundur. */
  public int $lockoutSeconds = 0;

  /** Sisa percobaan salah sebelum akun diblokir (0 = tidak perlu diperingatkan). */
  public int $attemptsBeforeBlock = 0;

  /** Akun yang dicoba sedang diblokir dan harus dibuka super_admin. */
  public bool $blocked = false;

  /**
   * Setiap MAX_ATTEMPTS kali salah password → akun dikunci LOCKOUT_SECONDS detik.
   * Ronde kedua habis (total MAX_ATTEMPTS × 2) → akun diblokir. super_admin tidak
   * pernah diblokir, hanya terus berulang dikunci per ronde.
   */
  public const MAX_ATTEMPTS = 3;
  public const LOCKOUT_SECONDS = 60;
  public const BLOCK_AFTER_ATTEMPTS = self::MAX_ATTEMPTS * 2;

  public function mount(): void
  {
    if (Auth::check()) {
      $this->redirectRoute('dashboard', navigate: true);
    }
  }

  public function login()
  {
    $this->blocked = false;
    $this->attemptsBeforeBlock = 0;

    if (trim($this->username) === '' || trim($this->password) === '') {
      $this->toastError('Username dan password wajib diisi.');

      return;
    }

    $user = User::where('username', $this->username)->first();

    if (! $user) {
      $this->failUnknownUser();

      return;
    }

    if ($user->isLoginBlocked()) {
      $this->showBlocked();

      return;
    }

    $canBlock = ! $user->hasRole('super_admin');

    if ($user->login_locked_until?->isFuture()) {
      $this->showStillLocked(
        $user->login_locked_until->getTimestamp() - now()->getTimestamp(),
        $canBlock ? self::BLOCK_AFTER_ATTEMPTS - $user->failed_login_attempts : 0,
      );

      return;
    }

    if (! Auth::attempt(['username' => $this->username, 'password' => $this->password], $this->remember)) {
      $this->recordFailedAttempt($user, $canBlock);

      return;
    }

    $user = Auth::user();

    if (! $user->is_active) {
      Auth::logout();
      $this->toastError('Akun Anda tidak aktif. Hubungi administrator.');

      return;
    }

    if ($user->failed_login_attempts > 0 || $user->login_locked_until) {
      $user->resetLoginAttempts();
    }

    session()->regenerate();

    $name = $user->name ?? $user->username;

    return redirect()->intended(route('dashboard'))
      ->with('success', "Selamat datang kembali, {$name}!");
  }

  /** Catat password salah lalu kunci sementara atau blokir sesuai jumlah percobaan. */
  protected function recordFailedAttempt(User $user, bool $canBlock): void
  {
    $user->increment('failed_login_attempts');
    $attempts = $user->failed_login_attempts;

    match ($this->outcomeFor($attempts, $canBlock)) {
      'block' => $this->blockUser($user),
      'lock' => $this->lockUser($user, $canBlock),
      'wrong' => $this->toastWrongCredentials($attempts, $canBlock),
    };
  }

  protected function blockUser(User $user): void
  {
    $user->forceFill(['login_blocked_at' => now(), 'login_locked_until' => null])->save();

    activity('auth')
      ->performedOn($user)
      ->causedBy($user)
      ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
      ->log("Akun {$user->username} diblokir otomatis karena berulang kali salah password");

    $this->showBlocked();
  }

  protected function lockUser(User $user, bool $canBlock): void
  {
    $user->forceFill([
      // super_admin memulai ronde baru setelah dikunci (sekaligus mencegah kolom meluap).
      'failed_login_attempts' => $canBlock ? $user->failed_login_attempts : 0,
      'login_locked_until' => now()->addSeconds(self::LOCKOUT_SECONDS),
    ])->save();

    $this->lockout($canBlock);
  }

  /**
   * Username tak dikenal tidak punya akun untuk dikunci, jadi dibatasi per
   * username + IP dengan pola pesan yang sama agar keberadaan akun tak terbaca.
   */
  protected function failUnknownUser(): void
  {
    $key = 'login:' . $this->throttleKey();
    $lockKey = $key . ':lock';

    if (RateLimiter::attempts($key) >= self::BLOCK_AFTER_ATTEMPTS) {
      $this->showBlocked();

      return;
    }

    if (RateLimiter::tooManyAttempts($lockKey, 1)) {
      $this->showStillLocked(
        RateLimiter::availableIn($lockKey),
        self::BLOCK_AFTER_ATTEMPTS - RateLimiter::attempts($key),
      );

      return;
    }

    $attempts = RateLimiter::hit($key, 3600);
    $outcome = $this->outcomeFor($attempts, canBlock: true);

    if ($outcome === 'lock') {
      RateLimiter::hit($lockKey, self::LOCKOUT_SECONDS);
    }

    match ($outcome) {
      'block' => $this->showBlocked(),
      'lock' => $this->lockout(canBlock: true),
      'wrong' => $this->toastWrongCredentials($attempts, canBlock: true),
    };
  }

  /** Akibat percobaan gagal ke-$attempts: 'wrong', 'lock', atau 'block'. */
  protected function outcomeFor(int $attempts, bool $canBlock): string
  {
    if ($canBlock && $attempts >= self::BLOCK_AFTER_ATTEMPTS) {
      return 'block';
    }

    return $attempts % self::MAX_ATTEMPTS === 0 ? 'lock' : 'wrong';
  }

  /** Kunci throttle unik per kombinasi username + IP. */
  protected function throttleKey(): string
  {
    return Str::transliterate(Str::lower($this->username) . '|' . request()->ip());
  }

  protected function toastWrongCredentials(int $attempts, bool $canBlock): void
  {
    $remaining = self::MAX_ATTEMPTS - ($attempts % self::MAX_ATTEMPTS);

    // Ronde kedua: berikutnya bukan dikunci lagi, melainkan diblokir.
    if ($canBlock && $attempts > self::MAX_ATTEMPTS) {
      $this->attemptsBeforeBlock = $remaining;
      $this->toastError("Username atau password salah. Sisa {$remaining} kali percobaan sebelum akun diblokir.");

      return;
    }

    $this->toastError("Username atau password salah. Sisa {$remaining} kali percobaan sebelum akun dikunci sementara.");
  }

  protected function lockout(bool $canBlock): void
  {
    $this->startLockout(self::LOCKOUT_SECONDS);

    $message = 'Anda telah ' . self::MAX_ATTEMPTS . ' kali salah memasukkan password. Silakan tunggu 1 menit.';
    if ($canBlock) {
      $this->attemptsBeforeBlock = self::MAX_ATTEMPTS;
      $message .= ' Jika salah ' . self::MAX_ATTEMPTS . ' kali lagi, akun akan diblokir dan Anda harus menghubungi Super Admin.';
    }

    $this->toastError($message, 'Akun Dikunci Sementara');
  }

  protected function showStillLocked(int $seconds, int $attemptsBeforeBlock): void
  {
    $this->attemptsBeforeBlock = max(0, $attemptsBeforeBlock);
    $this->startLockout($seconds);
    $this->toastError("Akun dikunci sementara. Coba lagi dalam {$this->lockoutSeconds} detik.");
  }

  protected function startLockout(int $seconds): void
  {
    $this->lockoutSeconds = max(1, $seconds);
  }

  protected function showBlocked(): void
  {
    $this->blocked = true;
    $this->lockoutSeconds = 0;
    $this->toastError(
      'Akun Anda diblokir karena berulang kali salah memasukkan password. Harap hubungi Super Admin.',
      'Akun Diblokir',
    );
  }

  public function render()
  {
    return view('livewire.auth.login');
  }
}
