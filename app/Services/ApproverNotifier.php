<?php

namespace App\Services;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Sumber tunggal untuk notifikasi WhatsApp ke approver SPPD.
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

        $this->sendPush(
            $sppd,
            $approval,
            'Pengajuan SPPD Baru',
            "Terdapat pengajuan SPPD baru dari {$sppd->user->name} yang memerlukan persetujuan Anda."
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

        $this->sendPush(
            $sppd,
            $approval,
            'Perbaikan Dokumen SPPD (Revisi)',
            "Pegawai {$sppd->user->name} telah mengirimkan perbaikan data SPPD yang memerlukan persetujuan kembali dari Anda."
        );
    }

    /**
     * Dispatch push notification job for the approver.
     */
    protected function sendPush(SppdRequest $sppd, SppdApproval $approval, string $title, string $body): void
    {
        try {
            $approver = $approval->approver;
            if (!$approver) {
                return;
            }

            $detailUrl = route('sppd.show', $sppd);

            \App\Jobs\SendPushNotificationJob::dispatch(
                $approver->id,
                $title,
                $body,
                $detailUrl
            );
        } catch (\Exception $e) {
            Log::warning('Gagal dispatch push notification ke approver SPPD.', [
                'sppd_id' => $sppd->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bangun pesan WhatsApp dan kirim ke approver bila nomornya terverifikasi.
     * Error notifikasi sengaja ditelan (hanya di-log) agar tidak memblokir alur/transaksi utama.
     */
    protected function send(SppdRequest $sppd, SppdApproval $approval, string $header, string $intro): void
    {
        try {
            $approver = $approval->approver;
            if (! $approver || ! $approver->phone || ! $approver->phone_verified) {
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

            SendWhatsAppNotificationJob::dispatch($approver->phone, $message);
        } catch (\Exception $e) {
            Log::warning('Gagal mengirim notifikasi approver SPPD.', [
                'sppd_id' => $sppd->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
