<?php

namespace App\Services\Tte;

use App\Models\SppdDigitalSignature;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

/**
 * Provider TTE simulasi ("TTE Testing") untuk pengujian tanpa akses BSrE.
 * Aktif bila config tte.simulation.enabled (env TTE_SIMULATION) bernilai true.
 *
 * Meniru LocalProxySignService: permintaan tanda tangan mengembalikan `id_dokumen`,
 * lalu PDF hasilnya diunduh dengan id itu. NIK tidak valid dan passphrase salah ditolak
 * dengan bentuk error yang sama seperti proxy BSrE, jadi alur gagal pun ikut teruji.
 *
 * Bukan tanda tangan kriptografis: PDF hanya ditambah baris komentar penanda di akhir
 * berkas (diabaikan pembaca PDF). Penanda itu dipakai verify() untuk meniru verifikasi
 * BSrE: dokumen utuh VALID, dokumen yang diubah TIDAK VALID.
 */
class SimulatedSignService implements SignatureProviderInterface
{
  public const PROVIDER = 'simulasi';

  private const MARKER = '%TTE-TESTING ';

  // Hasil tanda tangan yang menunggu diunduh; disk privat, bukan disk publik dokumen.
  private const PENDING_DIR = 'tte-simulasi';

  public static function enabled(): bool
  {
    return (bool) config('tte.simulation.enabled', false);
  }

  /** Nama provider yang dicatat pada tanda tangan baru. */
  public static function activeProviderName(): string
  {
    return static::enabled() ? static::PROVIDER : (string) config('tte.default_provider');
  }

  /** Passphrase yang diterima simulasi (sama untuk semua penanda tangan). */
  public static function passphrase(): string
  {
    return (string) config('tte.simulation.passphrase', 'tte-testing');
  }

  /** PDF ini ditandatangani oleh simulasi (bukan BSrE). */
  public static function isSimulatedPdf(string $pdf): bool
  {
    return str_contains($pdf, self::MARKER);
  }

  public function requestSign(
    string $filePath,
    string $nik,
    #[\SensitiveParameter] string $passphrase,
    int $page,
    int $xAxis,
    int $yAxis,
    int $width,
    int $height,
    string $linkQr,
    string $tampilan = 'visible'
  ): array|string {
    // Waktu tunggu seperti permintaan ke BSrE.
    Sleep::for(max(0, (int) config('tte.simulation.delay_ms', 1500)))->milliseconds();

    if (! preg_match('/^\d{16}$/', $nik)) {
      return $this->providerError(['error' => 'NIK tidak terdaftar pada layanan TTE']);
    }

    if (! hash_equals(static::passphrase(), $passphrase)) {
      return $this->providerError(['error' => 'Passphrase anda salah', 'status_code' => 2031]);
    }

    $pdf = is_file($filePath) ? file_get_contents($filePath) : false;
    if ($pdf === false || ! str_starts_with($pdf, '%PDF')) {
      return $this->providerError(['error' => 'Dokumen bukan berkas PDF yang valid']);
    }

    $id = bin2hex(random_bytes(16));
    $mark = base64_encode(json_encode([
      'id'        => $id,
      'page'      => $page,
      'signed_at' => now()->toIso8601String(),
    ]));

    Storage::disk('local')->put(
      self::PENDING_DIR . "/{$id}.pdf",
      rtrim($pdf, "\r\n") . "\n" . self::MARKER . $mark . "\n",
    );

    return ['id_dokumen' => $id];
  }

  public function downloadSignedPdf(string $signedDocumentId): string
  {
    $disk = Storage::disk('local');
    $path = self::PENDING_DIR . "/{$signedDocumentId}.pdf";

    if (! preg_match('/^[a-f0-9]{32}$/', $signedDocumentId) || ! $disk->exists($path)) {
      throw new \RuntimeException('Provider download failed: ' . json_encode(['error' => 'Dokumen tidak ditemukan']));
    }

    $pdf = $disk->get($path);
    $disk->delete($path);

    return $pdf;
  }

  /**
   * Tiruan BsreVerificationService::verify() untuk PDF hasil simulasi; bentuk hasilnya
   * sama, ditambah 'simulated' => true. Satu penanda = satu tanda tangan, jadi dokumen
   * SPPD (halaman 1 & 2) terhitung dua tanda tangan seperti di BSrE.
   */
  public function verify(string $pdf, string $filename = 'document.pdf'): array
  {
    $marks = $this->marks($pdf);

    $signature = SppdDigitalSignature::with('signer')
      ->where('provider_name', self::PROVIDER)
      ->whereIn('provider_id', array_column($marks, 'id'))
      ->first();

    $disk = Storage::disk(config('tte.storage.disk'));
    $stored = $signature?->signed_file_path && $disk->exists($signature->signed_file_path)
      ? $disk->get($signature->signed_file_path)
      : null;
    $intact = $stored !== null && hash_equals(hash('sha256', $stored), hash('sha256', $pdf));

    $notes = match (true) {
      $intact             => 'TTE Testing: tanda tangan simulasi, tidak diverifikasi oleh BSrE.',
      $signature !== null => 'Dokumen telah diubah setelah ditandatangani (TTE Testing).',
      default             => 'Tanda tangan simulasi tidak dikenali di aplikasi ini (TTE Testing).',
    };

    return [
      'ok'              => true,
      'available'       => true,
      'simulated'       => true,
      'summary'         => $intact ? 'VALID' : 'TIDAK VALID',
      'valid'           => $intact,
      'document_name'   => $filename,
      'signature_count' => count($marks),
      'notes'           => $notes,
      'details'         => array_map(fn (array $mark) => [
        'info_signer' => [
          'signer_name'         => $signature?->signer?->name ?? 'Tidak dikenal',
          'issuer_dn'           => 'CN=TTE Testing (Simulasi), O=Aplikasi SPPD, C=ID',
          'cert_user_certified' => $signature !== null,
        ],
        'signature_document' => [
          'document_integrity' => $intact,
          'signed_in'          => $this->formatSignedAt($mark['signed_at'] ?? null),
          'signed_using_tsa'   => false,
        ],
      ], $marks),
      'error' => null,
      'raw'   => null,
    ];
  }

  /**
   * Bentuk error sama dengan LocalProxySignService: body JSON dari proxy dibungkus
   * "Provider request failed:", sehingga SppdShow::parseTteError mengenalinya.
   */
  private function providerError(array $body): array
  {
    return ['error' => 'Provider request failed: ' . json_encode($body)];
  }

  /** @return list<array{id: string, page: ?int, signed_at: ?string}> */
  private function marks(string $pdf): array
  {
    preg_match_all('/' . preg_quote(self::MARKER, '/') . '([A-Za-z0-9+\/=]+)/', $pdf, $matches);

    return collect($matches[1])
      ->map(fn (string $encoded) => json_decode((string) base64_decode($encoded, true), true))
      ->filter(fn ($mark) => is_array($mark) && is_string($mark['id'] ?? null))
      ->values()
      ->all();
  }

  private function formatSignedAt(?string $iso): ?string
  {
    if (! $iso) {
      return null;
    }

    try {
      return Carbon::parse($iso)->timezone(config('app.timezone'))->translatedFormat('d F Y H:i:s') . ' WITA';
    } catch (\Throwable) {
      return null;
    }
  }
}
