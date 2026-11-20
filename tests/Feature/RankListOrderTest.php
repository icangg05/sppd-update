<?php

namespace Tests\Feature;

use App\Livewire\RankIndex;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Daftar Pangkat diurutkan dari golongan terendah ke tertinggi (PNS I/a–IV/e, lalu PPPK I–XVII,
 * tanpa golongan di akhir), bukan abjad, dan menampilkan 25 pangkat per halaman.
 */
class RankListOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    public function test_urut_golongan_terendah_ke_tertinggi(): void
    {
        foreach (['X', 'IV/e', null, 'IX', 'I/a', 'VI', 'III/b'] as $group) {
            Rank::create(['name' => 'Pangkat ' . ($group ?? 'kosong'), 'group' => $group]);
        }

        Livewire::test(RankIndex::class)->assertViewHas('ranks', fn ($ranks) => collect($ranks->items())->pluck('group')->all()
            === ['I/a', 'III/b', 'IV/e', 'VI', 'IX', 'X', null]);
    }

    public function test_dua_puluh_lima_per_halaman(): void
    {
        foreach (range(1, 30) as $i) {
            Rank::create(['name' => "Pangkat {$i}", 'group' => 'I/a']);
        }

        Livewire::test(RankIndex::class)->assertViewHas('ranks', fn ($ranks) => $ranks->perPage() === 25 && count($ranks->items()) === 25);
    }
}
