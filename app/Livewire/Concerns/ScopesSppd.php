<?php

namespace App\Livewire\Concerns;

use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pembatasan data SPPD sesuai lingkup OPD user (root OPD + seluruh turunannya).
 * Logika selaras dengan DashboardController::scopedSppd()/budgetScopeIds().
 */
trait ScopesSppd
{
  /**
   * ID departemen dalam lingkup user (root OPD + seluruh turunannya).
   * null = seluruh sistem (super_admin).
   */
  protected function scopeDepartmentIds(User $user): ?Collection
  {
    if ($user->hasRole('super_admin')) {
      return null;
    }

    return $user->department?->getScopeRootDepartment()->getScopedRelatedIds() ?? collect([0]);
  }

  /**
   * Query dasar SPPD sesuai lingkup: super_admin = semua,
   * selain itu = pengaju yang berada dalam lingkup OPD user.
   */
  protected function scopedSppd(User $user): Builder
  {
    $ids = $this->scopeDepartmentIds($user);

    if ($ids === null) {
      return SppdRequest::query();
    }

    return SppdRequest::whereHas('user', fn (Builder $q) => $q->whereIn('department_id', $ids));
  }
}
