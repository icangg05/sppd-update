<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Services\TelegramUpdateHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pengganti webhook untuk localhost/dev: mengambil pesan masuk bot lewat
 * long-polling getUpdates lalu memprosesnya dengan TelegramUpdateHandler yang
 * sama. Dijadwalkan tiap menit di routes/console.php saat
 * TELEGRAM_UPDATE_MODE=polling; setiap run berjalan ±--seconds lalu keluar.
 *
 * Telegram menolak getUpdates selama webhook aktif. Command ini sengaja tidak
 * menghapus webhook otomatis agar dev tidak mengganggu bot produksi.
 */
class PollTelegramUpdates extends Command
{
  /** Offset update berikutnya (update_id terakhir + 1). */
  public const OFFSET_KEY = 'telegram:update_offset';

  protected $signature = 'telegram:poll
    {--seconds=55 : Lama berjalan sebelum keluar (scheduler menjalankan ulang tiap menit)}';

  protected $description = 'Ambil pesan masuk bot Telegram lewat long-polling (untuk dev/localhost tanpa webhook)';

  public function handle(TelegramService $telegram, TelegramUpdateHandler $handler): int
  {
    if (empty(config('telegram.bot_token'))) {
      $this->warn('TELEGRAM_BOT_TOKEN belum dikonfigurasi. Dilewati.');

      return self::SUCCESS;
    }

    $cache = TelegramUpdateHandler::cache();
    $deadline = time() + max(1, (int) $this->option('seconds'));
    $processed = 0;

    do {
      $timeout = max(0, min(25, $deadline - time()));
      $response = $telegram->getUpdates((int) $cache->get(self::OFFSET_KEY, 0), $timeout);

      if (($response['ok'] ?? false) !== true) {
        $this->error('getUpdates gagal: ' . ($response['description'] ?? 'respons kosong')
          . '. Bila webhook masih aktif, jalankan `php artisan telegram:webhook --delete`.');

        return self::FAILURE;
      }

      foreach ($response['result'] ?? [] as $update) {
        // Majukan offset lebih dulu agar update yang gagal diproses tidak diulang terus.
        $cache->forever(self::OFFSET_KEY, (int) $update['update_id'] + 1);

        try {
          $handler->handle($update);
          $processed++;
        } catch (\Throwable $e) {
          Log::error('TelegramPoll: gagal memproses update.', [
            'update_id' => $update['update_id'] ?? null,
            'error' => $e->getMessage(),
          ]);
        }
      }
    } while (time() < $deadline);

    if ($processed > 0) {
      $this->info("{$processed} update diproses.");
    }

    return self::SUCCESS;
  }
}
