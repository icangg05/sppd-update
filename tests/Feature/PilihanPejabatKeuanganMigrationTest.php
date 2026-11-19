<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Migrasi pilihan PPTK/KPA/Bendahara: SPPD yang sudah ada mencatat penandatangan persis seperti yang
 * tercetak selama ini, lalu pengaturan Setuju Bayar per unit dihapus.
 *
 * Memakai DatabaseMigrations (tanpa transaksi pembungkus): di SQLite, mengubah kolom ber-FK membangun
 * ulang tabel departments, dan PRAGMA foreign_keys=OFF yang dipakai Laravel untuk itu tidak berlaku
 * di dalam transaksi (baris tabel turunan ikut terhapus). Di MySQL hal itu tidak terjadi.
 */
class PilihanPejabatKeuanganMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_sppd_lama_dicatat_seperti_cetakan_lama(): void
    {
        $migration = require database_path('migrations/2026_11_19_000001_add_finance_choices_to_users_and_sppd_requests.php');
        $migration->down();
        $this->assertTrue(Schema::hasColumn('departments', 'setuju_bayar_user_id'));
        $this->assertFalse(Schema::hasColumn('sppd_requests', 'kpa_id'));

        Role::firstOrCreate(['name' => 'kepala_opd', 'guard_name' => 'web'], ['label' => 'Kepala OPD']);
        $setda = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1, 'can_view_children_data' => true]);
        $bagian = Department::create(['name' => 'Bagian Perekonomian', 'type' => DepartmentType::BAGIAN, 'level' => 2, 'parent_id' => $setda->id, 'can_view_children_data' => true]);
        $bagianUmum = Department::create(['name' => 'Bagian Umum', 'type' => DepartmentType::BAGIAN, 'level' => 2, 'parent_id' => $setda->id, 'can_view_children_data' => true]);
        $subbagian = Department::create(['name' => 'Sub Bagian Rumah Tangga', 'type' => DepartmentType::SUBBAGIAN, 'level' => 3, 'parent_id' => $bagianUmum->id, 'can_view_children_data' => true]);
        $dinas = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1, 'can_view_children_data' => true]);

        $jabatan = Position::create(['name' => 'Bendahara Pengeluaran']);
        $kabag = User::factory()->create(['department_id' => $bagian->id, 'name' => 'Kabag Perekonomian']);
        DB::table('departments')->where('id', $setda->id)->update(['setuju_bayar_user_id' => $kabag->id, 'setuju_bayar_label' => 'Kuasa Pengguna Anggaran']);
        $bendaharaBagian = User::factory()->create(['department_id' => $bagian->id, 'position_id' => $jabatan->id]);
        $kepalaDinas = User::factory()->create(['department_id' => $dinas->id, 'name' => 'Kepala Dinas Sosial']);
        $kepalaDinas->assignRole('kepala_opd');

        $budget = Budget::create(['department_id' => $setda->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $category = SppdCategory::create(['name' => 'Koordinasi']);
        $sppdOf = fn (Department $unit) => DB::table('sppd_requests')->insertGetId([
            'user_id' => User::factory()->create(['department_id' => $unit->id])->id, 'creator_id' => $kabag->id,
            'budget_id' => $budget->id, 'category_id' => $category->id, 'purpose' => 'Rapat', 'start_date' => '2026-12-01',
            'end_date' => '2026-12-02', 'domain' => 'dalam_daerah', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sppdBagian = $sppdOf($bagian);
        $sppdSub = $sppdOf($subbagian);
        $sppdDinas = $sppdOf($dinas);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('departments', 'setuju_bayar_user_id'));
        $sppd = fn (int $id) => SppdRequest::find($id);
        // Setuju Bayar Setda + bendahara di unit pelaksana.
        $this->assertSame([$kabag->id, 'Kuasa Pengguna Anggaran', $bendaharaBagian->id, 'Bendahara Pengeluaran'],
            [$sppd($sppdBagian)->kpa_id, $sppd($sppdBagian)->kpa_label, $sppd($sppdBagian)->bendahara_id, $sppd($sppdBagian)->bendahara_label]);
        // Bendahara dulu hanya dicari di unit & induknya: tetap kosong (dipilih di Portal Dokumen).
        $this->assertSame($kabag->id, $sppd($sppdSub)->kpa_id);
        $this->assertNull($sppd($sppdSub)->bendahara_id);
        // Tanpa pengaturan: Kepala OPD, label kosong = label bawaan dokumen lama.
        $this->assertSame($kepalaDinas->id, $sppd($sppdDinas)->kpa_id);
        $this->assertNull($sppd($sppdDinas)->kpa_label);

        // Kolom pilihan per pegawai tersedia dan mulai kosong.
        $this->assertTrue(Schema::hasColumns('users', ['pptk_id', 'kpa_id', 'kpa_label', 'bendahara_id']));
        $this->assertNull($kabag->fresh()->kpa_id);
    }
}
