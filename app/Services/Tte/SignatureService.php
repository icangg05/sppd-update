<?php

namespace App\Services\Tte;

use App\Enums\SignatureDocumentType;
use App\Enums\SignatureStatus;
use App\Models\SppdDigitalSignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignatureService
{
  protected PdfGeneratorService $pdfGenerator;
  protected SignatureProviderInterface $provider;

  public function __construct(PdfGeneratorService $pdfGenerator)
  {
    $this->pdfGenerator = $pdfGenerator;
    $this->provider     = $this->resolveProvider();
  }

  public function sign(SppdDigitalSignature $signature, string $passphrase): bool|array
  {
    $draftFile = $this->pdfGenerator->generateDraft($signature);
    $linkQr    = url('/verify/' . $signature->document_type . '/' . $signature->sppdRequest->id . '/' . md5($signature->sppdRequest->document_number . $signature->sppdRequest->id));

    // Buat tampilan TTE dari provider selalu invisible untuk semua jenis dokumen
    // agar tidak memunculkan QR Code duplikat kecil dari provider di bagian bawah.
    // Dokumen akan sepenuhnya menggunakan QR Code yang di-render secara rapi lewat HTML.
    $tampilan = 'invisible';

    $result = $this->provider->requestSign(
      $draftFile,
      $signature->signer->nik,
      $passphrase,
      $signature->sign_page,
      $signature->sign_x,
      $signature->sign_y,
      $signature->sign_width,
      $signature->sign_height,
      $linkQr,
      $tampilan
    );

    if (is_array($result) && isset($result['error'])) {
      $message = 'Provider request failed: ' . json_encode($result, JSON_UNESCAPED_UNICODE);
      $signature->update([
        'status'        => SignatureStatus::REJECTED,
        'error_message' => $message,
      ]);
      // Hapus draft agar tidak membebani server
      $this->deleteDraftFile($draftFile);

      return ['details' => $result];
    }

    $signedPdf = $this->extractSignedPdf($result);

    if (empty($signedPdf)) {
      $signature->update([
        'status'        => SignatureStatus::REJECTED,
        'error_message' => 'Provider returned no PDF output.',
      ]);
      $this->deleteDraftFile($draftFile);

      return ['details' => 'Provider returned no PDF output.'];
    }

    // Jika dokumen adalah SPPD, lakukan penandatanganan kedua (Halaman Belakang)
    if (str_starts_with($signature->document_type, 'sppd')) {
      $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sppd_first_' . uniqid() . '.pdf';
      file_put_contents($tempFile, $signedPdf);

      // Lakukan request signature kedua secara invisible di Halaman 2
      $result2 = $this->provider->requestSign(
        $tempFile,
        $signature->signer->nik,
        $passphrase,
        2, // Halaman 2
        $signature->sign_x,
        $signature->sign_y,
        $signature->sign_width,
        $signature->sign_height,
        $linkQr,
        'invisible'
      );

      unlink($tempFile);

      if (is_array($result2) && isset($result2['error'])) {
        $message = 'Provider second request failed: ' . json_encode($result2, JSON_UNESCAPED_UNICODE);
        $signature->update([
          'status'        => SignatureStatus::REJECTED,
          'error_message' => $message,
        ]);
        $this->deleteDraftFile($draftFile);

        return ['details' => $result2];
      }

      $secondSignedPdf = $this->extractSignedPdf($result2);
      if (!empty($secondSignedPdf)) {
        $signedPdf = $secondSignedPdf;
      }
    }

    $signedPath = $this->storeSignedPdf($signature, $signedPdf);

    $signature->update([
      'status'           => SignatureStatus::SIGNED,
      'signed_at'        => now(),
      'provider_id'      => is_array($result) && isset($result['id_dokumen']) ? $result['id_dokumen'] : null,
      'signed_file_path' => $signedPath,
      'error_message'    => null,
    ]);

    // Poin 4: Hapus file draft (doc_dummy) setelah signing berhasil
    $this->deleteDraftFile($draftFile);

    return true;
  }

  /**
   * Ekstrak konten PDF dari response provider.
   */
  private function extractSignedPdf(array|string $result): ?string
  {
    $documentId = null;
    if (is_array($result) && isset($result['id_dokumen'])) {
      $documentId = $result['id_dokumen'];
    }

    if ($documentId) {
      try {
        return $this->provider->downloadSignedPdf($documentId);
      } catch (\Throwable $e) {
        Log::error('SignatureService.extractSignedPdf download failed', ['exception' => $e]);
        return null;
      }
    } elseif (is_string($result) && str_contains($result, '%PDF')) {
      return $result;
    }

    return null;
  }

  private function deleteDraftFile(string $draftFile): void
  {
    // Ambil direktori parent dari file draft (folder doc_dummy)
    $draftDir = dirname($draftFile);

    if (is_dir($draftDir)) {
      \Illuminate\Support\Facades\File::deleteDirectory($draftDir);
    } elseif (file_exists($draftFile)) {
      // Fallback: hapus file saja jika bukan direktori
      unlink($draftFile);
    }
  }

  protected function storeSignedPdf(SppdDigitalSignature $signature, string $pdfContents): string
  {
    $disk = Storage::disk(config('tte.storage.disk'));

    $directory = config('tte.storage.paths.signed');
    if (! $disk->exists($directory)) {
      $disk->makeDirectory($directory);
    }

    $sppd = $signature->sppdRequest;

    if ($signature->document_type === 'spt') {
      $personName = Str::slug($sppd->user->name ?? 'pelaksana');
      $prefix     = 'SPT';
    } elseif (preg_match('/^sppd_(\d+)$/', $signature->document_type, $m)) {
      $userId = (int) $m[1];
      if ($userId === $sppd->user_id) {
        $personName = Str::slug($sppd->user->name ?? 'pelaksana');
      } else {
        $follower   = $sppd->followers->first(fn($f) => $f->user_id === $userId);
        $personName = Str::slug($follower?->user?->name ?? 'pengikut');
      }
      $prefix = 'SPPD';
    } else {
      $personName = Str::slug($sppd->user->name ?? 'pelaksana');
      $prefix     = strtoupper($signature->document_type);
    }

    // Sesuaikan timestamp dengan tanggal dokumen
    $dateField = match (true) {
      $signature->document_type === 'spt'             => $sppd->spt_date,
      str_starts_with($signature->document_type, 'sppd') => $sppd->sppd_date,
      default                                          => null,
    };

    $timestamp = $dateField
      ? \Carbon\Carbon::parse($dateField)->setTimeFrom(now())->format('Y-m-d_Hi') . substr(now()->format('s'), 0, 1)
      : now()->format('Y-m-d_Hi') . substr(now()->format('s'), 0, 1);

    $filename     = $prefix . '_' . $personName . '_' . $timestamp . '.pdf';
    $relativePath = config('tte.storage.paths.signed') . '/' . $filename;
    $disk->put($relativePath, $pdfContents);

    return $relativePath;
  }

  protected function resolveProvider(): SignatureProviderInterface
  {
    $provider = config('tte.default_provider');

    return match ($provider) {
      'bssn'  => throw new \RuntimeException('BSSN provider not implemented yet.'),
      default => new LocalProxySignService(),
    };
  }
}
