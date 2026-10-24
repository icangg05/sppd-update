<?php

namespace Tests\Feature;

use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DefaultPasswordWarningTest extends TestCase
{
    use RefreshDatabase;

    private const WARNING = 'Anda masih memakai password bawaan';

    private function makeUser(string $password): User
    {
        return User::factory()->create(['username' => 'pegawai', 'password' => Hash::make($password)]);
    }

    public function test_header_memperingatkan_pengguna_dengan_password_bawaan(): void
    {
        $user = $this->makeUser(config('users.default_password'));

        $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertSee(self::WARNING);
    }

    public function test_header_tanpa_peringatan_jika_password_sudah_diganti(): void
    {
        $user = $this->makeUser('rahasia-baru');

        $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertDontSee(self::WARNING);
    }

    public function test_tidak_boleh_mengganti_password_ke_password_bawaan(): void
    {
        $default = config('users.default_password');
        $user = $this->makeUser('rahasia-lama');

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'rahasia-lama')
            ->set('password', $default)
            ->set('password_confirmation', $default)
            ->call('save')
            ->assertHasErrors(['password' => 'not_in']);

        $this->assertTrue(Hash::check('rahasia-lama', $user->fresh()->password));
    }

    public function test_mengganti_password_bawaan_menghilangkan_peringatan(): void
    {
        $default = config('users.default_password');
        $user = $this->makeUser($default);

        // Isi cache sesi lebih dulu, seperti saat header pertama kali dirender.
        $this->actingAs($user);
        $this->assertTrue($user->usesDefaultPasswordCached());

        Livewire::test(Profile::class)
            ->set('current_password', $default)
            ->set('password', 'rahasia-baru')
            ->set('password_confirmation', 'rahasia-baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('password-changed');

        $this->assertFalse($user->fresh()->usesDefaultPasswordCached());
    }
}
