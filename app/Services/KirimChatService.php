<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KirimChatService
{
    /**
     * Kirim pesan WhatsApp via Kirim.Chat API.
     *
     * @param  string  $phone    Nomor tujuan (format internasional, tanpa +)
     * @param  string  $message  Isi pesan
     */
    public function send(string $phone, string $message): bool
    {
        if (! config('kirimchat.enabled')) {
            return false;
        }

        $baseUrl = rtrim(config('kirimchat.base_url'), '/');
        $apiKey = config('kirimchat.api_key');

        if (empty($baseUrl)) {
            Log::warning('KirimChatService: KIRIMCHAT_BASE_URL tidak dikonfigurasi.');

            return false;
        }

        if (empty($apiKey)) {
            Log::warning('KirimChatService: KIRIMCHAT_API_KEY tidak dikonfigurasi.');

            return false;
        }

        $normalizedPhone = $this->normalizePhone($phone);

        try {
            $response = Http::timeout(15)
                ->retry(2, 1000)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/messages/send", [
                    'phone_number' => $normalizedPhone,
                    'channel' => 'whatsapp',
                    'message_type' => 'text',
                    'content' => $message,
                ]);

            if ($response->successful() && ($response->json('success') === true || $response->status() === 200)) {
                Log::info('KirimChatService: Pesan berhasil dikirim.', [
                    'phone' => $normalizedPhone,
                ]);

                return true;
            }

            Log::warning('KirimChatService: API mengembalikan respons non-sukses.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'phone' => $normalizedPhone,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('KirimChatService: Exception saat mengirim pesan.', [
                'error' => $e->getMessage(),
                'phone' => $normalizedPhone,
            ]);

            return false;
        }
    }

    /**
     * Cek kesehatan koneksi ke Kirim.Chat API.
     *
     * @return array{success: bool, authenticated: bool, message: string}
     */
    public function healthCheck(): array
    {
        $baseUrl = rtrim(config('kirimchat.base_url'), '/');
        $apiKey = config('kirimchat.api_key');

        if (empty($apiKey)) {
            return ['success' => false, 'authenticated' => false, 'message' => 'API Key belum dikonfigurasi.'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$baseUrl}/health");

            if ($response->successful()) {
                $data = $response->json('data', []);

                return [
                    'success' => true,
                    'authenticated' => $data['authenticated'] ?? false,
                    'message' => 'Koneksi berhasil.',
                ];
            }

            return [
                'success' => false,
                'authenticated' => false,
                'message' => 'API tidak dapat dijangkau (HTTP '.$response->status().').',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'authenticated' => false,
                'message' => 'Gagal terhubung: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Normalisasi nomor telepon ke format internasional tanpa + atau 0 di depan.
     * Contoh: 0812xxx → 62812xxx, +62812xxx → 62812xxx
     */
    private function normalizePhone(string $phone): string
    {
        if (str_contains($phone, '@')) {
            [$phone] = explode('@', $phone, 2);
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
