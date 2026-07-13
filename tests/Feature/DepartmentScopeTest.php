<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kecamatan melihat sub-unit internalnya, tapi TIDAK melihat kelurahan
     * (kelurahan = zona data mandiri berdasarkan tipe).
     */
    public function test_kecamatan_scope_excludes_kelurahan_but_keeps_internal_subunit(): void
    {
        $kecamatan = Department::create([
            'name' => 'Kecamatan Kadia',
            'type' => DepartmentType::KECAMATAN,
            'level' => 1,
            'can_view_children_data' => true,
        ]);

        $kelurahan = Department::create([
            'name' => 'Kelurahan Bende',
            'type' => DepartmentType::KELURAHAN,
            'parent_id' => $kecamatan->id,
            'level' => 2,
            'can_view_children_data' => true,
        ]);

        $subunit = Department::create([
            'name' => 'Sekretariat Kecamatan',
            'type' => DepartmentType::KECAMATAN,
            'parent_id' => $kecamatan->id,
            'level' => 2,
            'can_view_children_data' => true,
        ]);

        $scoped = $kecamatan->getScopedDescendantIds();

        $this->assertTrue($scoped->contains($subunit->id), 'sub-unit internal harus terlihat kecamatan');
        $this->assertFalse($scoped->contains($kelurahan->id), 'kelurahan tidak boleh masuk zona kecamatan');

        // Kelurahan adalah root zonanya sendiri.
        $this->assertSame($kelurahan->id, $kelurahan->getScopeRootDepartment()->id);
    }

    /**
     * Puskesmas di bawah Dinas Kesehatan diperlakukan sama seperti kelurahan:
     * zona data mandiri, tidak masuk zona induk.
     */
    public function test_dinkes_scope_excludes_puskesmas(): void
    {
        $dinkes = Department::create([
            'name' => 'Dinas Kesehatan',
            'type' => DepartmentType::DINKES,
            'level' => 1,
            'can_view_children_data' => true,
        ]);

        $puskesmas = Department::create([
            'name' => 'Puskesmas Mata',
            'type' => DepartmentType::PUSKESMAS,
            'parent_id' => $dinkes->id,
            'level' => 2,
            'can_view_children_data' => true,
        ]);

        $subunit = Department::create([
            'name' => 'Sekretariat Dinkes',
            'type' => DepartmentType::DINKES,
            'parent_id' => $dinkes->id,
            'level' => 2,
            'can_view_children_data' => true,
        ]);

        $scoped = $dinkes->getScopedDescendantIds();

        $this->assertTrue($scoped->contains($subunit->id), 'sub-unit internal harus terlihat dinkes');
        $this->assertFalse($scoped->contains($puskesmas->id), 'puskesmas tidak boleh masuk zona dinkes');
        $this->assertSame($puskesmas->id, $puskesmas->getScopeRootDepartment()->id);
    }

    /**
     * Setuju Bayar cukup diset SEKALI di Dinas → diwarisi sub-unit dalam
     * (Bidang → Seksi), tanpa diset per unit. Setting terdekat menang.
     */
    public function test_setuju_bayar_inherits_from_dinas_root(): void
    {
        $dinas = Department::create(['name' => 'Dinas Kominfo', 'type' => DepartmentType::OPD, 'level' => 1, 'can_view_children_data' => true]);
        $bidang = Department::create(['name' => 'Bidang Aptika', 'type' => DepartmentType::OPD, 'parent_id' => $dinas->id, 'level' => 2, 'can_view_children_data' => true]);
        $seksi = Department::create(['name' => 'Seksi Persandian', 'type' => DepartmentType::OPD, 'parent_id' => $bidang->id, 'level' => 3, 'can_view_children_data' => true]);

        $signer = User::factory()->create(['is_active' => true, 'department_id' => $dinas->id]);
        $dinas->update(['setuju_bayar_user_id' => $signer->id, 'setuju_bayar_label' => 'PENGGUNA ANGGARAN']);

        // Seksi 2 level di bawah Dinas tetap dapat penandatangan Dinas.
        $resolved = $seksi->resolveSetujuBayar();
        $this->assertSame($signer->id, $resolved['user']?->id);
        $this->assertSame('PENGGUNA ANGGARAN', $resolved['label']);
        $this->assertSame($dinas->id, $resolved['opd']->id);

        // Setting lebih dekat (Bidang) menang atas Dinas.
        $bidangSigner = User::factory()->create(['is_active' => true, 'department_id' => $bidang->id]);
        $bidang->update(['setuju_bayar_user_id' => $bidangSigner->id]);
        $this->assertSame($bidangSigner->id, $seksi->fresh()->resolveSetujuBayar()['user']?->id);
    }
}
