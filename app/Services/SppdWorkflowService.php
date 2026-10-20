<?php

namespace App\Services;

use App\Models\Department;
use App\Models\SppdRequest;
use App\Models\SppdWorkflow;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SppdWorkflowService
{
  /** Nilai pilihan "tidak ada pejabat ini" pada tahap yang boleh dilewati. */
  public const SKIP = 'skip';

  /**
   * Role yang boleh menjadi tahap persetujuan. Tingkat terendah adalah
   * Kabid/Irban/Kabag; Kasubag/Kasubid dan Staf tidak menyetujui SPPD.
   */
  public const APPROVER_ROLES = [
    'kabid_irban_kabag',
    'sekretaris_opd',
    'kepala_opd',
    'asisten',
    'sekda',
    'wakil_walikota',
    'walikota',
    'sekwan',
    'pimpinan_dprd',
  ];

  /** Role tingkat kota: dicari di seluruh sistem bila tidak ada di hierarki pemohon. */
  private const CITY_WIDE_ROLES = ['sekda', 'walikota', 'wakil_walikota'];

  /**
   * Workflow aktif yang berlaku bagi pelaksana dan domain perjalanan.
   * Urutan: paling spesifik (paling sedikit kriteria kosong) lebih dulu.
   */
  public function findWorkflow(User $pelaksana, ?string $destination): ?SppdWorkflow
  {
    $departmentType = $pelaksana->department?->type?->value;
    $role = $pelaksana->roles->first()?->name;

    return SppdWorkflow::where('is_active', true)
      ->where(function ($q) use ($departmentType) {
        $q->whereNull('department_type')->orWhereJsonContains('department_type', $departmentType);
      })
      ->where(function ($q) use ($role) {
        $q->whereNull('applicant_role')->orWhereJsonContains('applicant_role', $role);
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
      ->orderBy('id')
      ->first();
  }

  /**
   * Tahapan workflow dalam bentuk baku (format lama berupa array string ikut dinormalisasi).
   *
   * @return array<int, array{role:string, signs_spt:bool, signs_sppd:bool, optional:bool}>
   */
  public function normalizedSteps(SppdWorkflow $workflow): array
  {
    return collect($workflow->steps ?? [])->map(fn ($step) => [
      'role'       => is_array($step) ? (string) ($step['role'] ?? '') : (string) $step,
      'signs_spt'  => is_array($step) && filter_var($step['signs_spt'] ?? false, FILTER_VALIDATE_BOOLEAN),
      'signs_sppd' => is_array($step) && filter_var($step['signs_sppd'] ?? false, FILTER_VALIDATE_BOOLEAN),
      'optional'   => is_array($step) && filter_var($step['optional'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ])->all();
  }

  /**
   * Generate approval steps for an SPPD Request based on dynamic workflows.
   *
   * @param array<int|string, int|string|null> $choices Pejabat pilihan pembuat SPPD per indeks tahap
   *   (id user, atau self::SKIP untuk tahap opsional) — wajib untuk tahap berstatus "choose".
   * @return bool True if workflow successfully applied, False if no matching workflow found.
   */
  public function generateApprovals(SppdRequest $sppd, array $choices = []): bool
  {
    $pelaksana = $sppd->user;
    $workflow = $this->findWorkflow($pelaksana, $sppd->domain?->value);

    if (!$workflow || empty($workflow->steps)) {
      Log::warning("No matching workflow found for SPPD {$sppd->id}");
      return false;
    }

    $stepOrder = 1;
    $approvals = [];

    foreach ($this->normalizedSteps($workflow) as $index => $step) {
      if ($step['role'] === '') {
        continue;
      }

      $resolved = $this->resolveStep($step['role'], $pelaksana, $step['optional']);

      $approver = match ($resolved['status']) {
        'found'   => $resolved['approver'],
        'choose'  => $this->chosenApprover($resolved['candidates'], $choices[$index] ?? null, $step),
        'skipped' => null,
        default   => $this->failMissingApprover($step['role'], $sppd),
      };

      if (! $approver) {
        continue;
      }

      $approvals[] = [
        'approver_id' => $approver->id,
        'role_label'  => $this->getApproverLabel($approver, $step['role']),
        'step_order'  => $stepOrder++,
        'status'      => 'pending',
        'signs_spt'   => $step['signs_spt'],
        'signs_sppd'  => $step['signs_sppd'],
      ];
    }

    if (empty($approvals)) {
      return false;
    }

    // Delete existing just in case (e.g. if regenerating)
    $sppd->approvals()->delete();
    $sppd->approvals()->createMany($approvals);

    return true;
  }

  /**
   * Simulate approval steps for preview purposes without saving.
   * Tahap opsional yang tidak berlaku bagi pemohon tidak ikut ditampilkan.
   */
  public function simulateApprovals(User $pelaksana, ?string $destination = null): array
  {
    $workflow = $this->findWorkflow($pelaksana, $destination);

    if (!$workflow || empty($workflow->steps)) {
      return [];
    }

    $simulatedSteps = [];
    foreach ($this->normalizedSteps($workflow) as $index => $step) {
      if ($step['role'] === '') {
        continue;
      }

      $resolved = $this->resolveStep($step['role'], $pelaksana, $step['optional']);
      if ($resolved['status'] === 'skipped') {
        continue;
      }

      $approver = $resolved['approver'];
      $simulatedSteps[] = [
        'index'         => $index,
        'role'          => $step['role'],
        'role_label'    => $this->getApproverLabel($approver ?? new User(), $step['role']),
        'approver_id'   => $approver?->id,
        'approver_name' => match ($resolved['status']) {
          'found'  => $approver->name,
          'choose' => 'Dipilih saat pengajuan',
          default  => 'Pejabat tidak ditemukan',
        },
        'status'        => $resolved['status'],
        'optional'      => $step['optional'],
        'candidates'    => $resolved['candidates']->map(fn (User $u) => [
          'id'   => $u->id,
          'name' => $u->name,
          'unit' => $u->department?->name,
        ])->values()->all(),
        'signs_spt'     => $step['signs_spt'],
        'signs_sppd'    => $step['signs_sppd'],
      ];
    }

    return $simulatedSteps;
  }

  /**
   * Resolves a role string into a specific User based on the applicant's context.
   * Hanya mengembalikan pejabat yang pasti; tahap yang perlu dipilih menghasilkan null.
   */
  public function resolveApprover(string $roleName, User $applicant): ?User
  {
    $resolved = $this->resolveStep($roleName, $applicant);

    return $resolved['status'] === 'found' ? $resolved['approver'] : null;
  }

  /**
   * Tentukan pejabat satu tahap bagi pemohon.
   *
   * Dicari naik dari unit kerja pemohon. Bila struktur menunjuk tepat satu
   * pejabat, langsung dipakai. Bila ada beberapa kandidat — mis. beberapa Kabid
   * atau Asisten tercatat langsung di unit induk karena strukturnya datar —
   * pembuat SPPD memilih. Tahap opsional (Kabid/Irban/Kabag) yang tidak ada di
   * instansi pemohon dilewati: alur tidak harus dimulai dari tingkat terendah.
   *
   * @return array{status: string, approver: ?User, candidates: Collection<int, User>}
   *   status: found | choose | skipped | not_found
   */
  public function resolveStep(string $roleName, User $applicant, bool $optional = false): array
  {
    $roles = $this->getRoleSynonyms($roleName);
    $root = $this->instansiRoot($applicant->department);
    $currentDept = $applicant->department;

    // Trace up the hierarchy
    while ($currentDept) {
      $holders = $this->holders($roles, [$currentDept->id]);

      if ($holders->isNotEmpty()) {
        // Pejabat tunggal di sub-unit pemohon, atau role yang memang satu per unit,
        // pasti atasannya. Kabid tunggal di unit induk yang datar belum tentu
        // atasan pemohon (bisa staf Sekretariat), jadi tetap dikonfirmasi.
        $certain = $holders->count() === 1
          && (! $optional || $this->isUniquePerUnit($roleName) || $currentDept->id !== $root?->id);

        return $certain
          ? $this->resolution('found', $holders->first())
          : $this->resolution('choose', null, $holders);
      }

      // Zona mandiri (kelurahan/puskesmas) tidak mencari penyetuju ke unit
      // induknya: pejabat yang kosong harus diisi, bukan dialihkan diam-diam
      // ke Sekcam/Camat atau Dinas Kesehatan. Role tingkat kota tetap dicari global di bawah.
      if ($this->stopsClimbing($currentDept)) {
        break;
      }

      // Move up to the parent department
      $currentDept = $currentDept->parent;
    }

    if (in_array($roleName, self::CITY_WIDE_ROLES, true)) {
      $user = User::whereHas('roles', fn ($q) => $q->whereIn('name', $roles))->where('is_active', true)->first();
      if ($user) {
        return $this->resolution('found', $user);
      }
    }

    // Pejabat di unit lain dalam instansi yang sama, mis. Kabag yang tercatat di
    // unit Bagian sementara pemohon tercatat langsung di Sekretariat Daerah.
    if ($root) {
      $holders = $this->holders($roles, $this->zoneIds($root));

      if ($holders->count() === 1 && ! $optional) {
        return $this->resolution('found', $holders->first());
      }
      if ($holders->isNotEmpty()) {
        return $this->resolution('choose', null, $holders);
      }
    }

    return $this->resolution($optional ? 'skipped' : 'not_found');
  }

  /**
   * Apakah role berperan di workflow aktif untuk jenis unit kerja tertentu —
   * sebagai pemohon maupun penyetuju. Dipakai untuk menurunkan role default
   * jabatan: role yang tidak dikenal alur unitnya membuat pegawai tidak bisa
   * mengajukan SPPD, jadi pegawai tersebut cukup memakai Staf.
   */
  public function roleUsedInWorkflows(string $role, ?string $departmentType): bool
  {
    return SppdWorkflow::where('is_active', true)
      ->get(['department_type', 'applicant_role', 'steps'])
      ->contains(function (SppdWorkflow $workflow) use ($role, $departmentType) {
        $types = $workflow->department_type;
        if (! empty($types) && ! in_array($departmentType, $types, true)) {
          return false;
        }

        if (in_array($role, $workflow->applicant_role ?? [], true)) {
          return true;
        }

        foreach ($workflow->steps ?? [] as $step) {
          if ((is_array($step) ? ($step['role'] ?? null) : $step) === $role) {
            return true;
          }
        }

        return false;
      });
  }

  /**
   * Role lain yang setara dengan role tahap. Camat, Lurah, Kepala Puskesmas, dan
   * Sekcam umumnya memakai role Kepala/Sekretaris OPD, tetapi tetap dikenali
   * bila suatu saat diberi role khususnya.
   */
  public function getRoleSynonyms(string $role): array
  {
    $synonyms = [
      'kepala_opd'     => ['kepala_opd', 'camat', 'lurah', 'kapus'],
      'sekretaris_opd' => ['sekretaris_opd', 'sekcam'],
    ];

    return $synonyms[$role] ?? [$role];
  }

  /** Label role untuk ditampilkan (mis. "Kabid / Irban / Kabag"). */
  public function roleLabel(string $role): string
  {
    return Role::where('name', $role)->value('label') ?: ucwords(str_replace('_', ' ', $role));
  }

  private function getApproverLabel(User $approver, string $requestedRole): string
  {
    // Try to get the actual role label from the user that matched the synonym
    $synonyms = $this->getRoleSynonyms($requestedRole);
    $actualRole = $approver->roles->whereIn('name', $synonyms)->first();

    if ($actualRole) {
      return $actualRole->label ?? ucwords(str_replace('_', ' ', $actualRole->name));
    }

    return $this->roleLabel($requestedRole);
  }

  /** @return array{status: string, approver: ?User, candidates: Collection<int, User>} */
  private function resolution(string $status, ?User $approver = null, ?Collection $candidates = null): array
  {
    return ['status' => $status, 'approver' => $approver, 'candidates' => $candidates ?? collect()];
  }

  /** Pemegang aktif salah satu role di unit kerja tertentu. */
  private function holders(array $roles, array $departmentIds): Collection
  {
    return User::whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
      ->whereIn('department_id', $departmentIds)
      ->where('is_active', true)
      ->with(['department:id,name', 'roles'])
      ->orderBy('name')
      ->get();
  }

  /** Pejabat pilihan pembuat SPPD untuk tahap berstatus "choose"; null bila tahap dilewati. */
  private function chosenApprover(Collection $candidates, int|string|null $choice, array $step): ?User
  {
    if ($step['optional'] && $choice === self::SKIP) {
      return null;
    }

    $approver = $candidates->firstWhere('id', (int) $choice);
    if (! $approver) {
      throw new \Exception("Pilih pejabat {$this->roleLabel($step['role'])} pada alur persetujuan terlebih dahulu.");
    }

    return $approver;
  }

  private function failMissingApprover(string $stepRole, SppdRequest $sppd): never
  {
    Log::warning("Could not resolve approver for role '{$stepRole}' for SPPD {$sppd->id}");

    $message = "Gagal Mengajukan: Pejabat dengan jabatan '{$this->roleLabel($stepRole)}' tidak ditemukan di unit kerja Anda atau unit kerja induk. ";
    $message .= "Harap hubungi Admin untuk melengkapi data Pejabat di bidang Anda terlebih dahulu agar alur pengajuan tidak terputus.";

    throw new \Exception($message);
  }

  /** Role yang hanya boleh dipegang satu orang per unit/sistem (config role_uniqueness). */
  private function isUniquePerUnit(string $role): bool
  {
    return in_array($role, config('role_uniqueness.department', []), true)
      || in_array($role, config('role_uniqueness.system', []), true);
  }

  /** Zona mandiri berhenti di dirinya sendiri; tidak naik ke unit induk berjenis lain. */
  private function stopsClimbing(Department $dept): bool
  {
    return (bool) $dept->type?->isIndependentZone() && $dept->parent?->type !== $dept->type;
  }

  /** Unit teratas instansi pemohon (OPD, Setda, Kecamatan, atau zona mandiri itu sendiri). */
  private function instansiRoot(?Department $dept): ?Department
  {
    while ($dept && $dept->parent && ! $this->stopsClimbing($dept)) {
      $dept = $dept->parent;
    }

    return $dept;
  }

  /**
   * ID unit kerja dalam satu instansi: induk beserta turunannya, tanpa masuk ke
   * zona mandiri di bawahnya (Kelurahan, Puskesmas) yang punya pejabatnya sendiri.
   *
   * @return array<int, int>
   */
  private function zoneIds(Department $root): array
  {
    $ids = [$root->id];
    $types = [$root->id => $root->type];
    $frontier = [$root->id];

    while ($frontier) {
      $next = [];
      foreach (Department::whereIn('parent_id', $frontier)->get(['id', 'parent_id', 'type']) as $child) {
        if ($child->type?->isIndependentZone() && $child->type !== $types[$child->parent_id]) {
          continue;
        }

        $ids[] = $child->id;
        $types[$child->id] = $child->type;
        $next[] = $child->id;
      }
      $frontier = $next;
    }

    return $ids;
  }
}
