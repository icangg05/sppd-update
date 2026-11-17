<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\SppdCategory;
use App\Models\SppdFollower;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pelaksana & pengikut SPPD Inspektorat dalam daerah diberi jabatan perjalanan (susunan tim
 * pengawasan APIP); di SPT mereka tercetak dari jabatan tertinggi, lalu golongan tertinggi.
 * Inspektorat luar daerah memakai alur normal seperti OPD lain, tanpa jabatan perjalanan.
 */
class JabatanPengikutInspektoratTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengikut_spt_diurutkan_dari_jabatan_perjalanan_lalu_golongan(): void
    {
        $follower = function (string $name, ?string $jabatan, ?string $group, ?int $level = null): SppdFollower {
            $user = new User(['name' => $name]);
            $user->setRelation('position', $level === null ? null : new Position(['level' => $level]));
            $user->setRelation('rank', $group === null ? null : new Rank(['group' => $group]));

            return (new SppdFollower(['travel_position' => $jabatan]))->setRelation('user', $user);
        };

        $sppd = (new SppdRequest())->setRelation('followers', new Collection([
            $follower('Anggota III/c', 'Anggota', 'III/c'),
            $follower('Admin', 'Admin Tim', 'IV/b'),
            $follower('Tak dikenal', 'Ketua Kelas', 'IV/e'),
            $follower('Ketua', 'Ketua Tim', 'III/d'),
            $follower('Anggota IV/b', 'Anggota', 'IV/b'),
            // Jabatan struktural tinggi tidak mengalahkan jabatan perjalanan.
            $follower('Pengendali', 'Pengendali Teknis', 'IV/a', 1000),
            $follower('Anggota Kasubag', 'Anggota', 'III/a', 50),
            $follower('PJ', 'Penanggung Jawab', 'IV/c'),
        ]));

        $this->assertSame(
            ['PJ', 'Pengendali', 'Ketua', 'Anggota Kasubag', 'Anggota IV/b', 'Anggota III/c', 'Admin', 'Tak dikenal'],
            $sppd->followersForSpt()->map(fn ($f) => $f->user->name)->all(),
        );
    }

    public function test_peserta_spt_termasuk_pelaksana_diurutkan_dari_jabatan_perjalanan(): void
    {
        $sppd = $this->sppdWithTeam('Anggota', 'III/a', [
            ['Ketua', 'Ketua Tim', 'III/b'],
            ['Admin', 'Admin Tim', 'IV/a'],
            ['Anggota IV/b', 'Anggota', 'IV/b'],
            ['Anggota III/a', 'Anggota', 'III/a'],
        ]);

        // Pelaksana tidak lagi otomatis nomor 1; setara jabatan & golongan → pelaksana didahulukan.
        $this->assertSame(
            ['Ketua', 'Anggota IV/b', 'Pelaksana', 'Anggota III/a', 'Admin'],
            $sppd->sptParticipants()->map(fn ($p) => $p->user->name)->all(),
        );
        $this->assertSame(
            [false, false, true, false, false],
            $sppd->sptParticipants()->map(fn ($p) => $p->is_pelaksana)->all(),
        );
    }

    public function test_pelaksana_tanpa_jabatan_perjalanan_ikut_diurutkan(): void
    {
        // Data lama: pelaksana belum punya jabatan perjalanan, jadi diurutkan seperti jabatan tak dikenal.
        $sppd = $this->sppdWithTeam(null, 'III/a', [
            ['Anggota', 'Anggota', 'III/c'],
            ['Ketua', 'Ketua Tim', 'IV/a'],
        ]);

        $this->assertSame(
            ['Ketua', 'Anggota', 'Pelaksana'],
            $sppd->sptParticipants()->map(fn ($p) => $p->user->name)->all(),
        );
    }

    public function test_spt_luar_daerah_inspektorat_memakai_alur_normal(): void
    {
        foreach (['lddp', 'ldlp', 'luar_negeri'] as $domain) {
            // Jabatan perjalanan yang terlanjur tersimpan diabaikan: urutan & cetakan seperti OPD lain.
            $sppd = $this->sppdWithTeam('Ketua Tim', 'III/a', [
                ['Auditor', 'Penanggung Jawab', 'III/c'],
                ['Inspektur', 'Anggota', 'IV/c', 1],
                ['Irban IV/a', 'Admin Tim', 'IV/a', 3],
                ['Irban IV/b', null, 'IV/b', 3],
            ], domain: $domain, department: 'Inspektorat', levelPelaksana: 3);

            // Pelaksana ikut diurutkan bersama pengikut: struktur jabatan, lalu golongan.
            $this->assertSame(
                ['Inspektur', 'Irban IV/b', 'Irban IV/a', 'Pelaksana', 'Auditor'],
                $sppd->sptParticipants()->map(fn ($p) => $p->user->name)->all(),
                $domain,
            );
            $this->assertSame([null], $sppd->sptParticipants()->pluck('travel_position')->unique()->values()->all());
            $this->assertFalse($sppd->usesTravelPositions());
        }
    }

    public function test_spt_opd_lain_pelaksana_ikut_diurutkan_dan_didahulukan_bila_setara(): void
    {
        $sppd = $this->sppdWithTeam(null, 'III/a', [
            ['Staf', null, 'III/c'],
            ['Kepala', null, 'IV/c', 1],
            ['Setara pelaksana', null, 'III/a', 5],
        ], domain: 'lddp', department: 'Dinas Perhubungan', levelPelaksana: 5);

        $this->assertSame(
            ['Kepala', 'Pelaksana', 'Setara pelaksana', 'Staf'],
            $sppd->sptParticipants()->map(fn ($p) => $p->user->name)->all(),
        );
        $this->assertSame(
            [false, true, false, false],
            $sppd->sptParticipants()->map(fn ($p) => $p->is_pelaksana)->all(),
        );
    }

    public function test_peserta_setara_diurutkan_dari_masa_kerja_lalu_usia_menurut_nip(): void
    {
        $user = function (string $name, ?string $nip): User {
            $user = new User(['name' => $name, 'nip' => $nip]);
            $user->setRelation('position', null);
            $user->setRelation('rank', new Rank(['group' => 'III/d']));

            return $user;
        };

        // Semua setara jabatan & golongan; pelaksana tidak otomatis didahulukan bila NIP-nya lebih junior.
        $sppd = (new SppdRequest(['domain' => 'lddp']))
            ->setRelation('user', $user('Pelaksana TMT 2015', '199001012015031001'))
            ->setRelation('followers', new Collection(array_map(
                fn (User $u) => (new SppdFollower())->setRelation('user', $u),
                [
                    $user('Tanpa NIP', null),
                    $user('TMT 2005 lahir 1980', '198001012005011001'),
                    $user('PPPK 2015', '198501012015211001'),
                    $user('TMT 2005 lahir 1975', '197501012005011002'),
                    $user('NIP rusak', '19680101'),
                ],
            )));

        $this->assertSame(
            ['TMT 2005 lahir 1975', 'TMT 2005 lahir 1980', 'Pelaksana TMT 2015', 'PPPK 2015', 'Tanpa NIP', 'NIP rusak'],
            $sppd->sptParticipants()->map(fn ($p) => $p->user->name)->all(),
        );
    }

    public function test_luar_daerah_tanpa_pilihan_jabatan_perjalanan(): void
    {
        [$sppd, $pengikut] = $this->sppdInspektorat('lddp');

        $this->form($sppd)
            ->assertDontSee('Pengendali Teknis')
            ->assertDontSee('Urutan nama di SPT mengikuti jabatan ini')
            ->set('followers', [$pengikut->id])
            // Nilai lama yang tertinggal di state tidak ikut tersimpan.
            ->set("follower_positions.{$pengikut->id}", 'Ketua Tim')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNull($sppd->fresh()->travel_position);
        $this->assertSame(1, $sppd->followers()->count());
        $this->assertNull($sppd->followers()->value('travel_position'));
    }

    public function test_peringkat_jabatan_perjalanan(): void
    {
        $this->assertSame(0, SppdFollower::travelPositionRank('Penanggung Jawab'));
        $this->assertSame(3, SppdFollower::travelPositionRank('  ketua TIM '));
        $this->assertSame(5, SppdFollower::travelPositionRank('Admin Tim'));
        $this->assertSame(PHP_INT_MAX, SppdFollower::travelPositionRank(null));
        $this->assertSame(PHP_INT_MAX, SppdFollower::travelPositionRank('Ketua Kelas'));
    }

    public function test_pilihan_jabatan_di_form_urut_dari_tertinggi(): void
    {
        [$sppd, $pengikut] = $this->sppdInspektorat();

        $this->form($sppd)
            ->set('followers', [$pengikut->id])
            ->assertSeeInOrder(SppdFollower::TRAVEL_POSITIONS);
    }

    public function test_jabatan_di_luar_daftar_ditolak(): void
    {
        [$sppd, $pengikut] = $this->sppdInspektorat();

        $this->form($sppd)
            ->set('followers', [$pengikut->id])
            ->set("follower_positions.{$pengikut->id}", 'Ketua Kelas')
            ->call('submit')
            ->assertHasErrors(["follower_positions.{$pengikut->id}"])
            ->assertDispatched('toast', type: 'error', title: 'Data Belum Lengkap');

        $this->assertSame(0, $sppd->followers()->count());
    }

    public function test_jabatan_pelaksana_wajib_dipilih_dan_modal_tetap_terbuka(): void
    {
        [$sppd, $pengikut] = $this->sppdInspektorat();

        $this->form($sppd)
            ->set('showConfirmModal', true)
            ->set('followers', [$pengikut->id])
            ->set("follower_positions.{$pengikut->id}", 'Ketua Tim')
            ->call('submit')
            ->assertHasErrors(['travel_position'])
            ->assertHasNoErrors(["follower_positions.{$pengikut->id}"])
            ->assertSet('showConfirmModal', true)
            ->assertDispatched('toast', type: 'error', title: 'Data Belum Lengkap')
            // Toast menyebut siapa yang belum dipilih jabatannya (pengikut sudah diisi).
            ->assertDispatched('toast', fn ($name, $params) => str_contains($params['message'], $sppd->user->name)
                && ! str_contains($params['message'], $pengikut->name));

        $this->assertNull($sppd->fresh()->travel_position);
    }

    public function test_jabatan_dari_daftar_tersimpan(): void
    {
        [$sppd, $pengikut] = $this->sppdInspektorat();

        $this->form($sppd)
            ->assertSee('Urutan nama di SPT mengikuti jabatan ini')
            ->set('followers', [$pengikut->id])
            ->set('travel_position', 'Pengendali Teknis')
            ->set("follower_positions.{$pengikut->id}", 'Ketua Tim')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('Pengendali Teknis', $sppd->fresh()->travel_position);
        $this->assertSame('Ketua Tim', $sppd->followers()->value('travel_position'));
    }

    public function test_jabatan_pelaksana_tersimpan_dimuat_ulang_saat_perbaikan(): void
    {
        [$sppd] = $this->sppdInspektorat();
        $sppd->update(['travel_position' => 'Ketua Tim']);

        $this->form($sppd)->assertSet('travel_position', 'Ketua Tim');
    }

    /**
     * SPPD (tanpa DB) berisi pelaksana & pengikut dengan jabatan perjalanan dan golongan.
     * Tiap anggota tim: [nama, jabatan perjalanan, golongan, level jabatan struktural (opsional)].
     */
    private function sppdWithTeam(
        ?string $jabatanPelaksana,
        string $golonganPelaksana,
        array $team,
        string $domain = 'dalam_daerah',
        string $department = 'Inspektorat',
        ?int $levelPelaksana = null,
    ): SppdRequest {
        $user = function (string $name, string $group, ?int $level = null): User {
            $user = new User(['name' => $name]);
            $user->setRelation('position', $level === null ? null : new Position(['level' => $level]));
            $user->setRelation('rank', new Rank(['group' => $group]));

            return $user;
        };

        $pelaksana = $user('Pelaksana', $golonganPelaksana, $levelPelaksana);
        $pelaksana->setRelation('department', new Department(['name' => $department]));

        return (new SppdRequest(['travel_position' => $jabatanPelaksana, 'domain' => $domain]))
            ->setRelation('user', $pelaksana)
            ->setRelation('followers', new Collection(array_map(
                fn (array $f) => (new SppdFollower(['travel_position' => $f[1]]))->setRelation('user', $user($f[0], $f[2], $f[3] ?? null)),
                $team,
            )));
    }

    /** SPPD pelaksana Inspektorat yang sedang diperbaiki, plus satu calon pengikut. */
    private function sppdInspektorat(string $domain = 'dalam_daerah'): array
    {
        $inspektorat = Department::create(['name' => 'Inspektorat Daerah', 'type' => DepartmentType::OPD, 'level' => 1]);
        $pelaksana = User::factory()->create(['department_id' => $inspektorat->id]);
        $pengikut = User::factory()->create(['department_id' => $inspektorat->id, 'name' => 'Auditor Muda']);
        $budget = new Budget(['department_id' => $inspektorat->id, 'description' => 'Pengawasan', 'year' => now()->year]);
        $budget->save();

        $sppd = SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Pemeriksaan'])->id,
            'purpose' => 'Pemeriksaan reguler',
            'start_date' => today()->addDays(40),
            'end_date' => today()->addDays(41),
            'domain' => $domain,
            'status' => SppdStatus::IN_PROGRESS,
        ]);

        return [$sppd, $pengikut];
    }

    private function form(SppdRequest $sppd)
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
        $this->grantDefaultPermissions();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);

        return Livewire::withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class);
    }
}
