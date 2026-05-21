<?php

namespace App\Services\Tte;

use App\Enums\SignatureDocumentType;
use App\Helpers\QrSimulator;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfGeneratorService
{
    public function generateDraft(SppdDigitalSignature $signature): string
    {
        $sppd = $signature->sppdRequest;
        $filename = $this->buildDraftFilename($sppd, $signature->document_type);
        $relativePath = config('tte.storage.paths.draft') . '/' . $filename;
        $disk = Storage::disk(config('tte.storage.disk'));

        $directory = config('tte.storage.paths.draft');
        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $pdf = $this->renderDraftPdf($sppd, $signature->document_type);
        $disk->put($relativePath, $pdf);

        return $disk->path($relativePath);
    }

    protected function renderDraftPdf(SppdRequest $sppd, string $documentType): string
    {
        $viewData = match ($documentType) {
            SignatureDocumentType::SPPD->value => $this->buildSppdData($sppd),
            SignatureDocumentType::SPT->value => $this->buildSptData($sppd),
            default => $this->buildSppdData($sppd),
        };

        $pdf = Pdf::loadView($viewData['view'], $viewData['data']);

        if ($documentType === SignatureDocumentType::SPPD->value) {
            $pdf->setPaper([0, 0, 935.43, 684.45]);
        }

        if ($documentType === SignatureDocumentType::SPT->value) {
            $pdf->setPaper('f4', 'portrait');
        }

        return $pdf->output();
    }

    protected function buildDraftFilename(SppdRequest $sppd, string $documentType): string
    {
        $slug = Str::slug($sppd->document_number ?: $sppd->id);
        return strtoupper($documentType) . '-' . $slug . '-' . now()->format('YmdHis') . '.pdf';
    }

    protected function buildSppdData(SppdRequest $sppd): array
    {
        $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank', 'approvals.approver']);
        $duration = $sppd->start_date->diffInDays($sppd->end_date) + 1;

        $targetUser = $sppd->user;
        $isMain = true;

        $cityWideRoles = ['walikota', 'sekda', 'kepala_daerah'];
        $opdHeadApproval = $sppd->approvals()
            ->with('approver.roles')
            ->reorder('step_order', 'desc')
            ->get()
            ->first(function ($approval) use ($cityWideRoles) {
                return $approval->approver && !$approval->approver->roles->pluck('name')->intersect($cityWideRoles)->isNotEmpty();
            });

        $sppdApproval = $opdHeadApproval ?? $sppd->approvals()->reorder('step_order', 'asc')->first();
        $approver = $sppdApproval?->approver;
        $approverRole = $approver?->position_name ?? $approver?->position?->name ?? $sppdApproval?->role_label ?? 'Kepala Dinas';

        $pdfData = [
            'approver_name' => $approver?->name ?? '................................',
            'approver_role' => $approverRole,
            'approver_nip' => $approver?->nip ?? null,
            'approver_rank' => $approver?->rank?->name ?? '',
            'approver_group' => $approver?->rank?->group ?? '',
            'is_walikota' => false,
            'is_approved' => in_array($sppd->status->value, ['approved', 'completed']),
            'qr_image' => null,
            'duration' => $duration,
        ];

        if ($pdfData['is_approved']) {
            $verifyUrl = url('/verify/sppd/' . $sppd->id . '/' . md5($sppd->document_number . $targetUser->id));
            $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 50);
        }

        return [
            'view' => 'exports.sppd',
            'data' => [
                'sppd' => $sppd,
                'user' => $targetUser,
                'is_main' => $isMain,
                'pdfData' => $pdfData,
            ],
        ];
    }

    protected function buildSptData(SppdRequest $sppd): array
    {
        $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank']);
        $lastApproval = $sppd->approvals()->reorder('step_order', 'desc')->first();
        $duration = $sppd->start_date->diffInDays($sppd->end_date) + 1;

        $approver = $lastApproval?->approver;
        $approverRole = $approver?->position_name ?? $approver?->position?->name ?? $lastApproval?->role_label ?? 'Kepala Dinas';

        $pdfData = [
            'approver_name' => $approver->name ?? '................................',
            'approver_role' => $approverRole,
            'approver_nip' => $approver->nip ?? null,
            'approver_rank' => $approver->rank->name ?? '',
            'approver_group' => $approver->rank->group ?? '',
            'is_walikota' => $approver && $approver->hasRole('walikota'),
            'is_approved' => in_array($sppd->status->value, ['approved', 'completed']),
            'qr_image' => null,
            'duration' => $duration,
        ];

        if ($pdfData['is_approved']) {
            $verifyUrl = url('/verify/spt/' . $sppd->id . '/' . md5($sppd->document_number . $sppd->id));
            $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 65);
        }

        return [
            'view' => 'exports.spt',
            'data' => [
                'sppd' => $sppd,
                'pdfData' => $pdfData,
            ],
        ];
    }
}
