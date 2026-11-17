<?php

namespace Tests\Unit;

use App\Helpers\SmartTitle;
use PHPUnit\Framework\TestCase;

/** Kapitalisasi nama jabatan (dan teks lain) lewat SmartTitle::convert. */
class SmartTitleTest extends TestCase
{
    public function test_singkatan_yang_diketik_huruf_besar_dibiarkan(): void
    {
        $this->assertSame('Kader PDIP', SmartTitle::convert('Kader PDIP'));
        $this->assertSame('Kader HMI', SmartTitle::convert('kader HMI'));
        $this->assertSame('Direktur RSUD Antero Hamra', SmartTitle::convert('Direktur RSUD Antero Hamra'));
        $this->assertSame('Staf Seksi II', SmartTitle::convert('staf seksi II'));
        $this->assertSame('Kepala Tata Usaha (UPTD)', SmartTitle::convert('kepala tata usaha (UPTD)'));
        $this->assertSame('Kader PDI-P', SmartTitle::convert('kader PDI-P'));
    }

    public function test_singkatan_umum_dikenali_walau_diketik_huruf_kecil(): void
    {
        $this->assertSame('Direktur RSUD', SmartTitle::convert('direktur rsud'));
        $this->assertSame('Kepala Tata Usaha BLUD UPTD Puskesmas', SmartTitle::convert('kepala tata usaha blud uptd puskesmas'));
        $this->assertSame('Anggota DPRD dan Dewan Perwakilan', SmartTitle::convert('anggota dprd dan dewan perwakilan'));
    }

    public function test_teks_huruf_besar_semua_tetap_dirapikan(): void
    {
        // Caps Lock: hanya singkatan di daftar yang tetap huruf besar.
        $this->assertSame('Kepala Dinas Kesehatan', SmartTitle::convert('KEPALA DINAS KESEHATAN'));
        $this->assertSame('Direktur RSUD', SmartTitle::convert('DIREKTUR RSUD'));
    }

    public function test_kata_biasa_dan_kata_hubung(): void
    {
        $this->assertSame('Kepala Bidang Pencegahan dan Pengendalian', SmartTitle::convert('kepala bidang pencegahan dan pengendalian'));
        $this->assertSame('Kepala Seksi', SmartTitle::convert('kEPALA sEKSI'));
        $this->assertSame('Dalam Proses Pengajuan', SmartTitle::convert('Dalam Proses Pengajuan'));
    }
}
