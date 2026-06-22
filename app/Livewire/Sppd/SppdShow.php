<?php

namespace App\Livewire\Sppd;

use App\Enums\ApprovalStatus;
use App\Enums\SignatureDocumentType;
use App\Enums\SignatureStatus;
use App\Enums\SppdStatus;
use App\Jobs\SendTteSignRequestJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use App\Services\ApproverNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Throwable;

#[Layout('layouts.app')]
class SppdShow extends Component
{
  use InteractsWithToast;

  #[Locked]
  public int $sppdId;

  public string $approveNotes  = '';
  public string $passphrase    = '';
  public string $rejectNotes   = '';
  public string $revisionNotes = '';

  public bool $showDocumentModal = false;
  public bool $showApproveModal  = false;
  public bool $showRejectModal   = false;
  public bool $showRevisionModal = false;
  public bool $showPassphrase    = false;
  public bool $wasTteProcessing  = false;

  #[Url]
  public string $from = '';

  public function mount(SppdRequest $sppd): void
  {
    $this->sppdId = $sppd->id;
  }

  public function getSppd(): SppdRequest
  {
    return SppdRequest::with([
      'user.department',
      'creator',
      'reviser',
      'pptk',
      'budget.department',
      'category',
      'destinations.province',
      'destinations.regency',
      'followers.user',
      'approvals.approver',
      'costDetails',
      'actualExpenses',
      'advanceReceipts',
      'report',
      'digitalSignatures.signer',
    ])->findOrFail($this->sppdId);
  }

  public function approve(): void
  {
    $sppd = $this->getSppd();

    $approval = $sppd->approvals()
      ->where('approver_id', Auth::id())
      ->where('status', ApprovalStatus::PENDING)
      ->first();

    if (! $approval) {
      $this->showApproveModal = false;
      $this->toastError('Anda tidak memiliki hak untuk menyetujui SPPD ini.');
      return;
    }

    $hasUnapprovedPreviousSteps = $sppd->approvals()
      ->where('step_order', '<', $approval->step_order)
      ->where('status', '!=', ApprovalStatus::APPROVED->value)
      ->exists();

    if ($hasUnapprovedPreviousSteps) {
      $this->showApproveModal = false;
      $this->toastError('Anda belum dapat menyetujui dokumen ini karena langkah persetujuan sebelumnya belum selesai.');
      return;
    }

    $needsTte = $this->resolveNeedsTte($sppd, $approval);

    if ($needsTte && ! Auth::user()?->nik) {
      $this->showApproveModal = false;
      $this->toastError('NIK penandatangan belum tersedia. Silakan lengkapi profil Anda terlebih dahulu.');
      return;
    }

    if ($needsTte && empty($this->passphrase)) {
      $this->showApproveModal = false;
      $this->addError('passphrase', 'Passphrase wajib diisi untuk persetujuan dengan TTE.');
      return;
    }

    if ($needsTte && strlen($this->passphrase) < 4) {
      $this->showApproveModal = false;
      $this->addError('passphrase', 'Passphrase minimal 4 karakter.');
      return;
    }

    $sppd->update([
      'revision_note'  => null,
      'rejection_note' => null,
      'reviser_id'     => null,
    ]);

    if ($needsTte) {
      $this->processTteApproval($sppd, $approval);
      return;
    }

    $approval->approve($this->approveNotes ?: null);

    $allApproved = $sppd->approvals()->where('status', '!=', ApprovalStatus::APPROVED->value)->doesntExist();
    if ($allApproved) {
      $sppd->update(['status' => SppdStatus::APPROVED]);
      $this->notifyApplicant($sppd, 'approved', $this->approveNotes ?: null, Auth::user());
    } else {
      $nextApproval = $sppd->approvals()
        ->where('step_order', '>', $approval->step_order)
        ->orderBy('step_order', 'asc')
        ->where('status', ApprovalStatus::PENDING)
        ->first();
      if ($nextApproval) {
        $this->notifyApprover($sppd, $nextApproval);
      }
    }

    $this->approveNotes      = '';
    $this->passphrase        = '';
    $this->showApproveModal  = false;
    $this->refreshApprovalBadge();
    $this->toastSuccess('SPPD berhasil disetujui.');
  }

  public function reject(): void
  {
    $this->validate(['rejectNotes' => 'required|string|max:500']);

    $sppd = $this->getSppd();

    $approval = $sppd->approvals()
      ->where('approver_id', Auth::id())
      ->where('status', ApprovalStatus::PENDING)
      ->first();

    if (! $approval) {
      $this->toastError('Anda tidak memiliki hak untuk menolak SPPD ini.');
      $this->showRejectModal = false;
      return;
    }

    $approval->reject($this->rejectNotes);
    $sppd->update([
      'status'         => SppdStatus::REJECTED,
      'rejection_note' => $this->rejectNotes,
    ]);

    $this->notifyApplicant($sppd, 'rejected', $this->rejectNotes, Auth::user());

    $this->rejectNotes     = '';
    $this->showRejectModal = false;
    $this->refreshApprovalBadge();
    $this->toastSuccess('SPPD berhasil ditolak.');
  }

