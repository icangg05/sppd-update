<?php

namespace Tests\Unit;

use App\Support\NarrativeText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Uraian telaah berupa poin tampil sebagai daftar, bukan satu kalimat panjang. */
class NarrativeTextTest extends TestCase
{
    /** @return list<string> isi tiap poin; null bila hasilnya bukan satu daftar */
    private function items(string $text): ?array
    {
        $blocks = NarrativeText::blocks($text);

        return count($blocks) === 1 && $blocks[0]['type'] === 'list'
            ? array_column($blocks[0]['items'], 'text')
            : null;
    }

    public function test_poin_satu_baris_dipisah_titik_koma_seperti_input_inspektorat(): void
    {
        // Isi Analisis SPPD Inspektorat (id 192) dari data server.
        $text = '-Melakukan pengumpulan data, dokumen dan informasi; -telaah data dan dokumen; -pengujian data dan dokumen; -cek fisik pekerjaan; -wawancara/kalrifikasi/konfirmasi';

        $this->assertSame([
            'Melakukan pengumpulan data, dokumen dan informasi;',
            'Telaah data dan dokumen;',
            'Pengujian data dan dokumen;',
            'Cek fisik pekerjaan;',
            'Wawancara/kalrifikasi/konfirmasi',
        ], $this->items($text));
    }

    public function test_huruf_pertama_teks_baris_dan_poin_dikapitalkan(): void
    {
        $this->assertSame('Probity audit dilaksanakan', NarrativeText::capitalize('probity audit dilaksanakan'));
        $this->assertSame("Dasar:\n- Surat undangan\n2. Program kerja\nb) Rincian", NarrativeText::capitalize("dasar:\n- surat undangan\n2. program kerja\nb) rincian"));
        $this->assertSame('-Telaah data; -Cek fisik', NarrativeText::capitalize('-telaah data; -cek fisik'));
        // Titik koma biasa di tengah kalimat tidak memulai poin.
        $this->assertSame('Rapat dibuka; lalu dilanjutkan', NarrativeText::capitalize('rapat dibuka; lalu dilanjutkan'));
        $this->assertSame('Évaluasi', NarrativeText::capitalize('évaluasi'));
        $this->assertNull(NarrativeText::capitalize(null));

        // Tampilan: tiap poin & paragraf diawali huruf kapital.
        $this->assertSame(['Rapat koordinasi.', 'Survei lokasi.'], array_column(NarrativeText::blocks('1. rapat koordinasi. 2. survei lokasi.')[0]['items'], 'text'));
        $this->assertSame([['type' => 'p', 'text' => 'Masih harus ditindaklanjuti']], NarrativeText::blocks('masih harus ditindaklanjuti'));
    }

    public function test_tanda_hubung_di_dalam_kata_tidak_memecah_poin(): void
    {
        $this->assertSame(
            ['Surat KPK RI Nomor B/4679 tentang MCSP;', 'Tindak lanjut TP-TGR BPK Wua-Wua'],
            $this->items('-Surat KPK RI Nomor B/4679 tentang MCSP; -Tindak lanjut TP-TGR BPK Wua-Wua')
        );
    }

    public function test_poin_per_baris_dan_kalimat_pengantar(): void
    {
        $blocks = NarrativeText::blocks("Dasar pelaksanaan:\r\n- Surat undangan\r\n\r\n- Program kerja 2026");

        $this->assertSame(['type' => 'p', 'text' => 'Dasar pelaksanaan:'], $blocks[0]);
        $this->assertSame([['marker' => '•', 'text' => 'Surat undangan'], ['marker' => '•', 'text' => 'Program kerja 2026']], $blocks[1]['items']);
    }

    public function test_nomor_per_baris_mempertahankan_penomorannya(): void
    {
        $blocks = NarrativeText::blocks("1. Rapat koordinasi\n2) Survei lokasi\na. Rincian");

        $this->assertSame(['1.', '2)', 'a.'], array_column($blocks[0]['items'], 'marker'));
    }

    public static function nomorSatuBaris(): array
    {
        return [
            'titik koma' => ['1. Rapat koordinasi; 2. Survei lokasi; 3. Laporan', ['1. Rapat koordinasi;', '2. Survei lokasi;', '3. Laporan']],
            'titik' => ['1. Rapat koordinasi. 2. Survei lokasi.', ['1. Rapat koordinasi.', '2. Survei lokasi.']],
            'huruf' => ['a. Rapat; b. Survei', ['a. Rapat;', 'b. Survei']],
        ];
    }

    #[DataProvider('nomorSatuBaris')]
    public function test_nomor_dalam_satu_baris(string $text, array $expected): void
    {
        $blocks = NarrativeText::blocks($text);
        $this->assertSame($expected, array_map(fn (array $i) => $i['marker'] . ' ' . $i['text'], $blocks[0]['items']));
    }

    public function test_angka_di_tengah_kalimat_bukan_poin_baru(): void
    {
        // "ayat 2." tanpa tanda baca sebelumnya, dan "5." yang tidak berurutan, tetap di poin yang sama.
        $this->assertSame(
            ['Sesuai Pasal 5 ayat 2. Kemudian dilaporkan;', 'Evaluasi tahap 5. Selesai'],
            $this->items('1. Sesuai Pasal 5 ayat 2. Kemudian dilaporkan; 2. Evaluasi tahap 5. Selesai')
        );
    }

    public function test_kalimat_biasa_tetap_paragraf(): void
    {
        foreach ([
            'Masih harus ditindaklanjuti untuk PTL TP-TGR BPK Perwakilan Sulawesi Tenggara',
            '-Hanya satu poin saja',
            '1. Diawali angka tetapi hanya satu kalimat',
            'H. Abdullah menghadiri rapat',
        ] as $text) {
            $this->assertSame([['type' => 'p', 'text' => $text]], NarrativeText::blocks($text), $text);
        }

        $this->assertSame([], NarrativeText::blocks("  \n "));
    }
}
