<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdIndex;
use App\Livewire\Sppd\SppdShow;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Nomor dokumen hanya dipakai Inspektorat (diinput di form SPPD): keterangan
 * "belum bernomor" tidak tampil untuk SPPD instansi lain, dan Daftar SPPD Inspektorat
 * diurutkan menurut nomornya.
 */
class DocumentNumberDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Filter tahun & urutan nomor Daftar SPPD memakai YEAR() dan SUBSTRING_INDEX() MySQL;
        // padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
            $pdo->sqliteCreateFunction('SUBSTRING_INDEX', function ($value, $delimiter, $count) {
                if ($value === null) {
                    return null;
                }
                $parts = explode($delimiter, $value);

                return implode($delimiter, $count >= 0 ? array_slice($parts, 0, $count) : array_slice($parts, $count));
            }, 3);
        }

        foreach (['staf', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
    }

    private function sppd(string $instansi): SppdRequest
    {
        $department = Department::create(['name' => $instansi, 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $department->id]);
        $staf->assignRole('staf');
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
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

    public function test_daftar_sppd_inspektorat_urut_tanggal_spt_lalu_nomor_terbesar(): void
    {
        $inspektorat = Department::create(['name' => 'Inspektorat', 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $inspektorat->id]);
        $staf->assignRole('staf');
        $budget = new Budget(['department_id' => $inspektorat->id, 'description' => 'Pengawasan', 'year' => now()->year]);
        $budget->save();
        $category = SppdCategory::create(['name' => 'Monitoring & Evaluasi']);

        $make = fn (?string $nomor, string $sptDate, int $startInDays) => SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => $category->id,
            'purpose' => 'Monitoring',
            'start_date' => today()->addDays($startInDays),
            'end_date' => today()->addDays($startInDays),
            'spt_date' => $sptDate,
            'domain' => 'dalam_daerah',
            'document_number' => $nomor,
        ]);

        // Tanggal berangkat sengaja tidak searah dengan nomor (dulu daftar urut tanggal berangkat).
        $make('090 / 99 / ST / INSP. / 2026', '2026-11-10', 9);
        $make('090 / 101 / ST / INSP./ 2026', '2026-11-10', 1);
        $make(null, '2026-11-10', 20);
        $make('090 / 100 / ST / INSP. / 2026', '2026-11-10', 5);
        $make('090 / 102 / ST / INSP. / 2026', '2026-11-12', 0);

        $numbers = fn (User $viewer) => Livewire::actingAs($viewer)->test(SppdIndex::class)
            ->viewData('sppds')->getCollection()->pluck('document_number')->all();

        // 100 di atas 99: dibandingkan sebagai angka, bukan teks.
        $this->assertSame([
            '090 / 102 / ST / INSP. / 2026',
            '090 / 101 / ST / INSP./ 2026',
            '090 / 100 / ST / INSP. / 2026',
            '090 / 99 / ST / INSP. / 2026',
            null,
        ], $numbers($staf));

        // Di luar Inspektorat (Super Admin, semua OPD) tetap urut tanggal berangkat terbaru.
        $this->assertSame([
            null,
            '090 / 99 / ST / INSP. / 2026',
            '090 / 100 / ST / INSP. / 2026',
            '090 / 101 / ST / INSP./ 2026',
            '090 / 102 / ST / INSP. / 2026',
        ], $numbers($this->superAdmin));
    }

    public function test_nomor_dokumen_di_daftar_tidak_dipotong(): void
    {
        $sppd = $this->sppd('Inspektorat');
        $sppd->update(['document_number' => '090 / 384 / ST / INSP. / 2026']);

        Livewire::actingAs($this->superAdmin)->test(SppdIndex::class)
            ->assertSee('090 / 384 / ST / INSP. / 2026')
            ->assertSeeHtml('class="whitespace-nowrap font-mono text-slate-600"')
            ->assertDontSeeHtml('class="mt-0.5 truncate text-xs text-slate-500"');
    }

    public function test_keterangan_belum_bernomor_hanya_untuk_inspektorat(): void
    {
        $dinas = $this->sppd('Dinas Perhubungan');

        Livewire::actingAs($this->superAdmin)->test(SppdIndex::class)->assertDontSee('Belum bernomor');
        Livewire::actingAs($this->superAdmin)->test(SppdShow::class, ['sppd' => $dinas])->assertDontSee('Belum memiliki nomor dokumen');

        $inspektorat = $this->sppd('Inspektorat');

        Livewire::actingAs($this->superAdmin)->test(SppdIndex::class)->assertSee('Belum bernomor');
        Livewire::actingAs($this->superAdmin)->test(SppdShow::class, ['sppd' => $inspektorat])->assertSee('Belum memiliki nomor dokumen');

        // Nomor yang sudah terisi tetap tampil.
        $inspektorat->update(['document_number' => '090/12/ST/INSP./2026']);
        Livewire::actingAs($this->superAdmin)->test(SppdShow::class, ['sppd' => $inspektorat])->assertSee('090/12/ST/INSP./2026');
    }
}
