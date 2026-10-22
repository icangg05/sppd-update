<?php

namespace Tests\Feature;

use App\Support\FileSize;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/** Batas ukuran berkas di pesan validasi tampil dalam MB/KB, bukan kilobita mentah. */
class FileSizeValidationMessageTest extends TestCase
{
    private function message(mixed $value, string $rule): string
    {
        return Validator::make(['berkas' => $value], ['berkas' => $rule], [], ['berkas' => 'foto dokumentasi'])
            ->errors()->first('berkas');
    }

    public function test_ukuran_berkas_dikonversi_ke_mb(): void
    {
        app()->setLocale('id');
        $photo = UploadedFile::fake()->create('11mb.jpg', 11 * 1024);

        $this->assertSame('Kolom foto dokumentasi tidak boleh berukuran lebih dari 10 MB.', $this->message($photo, 'max:10240'));
        $this->assertSame('Kolom foto dokumentasi harus berukuran minimal 500 KB.', $this->message(UploadedFile::fake()->create('a.jpg', 10), 'min:500'));
        $this->assertSame('Kolom foto dokumentasi harus berukuran antara 1 MB sampai 1,5 MB.', $this->message($photo, 'between:1024,1536'));

        // Aturan yang sama untuk angka/teks tidak ikut diubah.
        $this->assertSame('Kolom foto dokumentasi tidak boleh bernilai lebih dari 10240.', $this->message(20000, 'numeric|max:10240'));
        $this->assertSame('Kolom foto dokumentasi tidak boleh berisi lebih dari 3 karakter.', $this->message('abcd', 'string|max:3'));
    }

    public function test_format_ukuran(): void
    {
        $this->assertSame('10 MB', FileSize::fromKilobytes(10240));
        $this->assertSame('50 MB', FileSize::fromKilobytes('51200'));
        $this->assertSame('1,5 MB', FileSize::fromKilobytes(1536));
        $this->assertSame('500 KB', FileSize::fromKilobytes(500));
        $this->assertSame('1.024 MB', FileSize::fromKilobytes(1024 * 1024));
    }
}