  public function revision(): void
  {
    $this->validate(['revisionNotes' => 'required|string|max:500']);

    $sppd = $this->getSppd();

    $approval = $sppd->approvals()
      ->where('approver_id', Auth::id())
      ->where('status', ApprovalStatus::PENDING)
      ->first();

    if (! $approval) {
      $this->toastError('Anda tidak memiliki hak untuk merevisi SPPD ini.');
      $this->showRevisionModal = false;
      return;
    }

    $sppd->update([
      'revision_note' => $this->revisionNotes,
      'reviser_id'    => Auth::id(),
    ]);

    $this->notifyApplicant($sppd, 'revision', $this->revisionNotes, Auth::user());

    $this->revisionNotes     = '';
    $this->showRevisionModal = false;
    $this->toastSuccess('Catatan revisi berhasil dikirim.');
  }

  protected function resolveNeedsTte(SppdRequest $sppd, $approval): bool
  {
    $hasAnyTteConfigured = $sppd->approvals->contains(fn($app) => $app->signs_spt || $app->signs_sppd);

    if ($hasAnyTteConfigured) {
      return (bool) $approval->signs_spt || (bool) $approval->signs_sppd;
    }

    // Fallback: final step signs everything
    return $approval->step_order === $sppd->approvals->max('step_order');
  }

  protected function processTteApproval(SppdRequest $sppd, $approval): void
  {
    $hasAnyTteConfigured = $sppd->approvals->contains(fn($app) => $app->signs_spt || $app->signs_sppd);
    $shouldSignSpt       = $hasAnyTteConfigured ? (bool) $approval->signs_spt : true;
    $shouldSignSppd      = $hasAnyTteConfigured ? (bool) $approval->signs_sppd : true;

    $documentsToSign = [];

    if ($shouldSignSpt) {
      $documentsToSign[] = [
        'type'   => 'spt',
        'coords' => $this->defaultSignatureCoordinates(SignatureDocumentType::SPT),
      ];
    }

    if ($shouldSignSppd) {
      $documentsToSign[] = [
        'type'   => 'sppd_' . $sppd->user_id,
        'coords' => $this->defaultSignatureCoordinates(SignatureDocumentType::SPPD),
      ];

      foreach ($sppd->followers as $follower) {
        $documentsToSign[] = [
          'type'   => 'sppd_' . $follower->user_id,
          'coords' => $this->defaultSignatureCoordinates(SignatureDocumentType::SPPD),
        ];
      }
    }

    $existingSignatures = $sppd->digitalSignatures()
      ->where('signer_id', Auth::id())
      ->get()
      ->keyBy('document_type');

    $jobs = [];
    foreach ($documentsToSign as $doc) {
      $existing = $existingSignatures->get($doc['type']);
      if ($existing && $existing->status === SignatureStatus::SIGNED) {
        continue;
      }

      $signature = $sppd->digitalSignatures()->updateOrCreate(
        [
          'signer_id'     => Auth::id(),
          'document_type' => $doc['type'],
        ],
        array_merge(
          [
            'sppd_request_id'  => $sppd->id,
            'signer_id'        => Auth::id(),
            'document_type'    => $doc['type'],
            'status'           => SignatureStatus::PROCESSING,
            'error_message'    => null,
            'signed_file_path' => null,
            'provider_name'    => config('tte.default_provider'),
          ],
          $doc['coords']
        )
      );

      $jobs[] = new SendTteSignRequestJob($signature, $this->passphrase, $this->approveNotes ?: null);
    }

    if (empty($jobs)) {
      // Semua dokumen sudah ditandatangani sebelumnya, langsung setujui langkah ini
      $approval->approve($this->approveNotes ?: null);
      $allApproved = $sppd->approvals()->where('status', '!=', ApprovalStatus::APPROVED->value)->doesntExist();
      if ($allApproved) {
        $sppd->update(['status' => SppdStatus::APPROVED]);
        $this->notifyApplicant($sppd, 'approved', $this->approveNotes ?: null, Auth::user());
      } else {
        $nextApproval = $sppd->approvals()
          ->where('step_order', '>', $approval->step_order)
          ->orderBy('step_order', 'asc')
          ->where('status', ApprovalStatus::PENDING)
          ->first();
        if ($nextApproval) {
          $this->notifyApprover($sppd, $nextApproval);
        }
      }
      $this->passphrase       = '';
      $this->approveNotes     = '';
      $this->showApproveModal = false;
      $this->refreshApprovalBadge();
      $this->toastSuccess('Seluruh dokumen telah berhasil ditandatangani.');
      return;
    }

    $sppdId   = $sppd->id;
    $signerId = Auth::id();
    Bus::chain($jobs)
      ->catch(function (Throwable $e) use ($sppdId, $signerId) {
        \App\Models\SppdDigitalSignature::where('sppd_request_id', $sppdId)
          ->where('signer_id', $signerId)
          ->where('status', SignatureStatus::PROCESSING)
          ->update([
            'status'        => SignatureStatus::REJECTED,
            'error_message' => $e->getMessage(),
          ]);
      })
      ->dispatch();

    $this->passphrase       = '';
    $this->approveNotes     = '';
    $this->showApproveModal = false;
    $this->toastSuccess('Persetujuan SPPD sedang diproses. Tanda tangan elektronik (TTE) yang belum ditandatangani sedang diproses di background.');
  }

