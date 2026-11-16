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
 * Pelaksana & pengikut SPPD Inspektorat diberi jabatan perjalanan (susunan tim pengawasan
 * APIP); di SPT mereka tercetak dari jabatan tertinggi, lalu golongan tertinggi.
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

    public function test_tanpa_jabatan_pelaksana_tetap_nomor_satu(): void
    {
        $sppd = $this->sppdWithTeam(null, 'III/a', [
            ['Anggota', 'Anggota', 'III/c'],
            ['Ketua', 'Ketua Tim', 'IV/a'],
        ]);

        $this->assertSame(
            ['Pelaksana', 'Ketua', 'Anggota'],
            $sppd->sptParticipants()->map(fn ($p) => $p->user->name)->all(),
        );
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

    /** SPPD (tanpa DB) berisi pelaksana & pengikut dengan jabatan perjalanan dan golongan. */
    private function sppdWithTeam(?string $jabatanPelaksana, string $golonganPelaksana, array $team): SppdRequest
    {
        $user = function (string $name, string $group): User {
            $user = new User(['name' => $name]);
            $user->setRelation('position', null);
            $user->setRelation('rank', new Rank(['group' => $group]));

            return $user;
        };

        return (new SppdRequest(['travel_position' => $jabatanPelaksana]))
            ->setRelation('user', $user('Pelaksana', $golonganPelaksana))
            ->setRelation('followers', new Collection(array_map(
                fn (array $f) => (new SppdFollower(['travel_position' => $f[1]]))->setRelation('user', $user($f[0], $f[2])),
                $team,
            )));
    }

    /** SPPD pelaksana Inspektorat yang sedang diperbaiki, plus satu calon pengikut. */
    private function sppdInspektorat(): array
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
            'domain' => 'lddp',
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
