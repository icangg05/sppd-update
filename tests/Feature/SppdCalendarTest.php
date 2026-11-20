<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdCalendar;
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
 * Kalender SPPD: tiap tanggal hanya menampilkan satu nama (judul event = nama pelaksana),
 * rincian lain dibawa di extendedProps untuk modal "semua nama di tanggal ini".
 */
class SppdCalendarTest extends TestCase
{
    use RefreshDatabase;

    private Department $opd;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
        $this->grantDefaultPermissions();

        $this->opd = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);

        $admin = User::factory()->create(['department_id' => $this->opd->id]);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    private function sppd(string $name, int $mulai, int $selesai, SppdStatus $status): SppdRequest
    {
        $pelaksana = User::factory()->create(['department_id' => $this->opd->id, 'name' => $name]);
        $budget = new Budget(['department_id' => $this->opd->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today()->addDays($mulai)->format('Y-m-d'),
            'end_date' => today()->addDays($selesai)->format('Y-m-d'),
            'domain' => 'lddp',
            'status' => $status,
        ]);
    }

    public function test_judul_event_hanya_nama_dan_rincian_di_extended_props(): void
    {
        $sppd = $this->sppd('Andi Saputra', 2, 4, SppdStatus::APPROVED);
        $this->sppd('Budi Selesai', -3, -1, SppdStatus::COMPLETED);
        $this->sppd('Citra Proses', 2, 2, SppdStatus::IN_PROGRESS);

        $events = Livewire::test(SppdCalendar::class)->viewData('events');

        $this->assertCount(2, $events, 'Hanya SPPD disetujui & selesai yang tampil.');

        $andi = $events->firstWhere('title', 'Andi Saputra');
        $this->assertSame(today()->addDays(5)->format('Y-m-d'), $andi['end'], 'End FullCalendar eksklusif.');
        $this->assertSame('approved', $andi['extendedProps']['status']);
        $this->assertSame('Dinas Sosial', $andi['extendedProps']['department']);
        $this->assertSame($sppd->periodLabel(shortMonth: true), $andi['extendedProps']['period']);
        $this->assertSame(3, $andi['extendedProps']['days']);
        $this->assertContains('sppd-ev--approved', $andi['classNames']);

        $this->assertSame('completed', $events->firstWhere('title', 'Budi Selesai')['extendedProps']['status']);
    }

    public function test_hitungan_berangkat_tujuh_hari_ke_depan(): void
    {
        $this->sppd('Hari Ini', 0, 1, SppdStatus::APPROVED);
        $this->sppd('Besok', 1, 2, SppdStatus::APPROVED);
        $this->sppd('Seminggu', 7, 8, SppdStatus::COMPLETED);
        $this->sppd('Delapan Hari', 8, 9, SppdStatus::APPROVED);

        Livewire::test(SppdCalendar::class)
            ->assertViewHas('departingNextWeekCount', 2)
            ->assertViewHas('activeTravelsCount', 1)
            ->assertSee('Berangkat 7 Hari ke Depan');
    }
}
