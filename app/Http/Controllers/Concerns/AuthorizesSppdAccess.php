<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SppdRequest;
use Illuminate\Support\Facades\Auth;

trait AuthorizesSppdAccess
{
    /**
     * Pastikan user yang sedang login berhak mengakses SPPD ini.
     * super_admin bebas; lainnya dibatasi hierarki department — konsisten dengan SppdController::index().
     */
    protected function authorizeSppdAccess(SppdRequest $sppd): void
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        $dept = $user->department;
        $allowedIds = $dept ? $dept->getScopedRelatedIds() : collect([$user->department_id]);

        abort_unless($allowedIds->contains($sppd->user->department_id), 403, 'Anda tidak memiliki akses ke SPPD ini.');
    }

    /**
     * Menu Selanjutnya (dokumen & biaya perjalanan): SPPD dalam lingkup, sudah disetujui
     * penuh, dan user memegang permission cost.view (cost.manage saja tidak cukup).
     */
    protected function authorizeCostMenu(SppdRequest $sppd): void
    {
        $this->authorizeSppdAccess($sppd);
        abort_unless(Auth::user()->canOpenCostMenu(), 403, 'Anda tidak memiliki akses ke menu Selanjutnya.');
        abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');
    }
}
