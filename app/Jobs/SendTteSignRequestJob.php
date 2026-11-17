<?php

namespace App\Jobs;

use App\Enums\ApprovalStatus;
use App\Enums\SignatureStatus;
use App\Enums\SppdStatus;
use App\Models\SppdDigitalSignature;
use App\Models\User;
use App\Services\Tte\LocalProxySignService;
use App\Services\Tte\SignatureService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTteSignRequestJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public SppdDigitalSignature $signature;
  public string $passphrase;
  public ?string $notes;
  public int $tries = 1;
  public int $timeout;

  public function __construct(SppdDigitalSignature $signature, #[\SensitiveParameter] string $passphrase, ?string $notes = null)
  {
    $this->signature = $signature;
    $this->passphrase = Crypt::encryptString($passphrase);
    $this->notes = $notes;
    // Dokumen SPPD = 2 permintaan ke provider (halaman 1 & 2), masing-masing bisa
    // diulang otomatis; +60 dtk untuk render PDF draft & unduh hasil. Bila kurang,
    // worker mematikan job di tengah proses penandatanganan.
    $this->timeout = 2 * LocalProxySignService::maxRequestSeconds() + 60;
  }

  public function handle(SignatureService $signatureService): void
  {
    $signature = SppdDigitalSignature::find($this->signature->id);
    if (!$signature) {
      return;
    }

    if ($signature->status === SignatureStatus::SIGNED || $signature->status === SignatureStatus::REJECTED) {
      return;
    }

    // Set status to PROCESSING when the job actually runs
    $signature->markProcessing();

    try {
      $decryptedPassphrase = Crypt::decryptString($this->passphrase);
      $res = $signatureService->sign($signature, $decryptedPassphrase);

      if ($res !== true) {
        $errMsg = $signature->error_message ?? 'Tanda tangan TTE gagal.';
        throw new \RuntimeException($errMsg);
      }

      // Handle approval transition after successful signature
      $this->handleApprovalTransition($signature);
    } catch (Throwable $exception) {
      // Penolakan provider sudah ditandai (REJECTED) & dicatat ke log TTE oleh
      // SignatureService. Kegagalan lain — mis. error saat membuat PDF draft —
      // belum tercatat, jadi dicatat di sini agar terlihat di halaman Log TTE.
      // Harus sebelum fail(): chain catch menandai semua dokumen PROCESSING
      // sebagai REJECTED lebih dulu daripada failed().
      if ($signature->status === SignatureStatus::PROCESSING) {
        $signature->markError($exception->getMessage());
        $signatureService->logTteActivity(
          $signature,
          'rejected',
          'Penandatanganan elektronik gagal (kesalahan sistem saat memproses dokumen)',
        );
      }

      Log::error('SendTteSignRequestJob failed', ['exception' => $exception, 'signature_id' => $signature->id]);

      // Immediately fail the job (no retries) — this triggers the chain catch handler
      // so remaining documents in the chain get marked as rejected right away.
      $this->fail($exception);
    }
  }

  /**
   * Dipanggil saat job gagal: lewat fail() di atas, atau oleh worker bila job
   * melewati batas waktu / terhenti di tengah jalan.
   */
  public function failed(Throwable $exception): void
  {
    $signature = SppdDigitalSignature::find($this->signature->id);

    // Dokumen yang sudah tertandatangani (gagal di langkah sesudahnya, mis. transisi
    // persetujuan) tidak ditandai gagal agar tidak perlu TTE ulang.
    if (!$signature || $signature->status === SignatureStatus::SIGNED) {
      return;
    }

    $signature->markError($exception->getMessage());

    // Job dihentikan worker (melewati batas waktu, atau worker mati lalu batas
    // percobaan habis) sehingga handle() tidak sempat mencatatnya ke log TTE.
    if ($exception instanceof MaxAttemptsExceededException) {
      app(SignatureService::class)->logTteActivity(
        $signature,
        'rejected',
        'Penandatanganan elektronik gagal (proses terhenti atau melewati batas waktu)',
      );
    }
  }

  /**
   * Handle the workflow approval transition and send notifications if all signatures succeeded.
   */
  protected function handleApprovalTransition(SppdDigitalSignature $signature): void
  {
    DB::transaction(function () use ($signature) {
      $signature->refresh();

      // If the signature is not signed, do not transition
      if ($signature->status !== SignatureStatus::SIGNED) {
        return;
      }

      $sppd = $signature->sppdRequest;
      $signerId = $signature->signer_id;

      // Check if there are other signatures for this SPPD by the same signer
      // that are still pending or processing
      $hasUnsigned = $sppd->digitalSignatures()
        ->where('signer_id', $signerId)
        ->whereIn('status', [SignatureStatus::PENDING, SignatureStatus::PROCESSING])
        ->exists();

      if (!$hasUnsigned) {
        // Check if any signatures failed for this signer
        $hasFailed = $sppd->digitalSignatures()
          ->where('signer_id', $signerId)
          ->where('status', SignatureStatus::REJECTED)
          ->exists();

        if (!$hasFailed) {
          // All signatures succeeded! Mark approval step as approved
          $approval = $sppd->approvals()
            ->where('approver_id', $signerId)
            ->where('status', ApprovalStatus::PENDING)
            ->first();

          if ($approval) {
            $approval->approve($this->notes);

            // Check if all approvals are completed
            $allApproved = $sppd->approvals()
              ->where('status', '!=', ApprovalStatus::APPROVED->value)
              ->doesntExist();

            if ($allApproved) {
              $sppd->update(['status' => SppdStatus::APPROVED]);

              $recipient = $sppd->creator ?? $sppd->user;
              if ($recipient && $recipient->canReceiveTelegram()) {
                $applicantName = $sppd->user?->name ?? 'Pegawai';
                $purpose = $sppd->purpose;
                $detailUrl = route('sppd.show', $sppd);
                $statusTitle = '✅ *STATUS: DOKUMEN DISETUJUI*';
                $body = "\nPengajuan SPPD atas nama *{$applicantName}* untuk perjalanan *\"{$purpose}\"* telah *DISETUJUI SEPENUHNYA* oleh semua pejabat penyetuju.";
                if ($this->notes) {
                  $body .= "\n\n• *Catatan:* {$this->notes}";
                }
                $message = "{$statusTitle}\n"
                  . "*────────────────────────────────*\n\n"
                  . "Halo *{$recipient->name}*,\n"
                  . "{$body}\n\n"
                  . "Silakan tinjau pengajuan Anda pada tautan berikut:\n"
                  . "🔗 {$detailUrl}\n\n"
                  . "Terima kasih.";

                SendTelegramNotificationJob::dispatch($recipient->telegram_chat_id, $message);
              }
            } else {
              // Find the next approval step and notify the next approver
              $nextApproval = $sppd->approvals()
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order', 'asc')
                ->where('status', ApprovalStatus::PENDING)
                ->first();

              if ($nextApproval && $nextApproval->approver && $nextApproval->approver->canReceiveTelegram()) {
                $approver = $nextApproval->approver;
                $applicantName = $sppd->user?->name ?? 'Pegawai';
                $purpose = $sppd->purpose;
                $startDate = Carbon::parse($sppd->start_date)->translatedFormat('d F Y');
                $endDate = Carbon::parse($sppd->end_date)->translatedFormat('d F Y');
                $detailUrl = route('sppd.show', $sppd);

                $message = "📝 *PENGAJUAN SPPD BARU*\n"
                  . "*────────────────────────────────*\n\n"
                  . "Halo *{$approver->name}*,\n"
                  . "Terdapat pengajuan SPPD baru yang memerlukan persetujuan Anda.\n\n"
                  . "• *Pelaksana:* {$applicantName}\n"
                  . "• *Maksud Perjalanan:* {$purpose}\n"
                  . "• *Tanggal:* {$startDate} s/d {$endDate}\n"
                  . "• *Peran Anda:* {$nextApproval->role_label}\n\n"
                  . "Silakan tinjau dan lakukan persetujuan melalui tautan berikut:\n"
                  . "🔗 {$detailUrl}\n\n"
                  . "Terima kasih.";

                SendTelegramNotificationJob::dispatch($approver->telegram_chat_id, $message);
              }
            }
          }
        }
      }
    });
  }
}