  protected function refreshApprovalBadge(): void
  {
    $this->dispatch('approval-updated');
  }

  protected function defaultSignatureCoordinates(SignatureDocumentType $type): array
  {
    return match ($type) {
      SignatureDocumentType::SPT => [
        'page_number' => 1,
        'x'           => 300,
        'y'           => 100,
        'width'       => 160,
        'height'      => 60,
      ],
      SignatureDocumentType::SPPD => [
        'page_number' => 1,
        'x'           => 300,
        'y'           => 100,
        'width'       => 160,
        'height'      => 60,
      ],
    };
  }

  protected function notifyApplicant(SppdRequest $sppd, string $action, ?string $notes, $actor): void
  {
    try {
      $recipient = $sppd->creator ?? $sppd->user;
      if ($recipient && $recipient->phone && $recipient->phone_verified) {
        $purpose      = $sppd->purpose;
        $detailUrl    = route('sppd.show', $sppd);
        $actorName    = $actor?->name ?? 'Pejabat';
        $travelerName = $sppd->user?->name ?? '-';

        $statusTitle = '';
        $body        = '';

        if ($action === 'approved') {
          $statusTitle = '✅ *STATUS: DOKUMEN DISETUJUI*';
          $body        = "\nPengajuan SPPD Anda untuk perjalanan *\"{$purpose}\"* telah *DISETUJUI SEPENUHNYA* oleh semua pejabat penyetuju."
            . "\n\n• *Pelaksana:* {$travelerName}";
          if ($notes) {
            $body .= "\n• *Catatan:* {$notes}";
          }
        } elseif ($action === 'rejected') {
          $statusTitle = '❌ *STATUS: DOKUMEN DITOLAK*';
          $body        = "\nPengajuan SPPD Anda untuk perjalanan *\"{$purpose}\"* telah *DITOLAK* oleh *{$actorName}*.\n\n"
            . "• *Pelaksana:* {$travelerName}\n"
            . '• *Alasan Penolakan:* ' . ($notes ?? 'Tidak ada catatan khusus.');
        } elseif ($action === 'revision') {
          $statusTitle = '⚠️ *STATUS: DOKUMEN PERLU REVISI*';
          $body        = "\nPengajuan SPPD Anda untuk perjalanan *\"{$purpose}\"* memerlukan *REVISI* oleh *{$actorName}*.\n\n"
            . "• *Pelaksana:* {$travelerName}\n"
            . '• *Catatan Revisi:* ' . ($notes ?? 'Harap tinjau kembali data pengajuan Anda.');
        }

        $message = "{$statusTitle}\n"
          . "*────────────────────────────────*\n\n"
          . "Halo *{$recipient->name}*,\n"
          . "{$body}\n\n"
          . "Silakan tinjau pengajuan Anda pada tautan berikut:\n"
          . "🔗 {$detailUrl}\n\n"
          . 'Terima kasih.';

        SendWhatsAppNotificationJob::dispatch($recipient->phone, $message);
      }
    } catch (\Exception $e) {
      // Ignore notification errors
    }
  }

  protected function notifyApprover(SppdRequest $sppd, $approval): void
  {
    app(ApproverNotifier::class)->notifyNewRequest($sppd, $approval);
  }

