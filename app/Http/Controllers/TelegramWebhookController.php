<?php

namespace App\Http\Controllers;

use App\Services\TelegramUpdateHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menerima update dari bot Telegram (didaftarkan lewat `php artisan telegram:webhook`).
 * Logika verifikasi ada di TelegramUpdateHandler agar sama dengan mode polling.
 */
class TelegramWebhookController extends Controller
{
  public function handle(Request $request, TelegramUpdateHandler $handler): JsonResponse
  {
    // Verifikasi shared-secret bila dikonfigurasi — mencegah pemalsuan request webhook.
    $secret = config('telegram.webhook_secret');
    if (! empty($secret)) {
      $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');
      if (! is_string($provided) || ! hash_equals($secret, $provided)) {
        Log::warning('TelegramWebhook: secret tidak valid, request ditolak.');

        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
      }
    }

    try {
      $handler->handle($request->all());
    } catch (\Throwable $e) {
      // Tetap balas 200: status non-2xx membuat Telegram mengirim ulang update yang sama terus-menerus.
      Log::error('TelegramWebhook: gagal memproses update.', [
        'update_id' => $request->input('update_id'),
        'error' => $e->getMessage(),
      ]);
    }

    return response()->json(['success' => true]);
  }
}
