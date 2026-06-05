<?php

namespace App\Services\Tte;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocalProxySignService implements SignatureProviderInterface
{
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
        string $passphrase,
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
        $headers = $this->buildHeaders();

        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => config('tte.providers.local_proxy.timeout', 90)])
                ->withHeaders($headers)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($url);

            if ($response->failed()) {
                Log::error('LocalProxySignService.requestSign failed', [
                    'url' => $url,
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
        } catch (\Throwable $e) {
            Log::error('LocalProxySignService.requestSign exception', ['exception' => $e]);
            return ['error' => $e->getMessage()];
        }
    }

    public function downloadSignedPdf(string $signedDocumentId): string
    {
        $url = $this->endpoint . '/api/sign/download/' . $signedDocumentId;
        $headers = $this->buildHeaders();

        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => config('tte.providers.local_proxy.timeout', 90)])
                ->withHeaders($headers)
                ->get($url);

            if ($response->failed()) {
                Log::error('LocalProxySignService.downloadSignedPdf failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Provider download failed: ' . $response->body());
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::error('LocalProxySignService.downloadSignedPdf exception', ['exception' => $e]);
            throw $e;
        }
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
