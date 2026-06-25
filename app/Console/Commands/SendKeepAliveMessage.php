<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim pesan WhatsApp "keep-alive" ke nomor di config secara terjadwal.
 *
 * Kirim.Chat menonaktifkan layanan bila tidak ada aktivitas kirim minimal 7 hari,
 * jadi command ini dijalankan berkala (lihat jadwal di routes/console.php) untuk
 * menjaga akun WA Business tetap "aktif". Nomor, status aktif, dan isi pesan
 * diambil dari config('kirimchat.keepalive.*'). Pengiriman dititipkan ke
 * SendWhatsAppNotificationJob agar memakai retry & service Kirim.Chat yang sama.
 */
class SendKeepAliveMessage extends Command
{
  protected $signature = 'wa:keepalive';

  protected $description = 'Kirim pesan WA keep-alive ke nomor config agar akun Kirim.Chat tetap aktif';

  public function handle(): int
  {
    if (! config('kirimchat.keepalive.enabled')) {
      $this->info('Keep-alive WA dinonaktifkan (kirimchat.keepalive.enabled=false). Dilewati.');

      return self::SUCCESS;
    }

    $phone = (string) config('kirimchat.keepalive.phone');

    if ($phone === '') {
      $this->warn('Nomor keep-alive (kirimchat.keepalive.phone) kosong. Dilewati.');

      return self::SUCCESS;
    }

    // Bungkus catatan dari config dengan template ber-emoji yang menarik.
    // Timestamp (berbeda tiap kirim) juga menghindari deteksi pesan identik berulang.
    $note = trim((string) config('kirimchat.keepalive.message'));
    $waktu = now()->locale('id')->isoFormat('dddd, D MMMM Y • HH:mm').' WITA';

    $message = "🟢 *LAYANAN SPPD KOTA KENDARI*\n"
      ."━━━━━━━━━━━━━━━━\n"
      ."🤖 {$note}\n\n"
      ."✅ *Status:* Sistem Aktif\n"
      ."🕒 {$waktu}\n\n"
      ."_Pesan otomatis — tidak perlu dibalas._ 🙏";

    SendWhatsAppNotificationJob::dispatch($phone, $message);

    Log::info('Keep-alive WA dijadwalkan untuk dikirim.', ['phone' => $phone]);
    $this->info("Pesan keep-alive WA dijadwalkan ke {$phone}.");

    return self::SUCCESS;
  }
}
