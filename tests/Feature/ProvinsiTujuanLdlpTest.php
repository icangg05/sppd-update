<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Province;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Tujuan LDLP boleh memilih provinsi mana pun, termasuk Sulawesi Tenggara. */
class ProvinsiTujuanLdlpTest extends TestCase
{
    use RefreshDatabase;

    public function test_ldlp_menampilkan_provinsi_sulawesi_tenggara(): void
    {
        Province::create(['name' => 'Sulawesi Tenggara']);
        Province::create(['name' => 'Sulawesi Selatan']);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);

        $opd = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);
        $pelaksana = User::factory()->create(['department_id' => $opd->id]);
        $budget = new Budget(['department_id' => $opd->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $sppd = SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'domain' => 'ldlp',
            'status' => SppdStatus::IN_PROGRESS,
        ]);

        Livewire::withQueryParams(['sppd_id' => $sppd->id])
            ->test(SppdCreateDetails::class)
            ->call('addDestination')
            ->assertSee('Sulawesi Selatan')
            ->assertSee('Sulawesi Tenggara');
    }
}
