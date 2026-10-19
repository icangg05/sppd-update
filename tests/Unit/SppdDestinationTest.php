<?php

namespace Tests\Unit;

use App\Helpers\SmartTitle;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdDestination;
use PHPUnit\Framework\TestCase;

class SppdDestinationTest extends TestCase
{
    public function test_tujuan_luar_negeri_memakai_kota_dan_negara(): void
    {
        $dest = new SppdDestination(['country' => 'Jepang', 'city' => 'tokyo', 'address' => 'KBRI tokyo']);

        $this->assertTrue($dest->isForeign());
        $this->assertSame('Tokyo, Jepang', $dest->place_name);
        $this->assertSame('Tokyo, Jepang', $dest->location_label);
        $this->assertSame('KBRI Tokyo, Tokyo, Jepang', $dest->full_address);
    }

    public function test_tujuan_dalam_negeri_memakai_provinsi_dan_kabupaten(): void
    {
        $dest = new SppdDestination(['address' => 'kantor gubernur']);
        $dest->setRelation('province', new Province(['name' => 'Sulawesi Selatan']));
        $dest->setRelation('regency', new Regency(['name' => 'Kota Makassar']));

        $this->assertFalse($dest->isForeign());
        $this->assertSame('Kota Makassar', $dest->place_name);
        $this->assertSame('Sulawesi Selatan, Kota Makassar', $dest->location_label);
        $this->assertSame('Kantor Gubernur', $dest->full_address);
    }

    public function test_alamat_dan_kota_disimpan_dengan_huruf_awal_kapital(): void
    {
        $dest = new SppdDestination(['city' => 'kuala lumpur', 'address' => 'kantor dinas kominfo prov. sulawesi tenggara']);

        $this->assertSame('Kuala Lumpur', $dest->getAttributes()['city']);
        $this->assertSame('Kantor Dinas Kominfo Prov. Sulawesi Tenggara', $dest->getAttributes()['address']);
    }

    public function test_kapitalisasi_tidak_mengubah_akronim(): void
    {
        $this->assertSame('KBRI di Jl. Ahmad Yani (Kendari/Kolaka)', SmartTitle::capitalizeWords('  KBRI di jl. ahmad yani (kendari/kolaka) '));
        $this->assertSame('Kantor Camat di Poasia dan Kantor Lurah ke-2', SmartTitle::capitalizeWords('kantor camat di poasia dan kantor lurah ke-2'));
        $this->assertSame('Di Kendari', SmartTitle::capitalizeWords('di kendari'));
        $this->assertNull(SmartTitle::capitalizeWords(null));
    }
}
