<?php

namespace App\Services;

use App\Models\SppdRequest;
use App\Models\SppdWorkflow;
use App\Models\User;
use App\Models\Department;
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
                $q->whereNull('destination')->orWhere('destination', $destination);
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
            $approverId = $this->resolveApproverId($stepRole, $pelaksana);

            if ($approverId) {
                $approvals[] = [
                    'sppd_request_id' => $sppd->id,
                    'approver_id'     => $approverId,
                    'role_label'      => ucwords(str_replace('_', ' ', $stepRole)),
                    'step_order'      => $stepOrder++,
                    'status'          => 'pending',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            } else {
                Log::warning("Could not resolve approver for role '{$stepRole}' for SPPD {$sppd->id}");
                $roleLabel = ucwords(str_replace('_', ' ', $stepRole));
                throw new \Exception("Tidak dapat menemukan pegawai dengan jabatan '{$roleLabel}' di instansi terkait. Proses dibatalkan karena alur persetujuan terputus.");
            }
        }

        if (!empty($approvals)) {
            // Delete existing just in case (e.g. if regenerating)
            $sppd->approvals()->delete();
            $sppd->approvals()->insert($approvals);
            return true;
        }

        return false;
    }

    /**
     * Resolves a role string into a specific User ID based on the applicant's context.
     */
    private function resolveApproverId(string $roleName, User $applicant): ?int
    {
        // 1. Try to find the role within the exact same department
        $user = User::role($roleName)
            ->where('department_id', $applicant->department_id)
            ->where('is_active', true)
            ->first();

        if ($user) {
            return $user->id;
        }

        // 2. If applicant has a parent department (e.g. Kelurahan -> Kecamatan), try parent department
        if ($applicant->department && $applicant->department->parent_id) {
            $user = User::role($roleName)
                ->where('department_id', $applicant->department->parent_id)
                ->where('is_active', true)
                ->first();

            if ($user) {
                return $user->id;
            }
        }

        // 3. Global lookup (e.g. Sekda, Walikota)
        $globalRoles = ['sekda', 'walikota', 'asisten', 'kepala_daerah'];
        if (in_array($roleName, $globalRoles)) {
            $user = User::role($roleName)->where('is_active', true)->first();
            return $user ? $user->id : null;
        }

        return null;
    }
}
