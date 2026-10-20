<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Models\SppdWorkflow;
use App\Services\SppdWorkflowService;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur bawaan harus mencakup setiap peran pemohon yang sah di setiap tipe
 * instansi untuk setiap domain perjalanan — tepat satu alur, tanpa celah
 * maupun tumpang tindih.
 */
class SppdWorkflowCoverageTest extends TestCase
{
    use RefreshDatabase;

    private const PERANGKAT_DAERAH = ['staf', 'kasubid_kasubag', 'kabid_irban_kabag', 'sekretaris_opd', 'kepala_opd'];
    private const SETDA = ['staf', 'kasubid_kasubag', 'kabid_irban_kabag', 'asisten', 'sekda', 'wakil_walikota', 'walikota'];

    /** Peran pemohon yang sah per tipe instansi. */
    private const MATRIX = [
        'opd'         => self::PERANGKAT_DAERAH,
        'dinkes'      => self::PERANGKAT_DAERAH,
        'sekretariat' => self::PERANGKAT_DAERAH,
        'kecamatan'   => ['staf', 'kasubid_kasubag', 'sekretaris_opd', 'sekcam', 'kepala_opd', 'camat'],
        'kelurahan'   => ['staf', 'kasubid_kasubag', 'sekretaris_opd', 'kepala_opd', 'lurah'],
        'puskesmas'   => ['staf', 'kasubid_kasubag', 'kepala_opd', 'kapus'],
        'setda'       => self::SETDA,
        'asisten'     => self::SETDA,
        'bagian'      => self::SETDA,
        'subbagian'   => self::SETDA,
        'dprd'        => ['staf', 'kasubid_kasubag', 'kabid_irban_kabag', 'sekwan', 'anggota_dprd', 'pimpinan_dprd'],
    ];

    /** Seluruh role RoleAndPermissionSeeder selain akun administratif. */
    private const APPLICANT_ROLES = [
        'kepala_opd', 'sekretaris_opd', 'kasubid_kasubag', 'kabid_irban_kabag', 'pimpinan_dprd', 'asisten',
        'sekda', 'walikota', 'wakil_walikota', 'sekwan', 'camat', 'sekcam', 'lurah', 'kapus', 'anggota_dprd', 'staf',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SppdWorkflowSeeder::class);
    }

    public function test_setiap_tipe_instansi_dan_role_pemohon_tercakup(): void
    {
        $this->assertEqualsCanonicalizing(array_column(DepartmentType::cases(), 'value'), array_keys(self::MATRIX));
        $this->assertEqualsCanonicalizing(self::APPLICANT_ROLES, array_values(array_unique(array_merge(...array_values(self::MATRIX)))));
    }

    public function test_setiap_kombinasi_cocok_dengan_tepat_satu_alur(): void
    {
        $workflows = SppdWorkflow::where('is_active', true)->get();
        $problems = [];

        foreach (self::MATRIX as $type => $roles) {
            foreach ($roles as $role) {
                foreach (SppdDomain::values() as $domain) {
                    $matches = $workflows->filter(fn (SppdWorkflow $w) =>
                        in_array($type, $w->department_type, true)
                        && in_array($role, $w->applicant_role, true)
                        && in_array($domain, $w->destination, true));

                    if ($matches->count() !== 1) {
                        $problems[] = "{$type} / {$role} / {$domain}: " . $matches->count() . ' alur';
                    }
                }
            }
        }

        $this->assertSame([], $problems);
    }

    public function test_tahapan_sesuai_aturan_penandatangan(): void
    {
        foreach (SppdWorkflow::all() as $workflow) {
            $steps = collect($workflow->steps);

            $this->assertSame(1, $steps->where('signs_spt', true)->count(), "{$workflow->name}: penanda tangan SPT");
            $this->assertSame(1, $steps->where('signs_sppd', true)->count(), "{$workflow->name}: penanda tangan SPPD");

            foreach ($steps as $step) {
                // Tingkat persetujuan terendah Kabid/Irban/Kabag.
                $this->assertContains($step['role'], SppdWorkflowService::APPROVER_ROLES, "{$workflow->name}: {$step['role']}");
                // Tahap yang bisa dilewati tidak boleh memegang tanda tangan.
                if ($step['optional']) {
                    $this->assertFalse($step['signs_spt'] || $step['signs_sppd'], "{$workflow->name}: tahap opsional menandatangani");
                }
            }

            // Perjalanan luar negeri selalu ditandatangani Wali Kota (kecuali DPRD oleh Pimpinan DPRD).
            if (in_array('luar_negeri', $workflow->destination, true)) {
                $this->assertContains($steps->firstWhere('signs_spt', true)['role'], ['walikota', 'pimpinan_dprd'], $workflow->name);
            }
        }
    }
}
