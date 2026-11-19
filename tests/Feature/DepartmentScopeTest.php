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
}
