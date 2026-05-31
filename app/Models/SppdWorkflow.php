<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\DepartmentType;
use App\Enums\SppdDomain;

class SppdWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department_type',
        'applicant_role',
        'destination',
        'steps',
        'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'department_type' => 'array',
        'destination' => 'array',
        'applicant_role' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the role designated to sign SPT.
     */
    public function getSptSignerRole(): ?string
    {
        if (empty($this->steps)) {
            return null;
        }

        foreach ($this->steps as $step) {
            if (is_array($step) && isset($step['signs_spt']) && $step['signs_spt']) {
                return $step['role'] ?? null;
            }
        }

        // Fallback for old format (array of strings): last step in the chain
        $lastStep = end($this->steps);
        return is_string($lastStep) ? $lastStep : ($lastStep['role'] ?? null);
    }

    /**
     * Get the role designated to sign SPPD.
     */
    public function getSppdSignerRole(?string $departmentType = null): ?string
    {
        if (empty($this->steps)) {
            return null;
        }

        foreach ($this->steps as $step) {
            if (is_array($step) && isset($step['signs_sppd']) && $step['signs_sppd']) {
                return $step['role'] ?? null;
            }
        }

        // Fallback for old format (array of strings)
        if ($departmentType === 'dprd') {
            return 'sekwan';
        }

        // Fallback to Kepala OPD if present in the steps, otherwise the last step
        $roles = [];
        foreach ($this->steps as $step) {
            $roles[] = is_string($step) ? $step : ($step['role'] ?? null);
        }

        if (in_array('kepala_opd', $roles)) {
            return 'kepala_opd';
        }

        $lastStep = end($this->steps);
        return is_string($lastStep) ? $lastStep : ($lastStep['role'] ?? null);
    }
}
