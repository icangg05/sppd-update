<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SignatureStatus;
use App\Livewire\Sppd\SppdShow;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Animasi TTE selesai di halaman persetujuan: event tte-completed hanya dikirim saat
 * proses TTE penanda tangan berakhir dan seluruh dokumennya berhasil ditandatangani.
 */
class TteCompleteAnimationTest extends TestCase
{
    use RefreshDatabase;

    private User $kepala;
    private SppdRequest $sppd;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['staf', 'kepala_opd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $dinas->id, 'name' => 'Budi Staf']);
        $staf->assignRole('staf');
        $this->kepala = User::factory()->create(['department_id' => $dinas->id]);
        $this->kepala->assignRole('kepala_opd');

        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();
        $this->sppd = SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
        ]);
    }

    private function signature(string $type): SppdDigitalSignature
    {
        return SppdDigitalSignature::create([
            'sppd_request_id' => $this->sppd->id,
            'signer_id' => $this->kepala->id,
            'document_type' => $type,
            'status' => SignatureStatus::PROCESSING,
        ]);
    }

    public function test_animasi_tampil_saat_semua_dokumen_selesai_ditandatangani(): void
    {
        $spt = $this->signature('spt');
        $sppd = $this->signature('sppd_' . $this->sppd->user_id);

        $page = Livewire::actingAs($this->kepala)
            ->test(SppdShow::class, ['sppd' => $this->sppd])
            ->assertSet('wasTteProcessing', true)
            ->assertNotDispatched('tte-completed');

        $spt->update(['status' => SignatureStatus::SIGNED, 'signed_at' => now()]);
        $sppd->update(['status' => SignatureStatus::SIGNED, 'signed_at' => now()]);

        $page->call('$refresh')->assertDispatched('tte-completed', fn (string $name, array $params) =>
            array_column($params['docs'], 'label') === ['Surat Perintah Tugas (SPT)', 'SPPD']
            && $params['docs'][1]['name'] === 'Budi Staf'
            && str_ends_with($params['time'], 'WITA'));
    }

    public function test_tidak_ada_animasi_bila_ada_dokumen_gagal(): void
    {
        $spt = $this->signature('spt');
        $sppd = $this->signature('sppd_' . $this->sppd->user_id);

        $page = Livewire::actingAs($this->kepala)->test(SppdShow::class, ['sppd' => $this->sppd]);

        $spt->update(['status' => SignatureStatus::SIGNED, 'signed_at' => now()]);
        $sppd->update(['status' => SignatureStatus::REJECTED]);

        $page->call('$refresh')->assertNotDispatched('tte-completed');
    }
}
