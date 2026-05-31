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
        if ($documentType === SignatureDocumentType::SPT->value) {
            $viewData = $this->buildSptData($sppd);
        } else {
            $viewData = $this->buildSppdData($sppd, $documentType);
        }

        $pdf = Pdf::loadView($viewData['view'], $viewData['data']);

        if ($documentType !== SignatureDocumentType::SPT->value) {
            $pdf->setPaper([0, 0, 935.43, 684.45]);
        } else {
            $pdf->setPaper('f4', 'portrait');
        }

        return $pdf->output();
    }

    protected function buildDraftFilename(SppdRequest $sppd, string $documentType): string
    {
        $slug = Str::slug($sppd->document_number ?: $sppd->id);
        return strtoupper($documentType) . '-' . $slug . '-' . now()->format('YmdHis') . '.pdf';
    }

    protected function buildSppdData(SppdRequest $sppd, string $documentType): array
    {
        $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank', 'approvals.approver']);
        $duration = $sppd->start_date->diffInDays($sppd->end_date) + 1;

        // Parse target user from document_type
        $targetUser = $sppd->user;
        $followerRecord = null;
        if (preg_match('/^sppd_(\d+)$/', $documentType, $matches)) {
            $userId = (int)$matches[1];
            if ($userId !== $sppd->user_id) {
                $followerRecord = $sppd->followers->first(fn($f) => $f->user_id === $userId);
                if ($followerRecord) {
                    $targetUser = $followerRecord->user;
                } else {
                    $targetUser = \App\Models\User::find($userId) ?? $sppd->user;
                }
            }
        }

        $isMain = $targetUser->id === $sppd->user_id;

        $isDprd = $targetUser->department?->type === \App\Enums\DepartmentType::DPRD
            || $targetUser->department?->parent?->type === \App\Enums\DepartmentType::DPRD;

        $sppdApproval = $this->resolveSppdApproval($sppd, $targetUser);

        $approver = $sppdApproval?->approver;
        $approverRole = $approver?->position_name ?? $approver?->position?->name ?? $sppdApproval?->role_label ?? 'Kepala Dinas';

        $pdfData = [
            'approver_name' => $approver?->name ?? '................................',
            'approver_role' => $approverRole,
            'approver_nip' => $approver?->nip ?? null,
            'approver_rank' => $approver?->rank?->name ?? '',
            'approver_group' => $approver?->rank?->group ?? '',
            'is_walikota' => false,
            'is_approved' => true, // Force true during TTE draft generation
            'qr_image' => null,
            'duration' => $duration,
            'is_follower' => !$isMain,
            'travel_position' => $followerRecord?->travel_position,
            'letterhead_url' => $targetUser->department?->getInheritedLetterhead()
        ];

        $verifyUrl = url('/verify/sppd/' . $sppd->id . '/' . md5($sppd->document_number . $targetUser->id));
        $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 50);

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
        $sptApproval = $this->resolveSptApproval($sppd);
        $duration = $sppd->start_date->diffInDays($sppd->end_date) + 1;

        $approver = $sptApproval?->approver;
        $approverRole = ($approver && $approver->isDprdMember() && $approver->dprd_jabatan)
            ? $approver->dprd_jabatan
            : ($approver?->position_name ?? $approver?->position?->name ?? $sptApproval?->role_label ?? 'Kepala Dinas');

        $isDprd = $sppd->user->department?->type === \App\Enums\DepartmentType::DPRD
            || $sppd->user->department?->parent?->type === \App\Enums\DepartmentType::DPRD;

        $pdfData = [
            'approver_name' => $approver?->name ?? '................................',
            'approver_role' => $approverRole,
            'approver_nip' => $approver?->nip ?? null,
            'approver_rank' => $approver?->rank?->name ?? '',
            'approver_group' => $approver?->rank?->group ?? '',
            'is_walikota' => $approver && $approver->hasRole('walikota'),
            'is_approved' => true, // Force true during TTE draft generation
            'qr_image' => null,
            'duration' => $duration,
            'letterhead_url' => $isDprd
                ? $sppd->user->department?->getInheritedLetterheadSecond()
                : $sppd->user->department?->getInheritedLetterhead()
        ];

        $verifyUrl = url('/verify/spt/' . $sppd->id . '/' . md5($sppd->document_number . $sppd->id));
        $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 65);

        $viewName = $isDprd ? 'exports.spt_dprd' : 'exports.spt';

        return [
            'view' => $viewName,
            'data' => [
                'sppd' => $sppd,
                'pdfData' => $pdfData,
            ],
        ];
    }

    private function resolveSptApproval(SppdRequest $sppd)
    {
        $approval = $sppd->approvals()->where('signs_spt', true)->first();
        if ($approval) {
            return $approval;
        }

        // Fallback: last step
        return $sppd->approvals()->reorder('step_order', 'desc')->first();
    }

    private function resolveSppdApproval(SppdRequest $sppd, ?\App\Models\User $targetUser = null)
    {
        $approval = $sppd->approvals()->where('signs_sppd', true)->first();
        if ($approval) {
            return $approval;
        }

        // Fallback: existing custom logic
        $targetUser = $targetUser ?? $sppd->user;
        $isDprd = $targetUser->department?->type === \App\Enums\DepartmentType::DPRD
            || $targetUser->department?->parent?->type === \App\Enums\DepartmentType::DPRD;

        if ($isDprd) {
            $sppdApproval = $sppd->approvals()
                ->with('approver.roles')
                ->get()
                ->first(function ($approval) {
                    return $approval->approver && $approval->approver->hasRole('sekwan');
                });
            if ($sppdApproval) {
                return $sppdApproval;
            }
        }

        $cityWideRoles = ['walikota', 'sekda', 'kepala_daerah'];
        $opdHeadApproval = $sppd->approvals()
            ->with('approver.roles')
            ->reorder('step_order', 'desc')
            ->get()
            ->first(function ($approval) use ($cityWideRoles) {
                return $approval->approver &&
                    !$approval->approver->roles->pluck('name')->intersect($cityWideRoles)->isNotEmpty();
            });

        return $opdHeadApproval ?? $sppd->approvals()->reorder('step_order', 'asc')->first();
    }
}
