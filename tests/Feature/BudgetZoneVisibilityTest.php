<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Budgets\BudgetIndex;
use App\Models\Budget;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Setda dan unit di bawahnya satu zona data: tiap Bagian melihat seluruh DPA Setda di halaman DPA,
 * sama dengan pilihan DPA di form SPPD. Mengubah dan menghapus tetap hanya oleh unit pemiliknya.
 */
class BudgetZoneVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Department $setda;
    private Department $perekonomian;
    private Department $hukum;
    private Budget $dpaSetda;
    private Budget $dpaPerekonomian;
    private Budget $dpaHukum;
    private Budget $dpaOpdLain;
    private User $adminPerekonomian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->withoutVite();

        $this->setda = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1]);
        $asisten = Department::create(['name' => 'Asisten Perekonomian', 'type' => DepartmentType::ASISTEN, 'level' => 0, 'parent_id' => $this->setda->id]);
        $this->perekonomian = Department::create(['name' => 'Bagian Perekonomian', 'type' => DepartmentType::BAGIAN, 'level' => 0, 'parent_id' => $asisten->id]);
        $this->hukum = Department::create(['name' => 'Bagian Hukum', 'type' => DepartmentType::BAGIAN, 'level' => 0, 'parent_id' => $asisten->id]);
        $opdLain = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);

        $this->dpaSetda = $this->budget($this->setda);
        $this->dpaPerekonomian = $this->budget($this->perekonomian);
        $this->dpaHukum = $this->budget($this->hukum);
        $this->dpaOpdLain = $this->budget($opdLain);

        $this->adminPerekonomian = User::factory()->create(['department_id' => $this->perekonomian->id]);
        $this->adminPerekonomian->assignRole('admin_opd');
    }

    private function budget(Department $department): Budget
    {
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return $budget;
    }

    public function test_bagian_melihat_seluruh_dpa_setda_tapi_tidak_dpa_opd_lain(): void
    {
        $shown = Livewire::actingAs($this->adminPerekonomian)
            ->test(BudgetIndex::class)
            ->viewData('budgets')
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            collect([$this->dpaSetda->id, $this->dpaPerekonomian->id, $this->dpaHukum->id])->sort()->values()->all(),
            $shown,
        );
    }

    public function test_filter_unit_kerja_berisi_unit_sezona_yang_punya_dpa(): void
    {
        Livewire::actingAs($this->adminPerekonomian)
            ->test(BudgetIndex::class)
            ->assertViewHas('departments', fn ($departments) => $departments->pluck('name')->all() === [
                'Bagian Hukum', 'Bagian Perekonomian', 'Sekretariat Daerah',
            ])
            ->set('department_id', (string) $this->hukum->id)
            ->assertViewHas('budgets', fn ($budgets) => $budgets->pluck('id')->all() === [$this->dpaHukum->id]);
    }

    public function test_dpa_unit_lain_sezona_tidak_bisa_diubah_atau_dihapus(): void
    {
        $this->actingAs($this->adminPerekonomian)->get(route('master.budgets.show', $this->dpaHukum->id))->assertOk();
        $this->actingAs($this->adminPerekonomian)->get(route('master.budgets.edit', $this->dpaHukum->id))->assertForbidden();
        $this->actingAs($this->adminPerekonomian)->delete(route('master.budgets.destroy', $this->dpaHukum->id))->assertForbidden();

        Livewire::actingAs($this->adminPerekonomian)->test(BudgetIndex::class)
            ->call('confirmDelete', $this->dpaSetda->id)
            ->assertSet('showDeleteModal', false)
            ->set('deletingId', $this->dpaSetda->id)
            ->call('delete');

        $this->assertNotNull(Budget::find($this->dpaHukum->id));
        $this->assertNotNull(Budget::find($this->dpaSetda->id));

        // DPA unitnya sendiri tetap bisa diubah.
        $this->actingAs($this->adminPerekonomian)->get(route('master.budgets.edit', $this->dpaPerekonomian->id))->assertOk();
    }
}
