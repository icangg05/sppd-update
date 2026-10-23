<?php

namespace Tests\Feature;

use App\Models\SppdRequest;
use Tests\TestCase;

/**
 * Galeri gambar global: komponen ter-render (dipasang di layout, jadi error di sini
 * merusak semua halaman) dan tautannya tidak memuat gambar sebelum diklik.
 */
class GalleryComponentTest extends TestCase
{
    public function test_galeri_dan_tautannya_ter_render(): void
    {
        $this->blade('<x-ui.gallery />')
            ->assertSee('Galeri gambar')
            ->assertSee('function galleryViewer()', false);

        $this->blade('<x-ui.gallery-link src="/storage/nota.jpg" group="bukti" caption="Nota hotel" class="text-xs">Lihat</x-ui.gallery-link>')
            ->assertSee('href="/storage/nota.jpg"', false)
            ->assertSee('data-gallery="bukti"', false)
            ->assertSee('data-caption="Nota hotel"', false)
            ->assertDontSee('<img', false)
            ->assertDontSee('target="_blank"', false);
    }

    public function test_hanya_lampiran_gambar_yang_dibuka_di_galeri(): void
    {
        $this->assertTrue((new SppdRequest(['attachment' => 'dokumen/Undangan.JPG']))->attachmentIsImage());
        $this->assertTrue((new SppdRequest(['attachment' => 'dokumen/foto.webp']))->attachmentIsImage());
        $this->assertFalse((new SppdRequest(['attachment' => 'dokumen/Undangan.pdf']))->attachmentIsImage());
        $this->assertFalse((new SppdRequest(['attachment' => null]))->attachmentIsImage());
    }
}
