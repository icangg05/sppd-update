<?php

namespace App\Rules;

use App\Models\Department;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Keunikan NIP/NIK per instansi: seorang pegawai boleh terdaftar (berakun)
 * di dua instansi berbeda dengan NIP/NIK yang sama, tetapi tidak boleh ganda
 * di dalam satu instansi yang sama — OPD induk beserta unit kerja di bawahnya
 * dihitung satu instansi, kecuali zona mandiri (Puskesmas, Kelurahan) yang
 * menjadi instansi tersendiri. Akun dengan NIP sama dianggap orang yang sama
 * saat mengecek bentrok tanggal perjalanan (lihat SppdRequest::clashingForUsers).
 */
class UniqueIdentityInInstansi implements ValidationRule
{
  public function __construct(
    protected string $column,
    protected string $label,
    protected ?int $departmentId,
    protected ?int $ignoreUserId = null,
  ) {}

  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    if (empty($value) || ! $this->departmentId) {
      return;
    }

    $dept = Department::find($this->departmentId);
    if (! $dept) {
      return;
    }

    // Batas instansi sama dengan alur persetujuan: zona mandiri berdiri sendiri.
    $workflow = app(SppdWorkflowService::class);
    $instansiIds = $workflow->zoneIds($workflow->instansiRoot($dept));

    $existing = User::where($this->column, $value)
      ->whereIn('department_id', $instansiIds)
      ->when($this->ignoreUserId, fn ($q) => $q->where('id', '!=', $this->ignoreUserId))
      ->with('department')
      ->first();

    if ($existing) {
      $unit = $existing->department?->name ?? '-';
      $fail("{$this->label} {$value} sudah terdaftar atas nama {$existing->name} pada instansi/unit kerja {$unit}.");
    }
  }
}
