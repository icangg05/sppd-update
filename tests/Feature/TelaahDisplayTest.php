<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdCreate;
use App\Livewire\Sppd\SppdShow;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Detail SPPD: uraian telaah berupa poin tampil sebagai daftar, dan setiap tahap alur
 * menandai pejabat yang menandatangani SPT/SPPD (sejak pratinjau Buat SPPD).
 */
class TelaahDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private User $admin;
    private User $staf;
    private User $kabid;
    private User $sekretaris;
    private User $kepala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(SppdWorkflowSeeder::class);

        $this->department = Department::create(['name' => 'Inspektorat', 'type' => DepartmentType::OPD, 'level' => 1, 'letterhead' => 'kop-surat/inspektorat.png']);
        $this->admin = $this->user('admin_opd');
        $this->kepala = $this->user('kepala_opd');
        $this->sekretaris = $this->user('sekretaris_opd');
        $this->kabid = $this->user('kabid_irban_kabag');
        $this->staf = $this->user('staf', ['atasan_id' => $this->kabid->id]);
    }

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id] + $attributes);
        $user->assignRole($role);

        return $user;
    }

    private function sppd(array $attributes = []): SppdRequest
    {
        $budget = new Budget(['department_id' => $this->department->id, 'description' => 'Pengawasan', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create($attributes + [
            'user_id' => $this->staf->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Pengawasan'])->id,
            'purpose' => 'Probity audit',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'lddp',
        ]);
    }

    public function test_poin_telaah_tampil_sebagai_daftar_di_detail_dan_halaman_publik(): void
    {
        $sppd = $this->sppd([
            'analysis' => '-Pengumpulan data; -Telaah dokumen; -Cek fisik pekerjaan',
            'problem' => 'Masih harus ditindaklanjuti',
        ]);

        Livewire::actingAs($this->admin)->test(SppdShow::class, ['sppd' => $sppd])
            ->assertSeeHtmlInOrder(['<ul role="list"', 'Pengumpulan data;', '</li>', 'Telaah dokumen;', '</li>', 'Cek fisik pekerjaan'])
            ->assertDontSee('-Pengumpulan data')
            ->assertSee('Masih harus ditindaklanjuti');

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSeeInOrder(['Pengumpulan data;', 'Telaah dokumen;', 'Cek fisik pekerjaan'])
            ->assertDontSee('-Pengumpulan data');
    }

    public function test_maksud_dan_uraian_telaah_selalu_diawali_huruf_kapital(): void
    {
        // Data lama berhuruf kecil (tersimpan sebelum aturan ini) tampil kapital tanpa diubah.
        $lama = $this->sppd();
        \Illuminate\Support\Facades\DB::table('sppd_requests')->where('id', $lama->id)->update([
            'purpose' => 'perjalanan dinas dalam rangka BLUD',
            'problem' => 'belum ada data',
            'facts' => "-surat undangan; -program kerja",
            'analysis' => 'perlu koordinasi',
        ]);
        $lama = $lama->fresh();

        $this->assertSame('Perjalanan dinas dalam rangka BLUD', $lama->purpose);
        $this->assertSame('Belum ada data', $lama->problem);
        $this->assertSame('-Surat undangan; -Program kerja', $lama->facts);
        $this->assertSame('Perlu koordinasi', $lama->analysis);
        $this->assertSame('perjalanan dinas dalam rangka BLUD', $lama->getRawOriginal('purpose'));

        // Isian baru tersimpan kapital.
        $baru = $this->sppd(['purpose' => 'monitoring kegiatan', 'analysis' => 'perlu tindak lanjut']);
        $this->assertSame('Monitoring kegiatan', $baru->fresh()->getRawOriginal('purpose'));
        $this->assertSame('Perlu tindak lanjut', $baru->fresh()->getRawOriginal('analysis'));

        Livewire::actingAs($this->admin)->test(SppdShow::class, ['sppd' => $lama])
            ->assertSee('Perjalanan dinas dalam rangka BLUD')
            ->assertSee('Belum ada data')
            ->assertDontSee('perjalanan dinas dalam rangka BLUD');
    }

    public function test_pratinjau_buat_sppd_menandai_penanda_tangan_spt_dan_sppd(): void
    {
        // Staf OPD luar daerah: Kabid → Sekretaris → Kepala (SPT & SPPD).
        $component = Livewire::actingAs($this->admin)->test(SppdCreate::class)
            ->set('domain', 'lddp')
            ->call('selectUser', $this->staf->id)
            ->assertSeeInOrder([$this->kabid->name, $this->sekretaris->name, $this->kepala->name, 'TTE SPT & SPPD']);

        // Hanya Kepala yang menandatangani: satu penanda saja.
        $this->assertSame(1, substr_count($component->html(), 'fa-signature'));
    }

    public function test_kartu_alur_detail_sppd_menandai_penanda_tangan(): void
    {
        $sppd = $this->sppd();
        $sppd->approvals()->createMany([
            ['approver_id' => $this->kabid->id, 'role_label' => 'Kabid', 'step_order' => 1, 'status' => 'pending'],
            ['approver_id' => $this->kepala->id, 'role_label' => 'Kepala OPD', 'step_order' => 2, 'status' => 'pending', 'signs_sppd' => true],
            ['approver_id' => $this->sekretaris->id, 'role_label' => 'Wali Kota', 'step_order' => 3, 'status' => 'pending', 'signs_spt' => true],
        ]);

        Livewire::actingAs($this->admin)->test(SppdShow::class, ['sppd' => $sppd])
            ->assertSeeInOrder([$this->kabid->name, $this->kepala->name, 'TTE SPPD', $this->sekretaris->name, 'TTE SPT']);
    }
}
