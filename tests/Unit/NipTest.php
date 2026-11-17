<?php

namespace Tests\Unit;

use App\Support\Nip;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Senioritas dari NIP (pembeda urutan SPT untuk peserta yang setara jabatan & golongan). */
class NipTest extends TestCase
{
    /** @return array<string, array{?string, ?string, ?string}> [NIP, TMT, tanggal lahir] */
    public static function nips(): array
    {
        return [
            'PNS' => ['196801011990031005', '199003', '19680101'],
            'PNS bertanda baca' => ['19680101 199003 1 005', '199003', '19680101'],
            'PPPK: tahun saja, bulan dianggap akhir tahun' => ['198809052024211011', '202413', '19880905'],
            'tanggal lahir rusak' => ['196810002002121005', '200212', null],
            'bulan TMT rusak' => ['197201031993131009', null, '19720103'],
            'bukan 18 digit' => ['19680101', null, null],
            'kosong' => [null, null, null],
        ];
    }

    #[DataProvider('nips')]
    public function test_membaca_tmt_dan_tanggal_lahir(?string $nip, ?string $tmt, ?string $birth): void
    {
        $this->assertSame($tmt, Nip::tmt($nip));
        $this->assertSame($birth, Nip::birthDate($nip));
    }

    public function test_urutan_senioritas_masa_kerja_lalu_usia_lalu_yang_tidak_diketahui(): void
    {
        $nips = [
            'tanpa NIP' => null,
            'PPPK 2015' => '198501012015211001',
            'TMT 2005-01, lahir 1980' => '198001012005011001',
            'TMT rusak, lahir 1970' => '197001011999131001',
            'TMT 2015-03' => '199001012015031001',
            'TMT 2005-01, lahir 1975' => '197501012005011002',
        ];

        uasort($nips, fn ($a, $b) => Nip::seniorityKey($a) <=> Nip::seniorityKey($b));

        $this->assertSame([
            'TMT 2005-01, lahir 1975',
            'TMT 2005-01, lahir 1980',
            'TMT 2015-03',
            'PPPK 2015',
            'TMT rusak, lahir 1970',
            'tanpa NIP',
        ], array_keys($nips));
    }
}
