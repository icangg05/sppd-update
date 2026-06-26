<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithPhoneVerification;
use App\Livewire\Concerns\InteractsWithToast;
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
  use InteractsWithPhoneVerification;

  // Identitas dasar
  public string $name = '';
  public string $username = '';
  public string $email = '';
  public string $nip = '';
  public string $nik = '';
  public string $phone = '';

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
    $this->phone = $user->phone ?? '';
    $this->phoneVerified = (bool) $user->phone_verified;

    // Memulihkan state verifikasi jika halaman di-refresh saat menunggu balasan.
    $this->restorePhoneVerificationState();
  }

  protected function rules(): array
  {
    $id = auth()->id();

    return [
      'name' => 'required|string|max:255',
      'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($id)],
      'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
      'nip' => ['nullable', 'string', 'max:20', Rule::unique('users', 'nip')->ignore($id)],
      'nik' => ['nullable', 'string', 'max:20', Rule::unique('users', 'nik')->ignore($id)],
      'phone' => 'nullable|string|max:20',
      'current_password' => ['nullable', 'required_with:password', 'current_password'],
      'password' => ['nullable', 'string', 'min:6', 'confirmed'],
    ];
  }

  protected $messages = [
    'current_password.required_with' => 'Masukkan password saat ini untuk mengganti password.',
    'current_password.current_password' => 'Password saat ini tidak sesuai.',
    'password.confirmed' => 'Konfirmasi password tidak cocok.',
    'password.min' => 'Password baru minimal 6 karakter.',
  ];

  // ── Hook verifikasi WhatsApp (InteractsWithPhoneVerification) ──
  protected function verificationUserId(): ?int
  {
    return auth()->id();
  }

  protected function onPhoneVerified(string $phone): void
  {
    auth()->user()->update([
      'phone' => $phone,
      'phone_verified' => true,
    ]);
  }

  protected function onPhoneReset(): void
  {
    auth()->user()->update([
      'phone' => null,
      'phone_verified' => false,
    ]);
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

    if (! empty($this->phone) && ! $this->phoneVerified) {
      $this->addError('phone', 'Nomor telepon harus diverifikasi terlebih dahulu.');
      $this->toastError('Nomor telepon harus diverifikasi terlebih dahulu.');
      return;
    }

    $user = auth()->user();

    $data = [
      'name' => $this->name,
      'username' => $this->username,
      'email' => $this->email ?: null,
      'nip' => $this->nip ?: null,
      'nik' => $this->nik ?: null,
    ];

    // Nomor hanya disimpan bila sudah terverifikasi.
    if ($this->phoneVerified) {
      $data['phone'] = $this->phone ?: null;
    }

    if (! empty($this->password)) {
      $data['password'] = Hash::make($this->password);
    }

    $user->update($data);

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
