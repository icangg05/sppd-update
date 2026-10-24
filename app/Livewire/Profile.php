<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithTelegramLink;
use App\Livewire\Concerns\InteractsWithToast;
use App\Rules\UniqueIdentityInInstansi;
use App\Services\UsernameGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Profile extends Component
{
  use InteractsWithToast;
  use InteractsWithTelegramLink;

  // Identitas dasar
  public string $name = '';
  public string $username = '';
  public string $email = '';
  public string $nip = '';
  public string $nik = '';
  public bool $telegramNotify = true;

  // Ganti password
  public string $current_password = '';
  public string $password = '';
  public string $password_confirmation = '';

  public function mount(): void
  {
    $user = auth()->user();

    $this->name = $user->name ?? '';
    $this->username = $user->username ?? '';
    $this->email = $user->email ?? '';
    $this->nip = $user->nip ?? '';
    $this->nik = $user->nik ?? '';
    $this->telegramNotify = (bool) $user->telegram_notify;
    $this->fillTelegramState($user->telegram_chat_id, $user->telegram_username);

    // Memulihkan polling jika halaman di-refresh saat menunggu pengguna menekan Start.
    $this->restoreTelegramLinkState();
  }

  protected function rules(): array
  {
    $user = auth()->user();
    $id = $user->id;

    return [
      'name' => 'required|string|max:255',
      'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($id)],
      'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
      // NIP/NIK unik per instansi (bukan global) — pegawai yang sama boleh
      // berakun di dua instansi berbeda dengan NIP/NIK yang sama.
      'nip' => ['nullable', 'string', 'max:20', new UniqueIdentityInInstansi('nip', 'NIP', $user->department_id, $id)],
      'nik' => ['nullable', 'string', 'max:20', new UniqueIdentityInInstansi('nik', 'NIK', $user->department_id, $id)],
      'current_password' => ['nullable', 'required_with:password', 'current_password'],
      // Tidak boleh kembali ke password bawaan — tujuannya justru menggantinya.
      'password' => ['nullable', 'string', 'min:6', 'confirmed', Rule::notIn([config('users.default_password')])],
    ];
  }

  protected $messages = [
    'current_password.required_with' => 'Masukkan password saat ini untuk mengganti password.',
    'current_password.current_password' => 'Password saat ini tidak sesuai.',
    'password.confirmed' => 'Konfirmasi password tidak cocok.',
    'password.min' => 'Password baru minimal 6 karakter.',
    'password.not_in' => 'Password baru tidak boleh sama dengan password bawaan.',
  ];

  // ── Hook tautan Telegram (InteractsWithTelegramLink) ──
  protected function linkUserId(): ?int
  {
    return auth()->id();
  }

  protected function onTelegramLinked(string $chatId, ?string $username): void
  {
    auth()->user()->update([
      'telegram_chat_id' => $chatId,
      'telegram_username' => $username,
      'telegram_linked_at' => now(),
    ]);
  }

  protected function onTelegramUnlinked(): void
  {
    auth()->user()->update([
      'telegram_chat_id' => null,
      'telegram_username' => null,
      'telegram_linked_at' => null,
    ]);
  }

  protected function canSendTestMessage(): bool
  {
    return $this->telegramNotify;
  }

  /** Saklar notifikasi Telegram; disimpan langsung agar tidak perlu tekan Simpan. */
  public function updatedTelegramNotify(bool $value): void
  {
    auth()->user()->update(['telegram_notify' => $value]);

    $value
      ? $this->toastSuccess('Notifikasi Telegram diaktifkan.')
      : $this->toastWarning('Notifikasi Telegram dinonaktifkan.');
  }

  /** Membuat username ringkas & unik dari nama (lihat UsernameGenerator). */
  public function generateUsername(): void
  {
    $username = app(UsernameGenerator::class)->generate($this->name, auth()->id());

    if ($username === null) {
      $this->toastError('Isi Nama Lengkap terlebih dahulu untuk membuat username.');
      return;
    }

    $this->username = $username;
    $this->resetErrorBag('username');
    $this->toastSuccess('Username dibuat: ' . $this->username);
  }

  public function save()
  {
    try {
      $this->validate();
    } catch (ValidationException $e) {
      $this->toastError('Periksa kembali isian formulir.');
      throw $e;
    }

    $user = auth()->user();

    $data = [
      'name' => $this->name,
      'username' => $this->username,
      'email' => $this->email ?: null,
      'nip' => $this->nip ?: null,
      'nik' => $this->nik ?: null,
    ];

    if (! empty($this->password)) {
      $data['password'] = Hash::make($this->password);
    }

    $user->update($data);

    if (isset($data['password'])) {
      // Peringatan password bawaan di header disembunyikan tanpa perlu muat ulang.
      $user->forgetDefaultPasswordCache();
      $this->dispatch('password-changed');
    }

    $this->reset('current_password', 'password', 'password_confirmation');

    $this->toastSuccess('Profil berhasil diperbarui.');
  }

  public function render()
  {
    return view('livewire.profile', [
      'user' => auth()->user()->load(['department', 'position', 'rank', 'roles']),
    ])->title('Profil Saya');
  }
}