  public function parseTteError(?string $rawError): array
  {
    if (empty($rawError)) {
      return [
        'type'    => 'sistem',
        'message' => 'Tanda tangan TTE gagal tanpa pesan error spesifik.',
        'icon'    => 'fa-circle-exclamation',
      ];
    }

    $cleanMsg = $rawError;

    // Bersihkan prefix common
    if (preg_match('/(?:Provider request failed:|Provider second request failed:)\s*(.*)/is', $rawError, $matches)) {
      $cleanMsg = trim($matches[1]);
    }

    // Coba decode JSON jika payload-nya berupa JSON string
    $decoded = json_decode($cleanMsg, true);
    if (is_array($decoded)) {
      if (isset($decoded['error'])) {
        $cleanMsg = $decoded['error'];
      } elseif (isset($decoded['message'])) {
        $cleanMsg = $decoded['message'];
      }

      // Kadang-kadang nested JSON
      $nestedDecoded = json_decode($cleanMsg, true);
      if (is_array($nestedDecoded)) {
        if (isset($nestedDecoded['error'])) {
          $cleanMsg = $nestedDecoded['error'];
        } elseif (isset($nestedDecoded['message'])) {
          $cleanMsg = $nestedDecoded['message'];
        }
      }
    }

    // Bersihkan lagi jika masih ada string JSON sisa
    if (is_string($cleanMsg)) {
      $cleanMsg = str_replace(['Provider request failed:', 'Provider second request failed:'], '', $cleanMsg);
      $cleanMsg = trim($cleanMsg, " \t\n\r\0\x0B\"'{}[]:");
    }

    // Klasifikasikan jenis error secara dinamis
    $lowerMsg = strtolower($cleanMsg);

    $isPassphrase = false;
    $isNik        = false;

    // Keywords untuk Passphrase salah
    $passphraseKeywords = [
      'passphrase',
      'password',
      'pin',
      'credential',
      'salah passphrase',
      'passphrase salah',
      'gagal mencocokkan',
      'match passphrase',
      'authentication failed',
      'wrong passphrase',
      'passphrase tidak sesuai',
      'passphrase salah',
      'kata sandi',
      'auth failed'
    ];

    // Keywords untuk NIK / Sertifikat bermasalah
    $nikKeywords = [
      'nik',
      'user tidak ditemukan',
      'tidak memiliki sertifikat',
      'sertifikat tidak ditemukan',
      'belum aktif',
      'expired',
      'revoked',
      'tidak terdaftar',
      'belum teregistrasi',
      'tidak berhak',
      'belum registrasi',
      'sertifikat belum diissue',
      'certificate not found',
      'user not found'
    ];

    foreach ($passphraseKeywords as $word) {
      if (str_contains($lowerMsg, $word)) {
        $isPassphrase = true;
        break;
      }
    }

    if (!$isPassphrase) {
      foreach ($nikKeywords as $word) {
        if (str_contains($lowerMsg, $word)) {
          $isNik = true;
          break;
        }
      }
    }

    if ($isPassphrase) {
      return [
        'type'    => 'passphrase',
        'message' => $cleanMsg ?: 'Passphrase salah.',
        'icon'    => 'fa-key',
      ];
    }

    if ($isNik) {
      return [
        'type'    => 'nik',
        'message' => $cleanMsg ?: 'NIK atau sertifikat elektronik bermasalah / belum terdaftar.',
        'icon'    => 'fa-id-card',
      ];
    }

    return [
      'type'    => 'sistem',
      'message' => $cleanMsg ?: 'Terjadi kesalahan sistem pada server BSrE.',
      'icon'    => 'fa-server',
    ];
  }

  public function cancelTte(): void
  {
    $sppd = $this->getSppd();

    $processingSignatures = $sppd->digitalSignatures()
      ->where('signer_id', Auth::id())
      ->where('status', SignatureStatus::PROCESSING)
      ->get();

    if ($processingSignatures->isEmpty()) {
      $this->toastError('Tidak ada proses TTE aktif yang dapat dibatalkan.');
      return;
    }

    foreach ($processingSignatures as $signature) {
      $signature->markError('Proses TTE dibatalkan oleh pengguna.');
    }

    $this->toastSuccess('Proses TTE berhasil dibatalkan.');
  }

  public function render()
  {
    $sppd = $this->getSppd();

    $needsTte   = false;
    $myApproval = $sppd->approvals
      ->where('approver_id', Auth::id())
      ->where('status', ApprovalStatus::PENDING)
      ->first();

    if ($myApproval) {
      $needsTte = $this->resolveNeedsTte($sppd, $myApproval);
    }

    // Check if TTE is processing - auto-poll if so
    $mySignatures = $sppd->digitalSignatures->where('signer_id', Auth::id());
    $isProcessing = $mySignatures
      ->contains(fn($sig) => $sig->status === SignatureStatus::PROCESSING);
    $hasFailedSignatures = $mySignatures
      ->contains(fn($sig) => $sig->status === SignatureStatus::REJECTED);

    if ($this->wasTteProcessing && ! $isProcessing) {
      $this->refreshApprovalBadge();
    }

    $this->wasTteProcessing = $isProcessing;

    return view('livewire.sppd.show', compact('sppd', 'needsTte', 'isProcessing', 'hasFailedSignatures'))
      ->title('Detail SPPD');
  }
}
