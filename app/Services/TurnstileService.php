<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifikasi token Cloudflare Turnstile di sisi server.
 *
 * Aktif hanya bila site key dan secret key sama-sama terisi, sehingga
 * lingkungan tanpa kunci tetap bisa login.
 */
class TurnstileService
{
  private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

  /** Site key untuk widget, atau null bila Turnstile tidak aktif. */
  public function siteKey(): ?string
  {
    $siteKey = config('services.turnstile.site_key');

    return filled($siteKey) && filled(config('services.turnstile.secret_key')) ? $siteKey : null;
  }

  public function enabled(): bool
  {
    return $this->siteKey() !== null;
  }

  /**
   * Periksa token ke Cloudflare. Token hanya bisa diverifikasi sekali.
   *
   * `remoteip` sengaja tidak dikirim: opsional, dan di balik proxy IP yang
   * terbaca bisa keliru sehingga token sah malah ditolak.
   *
   * Cloudflare tidak terjangkau dianggap tidak lolos: login ditolak, bukan dilewatkan.
   */
  public function verify(string $token): bool
  {
    if ($token === '') {
      return false;
    }

    try {
      $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
        'secret' => config('services.turnstile.secret_key'),
        'response' => $token,
      ]);
    } catch (ConnectionException $e) {
      Log::warning('TurnstileService: Cloudflare tidak dapat dihubungi.', ['error' => $e->getMessage()]);

      return false;
    }

    if ($response->json('success') === true) {
      return true;
    }

    Log::warning('TurnstileService: Token ditolak.', ['errors' => $response->json('error-codes')]);

    return false;
  }
}
