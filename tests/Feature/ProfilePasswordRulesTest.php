<?php

namespace Tests\Feature;

use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfilePasswordRulesTest extends TestCase
{
    use RefreshDatabase;

    private function savePassword(User $user, string $password)
    {
        return Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'lama-rahasia')
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->call('save');
    }

    private function makeUser(): User
    {
        return User::factory()->create(['username' => 'pegawai', 'password' => Hash::make('lama-rahasia')]);
    }

    public static function weakPasswords(): array
    {
        return [
            'kurang dari 8 karakter' => ['Ab-cdef', 'Password baru minimal 8 karakter.'],
            'tanpa huruf kapital' => ['rahasia-baru', 'Password baru harus memuat huruf kapital dan huruf kecil.'],
            'tanpa huruf kecil' => ['RAHASIA-BARU', 'Password baru harus memuat huruf kapital dan huruf kecil.'],
            'tanpa simbol' => ['RahasiaBaru', 'Password baru harus memuat minimal satu simbol.'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_password_lemah_ditolak_dengan_pesan_yang_jelas(string $password, string $message): void
    {
        $user = $this->makeUser();

        $component = $this->savePassword($user, $password);

        $this->assertContains($message, $component->errors()->get('password'));
        $this->assertTrue(Hash::check('lama-rahasia', $user->fresh()->password));
    }

    public function test_password_yang_memenuhi_syarat_diterima(): void
    {
        $user = $this->makeUser();

        $this->savePassword($user, 'Rahasia-baru')->assertHasNoErrors();

        $this->assertTrue(Hash::check('Rahasia-baru', $user->fresh()->password));
    }

    public function test_tanpa_mengisi_password_baru_profil_tetap_bisa_disimpan(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('lama-rahasia', $user->fresh()->password));
    }

    public function test_halaman_profil_menampilkan_syarat_password(): void
    {
        $this->actingAs($this->makeUser())->get(route('profile'))
            ->assertOk()
            ->assertSee('Syarat password yang aman')
            ->assertSee('Minimal 8 karakter')
            ->assertSee('Minimal 1 huruf kapital')
            ->assertSee('Minimal 1 huruf kecil')
            ->assertSee('Minimal 1 simbol');
    }
}
