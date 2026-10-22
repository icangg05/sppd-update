<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

/**
 * Menyimpan berkas unggahan; foto/gambar (JPEG, PNG, WebP) dikecilkan dan
 * dikompres lebih dulu agar hemat ruang penyimpanan dan cepat dibuka.
 *
 * - Sisi terpanjang dibatasi (default 1920 px) dan orientasi EXIF foto HP diterapkan.
 *   Gambar ditulis ulang sehingga metadata (termasuk lokasi GPS) ikut terbuang.
 * - JPEG & WebP tetap formatnya. PNG tanpa transparansi diubah ke JPEG (jauh lebih
 *   kecil untuk foto/tangkapan layar); PNG transparan tetap PNG.
 * - Berkas lain (PDF, DOCX, GIF animasi), gambar yang gagal dibaca/terlalu besar
 *   untuk memori, atau hasil kompresi yang justru lebih besar: disimpan apa adanya.
 */
class ImageCompressor
{
  public const MAX_DIMENSION = 1920;
  public const QUALITY = 80;

  /** Sisi terpanjang kop surat: lebar A4 pada 300 dpi — tetap tajam saat dicetak. */
  public const LETTERHEAD_DIMENSION = 2480;

  /** Batas piksel (±50 MP, foto HP resolusi penuh). GD butuh ±5 byte per piksel. */
  private const MAX_PIXELS = 50_000_000;

  private const COMPRESSIBLE = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

  public static function isImage(mixed $file): bool
  {
    return $file instanceof UploadedFile && str_starts_with((string) $file->getMimeType(), 'image/');
  }

  /**
   * Pengganti $file->store()/storeAs(): mengembalikan path relatif di disk.
   *
   * @param  string|null  $name  nama berkas tanpa ekstensi (null = nama acak seperti store()).
   * @param  bool  $keepFormat  true = PNG tidak diubah ke JPEG (mis. kop surat yang dicetak ke PDF).
   */
  public function store(
    UploadedFile $file,
    string $directory,
    string $disk = 'public',
    ?string $name = null,
    bool $keepFormat = false,
    int $maxDimension = self::MAX_DIMENSION,
  ): string {
    $name ??= Str::random(40);

    return $this->put($file, $directory, $disk, fn (string $suffix) => $name . $suffix, $keepFormat, $maxDimension);
  }

  /**
   * Simpan dengan nama berkas aslinya, mis. "Undangan Rapat.pdf". Bila nama itu sudah
   * dipakai di folder yang sama, diberi nomor urut agar tidak saling timpa:
   * "Undangan Rapat_1.pdf", "Undangan Rapat_2.pdf", dst.
   */
  public function storeWithOriginalName(UploadedFile $file, string $directory, string $disk = 'public'): string
  {
    $base = self::safeBaseName($file->getClientOriginalName());
    $storage = Storage::disk($disk);

    return $this->put($file, $directory, $disk, function (string $suffix) use ($storage, $directory, $base) {
      $name = $base . $suffix;
      for ($i = 1; $storage->exists(trim($directory, '/') . "/{$name}"); $i++) {
        $name = "{$base}_{$i}{$suffix}";
      }

      return $name;
    });
  }

  /**
   * Nama berkas tanpa ekstensi yang aman untuk URL & sistem berkas: huruf, angka, spasi,
   * titik, tanda hubung, garis bawah, dan kurung dipertahankan; karakter lain (/, ?, #, %, +, ...)
   * diganti "_".
   */
  public static function safeBaseName(string $clientName): string
  {
    $base = preg_replace('/[^\p{L}\p{N} ._()\-]+/u', '_', pathinfo($clientName, PATHINFO_FILENAME)) ?? '';
    $base = mb_substr(trim(preg_replace('/\s+/u', ' ', $base), ' ._'), 0, 120);

    return $base !== '' ? $base : 'dokumen';
  }

  /**
   * @param  \Closure(string): string  $filename  menerima akhiran ".ext" (atau "") dan mengembalikan nama berkas.
   */
  private function put(
    UploadedFile $file,
    string $directory,
    string $disk,
    \Closure $filename,
    bool $keepFormat = false,
    int $maxDimension = self::MAX_DIMENSION,
  ): string {
    $directory = trim($directory, '/');
    $compressed = $this->compress($file, $keepFormat, $maxDimension);

    if ($compressed === null) {
      $ext = $this->extension($file, (string) $file->getMimeType());

      return $file->storeAs($directory, $filename($ext !== '' ? ".{$ext}" : ''), $disk);
    }

    [$bytes, $format] = $compressed;
    $path = $directory . '/' . $filename('.' . $this->extension($file, array_search($format, self::COMPRESSIBLE, true)));
    Storage::disk($disk)->put($path, $bytes);

    return $path;
  }

  /** Ekstensi asli dipakai bila cocok dengan isi berkas (mis. .jpeg); selain itu ekstensi baku MIME-nya. */
  private function extension(UploadedFile $file, string $mime): string
  {
    $client = strtolower($file->getClientOriginalExtension());
    $valid = MimeTypes::getDefault()->getExtensions($mime);

    return in_array($client, $valid, true) ? $client : ($valid[0] ?? '');
  }

