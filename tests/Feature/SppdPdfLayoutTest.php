<?php

namespace Tests\Feature;

use App\Enums\SppdDomain;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdRequest;
use App\Models\User;
use App\Support\SppdPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kotak tanda tangan II-V di halaman belakang SPPD mengisi sisa ruang di atas footer:
 * lebih tinggi saat nama jabatan pendek, lebih rendah saat jabatan beberapa baris,
 * dan dokumen tetap satu halaman.
 */
class SppdPdfLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function data(string $approverRole): array
    {
        $sppd = new SppdRequest([
            'purpose' => 'Koordinasi kegiatan',
            'start_date' => Carbon::parse('2026-12-01'),
            'end_date' => Carbon::parse('2026-12-03'),
            'spt_date' => Carbon::parse('2026-11-17'),
            'sppd_date' => Carbon::parse('2026-11-17'),
            'departure_place' => 'kantor dinas',
            'domain' => SppdDomain::DALAM_DAERAH,
            'transport_name' => 'Mobil',
        ]);
        $user = new User(['name' => 'Budi', 'nip' => '1990']);
        $user->setRelation('department', new Department(['name' => 'Dinas Kominfo']));
        $sppd->setRelation('user', $user);
        $sppd->setRelation('budget', new Budget(['account_code' => '5.1.02', 'source' => 'APBD']));
        $sppd->setRelation('followers', collect());
        $sppd->setRelation('destinations', collect());

        return [
            'sppd' => $sppd,
            'user' => $user,
            'is_main' => true,
            'pdfData' => [
                'approver_name' => 'SAHURIYANTO MERONDA, SP., MM.', 'approver_role' => $approverRole,
                'approver_nip' => '198003252009011001', 'approver_rank' => '', 'approver_group' => '',
                'is_walikota' => false, 'is_approved' => false, 'qr_image' => null, 'duration' => 3,
                'is_follower' => false, 'travel_position' => null, 'letterhead_url' => null,
            ],
        ];
    }

    public function test_kotak_tanda_tangan_mengisi_sisa_ruang_sesuai_panjang_jabatan(): void
    {
        $short = $this->data('Kepala Dinas');
        $long = $this->data('Pelaksana Harian Kepala Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah Kota Kendari');

        $shortHeight = SppdPdf::fittedBoxHeight($short);
        $longHeight = SppdPdf::fittedBoxHeight($long);

        // Jabatan 3 baris tercetak dua kali (I dan VI): ruangnya diambil dari kotak II-V.
        $this->assertGreaterThan($longHeight, $shortHeight);
        $this->assertGreaterThan(SppdPdf::MIN_BOX_HEIGHT, $longHeight);

        foreach ([$short, $long] as $data) {
            $pdf = SppdPdf::make($data);
            $pdf->output();
            $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
        }
    }
}
