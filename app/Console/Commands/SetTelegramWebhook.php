<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Mendaftarkan (atau menghapus) URL webhook bot Telegram. Cukup dijalankan
 * sekali setiap kali domain / secret berubah. Telegram mewajibkan URL HTTPS publik.
 */
class SetTelegramWebhook extends Command
{
  protected $signature = 'telegram:webhook
    {--delete : Hapus webhook (mis. sebelum memakai mode polling)}
    {--info : Tampilkan status webhook saat ini}';

  protected $description = 'Daftarkan webhook bot Telegram ke route webhook.telegram';

  public function handle(TelegramService $telegram): int
  {
    $me = $telegram->getMe();
    if (! $me['success']) {
      $this->error('Token bot tidak valid: ' . $me['message']);

      return self::FAILURE;
    }

    $this->line("Bot: @{$me['username']}");

    if ($this->option('info')) {
      $info = $telegram->getWebhookInfo()['result'] ?? [];
      $this->table(['Kunci', 'Nilai'], collect($info)->map(fn ($value, $key) => [
        $key,
        is_scalar($value) ? (string) $value : json_encode($value),
      ])->values()->all());

      return self::SUCCESS;
    }

    if ($this->option('delete')) {
      $result = $telegram->deleteWebhook();
      if (($result['ok'] ?? false) !== true) {
        $this->error('Gagal menghapus webhook: ' . ($result['description'] ?? 'respons kosong'));

        return self::FAILURE;
      }

      $this->info('Webhook dihapus.');

      return self::SUCCESS;
    }

    $url = route('webhook.telegram');
    if (! str_starts_with($url, 'https://')) {
      $this->error("URL webhook harus HTTPS publik, didapat: {$url}. Periksa APP_URL, atau pakai TELEGRAM_UPDATE_MODE=polling untuk localhost.");

      return self::FAILURE;
    }

    $secret = config('telegram.webhook_secret');
    if (empty($secret)) {
      $this->warn('TELEGRAM_WEBHOOK_SECRET kosong, sehingga request webhook tidak divalidasi. Disarankan diisi untuk produksi.');
    }

    $result = $telegram->setWebhook($url, $secret);
    if (($result['ok'] ?? false) !== true) {
      $this->error('Gagal mendaftarkan webhook: ' . ($result['description'] ?? 'respons kosong'));

      return self::FAILURE;
    }

    $this->info("Webhook terdaftar: {$url}");

    return self::SUCCESS;
  }
}
