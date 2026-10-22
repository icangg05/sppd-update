<?php

namespace Tests\Feature;

use App\Livewire\AppSettingIndex;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\ImageCompressor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImageCompressorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    /** Gambar berderau (noise) — tidak mudah dikompres, mirip foto asli. */
    private function noisyImage(int $width, int $height, string $format, bool $transparent = false, int $block = 4): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        mt_srand(42);
        for ($y = 0; $y < $height; $y += $block) {
            for ($x = 0; $x < $width; $x += $block) {
                $alpha = $transparent && $x < $width / 2 ? 127 : 0;
                $color = imagecolorallocatealpha($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), $alpha);
                imagefilledrectangle($image, $x, $y, $x + $block - 1, $y + $block - 1, $color);
            }
        }

        ob_start();
        match ($format) {
            'jpg' => imagejpeg($image, null, 95),
            'png' => imagepng($image),
        };

        return ob_get_clean();
    }

    /** Sisipkan segmen EXIF berisi tag Orientation tepat setelah penanda SOI JPEG. */
    private function withOrientation(string $jpeg, int $orientation): string
    {
        $tiff = 'MM' . pack('n', 42) . pack('N', 8)
            . pack('n', 1) . pack('nnN', 0x0112, 3, 1) . pack('n', $orientation) . "\0\0"
            . pack('N', 0);
        $app1 = "Exif\0\0" . $tiff;

        return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2);
    }

    private function upload(string $name, string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    public function test_foto_besar_dikecilkan_dan_dikompres(): void
    {
        $original = $this->noisyImage(3000, 2000, 'jpg');
        $path = app(ImageCompressor::class)->store($this->upload('foto.jpg', $original), 'dokumen-2026/nota-perjalanan');

        $this->assertStringStartsWith('dokumen-2026/nota-perjalanan/', $path);
        $this->assertStringEndsWith('.jpg', $path);

        $stored = Storage::disk('public')->get($path);
        [$width, $height] = getimagesizefromstring($stored);
        $this->assertSame([1920, 1280], [$width, $height]);
        $this->assertLessThan(strlen($original), strlen($stored));
    }

    public function test_png_tanpa_transparansi_diubah_ke_jpeg(): void
    {
        $path = app(ImageCompressor::class)->store($this->upload('layar.png', $this->noisyImage(800, 600, 'png', block: 1)), 'dokumen-pendukung');

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring(Storage::disk('public')->get($path))[2]);
    }

    public function test_png_transparan_dan_kop_surat_tetap_png(): void
    {
        $compressor = app(ImageCompressor::class);

        $transparent = $compressor->store($this->upload('logo.png', $this->noisyImage(800, 600, 'png', transparent: true, block: 1)), 'dokumen-pendukung');
        $this->assertStringEndsWith('.png', $transparent);

        $letterhead = $compressor->store($this->upload('kop.png', $this->noisyImage(2600, 300, 'png', block: 1)), 'kop-surat',
            name: '7_primary', keepFormat: true, maxDimension: ImageCompressor::LETTERHEAD_DIMENSION);
        $this->assertSame('kop-surat/7_primary.png', $letterhead);
        $this->assertSame(ImageCompressor::LETTERHEAD_DIMENSION, getimagesizefromstring(Storage::disk('public')->get($letterhead))[0]);
    }

    public function test_orientasi_exif_foto_hp_diterapkan(): void
    {
        // Sensor menyimpan 400x300, tag Orientation 6 = harus diputar 90° searah jarum jam.
        $jpeg = $this->withOrientation($this->noisyImage(400, 300, 'jpg'), 6);
        $path = app(ImageCompressor::class)->store($this->upload('hp.jpg', $jpeg), 'dokumentasi-perjalanan');

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([300, 400], [$width, $height]);
    }

    public function test_berkas_bukan_gambar_disimpan_apa_adanya(): void
    {
        $pdf = "%PDF-1.4\n" . str_repeat('isi laporan ', 200) . "\n%%EOF";
        $path = app(ImageCompressor::class)->store($this->upload('laporan.pdf', $pdf), 'laporan-perjalanan');

        $this->assertStringEndsWith('.pdf', $path);
        $this->assertSame($pdf, Storage::disk('public')->get($path));
    }

    public function test_gambar_kecil_yang_sudah_optimal_tidak_dibesarkan(): void
    {
        $tiny = UploadedFile::fake()->image('ikon.png', 20, 20);
        $original = $tiny->get();

        $path = app(ImageCompressor::class)->store($tiny, 'dokumen-pendukung');

        $this->assertStringEndsWith('.png', $path);
        $this->assertSame($original, Storage::disk('public')->get($path));
    }

    public function test_nama_asli_dipakai_dan_diberi_nomor_urut_bila_sudah_ada(): void
    {
        $compressor = app(ImageCompressor::class);
        $pdf = "%PDF-1.4\nlaporan\n%%EOF";

        $paths = collect(range(1, 3))->map(fn () => $compressor->storeWithOriginalName(
            $this->upload('Laporan Perjalanan Dinas.pdf', $pdf), 'dokumen-2026/laporan-perjalanan'));

        $this->assertSame([
            'dokumen-2026/laporan-perjalanan/Laporan Perjalanan Dinas.pdf',
            'dokumen-2026/laporan-perjalanan/Laporan Perjalanan Dinas_1.pdf',
            'dokumen-2026/laporan-perjalanan/Laporan Perjalanan Dinas_2.pdf',
        ], $paths->all());

        // Foto PNG yang dikompres ke JPEG: nama asli tetap, ekstensi mengikuti hasil kompresi.
        $photo = $compressor->storeWithOriginalName($this->upload('IMG_0012.PNG', $this->noisyImage(800, 600, 'png', block: 1)), 'dokumen-2026/dokumentasi-perjalanan');
        $this->assertSame('dokumen-2026/dokumentasi-perjalanan/IMG_0012.jpg', $photo);

        // Ekstensi asli yang cocok dengan isinya dipertahankan.
        $jpeg = $compressor->storeWithOriginalName(UploadedFile::fake()->image('Undangan.JPEG', 20, 20), 'dokumen-2026/dokumen-pendukung');
        $this->assertSame('dokumen-2026/dokumen-pendukung/Undangan.jpeg', $jpeg);
    }

    public function test_nama_asli_dibersihkan_dari_karakter_berbahaya(): void
    {
        $this->assertSame('Undangan Rapat (Final)', ImageCompressor::safeBaseName('Undangan   Rapat (Final).pdf'));
        $this->assertSame('SPT No_12_2026', ImageCompressor::safeBaseName('SPT No#12+2026.pdf'));
        $this->assertSame('passwd', ImageCompressor::safeBaseName('../../etc/passwd'));
        $this->assertSame('Surat_Ka_Dinas', ImageCompressor::safeBaseName('Surat?Ka%Dinas.docx'));
        $this->assertSame('dokumen', ImageCompressor::safeBaseName('.pdf'));
        $this->assertSame('Laporan Kendari_Makassar Ü', ImageCompressor::safeBaseName('Laporan Kendari–Makassar Ü.pdf'));
    }

    public function test_batas_upload_diambil_dari_pengaturan(): void
    {
        $settings = app(AppSettings::class);
        $this->assertSame(10 * 1024, $settings->uploadMaxKb(true));
        $this->assertSame(50 * 1024, $settings->uploadMaxKb(false));
        $this->assertSame('10 MB', $settings->uploadMaxLabel(true));

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'super_admin']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(AppSettingIndex::class)
            ->set('values.upload_max_image_mb', '101')
            ->call('save')
            ->assertHasErrors('values.upload_max_image_mb')
            ->set('values.upload_max_image_mb', '5')
            ->call('save')
            ->assertHasNoErrors();

        // Tersimpan sebagai bilangan di data.json.
        $stored = collect(json_decode(Storage::disk('local')->get(AppSettings::PATH), true))->keyBy('key');
        $this->assertSame(5, $stored['upload_max_image_mb']['value']);
        $this->assertSame(5 * 1024, (new AppSettings)->uploadMaxKb(true));
    }
}
