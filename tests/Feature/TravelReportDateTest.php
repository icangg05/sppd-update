<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\TravelReport;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tanggal laporan perjalanan tidak boleh sebelum perjalanan selesai (tanggal kembali).
 */
class TravelReportDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanggal_laporan_tidak_boleh_sebelum_tanggal_kembali(): void
    {
        foreach (['staf', 'admin_opd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $admin = User::factory()->create(['department_id' => $dinas->id]);
        $admin->assignRole('admin_opd');
        $staf = User::factory()->create(['department_id' => $dinas->id]);
        $staf->assignRole('staf');
        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        // Perjalanan masih berlangsung: kembali 3 hari lagi.
        $sppd = SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today()->addDays(3),
            'domain' => 'dalam_daerah',
            'status' => SppdStatus::APPROVED,
        ]);
        $end = today()->addDays(3)->format('Y-m-d');

        $page = Livewire::actingAs($admin)->test(TravelReport::class, ['sppd' => $sppd])
            // Default tidak jatuh di tanggal yang nonaktif.
            ->assertSet('reportDate', $end)
            ->assertSeeHtml('min: \''.$end.'\'');

        $page->set('reportDate', today()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['reportDate' => 'after_or_equal']);

        $page->set('reportDate', $end)
            ->call('save')
            ->assertHasNoErrors('reportDate');
    }
}