  /**
   * @return array{0: string, 1: string}|null  [isi berkas, ekstensi]; null = simpan berkas asli.
   */
  public function compress(UploadedFile $file, bool $keepFormat = false, int $maxDimension = self::MAX_DIMENSION): ?array
  {
    $format = self::COMPRESSIBLE[$file->getMimeType()] ?? null;
    if ($format === null) {
      return null;
    }

    try {
      $contents = $file->get();
      $size = @getimagesizefromstring($contents);
      if (! $size || $size[0] * $size[1] > self::MAX_PIXELS || ! $this->ensureMemory($size[0] * $size[1])) {
        return null;
      }

      $image = @imagecreatefromstring($contents);
      if (! $image) {
        return null;
      }

      if ($format === 'jpg') {
        $image = $this->applyOrientation($image, $this->orientation($contents));
      }

      $image = $this->resize($image, $maxDimension);

      if ($format === 'png' && ! $keepFormat && ! $this->hasTransparency($image)) {
        $image = $this->flatten($image);
        $format = 'jpg';
      }

      $bytes = $this->encode($image, $format);
    } catch (\Throwable $e) {
      logger()->warning('Kompresi gambar gagal, berkas asli disimpan: ' . $e->getMessage());

      return null;
    }

    // Gambar yang sudah optimal bisa membesar saat ditulis ulang — simpan yang asli saja.
    return $bytes !== '' && strlen($bytes) < strlen($contents) ? [$bytes, $format] : null;
  }

  /**
   * Foto besar (mis. 40 MP ≈ 200 MB saat dibuka GD) bisa melewati memory_limit dan
   * berakhir fatal error yang tidak bisa ditangkap — naikkan batasnya sementara
   * untuk request ini. Bila tidak bisa dinaikkan, berkas asli yang disimpan.
   */
  private function ensureMemory(int $pixels): bool
  {
    $limit = trim((string) ini_get('memory_limit'));
    if ($limit === '' || $limit === '-1') {
      return true;
    }

    $bytes = (int) $limit * match (strtoupper(substr($limit, -1))) {
      'G' => 1024 ** 3,
      'M' => 1024 ** 2,
      'K' => 1024,
      default => 1,
    };
    $needed = memory_get_usage(true) + $pixels * 5 + 64 * 1024 ** 2;

    return $needed <= $bytes || ini_set('memory_limit', (string) $needed) !== false;
  }

  private function orientation(string $contents): int
  {
    if (! function_exists('exif_read_data')) {
      return 1;
    }

    $stream = fopen('php://memory', 'r+b');
    fwrite($stream, $contents);
    rewind($stream);
    $exif = @exif_read_data($stream);
    fclose($stream);

    return (int) ($exif['Orientation'] ?? 1);
  }

  /** Tegakkan foto HP sesuai tag Orientation (imagerotate berputar berlawanan arah jarum jam). */
  private function applyOrientation(GdImage $image, int $orientation): GdImage
  {
    $angle = match ($orientation) {
      3, 4 => 180,
      5, 6 => 270,
      7, 8 => 90,
      default => 0,
    };

    if ($angle) {
      $image = imagerotate($image, $angle, 0);
    }

    if (in_array($orientation, [2, 4, 5, 7], true)) {
      imageflip($image, IMG_FLIP_HORIZONTAL);
    }

    return $image;
  }

  private function resize(GdImage $image, int $maxDimension): GdImage
  {
    $width = imagesx($image);
    $height = imagesy($image);
    $scale = $maxDimension / max($width, $height);

    if ($scale >= 1) {
      return $image;
    }

    $newWidth = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));

    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    return $resized;
  }

  /**
   * Cek piksel transparan pada sampel ±40 ribu titik (cukup cepat untuk gambar besar).
   * Transparansi kecil yang terlewat tetap aman karena flatten() memberi latar putih.
   */
  private function hasTransparency(GdImage $image): bool
  {
    // PNG berpalet bisa menyimpan alpha per warna — samakan ke truecolor dulu.
    imagepalettetotruecolor($image);

    $width = imagesx($image);
    $height = imagesy($image);
    $step = max(1, (int) sqrt($width * $height / 40_000));

    for ($y = 0; $y < $height; $y += $step) {
      for ($x = 0; $x < $width; $x += $step) {
        if ((imagecolorat($image, $x, $y) >> 24) & 0x7F) {
          return true;
        }
      }
    }

    return false;
  }

  /** Tempel di atas latar putih agar sisa alpha tidak menjadi hitam di JPEG. */
  private function flatten(GdImage $image): GdImage
  {
    $width = imagesx($image);
    $height = imagesy($image);

    $canvas = imagecreatetruecolor($width, $height);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

    return $canvas;
  }

  private function encode(GdImage $image, string $format): string
  {
    if ($format === 'png') {
      imagealphablending($image, false);
      imagesavealpha($image, true);
    }

    ob_start();
    match ($format) {
      'jpg'  => imagejpeg($image, null, self::QUALITY),
      'webp' => imagewebp($image, null, self::QUALITY),
      'png'  => imagepng($image, null, 9),
    };

    return (string) ob_get_clean();
  }
}
