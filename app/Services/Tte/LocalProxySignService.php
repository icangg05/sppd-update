<?php

namespace App\Services\Tte;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LocalProxySignService implements SignatureProviderInterface
{
    /**
     * Penanda gangguan sesaat di jalur proxy → BSrE. Proxy membungkus error dari
     * BSrE di dalam JSON, mis. {"error":"502 Bad Gateway: \"<html>…nginx…\""},
     * sehingga dikenali dari isi body, bukan hanya dari kode HTTP.
     */
    private const TRANSIENT_BODY_MARKERS = [
        '502 Bad Gateway',
        '503 Service Unavailable',
        '504 Gateway Time',
        'ResourceAccessException',
    ];

    protected string $endpoint;
    protected array $config;

    public function __construct()
    {
        $this->config = config('tte.providers.' . config('tte.default_provider')) ?: [];
        $this->endpoint = rtrim($this->config['endpoint'] ?? config('tte.providers.local_proxy.endpoint'), '/');
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
        $query = http_build_query([
            'nik' => $nik,
            'passphrase' => $passphrase,
            'tampilan' => $tampilan,
            'halaman' => $page === 1 ? 'pertama' : 'terakhir',
            'image' => 'false',
            'linkQR' => $linkQr,
            'xAxis' => $xAxis,
            'yAxis' => $yAxis,
            'width' => $width,
            'height' => $height,
            'jenis_response' => 'BASE64',
        ]);

        $url = $this->endpoint . '/api/sign/pdf?' . $query;

        try {
            $response = $this->client()
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($url);

            if ($response->failed()) {
                // URL tanpa query string: query berisi passphrase, jangan sampai tertulis di log.
                Log::error('LocalProxySignService.requestSign failed', [
                    'url' => $this->endpoint . '/api/sign/pdf',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['error' => 'Provider request failed: ' . $response->body()];
            }

            $payload = $response->json();
            if (is_array($payload) && !empty($payload)) {
                return $payload;
            }

            return $response->body();
        } catch (Throwable $e) {
            Log::error('LocalProxySignService.requestSign exception', ['exception' => $e]);
            return ['error' => $e->getMessage()];
        }
    }

    public function downloadSignedPdf(string $signedDocumentId): string
    {
        $url = $this->endpoint . '/api/sign/download/' . $signedDocumentId;

        try {
            $response = $this->client()->get($url);

            if ($response->failed()) {
                Log::error('LocalProxySignService.downloadSignedPdf failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Provider download failed: ' . $response->body());
            }

            return $response->body();
        } catch (Throwable $e) {
            Log::error('LocalProxySignService.downloadSignedPdf exception', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Batas waktu terlama satu permintaan ke provider, termasuk semua pengulangan
     * dan jedanya (detik). Dipakai untuk batas waktu job TTE.
     */
    public static function maxRequestSeconds(): int
    {
        $retries = static::retries();
        $totalDelayMs = static::retryDelayMs() * $retries * ($retries + 1) / 2; // jeda 1×, 2×, … dasar

        return (int) ceil(($retries + 1) * static::timeout() + $totalDelayMs / 1000);
    }

    /**
     * Klien HTTP dengan pengulangan otomatis untuk gangguan sesaat. Bila semua
     * percobaan gagal, respons gagal terakhir dikembalikan (bukan dilempar).
     */
    protected function client(): PendingRequest
    {
        return Http::withOptions(['verify' => false, 'timeout' => static::timeout()])
            ->withHeaders($this->buildHeaders())
            ->retry(
                static::retries() + 1,
                fn (int $attempt) => $attempt * static::retryDelayMs(),
                fn (Throwable $e) => $this->isTransient($e),
                throw: false,
            );
    }

    /**
     * Gangguan sesaat yang aman diulang otomatis:
     * - 502/503/504 dari proxy, atau error gateway BSrE yang diteruskan proxy;
     * - koneksi gagal (ditolak, terputus, gagal tersambung).
     *
     * Tidak diulang:
     * - passphrase salah dan error lain dari BSrE: hasilnya pasti sama, dan
     *   percobaan salah ikut bertambah di BSrE;
     * - sudah tersambung tetapi tak ada jawaban sampai batas waktu: mengulang
     *   berarti menunggu sepenuhnya sekali lagi, dan selama itu antrean (termasuk
     *   notifikasi Telegram & heartbeat queue) tertahan.
     */
    protected function isTransient(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            $transient = ! str_contains($e->getMessage(), 'Operation timed out');
        } elseif ($e instanceof RequestException) {
            $body = $e->response->body();
            $transient = stripos($body, 'passphrase') === false
                && (in_array($e->response->status(), [502, 503, 504], true)
                    || Str::contains($body, self::TRANSIENT_BODY_MARKERS));
        } else {
            $transient = false;
        }

        if ($transient) {
            Log::warning('LocalProxySignService: gangguan sesaat dari provider TTE', [
                'error' => Str::limit($e->getMessage(), 300),
            ]);
        }

        return $transient;
    }

    /**
     * Batas waktu request (detik). Guzzle hanya menerima int|float, sedangkan nilai
     * dari .env (E_SIGN_TIMEOUT) terbaca sebagai string — jadi selalu dikonversi.
     */
    protected static function timeout(): int
    {
        return (int) config('tte.providers.local_proxy.timeout', 60) ?: 60;
    }

    /** Jumlah pengulangan otomatis setelah percobaan pertama gagal karena gangguan sesaat. */
    protected static function retries(): int
    {
        return max(0, (int) config('tte.providers.local_proxy.retries', 2));
    }

    /** Jeda dasar antar-percobaan (ms); percobaan ke-n menunggu n × jeda ini. */
    protected static function retryDelayMs(): int
    {
        return max(0, (int) config('tte.providers.local_proxy.retry_delay_ms', 5000));
    }

    protected function buildHeaders(): array
    {
        $headers = [];
        if (!empty($this->config['basic_auth'])) {
            $headers['Authorization'] = 'Basic ' . $this->config['basic_auth'];
        }
        return $headers;
    }
}
