<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdShow;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Judul halaman detail SPPD menyebut tanggal perjalanan beserta durasinya. */
class SppdShowDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_judul_detail_menyebut_durasi_perjalanan(): void
    {
        foreach (['staf', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $department = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $department->id]);
        $staf->assignRole('staf');
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $start = today()->next('Monday');
        $sppd = SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => $start,
            'end_date' => $start->copy()->addDays(2),
            'domain' => 'lddp',
        ]);

        Livewire::actingAs($superAdmin)
            ->test(SppdShow::class, ['sppd' => $sppd])
            ->assertSeeInOrder([
                'Detail Surat Perjalanan Dinas',
                $start->translatedFormat('d M Y') . ' s/d ' . $start->copy()->addDays(2)->translatedFormat('d M Y'),
                '3 hari',
                'Informasi Perjalanan',
            ]);
    }
}
