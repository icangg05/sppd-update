<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Services\KirimChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class KirimChatWebhookController
 *
 * Menangani callback webhook masuk dari layanan Kirim.Chat untuk verifikasi nomor WhatsApp.
 * Verifikasi dilakukan dengan mencocokkan nomor pengirim dengan nomor yang terdaftar.
 */
class KirimChatWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from Kirim.Chat.
     *
     * Alur: webhook menerima pesan masuk → cari verifikasi pending berdasarkan nomor pengirim
     * → jika cocok: verified + kirim notif sukses
     * → jika tidak ditemukan: abaikan (timeout di frontend yang menangani)
     *
     * @param  Request  $request
     * @param  KirimChatService  $kirimChatService
     * @return JsonResponse
     */
    public function handle(Request $request, KirimChatService $kirimChatService): JsonResponse
    {
        Log::info('KirimChatWebhook: Request masuk.', $request->all());

        $event = $request->input('event') ?? $request->input('event_type');
        $data = $request->input('data') ?? $request->all();

        // Validasi event jika ada, pastikan tipe event adalah message.received
        if ($event && $event !== 'message.received') {
            return response()->json([
                'success' => false,
                'message' => 'Event diabaikan, hanya memproses message.received.',
            ], 200);
        }

        $from = $data['customer_phone'] ?? $data['from'] ?? ($data['raw']['message']['from'] ?? null);
        $messageText = $data['content'] ?? $data['message'] ?? $data['body'] ?? ($data['raw']['message']['text']['body'] ?? null);

        if (empty($from) || empty($messageText)) {
            Log::warning('KirimChatWebhook: Data pengirim atau isi pesan kosong.', [
                'from' => $from,
                'messageText' => $messageText,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Data pengirim atau isi pesan kosong.',
            ], 200);
        }

        // Ekstrak nomor dari isi pesan template (📱 *Nomor:* XXXXXXXXXXX)
        $templatePhone = null;
        if (preg_match('/Nomor:\*?\s*([0-9+]+)/', $messageText, $phoneMatch)) {
            $templatePhone = $phoneMatch[1];
        }

        if (! $templatePhone) {
            Log::info('KirimChatWebhook: Pesan tidak mengandung nomor verifikasi, diabaikan.');

            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak mengandung format verifikasi.',
            ], 200);
        }

        // Cari verifikasi pending berdasarkan nomor di pesan template
        $templatePhoneNormalized = $this->normalizePhone($templatePhone);
        $token = Cache::get("wa_verification_phone:{$templatePhoneNormalized}");

        if (! $token) {
            Log::info("KirimChatWebhook: Tidak ada verifikasi pending untuk nomor {$templatePhoneNormalized}.");

            return response()->json([
                'success' => false,
                'message' => 'Tidak ada verifikasi yang menunggu untuk nomor ini.',
            ], 200);
        }

        $cached = Cache::get("wa_verification:{$token}");

        if (! $cached) {
            Log::warning("KirimChatWebhook: Token {$token} sudah kedaluwarsa.");

            Cache::forget("wa_verification_phone:{$templatePhoneNormalized}");

            return response()->json([
                'success' => false,
                'message' => 'Verifikasi sudah kedaluwarsa.',
            ], 200);
        }

        // Bandingkan nomor pengirim dengan nomor yang terdaftar di verifikasi
        $fromNormalized = $this->normalizePhone($from);
        $cachedNormalized = $this->normalizePhone($cached['phone']);

        if ($fromNormalized !== $cachedNormalized) {
            Log::warning("KirimChatWebhook: Nomor tidak cocok. Pengirim: {$fromNormalized}, Terdaftar: {$cachedNormalized}");

            // Simpan status gagal di cache agar polling frontend mengetahui
            Cache::put("wa_verification_failed:{$token}", [
                'message' => "Nomor pengirim ({$fromNormalized}) tidak sesuai dengan nomor yang didaftarkan ({$cachedNormalized}). Pastikan mengirim dari nomor yang benar.",
            ], now()->addMinutes(15));

            $reply = "❌ *VERIFIKASI GAGAL*\n\n" .
                     "Nomor pengirim tidak sesuai dengan nomor yang didaftarkan di aplikasi SPPD.\n\n" .
                     "📱 *Nomor terdaftar:* {$cachedNormalized}\n" .
                     "📱 *Nomor pengirim:* {$fromNormalized}\n\n" .
                     "Harap mengirimkan pesan verifikasi dari nomor WhatsApp yang Anda daftarkan.";

            SendWhatsAppNotificationJob::dispatch($from, $reply);

            return response()->json([
                'success' => false,
                'message' => 'Nomor pengirim tidak cocok.',
            ], 200);
        }

        // ✅ Nomor cocok — verifikasi berhasil
        Cache::put("wa_verified_status:{$token}", [
            'verified' => true,
            'phone' => $cachedNormalized,
        ], now()->addMinutes(15));

        // Update nomor telepon + status verifikasi di database
        if (! empty($cached['user_id'])) {
            $user = \App\Models\User::find($cached['user_id']);
            if ($user) {
                $user->update([
                    'phone' => $cachedNormalized,
                    'phone_verified' => true,
                ]);
                Log::info("KirimChatWebhook: Berhasil memverifikasi nomor telepon user ID {$user->id}.");
            }
        }

        // Kirim balasan WhatsApp sukses (nomor format 62xxx)
        $name = $cached['name'] ?? 'Pegawai';
        $reply = "✅ *VERIFIKASI BERHASIL!*\n\n" .
                 "Halo *{$name}*, nomor WhatsApp Anda ({$cachedNormalized}) telah sukses terverifikasi pada *Sistem SPPD Elektronik Kota Kendari*.\n\n" .
                 "Anda sekarang akan menerima notifikasi perjalanan dinas secara otomatis di nomor ini. Terima kasih!";

        SendWhatsAppNotificationJob::dispatch($from, $reply);

        // Bersihkan cache verifikasi
        Cache::forget("wa_verification:{$token}");
        Cache::forget("wa_verification_phone:{$templatePhoneNormalized}");

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi berhasil diproses.',
        ], 200);
    }

    /**
     * Normalisasi nomor telepon ke format internasional.
     *
     * @param  string  $phone
     * @return string
     */
    private function normalizePhone(string $phone): string
    {
        if (str_contains($phone, '@')) {
            [$phone] = explode('@', $phone, 2);
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9 && strlen($phone) <= 13) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}

