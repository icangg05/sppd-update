<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
  public string $username = '';
  public string $password = '';
  public bool $remember = false;

  /** Sisa detik penguncian (0 = tidak terkunci). Dipakai untuk hitung mundur. */
  public int $lockoutSeconds = 0;

  /** Maksimal percobaan gagal sebelum dikunci, dan lama penguncian (detik). */
  private const MAX_ATTEMPTS = 4;
  private const DECAY_SECONDS = 30;

  public function mount(): void
  {
    if (Auth::check()) {
      $this->redirectRoute('dashboard', navigate: true);

      return;
    }

    // Pulihkan status kunci bila halaman dimuat ulang saat masih terkunci.
    $this->lockoutSeconds = RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)
      ? RateLimiter::availableIn($this->throttleKey())
      : 0;
  }

  /** Kunci throttle unik per kombinasi username + IP. */
  protected function throttleKey(): string
  {
    return Str::transliterate(Str::lower($this->username) . '|' . request()->ip());
  }

  public function login()
  {
    $this->validate([
      'username' => ['required', 'string'],
      'password' => ['required', 'string'],
    ], [], [
      'username' => 'username',
      'password' => 'password',
    ]);

    $this->ensureIsNotRateLimited();

    if (! Auth::attempt(['username' => $this->username, 'password' => $this->password], $this->remember)) {
      RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
      $this->syncLockout();

      throw ValidationException::withMessages([
        'username' => 'Username atau password salah.',
      ]);
    }

    $user = Auth::user();

    if (! $user->is_active) {
      Auth::logout();

      throw ValidationException::withMessages([
        'username' => 'Akun Anda tidak aktif. Hubungi administrator.',
      ]);
    }

    RateLimiter::clear($this->throttleKey());
    session()->regenerate();

    $name = $user->name ?? $user->username;

    return redirect()->intended(route('dashboard'))
      ->with('success', "Selamat datang kembali, {$name}!");
  }

  /** Tolak login bila percobaan sudah melebihi batas, sambil set hitung mundur. */
  protected function ensureIsNotRateLimited(): void
  {
    if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
      return;
    }

    $this->syncLockout();

    throw ValidationException::withMessages([
      'username' => "Terlalu banyak percobaan gagal. Coba lagi dalam {$this->lockoutSeconds} detik.",
    ]);
  }

  protected function syncLockout(): void
  {
    $this->lockoutSeconds = RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)
      ? RateLimiter::availableIn($this->throttleKey())
      : 0;
  }

  public function render()
  {
    return view('livewire.auth.login');
  }
}
