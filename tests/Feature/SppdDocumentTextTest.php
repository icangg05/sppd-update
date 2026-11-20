<?php

namespace Tests\Feature;

use App\Enums\SppdDomain;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\SppdDestination;
use App\Models\SppdFollower;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
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

    public function test_spt_satu_hari_menyebut_satu_tanggal_tanpa_sd(): void
    {
        $sppd = $this->makeSppd(SppdDomain::DALAM_DAERAH, [['address' => 'posyandu lalombaku, kelurahan watulondo']]);
        $sppd->start_date = $sppd->end_date = Carbon::parse('2026-12-10');
        $pdfData = ['duration' => 1] + $this->pdfData();

        $spt = $this->text(view('exports.spt', ['sppd' => $sppd, 'pdfData' => $pdfData])->render());
        $this->assertStringContainsString(
            'Mengikuti Kegiatan Evaluasi Kinerja PEMDI Onsite Wilayah Provinsi Sulawesi Tenggara di Posyandu Lalombaku, Kelurahan Watulondo, selama 1 hari, pada tanggal 10 Desember 2026.',
            $spt,
        );
        $this->assertStringNotContainsString('s/d', $spt);

        $dprd = $this->text(view('exports.spt_dprd', ['sppd' => $sppd, 'pdfData' => $pdfData, 'participants' => collect()])->render());
        $this->assertStringContainsString('Hari/Tanggal : Selama 1 hari, pada tanggal 10 Desember 2026.', $dprd);
        $this->assertStringNotContainsString('s/d', $dprd);
    }

    public function test_spt_dprd_lebih_dari_sehari_tetap_memakai_rentang(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LDDP, [['address' => 'jakarta']]);

        $dprd = $this->text(view('exports.spt_dprd', ['sppd' => $sppd, 'pdfData' => $this->pdfData(), 'participants' => collect()])->render());
        $this->assertStringContainsString('Hari/Tanggal : Selama 2 hari dari tanggal 05 Oktober 2026 s/d 06 Oktober 2026.', $dprd);
    }

    public function test_spt_tanpa_pangkat_tidak_mencetak_barisnya(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LDDP, [['address' => 'kantor gubernur']]);
        $pdfData = ['approver_nip' => '197001012000011002'] + $this->pdfData();

        $spt = $this->text(view('exports.spt', ['sppd' => $sppd, 'pdfData' => $pdfData])->render());
        $this->assertStringNotContainsString('Pangkat/Golongan', $spt);
        $this->assertStringNotContainsString(' - NIP', $spt);
        $this->assertStringNotContainsString('Gol.', $spt);
        $this->assertStringContainsString('NIP. 197001012000011002', $spt);

        $dprd = $this->text(view('exports.spt_dprd', ['sppd' => $sppd, 'pdfData' => $pdfData, 'participants' => collect()])->render());
        $this->assertStringNotContainsString(' - NIP', $dprd);
        $this->assertStringNotContainsString('Gol.', $dprd);
        $this->assertStringContainsString('NIP. 197001012000011002', $dprd);
    }

    public function test_spt_peserta_tanpa_nip_atau_pangkat_tidak_mencetak_barisnya(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LDDP, [['address' => 'kantor gubernur']]);
        $sppd->user->nip = null;
        $pengikut = new User(['name' => 'Sari', 'nip' => '']);
        $pengikut->setRelation('rank', new Rank(['name' => 'Penata', 'group' => 'III/c']));
        $pengikut->setRelation('position', new Position(['name' => 'Pranata Komputer']));
        $sppd->setRelation('followers', collect([(new SppdFollower())->setRelation('user', $pengikut)]));

        $spt = $this->text(view('exports.spt', ['sppd' => $sppd, 'pdfData' => $this->pdfData()])->render());

        // Budi tanpa pangkat & NIP: langsung ke Jabatan. Sari berpangkat tanpa NIP: baris NIP saja yang hilang.
        $this->assertStringContainsString('Nama : Budi Jabatan', $spt);
        $this->assertStringContainsString('Nama : Sari Pangkat/Golongan : Penata, Gol. III/c Jabatan : Pranata Komputer', $spt);
        $this->assertStringNotContainsString('NIP : -', $spt);
        $this->assertStringNotContainsString('Pangkat/Golongan : -', $spt);
    }

    public function test_pegawai_tanpa_jabatan_dicetak_dengan_label_role(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LDDP, [['address' => 'kantor gubernur']]);
        $sppd->user->setRelation('position', null);
        $sppd->user->setRelation('roles', collect([new Role(['name' => 'kabid_irban_kabag', 'label' => 'Kabid / Irban / Kabag'])]));

        $spt = $this->text(view('exports.spt', ['sppd' => $sppd, 'pdfData' => $this->pdfData()])->render());
        $sppdDoc = $this->text(view('exports.sppd', [
            'sppd' => $sppd,
            'user' => $sppd->user,
            'is_main' => true,
            'pdfData' => $this->pdfData() + ['is_follower' => false, 'travel_position' => null],
        ])->render());

        $this->assertStringContainsString('Jabatan : Kabid / Irban / Kabag', $spt);
        $this->assertStringContainsString('b. Kabid / Irban / Kabag', $sppdDoc);
        $this->assertStringNotContainsString('kabid_irban_kabag', $spt . $sppdDoc);
    }

    public function test_label_jabatan_mendahulukan_nama_jabatan(): void
    {
        $user = new User(['name' => 'Sari']);
        $user->setRelation('roles', collect([new Role(['name' => 'staf', 'label' => 'Staf'])]));
        $user->setRelation('position', new Position(['name' => 'Pranata Komputer']));
        $this->assertSame('Pranata Komputer', $user->jabatanLabel());

        $user->setRelation('position', null);
        $this->assertSame('Staf', $user->jabatanLabel());

        $user->setRelation('roles', collect());
        $this->assertSame('-', $user->jabatanLabel());
    }

    public function test_spt_dengan_pangkat_tetap_lengkap(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LDDP, [['address' => 'kantor gubernur']]);
        $sppd->user->setRelation('rank', new Rank(['name' => 'Penata', 'group' => 'III/c']));
        $pdfData = ['approver_nip' => '197001012000011002', 'approver_rank' => 'Pembina', 'approver_group' => 'IV/a'] + $this->pdfData();

        $spt = $this->text(view('exports.spt', ['sppd' => $sppd, 'pdfData' => $pdfData])->render());

        $this->assertStringContainsString('Pangkat/Golongan : Penata, Gol. III/c NIP', $spt);
        $this->assertStringContainsString('Pembina, Gol. IV/a NIP. 197001012000011002', $spt);
    }

    public function test_pangkat_golongan_hanya_mencetak_bagian_yang_ada(): void
    {
        $this->assertNull(Rank::pangkatGolongan(null, null));
        $this->assertNull(Rank::pangkatGolongan('', ' '));
        $this->assertSame('Penata', Rank::pangkatGolongan('Penata', null));
        $this->assertSame('Gol. III/c', Rank::pangkatGolongan(null, 'III/c'));
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

    /**
     * Tanda tangan dan tembusan SPT satu paket page-break-inside: avoid, sehingga tembusan tidak
     * pernah tertinggal sendirian di halaman terakhir. Kalimat "Demikian" sengaja di luar paket.
     */
    public function test_tanda_tangan_dan_tembusan_spt_satu_paket_yang_tidak_terpotong(): void
    {
        $sppd = $this->makeSppd(SppdDomain::LDDP, [['address' => 'kantor gubernur']]);
        $pdfData = ['approver_nip' => '197001012000011002'] + $this->pdfData();

        $cases = [
            'exports.spt' => [['NIP. 197001012000011002', 'Tembusan Yth:'], 'Demikian'],
            'exports.spt_dprd' => [['NIP. 197001012000011002', 'Tembusan Yth:'], 'Demikian'],
        ];

        foreach ($cases as $view => [$expected, $outside]) {
            $html = view($view, ['sppd' => $sppd, 'pdfData' => $pdfData, 'participants' => collect()])->render();
            $this->assertMatchesRegularExpression('/\.closing-block\s*\{[^}]*page-break-inside:\s*avoid/', $html, $view);

            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $blocks = (new \DOMXPath($dom))->query('//div[@class="closing-block"]');
            $this->assertSame(1, $blocks->length, $view);

            $closing = $this->text($blocks->item(0)->textContent);
            foreach ($expected as $part) {
                $this->assertStringContainsString($part, $closing, "{$view}: \"{$part}\" harus di dalam paket penutup");
            }
            $this->assertStringNotContainsString($outside, $closing, "{$view}: \"{$outside}\" di luar paket penutup");
        }
    }
}
