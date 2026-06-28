<?php

namespace App\Console\Commands;

use App\Services\KirimChatService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Menjaga akun Kirim.Chat tetap aktif secara terjadwal.
 *
 * Kirim.Chat menonaktifkan layanan bila tidak ada aktivitas minimal 7 hari.
 * Alih-alih mengirim pesan WhatsApp (teks bebas tunduk pada window 24-jam Meta
 * sehingga sering ditolak `WindowClosed`, sedangkan template berbiaya), command
 * ini melakukan ping API ter-autentikasi yang bebas dari kedua batasan tersebut.
 * Jadwal diatur di routes/console.php via config('kirimchat.keepalive.*').
 */
class SendKeepAliveMessage extends Command
{
  protected $signature = 'wa:keepalive';

  protected $description = 'Ping aktivitas Kirim.Chat agar akun tetap aktif (tanpa kirim pesan)';

  public function handle(KirimChatService $kirimChat): int
  {
    if (! config('kirimchat.keepalive.enabled')) {
      $this->info('Keep-alive dinonaktifkan (kirimchat.keepalive.enabled=false). Dilewati.');

      return self::SUCCESS;
    }

    $result = $kirimChat->pingActivity();

    if ($result['success']) {
      Log::info('Keep-alive Kirim.Chat: ping aktivitas berhasil.', [
        'authenticated' => $result['authenticated'],
      ]);
      $this->info('Ping aktivitas Kirim.Chat berhasil. '.$result['message']);

      return self::SUCCESS;
    }

    Log::warning('Keep-alive Kirim.Chat: ping aktivitas gagal.', [
      'message' => $result['message'],
    ]);
    $this->warn('Ping aktivitas Kirim.Chat gagal: '.$result['message']);

    return self::SUCCESS;
  }
}
