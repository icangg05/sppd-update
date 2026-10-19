<?php

namespace App\Services;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Sumber tunggal untuk notifikasi Telegram ke approver SPPD.
 * Sebelumnya logika ini terduplikasi di beberapa komponen Livewire.
 */
class ApproverNotifier
{
    /**
     * Beritahu approver bahwa ada pengajuan SPPD baru yang menunggu persetujuannya.
     */
    public function notifyNewRequest(SppdRequest $sppd, SppdApproval $approval): void
    {
        $this->send(
            $sppd,
            $approval,
            '📝 *PENGAJUAN SPPD BARU*',
            'Terdapat pengajuan SPPD baru yang memerlukan persetujuan Anda.'
        );
    }

    /**
     * Beritahu approver bahwa ada revisi SPPD yang perlu disetujui kembali.
     */
    public function notifyRevision(SppdRequest $sppd, SppdApproval $approval): void
    {
        $this->send(
            $sppd,
            $approval,
            '📝 *PERBAIKAN DOKUMEN SPPD (REVISI)*',
            'Pegawai telah mengirimkan perbaikan/revisi data SPPD berikut yang memerlukan persetujuan kembali dari Anda.'
        );
    }

    /**
     * Bangun pesan notifikasi dan kirim ke approver bila akun Telegram-nya terhubung.
     * Error notifikasi sengaja ditelan (hanya di-log) agar tidak memblokir alur/transaksi utama.
     */
    protected function send(SppdRequest $sppd, SppdApproval $approval, string $header, string $intro): void
    {
        try {
            $approver = $approval->approver;
            if (! $approver || ! $approver->canReceiveTelegram()) {
                return;
            }

            $startDate = Carbon::parse($sppd->start_date)->translatedFormat('d F Y');
            $endDate = Carbon::parse($sppd->end_date)->translatedFormat('d F Y');
            $detailUrl = route('sppd.show', $sppd);

            $message = "{$header}\n"
                . "*────────────────────────────────*\n\n"
                . "Halo *{$approver->name}*,\n"
                . "{$intro}\n\n"
                . "• *Pelaksana:* {$sppd->user->name}\n"
                . "• *Maksud Perjalanan:* {$sppd->purpose}\n"
                . "• *Tanggal:* {$startDate} s/d {$endDate}\n"
                . "• *Peran Anda:* {$approval->role_label}\n\n"
                . "Silakan tinjau dan lakukan persetujuan melalui tautan berikut:\n"
                . "🔗 {$detailUrl}\n\n"
                . "Terima kasih.";

            SendTelegramNotificationJob::dispatch($approver->telegram_chat_id, $message);
        } catch (\Exception $e) {
            Log::warning('Gagal mengirim notifikasi approver SPPD.', [
                'sppd_id' => $sppd->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
