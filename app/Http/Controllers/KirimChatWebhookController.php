<?php

namespace App\Http\Controllers;

use App\Services\KirimChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class KirimChatWebhookController
 *
 * Menangani callback webhook masuk dari layanan Kirim.Chat untuk verifikasi nomor WhatsApp.
 */
class KirimChatWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from Kirim.Chat.
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

        // Cari token verifikasi dengan pola V-XXXXXX atau V-XXXXX
        $token = null;
        if (preg_match('/(V-\d+)/i', $messageText, $matches)) {
            $token = strtoupper($matches[1]);
        }

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan kode verifikasi yang valid.',
            ], 200);
        }

        $cached = Cache::get("wa_verification:{$token}");

        if (!$cached) {
            Log::warning("KirimChatWebhook: Token {$token} tidak ditemukan di Cache atau sudah kedaluwarsa.");

            $reply = "❌ *VERIFIKASI GAGAL*\n\n" .
                     "Mohon maaf, proses verifikasi nomor WhatsApp Anda tidak dapat diproses.\n\n" .
                     "⚠️ *Penyebab:* Kode verifikasi salah, sudah kedaluwarsa, atau format pesan telah diubah.\n" .
                     "👉 *Solusi:* Silakan kembali ke halaman Profil di aplikasi SPPD, refresh halaman, dan klik ulang tombol Verifikasi Nomor.";

            $kirimChatService->send($from, $reply);

            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau kedaluwarsa.',
            ], 200);
        }

        // Normalisasi nomor telepon untuk dicocokkan
        $fromNormalized = $this->normalizePhone($from);
        $cachedNormalized = $this->normalizePhone($cached['phone']);

        if ($fromNormalized !== $cachedNormalized) {
            Log::warning("KirimChatWebhook: Kecocokan nomor gagal untuk token {$token}. Pengirim: {$fromNormalized}, Terdaftar: {$cachedNormalized}");

            $reply = "❌ *VERIFIKASI GAGAL*\n\n" .
                     "Nomor pengirim tidak sesuai dengan nomor yang didaftarkan di aplikasi SPPD.\n\n" .
                     "Harap mengirimkan pesan verifikasi dari nomor WhatsApp yang Anda daftarkan ({$cached['phone']}).";

            $kirimChatService->send($from, $reply);

            return response()->json([
                'success' => false,
                'message' => 'Nomor pengirim tidak cocok dengan data pendaftaran.',
            ], 200);
        }

        // Simpan status sukses verifikasi di Cache untuk polling frontend
        Cache::put("wa_verified_status:{$token}", [
            'verified' => true,
            'phone' => $cached['phone'],
        ], now()->addMinutes(15));

        // Jika user_id ada (proses edit pegawai), update nomor telepon di database
        if (!empty($cached['user_id'])) {
            $user = \App\Models\User::find($cached['user_id']);
            if ($user) {
                $user->update(['phone' => $cached['phone']]);
                Log::info("KirimChatWebhook: Berhasil memperbarui nomor telepon user ID {$user->id}.");
            }
        }

        // Kirim balasan WhatsApp sukses
        $name = $cached['name'] ?? 'Pegawai';
        $reply = "✅ *VERIFIKASI BERHASIL!*\n\n" .
                 "Halo *{$name}*, nomor WhatsApp Anda ({$cached['phone']}) telah sukses terverifikasi pada *Sistem SPPD Elektronik Kota Kendari*.\n\n" .
                 "Anda sekarang akan menerima notifikasi perjalanan dinas secara otomatis di nomor ini. Terima kasih!";

        $kirimChatService->send($from, $reply);

        // Hapus token verifikasi dari cache agar satu kali pakai
        Cache::forget("wa_verification:{$token}");

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
