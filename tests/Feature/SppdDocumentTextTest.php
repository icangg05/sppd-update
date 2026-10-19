<?php

namespace Tests\Feature;

use App\Enums\SppdDomain;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdDestination;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SppdDocumentTextTest extends TestCase
{
    use RefreshDatabase;

    private function makeSppd(SppdDomain $domain, array $destinations): SppdRequest
    {
        $sppd = new SppdRequest([
            'purpose' => 'Mengikuti Kegiatan Evaluasi Kinerja PEMDI Onsite Wilayah Provinsi Sulawesi Tenggara',
            'start_date' => Carbon::parse('2026-10-05'),
            'end_date' => Carbon::parse('2026-10-06'),
            'spt_date' => Carbon::parse('2026-10-01'),
            'sppd_date' => Carbon::parse('2026-10-01'),
            'departure_place' => 'kantor dinas kominfo kota kendari',
            'domain' => $domain,
            'transport_name' => 'Pesawat',
        ]);

        $user = new User(['name' => 'Budi', 'nip' => '1990']);
        $user->setRelation('department', new Department(['name' => 'Dinas Kominfo']));
        $sppd->setRelation('user', $user);
        $sppd->setRelation('budget', new Budget(['account_code' => '5.1.02', 'source' => 'APBD']));
        $sppd->setRelation('followers', collect());
        $sppd->setRelation('destinations', collect(array_map(fn ($d) => new SppdDestination($d), $destinations)));

        return $sppd;
    }

    private function pdfData(): array
    {
        return [
            'approver_name' => 'Pejabat', 'approver_role' => 'Kepala Dinas', 'approver_nip' => null,
            'approver_rank' => '', 'approver_group' => '', 'is_walikota' => false, 'is_approved' => false,
            'qr_image' => null, 'duration' => 2, 'letterhead_url' => null,
        ];
    }

    /** Teks polos dokumen dengan spasi dirapatkan, agar mudah dicocokkan. */
    private function text(string $html): string
    {
        return preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags($html)));
    }

    public function test_kalimat_spt_menyebut_tempat_tujuan(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LDDP, [['address' => 'kantor dinas kominfo prov. sulawesi tenggara']]);

        $html = view('exports.spt', ['sppd' => $sppd, 'pdfData' => $this->pdfData()])->render();

        $this->assertStringContainsString(
            'Mengikuti Kegiatan Evaluasi Kinerja PEMDI Onsite Wilayah Provinsi Sulawesi Tenggara di Kantor Dinas Kominfo Prov. Sulawesi Tenggara Selama 2 hari dari tanggal 05 Oktober 2026 s/d 06 Oktober 2026.',
            $this->text($html),
        );
    }

    public function test_sppd_luar_negeri_menyebut_kota_dan_negara(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LUAR_NEGERI, [
            ['country' => 'Jepang', 'city' => 'tokyo', 'address' => 'KBRI tokyo'],
            ['country' => 'Korea Selatan', 'city' => 'seoul', 'address' => 'KBRI seoul'],
        ]);

        $html = view('exports.sppd', [
            'sppd' => $sppd,
            'user' => $sppd->user,
            'is_main' => true,
            'pdfData' => $this->pdfData() + ['is_follower' => false, 'travel_position' => null],
        ])->render();
        $text = $this->text($html);

        $this->assertStringContainsString('Kantor Dinas Kominfo Kota Kendari', $text);
        $this->assertStringContainsString('b. KBRI Tokyo, Tokyo, Jepang; KBRI Seoul, Seoul, Korea Selatan', $text);
        $this->assertStringContainsString('Tiba di : Tokyo, Jepang', $text);
        $this->assertStringContainsString('Berangkat dari : Tokyo, Jepang', $text);
        $this->assertStringContainsString('Ke : Seoul, Korea Selatan', $text);

        // Nama tempat tidak dipaksa HURUF BESAR, dan titik dua di kolom terpisah agar sejajar.
        $this->assertDoesNotMatchRegularExpression('/<td[^>]*uppercase[^>]*>\s*Tokyo, Jepang/', $html);
        $this->assertMatchesRegularExpression('/<td[^>]*>:<\/td>\s*<td[^>]*>Tokyo, Jepang<\/td>/', $html);
    }
}
