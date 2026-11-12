<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Perjalanan dinas dalam daerah hanya dihitung pada hari kerja: 5 hari mulai Rabu
 * = Rabu, Kamis, Jumat, lalu lompat ke Senin, Selasa. Domain lain menghitung semua hari.
 */
class HariKerjaDalamDaerahTest extends TestCase
{
    use RefreshDatabase;

    private function hari(SppdDomain $domain, string $mulai, string $selesai): int
    {
        return $domain->travelDays(Carbon::parse($mulai), Carbon::parse($selesai));
    }

    public function test_dalam_daerah_melewati_sabtu_dan_minggu(): void
    {
        // Rabu 11 s/d Selasa 17 November 2026.
        $this->assertSame(5, $this->hari(SppdDomain::DALAM_DAERAH, '2026-11-11', '2026-11-17'));
        $this->assertSame(1, $this->hari(SppdDomain::DALAM_DAERAH, '2026-11-11', '2026-11-11'));
        $this->assertSame(3, $this->hari(SppdDomain::DALAM_DAERAH, '2026-11-11', '2026-11-13'));
        // Senin 2 s/d Jumat 13 November: dua pekan kerja.
        $this->assertSame(10, $this->hari(SppdDomain::DALAM_DAERAH, '2026-11-02', '2026-11-13'));
        $this->assertSame(0, $this->hari(SppdDomain::DALAM_DAERAH, '2026-11-14', '2026-11-15'));
    }

    public function test_domain_lain_menghitung_semua_hari(): void
    {
        foreach ([SppdDomain::LDDP, SppdDomain::LDLP, SppdDomain::LUAR_NEGERI] as $domain) {
            $this->assertSame(7, $this->hari($domain, '2026-11-11', '2026-11-17'), $domain->value);
            $this->assertFalse($domain->skipsWeekends());
        }
    }

    public function test_lama_perjalanan_sppd_mengikuti_domain(): void
    {
        $dalamDaerah = new SppdRequest(['domain' => 'dalam_daerah', 'start_date' => '2026-11-11', 'end_date' => '2026-11-17']);
        $luarDaerah = new SppdRequest(['domain' => 'lddp', 'start_date' => '2026-11-11', 'end_date' => '2026-11-17']);

        $this->assertSame(5, $dalamDaerah->durationInDays());
        $this->assertSame(5, $dalamDaerah->duration_days);
        $this->assertSame(7, $luarDaerah->durationInDays());
    }

    public function test_dalam_daerah_tidak_bisa_berangkat_atau_kembali_di_akhir_pekan(): void
    {
        $form = $this->formSppd('dalam_daerah');

        // Sabtu 14 s/d Minggu 22 November 2026.
        $form->set('start_date', '2026-11-14')->set('end_date', '2026-11-22')
            ->call('openConfirmModal')
            ->assertHasErrors(['start_date', 'end_date'])
            ->assertSee('Perjalanan dalam daerah tidak bisa berangkat pada hari Sabtu')
            ->assertSee('Perjalanan dalam daerah tidak bisa kembali pada hari Minggu');

        // Rabu 11 s/d Selasa 17 November 2026 melewati akhir pekan: boleh.
        $form->set('start_date', '2026-11-11')->set('end_date', '2026-11-17')
            ->call('openConfirmModal')
            ->assertHasNoErrors(['start_date', 'end_date']);
    }

    public function test_domain_lain_boleh_berangkat_di_akhir_pekan(): void
    {
        $this->formSppd('lddp')
            ->set('start_date', '2026-11-14')->set('end_date', '2026-11-15')
            ->call('openConfirmModal')
            ->assertHasNoErrors(['start_date', 'end_date']);
    }

    /** Form Edit Perbaikan SPPD dengan domain tertentu, dibuka sebagai Super Admin. */
    private function formSppd(string $domain)
    {
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
            'domain' => $domain,
            'status' => SppdStatus::IN_PROGRESS,
        ]);

        return Livewire::withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class);
    }
}
