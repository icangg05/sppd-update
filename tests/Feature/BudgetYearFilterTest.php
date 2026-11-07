<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Budgets\BudgetIndex;
use App\Models\Budget;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Filter tahun di halaman DPA hanya menawarkan tahun yang punya DPA dalam
 * lingkup pengguna, dan tahun bawaannya selalu salah satu dari pilihan itu.
 */
class BudgetYearFilterTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin_opd', 'guard_name' => 'web'], ['label' => 'Admin OPD']);

        $this->dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->admin = User::factory()->create(['department_id' => $this->dinas->id]);
        $this->admin->assignRole('admin_opd');
    }

    private function budget(Department $department, int $year): void
    {
        (new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => $year]))->save();
    }

    public function test_pilihan_tahun_hanya_tahun_yang_punya_dpa_di_instansinya(): void
    {
        $lain = Department::create(['name' => 'Dinas Pangan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $tahunIni = now()->year;

        $this->budget($this->dinas, $tahunIni);
        $this->budget($this->dinas, $tahunIni);
        $this->budget($this->dinas, $tahunIni - 2);
        // Tahun milik instansi lain tidak ikut ditawarkan.
        $this->budget($lain, $tahunIni - 1);

        Livewire::actingAs($this->admin)
            ->test(BudgetIndex::class)
            ->assertViewHas('years', [(string) $tahunIni, (string) ($tahunIni - 2)])
            ->assertSet('year', (string) $tahunIni);
    }

    public function test_tanpa_dpa_tahun_berjalan_bawaannya_tahun_terbaru_yang_ada(): void
    {
        $this->budget($this->dinas, now()->year - 3);
        $this->budget($this->dinas, now()->year - 1);

        Livewire::actingAs($this->admin)
            ->test(BudgetIndex::class)
            ->assertSet('year', (string) (now()->year - 1))
            ->set('year', (string) (now()->year - 3))
            ->call('resetFilters')
            ->assertSet('year', (string) (now()->year - 1));
    }

    public function test_tahun_dari_url_yang_tidak_punya_dpa_diganti_tahun_bawaan(): void
    {
        $this->budget($this->dinas, now()->year);

        Livewire::withQueryParams(['year' => '2001'])
            ->actingAs($this->admin)
            ->test(BudgetIndex::class)
            ->assertSet('year', (string) now()->year);
    }

    public function test_belum_ada_dpa_sama_sekali_menampilkan_semua_tahun(): void
    {
        Livewire::actingAs($this->admin)
            ->test(BudgetIndex::class)
            ->assertViewHas('years', [])
            ->assertSet('year', '');
    }
}
