<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWAService
{
    /**
     * Send a WhatsApp message via OpenWA API.
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        if (! config('openwa.enabled')) {
            return false;
        }

        $url = rtrim(config('openwa.url'), '/');
        $key = config('openwa.key');
        $sessionId = config('openwa.session_id');

        if (empty($url)) {
            Log::warning('OpenWAService: OPENWA_URL is not configured.');

            return false;
        }

        if (empty($sessionId)) {
            Log::warning('OpenWAService: OPENWA_SESSION_ID is not configured.');

            return false;
        }

        $normalizedPhone = $this->normalizePhone($phone);
        $chatId = $this->buildChatId($phone, $normalizedPhone);

        try {
            $response = $this->postSendText($url, $key, $sessionId, [
                'chatId' => $chatId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('OpenWAService: API returned non-2xx response.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chatId' => $chatId,
                'phone' => $normalizedPhone,
            ]);

            $fallbackResponse = $this->postSendText($url, $key, $sessionId, [
                'chatId' => $chatId,
                'phone' => $normalizedPhone,
                'text' => $message,
            ]);

            if ($fallbackResponse->successful()) {
                return true;
            }

            Log::warning('OpenWAService: Fallback send-text with phone field failed.', [
                'status' => $fallbackResponse->status(),
                'body' => $fallbackResponse->body(),
                'chatId' => $chatId,
                'phone' => $normalizedPhone,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('OpenWAService: Exception saat mengirim pesan.', [
                'error' => $e->getMessage(),
                'chatId' => $chatId,
                'phone' => $normalizedPhone,
            ]);

            return false;
        }
    }

    private function buildChatId(string $phone, string $normalizedPhone): string
    {
        if (! str_contains($phone, '@')) {
            return $normalizedPhone.'@c.us';
        }

        [$raw, $suffix] = explode('@', $phone, 2);

        return $this->normalizePhone($raw).'@'.$suffix;
    }

    private function postSendText(string $url, string $key, string $sessionId, array $payload)
    {
        return Http::timeout(15)
            ->retry(2, 1000)
            ->withHeaders([
                'X-API-Key' => $key,
                'Content-Type' => 'application/json',
            ])
            ->post("{$url}/api/sessions/{$sessionId}/messages/send-text", $payload);
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
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9 && strlen($phone) <= 13) {
            $phone = '62'.$phone;
        }

        return $phone;
    }
}
