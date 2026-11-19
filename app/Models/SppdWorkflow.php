<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use Illuminate\Support\Collection;

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
     * Alur aktif lain yang cakupannya beririsan dengan cakupan ini, beserta irisannya
     * (tipe instansi × peran pemohon × wilayah). Pada irisan itu sistem hanya memakai alur
     * yang dibuat lebih dulu (lihat SppdWorkflowService::findWorkflow), jadi alur lainnya
     * tidak pernah terpakai di sana. Null pada data lama berarti berlaku untuk semua.
     *
     * @return Collection<int, array{workflow: self, department_type: ?array, applicant_role: ?array, destination: ?array}>
     */
    public static function overlapping(?array $departmentType, ?array $applicantRole, ?array $destination, ?int $exceptId = null): Collection
    {
        $intersect = fn (?array $a, ?array $b) => match (true) {
            $a === null => $b,
            $b === null => $a,
            default => array_values(array_intersect($a, $b)),
        };

        return static::where('is_active', true)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('id')
            ->get()
            ->map(fn (self $w) => [
                'workflow'        => $w,
                'department_type' => $intersect($departmentType, $w->department_type),
                'applicant_role'  => $intersect($applicantRole, $w->applicant_role),
                'destination'     => $intersect($destination, $w->destination),
            ])
            ->filter(fn (array $o) => $o['department_type'] !== [] && $o['applicant_role'] !== [] && $o['destination'] !== [])
            ->values();
    }

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
