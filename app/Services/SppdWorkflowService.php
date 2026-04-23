<?php

namespace App\Services;

use App\Models\SppdRequest;
use App\Models\SppdWorkflow;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SppdWorkflowService
{
  /**
   * Generate approval steps for an SPPD Request based on dynamic workflows.
   *
   * @param SppdRequest $sppd
   * @return bool True if workflow successfully applied, False if no matching workflow found.
   */
  public function generateApprovals(SppdRequest $sppd): bool
  {
    $pelaksana = $sppd->user;
    $departmentType = $pelaksana->department?->type?->value;
    $role = $pelaksana->roles->first()?->name;
    $destination = $sppd->domain?->value;

    // Find the best matching workflow
    // Order by most specific (least nulls) first
    $workflow = SppdWorkflow::where('is_active', true)
      ->where(function ($q) use ($departmentType) {
        $q->whereNull('department_type')->orWhere('department_type', $departmentType);
      })
      ->where(function ($q) use ($role) {
        $q->whereNull('applicant_role')->orWhere('applicant_role', $role);
      })
      ->where(function ($q) use ($destination) {
        $q->whereNull('destination')->orWhereJsonContains('destination', $destination);
      })
      ->orderByRaw('
                (department_type IS NOT NULL) +
                (applicant_role IS NOT NULL) +
                (destination IS NOT NULL) DESC
            ')
      ->first();

    if (!$workflow || empty($workflow->steps)) {
      Log::warning("No matching workflow found for SPPD {$sppd->id}");
      return false;
    }

    $stepOrder = 1;
    $approvals = [];

    foreach ($workflow->steps as $stepRole) {
      $approver = $this->resolveApprover($stepRole, $pelaksana);

      if ($approver) {
        $approvals[] = [
          'sppd_request_id' => $sppd->id,
          'approver_id'     => $approver->id,
          'role_label'      => $this->getApproverLabel($approver, $stepRole),
          'step_order'      => $stepOrder++,
          'status'          => 'pending',
          'created_at'      => now(),
          'updated_at'      => now(),
        ];
      } else {
        Log::warning("Could not resolve approver for role '{$stepRole}' for SPPD {$sppd->id}");
        $roleLabel = ucwords(str_replace('_', ' ', $stepRole));

        $message = "Gagal Mengajukan: Pejabat dengan jabatan '{$roleLabel}' tidak ditemukan di unit kerja Anda atau unit kerja induk. ";
        $message .= "Harap hubungi Admin untuk melengkapi data Pejabat di bidang Anda terlebih dahulu agar alur pengajuan tidak terputus.";

        throw new \Exception($message);
      }
    }

    if (!empty($approvals)) {
      // Delete existing just in case (e.g. if regenerating)
      $sppd->approvals()->delete();
      $sppd->approvals()->insert($approvals);
      return true;
    }
  }

  /**
   * Simulate approval steps for preview purposes without saving.
   */
  public function simulateApprovals(User $pelaksana, string $destination = null): array
  {
    $departmentType = $pelaksana->department?->type?->value;
    $role = $pelaksana->roles->first()?->name;

    $workflow = SppdWorkflow::where('is_active', true)
      ->where(function ($q) use ($departmentType) {
        $q->whereNull('department_type')->orWhere('department_type', $departmentType);
      })
      ->where(function ($q) use ($role) {
        $q->whereNull('applicant_role')->orWhere('applicant_role', $role);
      })
      ->where(function ($q) use ($destination) {
        if ($destination) {
          $q->whereNull('destination')->orWhereJsonContains('destination', $destination);
        } else {
          $q->whereNull('destination');
        }
      })
      ->orderByRaw('
                (department_type IS NOT NULL) +
                (applicant_role IS NOT NULL) +
                (destination IS NOT NULL) DESC
            ')
      ->first();

    if (!$workflow || empty($workflow->steps)) {
      return [];
    }

    $simulatedSteps = [];
    foreach ($workflow->steps as $stepRole) {
      $approver = $this->resolveApprover($stepRole, $pelaksana);
      $simulatedSteps[] = [
        'role' => $stepRole,
        'role_label' => $this->getApproverLabel($approver ?? new User(), $stepRole),
        'approver_name' => $approver ? $approver->name : 'Pejabat tidak ditemukan',
        'status' => $approver ? 'found' : 'not_found'
      ];
    }

    return $simulatedSteps;
  }

  /**
   * Resolves a role string into a specific User based on the applicant's context.
   * Searches recursively up the organizational tree.
   */
  public function resolveApprover(string $roleName, User $applicant): ?User
  {
    $searchRoles = $this->getRoleSynonyms($roleName);
    $currentDept = $applicant->department;

    // Trace up the hierarchy
    while ($currentDept) {
      $user = User::role($searchRoles)
        ->where('department_id', $currentDept->id)
        ->where('is_active', true)
        ->first();

      if ($user) {
        return $user;
      }

      // Move up to the parent department
      $currentDept = $currentDept->parent;
    }

    // Global lookup for truly unique city-wide roles if not found in hierarchy
    $globalRoles = ['sekda', 'walikota', 'kepala_daerah'];
    if (in_array($roleName, $globalRoles)) {
      return User::role($roleName)->where('is_active', true)->first();
    }

    return null;
  }

  public function getRoleSynonyms(string $role): array
  {
    $synonyms = [
      'kabid' => ['kabid', 'irban', 'kabag'],
      'kasubag' => ['kasubag', 'kasi', 'kepala_uptd'],
    ];

    return $synonyms[$role] ?? [$role];
  }

  private function getApproverLabel(User $approver, string $requestedRole): string
  {
    // Try to get the actual role name from the user that matched the synonym
    $synonyms = $this->getRoleSynonyms($requestedRole);
    $actualRole = $approver->roles->whereIn('name', $synonyms)->first();

    if ($actualRole) {
      return ucwords(str_replace('_', ' ', $actualRole->name));
    }

    return ucwords(str_replace('_', ' ', $requestedRole));
  }
}
