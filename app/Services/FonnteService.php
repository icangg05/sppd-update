<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Send a WhatsApp message via Fonnte API.
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        if (! config('fonnte.enabled')) {
            return false;
        }

        $url = rtrim(config('fonnte.url'), '/');
        $token = config('fonnte.token');

        if (empty($url)) {
            Log::warning('FonnteService: FONNTE_URL is not configured.');

            return false;
        }

        if (empty($token)) {
            Log::warning('FonnteService: FONNTE_TOKEN is not configured.');

            return false;
        }

        $normalizedPhone = $this->normalizePhone($phone);

        try {
            $response = Http::timeout(15)
                ->retry(2, 1000)
                ->withHeaders([
                    'Authorization' => $token,
                ])
                ->post($url, [
                    'target' => $normalizedPhone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FonnteService: API returned non-2xx response.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'phone' => $normalizedPhone,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('FonnteService: Exception saat mengirim pesan.', [
                'error' => $e->getMessage(),
                'phone' => $normalizedPhone,
            ]);

            return false;
        }
    }

    /**
     * Normalize phone to international format without leading + or 0.
     * e.g. 0812xxx → 62812xxx, +62812xxx → 62812xxx
     *
     * @param string $phone
     * @return string
     */
    private function normalizePhone(string $phone): string
    {
        if (str_contains($phone, '@')) {
            [$phone, $suffix] = explode('@', $phone, 2);
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9 && strlen($phone) <= 13) {
            $phone = '62'.$phone;
        }

        return $phone;
    }
}
