<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Send a WhatsApp message via Fonnte API.
     *
     * Returns true on success, false on failure (does not throw).
     */
    public function send(string $phone, string $message): bool
    {
        if (! config('fonnte.enabled')) {
            return false;
        }

        $token = config('fonnte.token');

        if (empty($token)) {
            Log::warning('FonnteService: FONNTE_TOKEN is not configured.');

            return false;
        }

        // Normalize phone number: remove leading 0 or +
        $phone = $this->normalizePhone($phone);

        try {
            $response = Http::timeout(15)
                ->retry(2, 1000)
                ->withHeaders(['Authorization' => $token])
                ->post(config('fonnte.endpoint'), [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => config('fonnte.country_code', '62'),
                ]);

            if (! $response->successful()) {
                Log::warning('FonnteService: API returned non-2xx response.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'phone' => $phone,
                ]);

                return false;
            }

            $body = $response->json();

            if (isset($body['status']) && $body['status'] === false) {
                Log::warning('FonnteService: API returned status false.', [
                    'response' => $body,
                    'phone' => $phone,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FonnteService: Exception saat mengirim pesan.', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);

            return false;
        }
    }

    /**
     * Normalize phone to international format without leading + or 0.
     * e.g. 0812xxx → 812xxx, +62812xxx → 62812xxx
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9 && strlen($phone) <= 13) {
            $phone = '62'.$phone;
        }

        return $phone;
    }
}
