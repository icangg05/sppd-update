<?php

namespace Tests\Feature;

use App\Livewire\Budgets\BudgetIndex;
use App\Livewire\CountryIndex;
use App\Livewire\ForeignCityIndex;
use App\Livewire\PositionIndex;
use App\Livewire\ProvinceIndex;
use App\Livewire\RegencyIndex;
use App\Livewire\RoleIndex;
use App\Livewire\Sppd\SppdIndex;
use App\Livewire\WorkflowIndex;
use App\Models\SppdWorkflow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Jumlah baris per halaman: 25 di Daftar SPPD, Data Jabatan, DPA, master wilayah, dan Role &
 * Permission; 100 di Alur Persetujuan. Kolom Tujuan Wilayah di Alur Persetujuan memakai singkatan.
 */
class MasterListPerPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftar SPPD memakai YEAR() MySQL (pilihan filter tahun); padanannya untuk SQLite.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    public function test_dua_puluh_lima_per_halaman(): void
    {
        $lists = [
            SppdIndex::class => 'sppds',
            PositionIndex::class => 'positions',
            BudgetIndex::class => 'budgets',
            ProvinceIndex::class => 'provinces',
            RegencyIndex::class => 'regencies',
            CountryIndex::class => 'countries',
            ForeignCityIndex::class => 'cities',
            RoleIndex::class => 'roles',
        ];

        foreach ($lists as $component => $key) {
            Livewire::test($component)->assertViewHas($key, fn ($page) => $page->perPage() === 25);
        }
    }

    public function test_alur_persetujuan_seratus_per_halaman(): void
    {
        foreach (range(1, 105) as $i) {
            SppdWorkflow::create(['name' => "Alur {$i}", 'steps' => [['role' => 'kepala_opd']], 'is_active' => true]);
        }

        Livewire::test(WorkflowIndex::class)
            ->assertViewHas('workflows', fn ($page) => $page->perPage() === 100 && count($page->items()) === 100);
    }

    public function test_tujuan_wilayah_alur_memakai_singkatan(): void
    {
        SppdWorkflow::create([
            'name' => 'Alur Luar Daerah',
            'destination' => ['lddp', 'ldlp'],
            'steps' => [['role' => 'kepala_opd']],
            'is_active' => true,
        ]);

        Livewire::test(WorkflowIndex::class)
            ->assertSeeHtml('title="Luar Daerah Dalam Provinsi (LDDP)"')
            ->assertDontSee('Luar Daerah Dalam Provinsi (LDDP)</span>', false)
            ->assertSeeInOrder(['LDDP', 'LDLP']);
    }
}
