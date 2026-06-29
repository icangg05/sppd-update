<?php

namespace App\Services\Tte;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifikasi keaslian dokumen PDF ber-TTE ke BSrE.
 *
 * Mengunggah berkas PDF (field 'signed_file') ke endpoint verify; BSrE
 * memeriksa keaslian tanda tangan PAdES-nya. Respons memuat:
 * summary, notes, nama_dokumen, jumlah_signature, details[].
 *
 * Mode auth (config tte.verify.auth):
 *  - 'basic'  : via proxy lokal /api/sign/verify (default)
 *  - 'bearer' : token statis langsung ke BSrE
 *  - 'oauth'  : client_credentials langsung ke BSrE
 */
class BsreVerificationService
{
  protected array $config;

  public function __construct()
  {
    $this->config = config('tte.verify', []);
  }

  /**
   * Apakah verifikasi aktif & kredensial untuk mode auth-nya tersedia.
   */
  public function isEnabled(): bool
  {
    if (! ($this->config['enabled'] ?? false) || empty($this->config['endpoint'])) {
      return false;
    }

    return match ($this->config['auth'] ?? 'basic') {
      'basic'  => ! empty($this->config['basic_auth']),
      'bearer' => ! empty($this->config['token']),
      'oauth'  => ! empty($this->config['client_id']) && ! empty($this->config['client_secret']),
      default  => false,
    };
  }

  /**
   * Verifikasi isi PDF.
   *
   * @return array{ok: bool, available: bool, summary: ?string, valid: ?bool,
   *               document_name: ?string, signature_count: ?int, notes: ?string,
   *               details: array, error: ?string, raw: mixed}
   */
  public function verify(string $pdfBinary, string $filename = 'document.pdf'): array
  {
    $base = [
      'ok' => false,
      'available' => $this->isEnabled(),
      'summary' => null,
      'valid' => null,
      'document_name' => null,
      'signature_count' => null,
      'notes' => null,
      'details' => [],
      'error' => null,
      'raw' => null,
    ];

    if (! $this->isEnabled()) {
      return array_merge($base, ['error' => 'Verifikasi dokumen belum dikonfigurasi.']);
    }

    try {
      $request = Http::withOptions(['verify' => false])
        ->timeout((int) ($this->config['timeout'] ?? 30))
        ->attach('signed_file', $pdfBinary, $filename);

      $request = $this->applyAuth($request);
      if (! $request) {
        return array_merge($base, ['error' => 'Gagal memperoleh kredensial verifikasi.']);
      }

      $response = $request->post($this->config['endpoint']);

      if ($response->failed()) {
        Log::warning('BsreVerificationService.verify failed', [
          'status' => $response->status(),
          'body' => $response->body(),
        ]);

        return array_merge($base, ['error' => 'Server verifikasi menolak permintaan (HTTP ' . $response->status() . ').']);
      }

      $json = $response->json();
      if (! is_array($json)) {
        return array_merge($base, ['error' => 'Respons verifikasi tidak dapat dibaca.', 'raw' => $response->body()]);
      }

      $summary = $json['summary'] ?? $json['SUMMARY'] ?? null;

      return array_merge($base, [
        'ok' => true,
        'summary' => $summary,
        'valid' => $this->interpretSummary($summary),
        'document_name' => $json['nama_dokumen'] ?? $json['document_name'] ?? null,
        'signature_count' => isset($json['jumlah_signature']) ? (int) $json['jumlah_signature'] : null,
        'notes' => $json['notes'] ?? null,
        'details' => is_array($json['details'] ?? null) ? $json['details'] : [],
        'raw' => $json,
      ]);
    } catch (\Throwable $e) {
      Log::error('BsreVerificationService.verify exception', ['exception' => $e]);

      return array_merge($base, ['error' => 'Tidak dapat terhubung ke server verifikasi: ' . $e->getMessage()]);
    }
  }

  /**
   * Pasang autentikasi sesuai mode. Mengembalikan null bila gagal (mis. token oauth).
   */
  protected function applyAuth(PendingRequest $request): ?PendingRequest
  {
    return match ($this->config['auth'] ?? 'basic') {
      'basic'  => $request->withHeaders(['Authorization' => 'Basic ' . $this->config['basic_auth']]),
      'bearer' => $request->withToken($this->config['token']),
      'oauth'  => ($token = $this->resolveOauthToken()) ? $request->withToken($token) : null,
      default  => null,
    };
  }

  /**
   * Tentukan valid/tidak dari teks summary (mis. "VALID", "INVALID", "TIDAK VALID").
   */
  protected function interpretSummary(?string $summary): ?bool
  {
    if (! $summary) {
      return null;
    }

    if (preg_match('/INVALID|NOT\s+VALID|TIDAK\s+VALID/i', $summary)) {
      return false;
    }

    return (bool) preg_match('/\bVALID\b/i', $summary);
  }

  /**
   * Token OAuth client_credentials (di-cache hingga mendekati kedaluwarsa).
   */
  protected function resolveOauthToken(): ?string
  {
    return Cache::remember('tte.verify.oauth_token', now()->addMinutes(50), function () {
      try {
        $response = Http::withOptions(['verify' => false])
          ->timeout((int) ($this->config['timeout'] ?? 30))
          ->post($this->config['oauth_endpoint'], [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => 'client_credentials',
          ]);

        return $response->successful() ? ($response->json('access_token') ?: null) : null;
      } catch (\Throwable $e) {
        Log::error('BsreVerificationService.resolveOauthToken exception', ['exception' => $e]);
        return null;
      }
    });
  }
}
