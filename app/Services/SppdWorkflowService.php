<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\Position;
use App\Models\SppdRequest;
use App\Models\SppdWorkflow;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SppdWorkflowService
{
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
  public const CITY_WIDE_ROLES = ['sekda', 'walikota', 'wakil_walikota'];

  /**
   * Role yang bisa dijabat beberapa orang di satu instansi: pejabat tunggal yang
   * tercatat di unit induk yang datar belum tentu atasan pemohon, jadi dikonfirmasi.
   */
  private const CONFIRM_ROLES = ['kabid_irban_kabag'];

  /** Batas panjang rantai atasan yang ditelusuri. */
  private const CHAIN_DEPTH = 8;

  /** @var array<int, array{links: array, open: bool}> Rantai atasan per pegawai (per instance). */
  private array $chainCache = [];

  /** Workflow aktif yang berlaku bagi pelaksana dan domain perjalanan. */
  public function findWorkflow(User $pelaksana, ?string $destination): ?SppdWorkflow
  {
    return $this->matchWorkflow($pelaksana, $destination)['workflow'];
  }

  /**
   * Workflow untuk pelaksana. Urutan: paling spesifik lebih dulu. Alur yang
   * membedakan atasan langsung pemohon hanya cocok bila atasan efektifnya sudah
   * pasti; bila belum, needs_supervisor = true agar atasannya diatur lebih dulu.
   *
   * @return array{workflow: ?SppdWorkflow, needs_supervisor: bool, supervisor_role: ?string, supervisor_roles: string[], preview: ?SppdWorkflow}
   *   supervisor_roles: role atasan dari semua alur yang membedakan atasan;
   *   preview: alur yang ditampilkan, yaitu workflow, atau bila atasan belum pasti alur
   *   terpanjang (atasan berperingkat paling rendah) agar tahap atasannya bisa dipilih.
   */
  public function matchWorkflow(User $pelaksana, ?string $destination): array
  {
    $departmentType = $pelaksana->department?->type?->value;
    $role = $pelaksana->roles->first()?->name;

    $workflows = SppdWorkflow::where('is_active', true)
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
                (destination IS NOT NULL) +
                (supervisor_role IS NOT NULL) DESC
            ')
      ->orderBy('id')
      ->get();

    $bySupervisor = $workflows->filter(fn (SppdWorkflow $w) => ! empty($w->supervisor_role));
    $differentiates = $bySupervisor->isNotEmpty();
    $level = fn (SppdWorkflow $w) => collect($w->supervisor_role)->map(fn (string $r) => Position::levelForRole($r))->min();
    $effective = $differentiates ? $this->effectiveSupervisor($pelaksana) : ['status' => 'none', 'user' => null];
    $supervisorRole = $effective['user'] ? $this->approverRoleOf($effective['user']) : null;

    $workflow = $workflows->first(fn (SppdWorkflow $w) => empty($w->supervisor_role)
      || ($supervisorRole !== null && in_array($supervisorRole, $w->supervisor_role, true)));

    // Pejabat di antara pegawai dan atasannya belum terdaftar (atau belum ada pejabat sama
    // sekali di atasnya): pakai alur dengan atasan tertinggi yang masih di bawah atasan
    // sebenarnya, mis. Sekretaris → Kepala bila Sekretaris belum ada. Tahap yang kosong
    // tetap wajib dilengkapi sebelum SPPD dibuat. Atasan yang ambigu harus diatur dulu.
    if (! $workflow && $differentiates && $effective['status'] !== 'unknown') {
      $ceiling = $supervisorRole ? Position::levelForRole($supervisorRole) : PHP_INT_MIN;
      $workflow = $bySupervisor->filter(fn (SppdWorkflow $w) => $level($w) > $ceiling)->sortBy($level)->first();
    }

    $needsSupervisor = ! $workflow && $differentiates && $supervisorRole === null;

    return [
      'workflow'         => $workflow,
      'needs_supervisor' => $needsSupervisor,
      'supervisor_role'  => $supervisorRole,
      'supervisor_roles' => $bySupervisor->flatMap(fn (SppdWorkflow $w) => $w->supervisor_role)->unique()->values()->all(),
      'preview'          => $needsSupervisor ? $bySupervisor->sortByDesc($level)->first() : $workflow,
    ];
  }

  /**
   * Tahapan workflow dalam bentuk baku (format lama berupa array string ikut dinormalisasi).
   * Semua tahap wajib dijalankan.
   *
   * @return array<int, array{role:string, signs_spt:bool, signs_sppd:bool}>
   */
  public function normalizedSteps(SppdWorkflow $workflow): array
  {
    return collect($workflow->steps ?? [])->map(fn ($step) => [
      'role'       => is_array($step) ? (string) ($step['role'] ?? '') : (string) $step,
      'signs_spt'  => is_array($step) && filter_var($step['signs_spt'] ?? false, FILTER_VALIDATE_BOOLEAN),
      'signs_sppd' => is_array($step) && filter_var($step['signs_sppd'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ])->all();
  }

  /** Alur lengkap: ada tahap dan setiap tahap sudah punya pejabat pasti. */
  public static function isComplete(array $steps): bool
  {
    return $steps !== [] && collect($steps)->every(fn (array $step) => $step['status'] === 'found');
  }

  /**
   * Generate approval steps for an SPPD Request based on dynamic workflows.
   * Setiap tahap harus sudah punya pejabat pasti (lihat isComplete).
   *
   * @return bool True if workflow successfully applied, False if no matching workflow found.
   */
  public function generateApprovals(SppdRequest $sppd): bool
  {
    $pelaksana = $sppd->user;
    $workflow = $this->findWorkflow($pelaksana, $sppd->domain?->value);

    if (!$workflow || empty($workflow->steps)) {
      Log::warning("No matching workflow found for SPPD {$sppd->id}");
      return false;
    }

    $stepOrder = 1;
    $approvals = [];

    foreach ($this->normalizedSteps($workflow) as $step) {
      if ($step['role'] === '') {
        continue;
      }

      $resolved = $this->resolveStep($step['role'], $pelaksana);
      if ($resolved['status'] !== 'found') {
        $this->failMissingApprover($step['role'], $resolved['status'], $sppd);
      }

      $approver = $resolved['approver'];
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
   * Tahap "choose" membawa bawahan yang tautan atasannya akan diisi saat dipilih.
   * Bila atasan pemohon belum pasti, yang tampil alur pratinjau (lihat matchWorkflow)
   * dengan tahap atasannya belum dipilih, jadi alurnya tidak pernah lengkap.
   * supervisor_step: tahap atasan langsung pemohon pada alur yang dibedakan per atasan.
   */
  public function simulateApprovals(User $pelaksana, ?string $destination = null): array
  {
    $match = $this->matchWorkflow($pelaksana, $destination);
    $workflow = $match['preview'];

    if (!$workflow || empty($workflow->steps)) {
      return [];
    }

    $supervisorIndex = empty($workflow->supervisor_role) ? null
      : collect($this->normalizedSteps($workflow))->search(fn (array $s) => in_array($s['role'], $workflow->supervisor_role, true));

    $simulatedSteps = [];
    foreach ($this->normalizedSteps($workflow) as $index => $step) {
      if ($step['role'] === '') {
        continue;
      }

      $resolved = $this->resolveStep($step['role'], $pelaksana);
      if ($match['needs_supervisor'] && $index === $supervisorIndex && $resolved['status'] === 'found') {
        $resolved = $this->resolution('choose', null, collect([$resolved['approver']]));
      }
      $approver = $resolved['approver'];
      // Tahap atasan: calonnya sama dengan daftar di modal pilih atasan (sub-unit mana pun di instansi).
      $candidates = $index === $supervisorIndex && $resolved['status'] === 'choose'
        ? array_map(fn (array $c) => array_intersect_key($c, array_flip(['id', 'name', 'unit', 'position'])),
          $this->supervisorCandidates($pelaksana, '', null, 500, [$step['role']]))
        : $resolved['candidates']->map(fn (User $u) => [
          'id'       => $u->id,
          'name'     => $u->name,
          'unit'     => $u->department?->name,
          'position' => $u->position?->name,
        ])->values()->all();
      $simulatedSteps[] = [
        'index'         => $index,
        'role'          => $step['role'],
        'role_label'    => $this->getApproverLabel($approver ?? new User(), $step['role']),
        'approver_id'   => $approver?->id,
        'approver_name' => match ($resolved['status']) {
          'found'  => $approver->name,
          'choose' => 'Belum dipilih',
          default  => 'Pejabat tidak ditemukan',
        },
        'status'        => $resolved['status'],
        'candidates'    => $candidates,
        'signs_spt'     => $step['signs_spt'],
        'signs_sppd'    => $step['signs_sppd'],
        'supervisor_step' => $index === $supervisorIndex,
      ];
    }

    // Bawahan yang dipakai saat pejabat tahap dipilih: tahap "choose", atau tahap terisi
    // yang bisa dipilih ulang (Kabid, Asisten). Null = lengkapi tahap sebelumnya dulu.
    foreach ($simulatedSteps as $position => &$row) {
      $selectable = $row['status'] === 'choose' || ($row['status'] === 'found' && $this->isReassignable($row['role']));
      $subordinate = $selectable ? $this->stepSubordinate($pelaksana, $simulatedSteps, $position) : null;
      $row['subordinate_id'] = $subordinate['user']?->id ?? null;
      $row['subordinate_name'] = $subordinate['user']?->name ?? null;
    }
    unset($row);

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
   * Pertama ditelusuri rantai atasan pemohon (atasan yang diatur, atau yang pasti
   * dari struktur): pemegang role tahap pertama di rantai itulah pejabatnya. Bila
   * tidak ada, dicari naik dari unit kerja pemohon. Bila struktur menunjuk tepat
   * satu pejabat, langsung dipakai; bila ada beberapa kandidat (mis. beberapa
   * Kabid atau Asisten di unit induk yang datar), pejabatnya dipilih dan pilihan
   * itu disimpan sebagai atasan (lihat chooseStepApprover).
   *
   * @return array{status: string, approver: ?User, candidates: Collection<int, User>}
   *   status: found | choose | not_found
   */
  public function resolveStep(string $roleName, User $applicant): array
  {
    $roles = $this->getRoleSynonyms($roleName);

    foreach ($this->supervisorChain($applicant)['links'] as $link) {
      if ($link['user']->roles->whereIn('name', $roles)->isNotEmpty()) {
        return $this->resolution('found', $link['user']);
      }
    }

    $root = $this->instansiRoot($applicant->department);
    $confirm = in_array($roleName, self::CONFIRM_ROLES, true) && ! $this->isUniquePerUnit($roleName);
    $currentDept = $applicant->department;

    // Trace up the hierarchy
    while ($currentDept) {
      $holders = $this->holders($roles, [$currentDept->id]);

      if ($holders->isNotEmpty()) {
        // Pejabat tunggal di sub-unit pemohon, atau role yang memang satu per unit,
        // pasti atasannya. Kabid tunggal di unit induk yang datar belum tentu
        // atasan pemohon (bisa staf Sekretariat), jadi tetap dikonfirmasi.
        $certain = $holders->count() === 1 && (! $confirm || $currentDept->id !== $root?->id);

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

      if ($holders->count() === 1 && ! $confirm) {
        return $this->resolution('found', $holders->first());
      }
      if ($holders->isNotEmpty()) {
        return $this->resolution('choose', null, $holders);
      }
    }

    return $this->resolution('not_found');
  }

  // ── Atasan langsung ───────────────────────────────────────────────────

  /**
   * Rantai atasan pegawai: atasan yang diatur (atasan_id), atau bila kosong,
   * atasan yang pasti dari struktur unit kerja.
   *
   * @return array{links: array<int, array{user: User, source: string}>, open: bool}
   *   source: set (diatur) | structure (dari struktur unit kerja);
   *   open = rantai terhenti karena atasan berikutnya belum pasti.
   */
  public function supervisorChain(User $user): array
  {
    if (isset($this->chainCache[$user->id])) {
      return $this->chainCache[$user->id];
    }

    $links = [];
    $seen = [$user->id => true];
    $current = $user;
    $open = false;

    for ($depth = 0; $depth < self::CHAIN_DEPTH; $depth++) {
      $next = $current->atasan_id
        ? User::with(['roles', 'department.parent', 'position'])->where('is_active', true)->find($current->atasan_id)
        : null;
      $source = 'set';

      if (! $next) {
        $derived = $this->derivedSupervisor($current);
        $source = 'structure';
        if (! $derived['user']) {
          $open = $derived['ambiguous'];
          break;
        }
        // Muat ulang lengkap: holders() hanya memuat nama unit, padahal penelusuran
        // berikutnya butuh induk & jenis unit kerjanya.
        $next = User::with(['roles', 'department.parent', 'position'])->find($derived['user']->id);
      }

      if (isset($seen[$next->id])) {
        break;
      }

      $seen[$next->id] = true;
      $links[] = ['user' => $next, 'source' => $source];
      $current = $next;
    }

    return $this->chainCache[$user->id] = ['links' => $links, 'open' => $open];
  }

  /**
   * Atasan langsung efektif: penyetuju pertama di rantai atasan (Kasubag/staf di
   * tengah rantai dilewati). Dipakai untuk memilih workflow.
   *
   * @return array{status: string, user: ?User, source: ?string}
   *   status: found | unknown (belum pasti, perlu diatur) | none (tidak ada pejabat di atasnya)
   */
  public function effectiveSupervisor(User $user): array
  {
    $chain = $this->supervisorChain($user);

    foreach ($chain['links'] as $link) {
      if ($this->approverRoleOf($link['user'])) {
        return ['status' => 'found', 'user' => $link['user'], 'source' => $link['source']];
      }
    }

    return ['status' => $chain['open'] ? 'unknown' : 'none', 'user' => null, 'source' => null];
  }

  /**
   * Calon atasan langsung: pemegang role penyetuju yang lebih tinggi dari pegawai,
   * di instansinya serta Asisten/Sekda/Wali Kota di Sekretariat Daerah. Yang terdekat
   * (tingkat paling dekat di atas pegawai) tampil lebih dulu. $onlyRoles membatasi
   * ke role penyetuju tertentu (beserta sinonimnya).
   *
   * @return array<int, array{id:int, name:string, nip:?string, role_label:string, unit:?string, position:?string}>
   */
  public function supervisorCandidates(User $user, string $search = '', ?int $rank = null, int $limit = 60, ?array $onlyRoles = null): array
  {
    $rank ??= $this->rankOf($user);
    $roles = $this->rolesAbove($rank);
    if ($onlyRoles !== null) {
      $roles = array_values(array_intersect($roles, collect($onlyRoles)->flatMap(fn (string $r) => $this->getRoleSynonyms($r))->all()));
    }
    if ($roles === [] || ! $user->department) {
      return [];
    }

    $instansiIds = $this->zoneIds($this->instansiRoot($user->department));
    $setda = Department::where('type', DepartmentType::SETDA)->whereNull('parent_id')->orderBy('id')->first();
    // Dari Setda hanya pejabat setingkat Asisten ke atas; Kabag Setda bukan atasan pegawai OPD.
    $setdaIds = $setda ? array_diff($this->zoneIds($setda), $instansiIds) : [];
    $cityRoles = array_values(array_filter($roles, fn (string $r) => Position::levelForRole($r) <= Position::levelForRole('asisten')));

    $term = trim($search);

    return User::with(['roles', 'department', 'position'])
      ->where('is_active', true)
      ->when($user->exists, fn ($q) => $q->whereKeyNot($user->id))
      ->where(fn ($q) => $q
        ->where(fn ($w) => $w->whereIn('department_id', $instansiIds)->whereHas('roles', fn ($r) => $r->whereIn('name', $roles)))
        ->when($setdaIds !== [] && $cityRoles !== [], fn ($q) => $q->orWhere(fn ($w) => $w
          ->whereIn('department_id', $setdaIds)->whereHas('roles', fn ($r) => $r->whereIn('name', $cityRoles)))))
      ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%")))
      ->get()
      ->sortBy([fn (User $a, User $b) => $this->rankOf($b) <=> $this->rankOf($a), fn (User $a, User $b) => strcmp($a->name, $b->name)])
      ->take($limit)
      ->map(fn (User $u) => [
        'id'         => $u->id,
        'name'       => $u->name,
        'nip'        => $u->nip,
        'role_label' => $this->roleLabel($this->approverRoleOf($u) ?? 'staf'),
        'unit'       => $u->department?->name,
        'position'   => $u->position?->name,
      ])
      ->values()
      ->all();
  }

  /** Atasan yang dipakai seluruh rekan satu unit dengan peran yang sama, bila semuanya sepakat. */
  public function suggestedSupervisor(User $user): ?User
  {
    $ids = $this->colleagues($user)->whereNotNull('atasan_id')->distinct()->pluck('atasan_id');

    return $ids->count() === 1 ? User::where('is_active', true)->find($ids->first()) : null;
  }

  /** Rekan satu unit dengan peran yang sama yang belum punya atasan langsung. */
  public function colleaguesWithoutSupervisor(User $user): Collection
  {
    return $this->colleagues($user)->whereNull('atasan_id')->orderBy('name')->get(['id', 'name']);
  }

  /**
   * Pesan kesalahan bila $supervisor tidak sah sebagai atasan langsung $user, atau null.
   * $rank: tingkat role $user bila role-nya sedang diubah (form Pegawai).
   */
  public function supervisorError(User $user, ?User $supervisor, ?int $rank = null): ?string
  {
    if (! $supervisor) {
      return null;
    }
    if ($user->exists && $supervisor->id === $user->id) {
      return 'Pegawai tidak bisa menjadi atasan dirinya sendiri.';
    }
    // Model yang baru dibuat belum membawa nilai bawaan is_active dari database.
    if ($supervisor->is_active === false) {
      return "{$supervisor->name} sudah tidak aktif.";
    }
    if ($this->rankOf($supervisor) >= ($rank ?? $this->rankOf($user))) {
      return "{$supervisor->name} tidak berada di atas {$user->name} dalam jenjang jabatan.";
    }

    // Rantai tidak boleh berputar kembali ke pegawai itu sendiri.
    $current = $supervisor;
    for ($depth = 0; $user->exists && $current?->atasan_id && $depth < self::CHAIN_DEPTH; $depth++) {
      if ($current->atasan_id === $user->id) {
        return 'Pilihan ini membuat rantai atasan berputar.';
      }
      $current = User::find($current->atasan_id);
    }

    return null;
  }

  /**
   * Atur atasan langsung pegawai, sekaligus rekan satu unit yang belum punya atasan
   * ($alsoUserIds). Perubahan tercatat di log aktivitas pegawai.
   *
   * @throws \RuntimeException
   */
  public function setSupervisor(User $user, User $supervisor, array $alsoUserIds = []): void
  {
    if ($error = $this->supervisorError($user, $supervisor)) {
      throw new \RuntimeException($error);
    }

    $others = $alsoUserIds === [] ? collect() : $this->colleagues($user)
      ->whereNull('atasan_id')
      ->whereIn('id', $alsoUserIds)
      ->get()
      ->filter(fn (User $u) => $this->supervisorError($u, $supervisor) === null);

    DB::transaction(function () use ($user, $supervisor, $others) {
      $user->update(['atasan_id' => $supervisor->id]);
      $others->each(fn (User $u) => $u->update(['atasan_id' => $supervisor->id]));
    });

    $this->chainCache = [];
  }

  /**
   * Pilih pejabat tahap $role: disimpan sebagai atasan bawahan tahap itu (lihat
   * stepSubordinate), sehingga pengajuan berikutnya langsung memakai pejabat ini.
   * Berlaku untuk tahap "choose", dan untuk mengganti pilihan pada tahap terisi
   * yang role-nya bisa dijabat beberapa orang (Kabid, Asisten).
   *
   * @throws \RuntimeException
   */
  public function chooseStepApprover(User $pelaksana, ?string $destination, string $role, int $approverId): User
  {
    $label = $this->roleLabel($role);
    $steps = $this->simulateApprovals($pelaksana, $destination);
    $position = collect($steps)->search(fn (array $s) => $s['role'] === $role
      && ($s['status'] === 'choose' || ($s['status'] === 'found' && $this->isReassignable($role))));

    if ($position === false) {
      throw new \RuntimeException("Tahap {$label} tidak dapat dipilih ulang.");
    }

    $allowed = $steps[$position]['status'] === 'choose'
      ? array_column($steps[$position]['candidates'], 'id')
      : $this->stepCandidates($role, $pelaksana)->pluck('id')->all();
    $approver = in_array($approverId, $allowed, true) ? User::with('roles')->find($approverId) : null;
    if (! $approver) {
      throw new \RuntimeException("Pegawai tersebut bukan {$label} di instansi pelaksana.");
    }

    $subordinate = $this->stepSubordinate($pelaksana, $steps, $position);
    if (! $subordinate) {
      throw new \RuntimeException("Lengkapi tahap sebelum {$label} terlebih dahulu.");
    }

    DB::transaction(function () use ($subordinate, $approver) {
      if ($subordinate['link']) {
        $this->insertSupervisor($subordinate['link']['from'], $subordinate['link']['to']);
      }
      $this->insertSupervisor($subordinate['user'], $approver);
    });

    $this->chainCache = [];

    return $approver;
  }

  /**
   * Pilih pejabat tahap atasan (supervisor_step): disimpan sebagai atasan langsung
   * bawahan tahap itu. Calonnya pemegang role atasan dari semua alur yang berlaku,
   * jadi memilih Asisten (bukan Kabag) memakai alur untuk pegawai di bawah Asisten.
   * $alsoUserIds: rekan satu unit pelaksana, hanya bila bawahannya pelaksana sendiri.
   *
   * @throws \RuntimeException
   */
  public function chooseSupervisorStep(User $pelaksana, ?string $destination, int $supervisorId, array $alsoUserIds = []): User
  {
    $step = collect($this->simulateApprovals($pelaksana, $destination))->firstWhere('supervisor_step', true);
    $subordinate = ($step['subordinate_id'] ?? null) ? User::with(['roles', 'department.parent'])->find($step['subordinate_id']) : null;
    if (! $subordinate) {
      throw new \RuntimeException('Alur ini tidak memiliki tahap atasan yang bisa dipilih.');
    }

    $roles = $this->matchWorkflow($pelaksana, $destination)['supervisor_roles'];
    $allowed = array_column($this->supervisorCandidates($subordinate, '', null, 500, $roles), 'id');
    $supervisor = in_array($supervisorId, $allowed, true) ? User::with('roles')->find($supervisorId) : null;
    if (! $supervisor) {
      throw new \RuntimeException('Pegawai tersebut bukan calon atasan yang sah.');
    }

    $this->setSupervisor($subordinate, $supervisor, $subordinate->id === $pelaksana->id ? $alsoUserIds : []);

    return $supervisor;
  }

  /** Pemegang role tahap di instansi asal calonnya (lihat officialPoolRoot). */
  public function stepCandidates(string $role, User $pelaksana): Collection
  {
    $root = $this->officialPoolRoot($role, $pelaksana);

    return $root ? $this->holders($this->getRoleSynonyms($role), $this->zoneIds($root)) : collect();
  }

  /** Role yang bisa dijabat beberapa orang sehingga pejabat tahapnya bisa dipilih ulang. */
  public function isReassignable(string $role): bool
  {
    return ! $this->isUniquePerUnit($role) && ! in_array($role, self::CITY_WIDE_ROLES, true);
  }

  /**
   * Bawahan yang atasannya diisi saat pejabat tahap ke-$position dipilih:
   *  - tahap terisi lewat rantai: anggota rantai tepat sebelum pejabat itu;
   *  - selain itu, anggota rantai terakhir yang tingkatnya di bawah role tahap,
   *    dihitung sampai pejabat tahap sebelumnya. Bila pejabat tahap sebelumnya belum
   *    ada di rantai, ujung rantai ditautkan dulu ke pejabat itu ($link).
   * Null bila tahap sebelumnya belum lengkap.
   *
   * @return array{user: User, link: ?array{from: User, to: User}}|null
   */
  private function stepSubordinate(User $pelaksana, array $steps, int $position): ?array
  {
    $chain = [$pelaksana, ...array_column($this->supervisorChain($pelaksana)['links'], 'user')];
    $ids = array_map(fn (User $u) => $u->id, $chain);
    $stepLevel = Position::levelForRole($steps[$position]['role']);

    if ($steps[$position]['status'] === 'found' && ($at = array_search($steps[$position]['approver_id'], $ids, true))) {
      return ['user' => $chain[$at - 1], 'link' => null];
    }

    $link = null;
    if ($position > 0) {
      $previous = $steps[$position - 1];
      if ($previous['status'] !== 'found') {
        return null;
      }

      $at = array_search($previous['approver_id'], $ids, true);
      if ($at === false) {
        $previousUser = User::with('roles')->find($previous['approver_id']);
        $tail = collect($chain)->last(fn (User $u) => $this->rankOf($u) > $this->rankOf($previousUser)) ?? $pelaksana;
        $link = ['from' => $tail, 'to' => $previousUser];
        $chain = [$previousUser];
      } else {
        $chain = array_slice($chain, 0, $at + 1);
      }
    }

    $user = collect($chain)->last(fn (User $u) => $this->rankOf($u) > $stepLevel) ?? $chain[0];

    return ['user' => $user, 'link' => $link];
  }

  /**
   * Sisipkan $supervisor sebagai atasan $user. Atasan lama $user menjadi atasan
   * $supervisor bila $supervisor belum punya atasan dan atasan lama memang di atasnya.
   *
   * @throws \RuntimeException
   */
  private function insertSupervisor(User $user, User $supervisor): void
  {
    if ($user->atasan_id === $supervisor->id) {
      return;
    }
    if ($error = $this->supervisorError($user, $supervisor)) {
      throw new \RuntimeException($error);
    }

    $previous = $user->atasan_id ? User::find($user->atasan_id) : null;
    $user->update(['atasan_id' => $supervisor->id]);

    if ($previous && ! $supervisor->atasan_id && $this->supervisorError($supervisor, $previous) === null) {
      $supervisor->update(['atasan_id' => $previous->id]);
    }
  }

  /**
   * Atasan pasti dari struktur unit kerja: pejabat terdekat di atas pegawai, dicari
   * naik dari unit kerjanya. Di unit induk instansi hanya role yang satu per unit
   * (mis. Sekretaris) yang dianggap pasti; Kabid/Asisten di sana perlu diatur.
   *
   * @return array{user: ?User, ambiguous: bool}
   */
  private function derivedSupervisor(User $user): array
  {
    $roles = $this->rolesAbove($this->rankOf($user));
    $root = $this->instansiRoot($user->department);
    $dept = $user->department;

    while ($roles !== [] && $dept) {
      $holders = $this->holders($roles, [$dept->id])->reject(fn (User $h) => $h->id === $user->id);

      if ($holders->isNotEmpty()) {
        $nearestRank = $holders->max(fn (User $h) => $this->rankOf($h));
        $nearest = $holders->filter(fn (User $h) => $this->rankOf($h) === $nearestRank)->values();
        $role = $this->approverRoleOf($nearest->first());
        $certain = $nearest->count() === 1 && ($dept->id !== $root?->id || $this->isUniquePerUnit((string) $role));

        return ['user' => $certain ? $nearest->first() : null, 'ambiguous' => ! $certain];
      }

      if ($this->stopsClimbing($dept)) {
        break;
      }
      $dept = $dept->parent;
    }

    return ['user' => null, 'ambiguous' => false];
  }

  /** Rekan aktif satu unit kerja dengan peran utama yang sama. */
  private function colleagues(User $user): \Illuminate\Database\Eloquent\Builder
  {
    $role = $user->roles->first()?->name;

    return User::where('department_id', $user->department_id)
      ->where('is_active', true)
      ->when($user->exists, fn ($q) => $q->whereKeyNot($user->id))
      ->whereHas('roles', fn ($q) => $q->where('name', $role));
  }

  /** Tingkat jabatan pegawai (angka kecil = lebih tinggi), dari role tertingginya. */
  public function rankOf(User $user): int
  {
    return $user->roles->map(fn ($role) => Position::levelForRole($role->name))->min() ?? Position::DEFAULT_LEVEL;
  }

  /** Role penyetuju baku pegawai (Camat → kepala_opd, Sekcam → sekretaris_opd), atau null. */
  public function approverRoleOf(User $user): ?string
  {
    foreach ($user->roles->pluck('name') as $name) {
      foreach (self::APPROVER_ROLES as $approverRole) {
        if (in_array($name, $this->getRoleSynonyms($approverRole), true)) {
          return $approverRole;
        }
      }
    }

    return null;
  }

  /** Nama role penyetuju (termasuk sinonim) yang tingkatnya di atas $rank. */
  private function rolesAbove(int $rank): array
  {
    return collect(self::APPROVER_ROLES)
      ->flatMap(fn (string $role) => $this->getRoleSynonyms($role))
      ->filter(fn (string $role) => Position::levelForRole($role) < $rank)
      ->unique()
      ->values()
      ->all();
  }

  /** Unit teratas instansi pelaksana (OPD, Setda, Kecamatan, Kelurahan, Puskesmas, ...). */
  public function instansiOf(User $pelaksana): ?Department
  {
    return $this->instansiRoot($pelaksana->department);
  }

  /**
   * Instansi asal calon pejabat suatu tahap: pejabat tingkat kota (Sekda, Wali Kota)
   * berasal dari Sekretariat Daerah; selain itu dari instansi pelaksana.
   */
  public function officialPoolRoot(string $role, User $pelaksana): ?Department
  {
    if (in_array($role, self::CITY_WIDE_ROLES, true)) {
      return Department::where('type', DepartmentType::SETDA)->whereNull('parent_id')->orderBy('id')->first();
    }

    return $this->instansiRoot($pelaksana->department);
  }

  /**
   * Pegawai di instansi pelaksana yang dapat ditetapkan sebagai pejabat untuk
   * tahap yang belum terisi. Pegawai yang jabatannya sesuai tampil lebih dulu.
   *
   * @return array<int, array{id:int, name:string, nip:?string, position:?string, unit:?string, fits:bool}>
   */
  public function officialCandidates(string $role, User $pelaksana, string $search = '', int $limit = 8): array
  {
    $term = trim($search);

    return ($this->candidateQuery($role, $pelaksana)?->with(['position', 'department'])
      ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%")))
      ->orderByRaw('exists (select 1 from positions where positions.id = users.position_id and positions.default_role = ?) desc', [$role])
      ->orderBy('name')
      ->limit($limit)
      ->get() ?? collect())
      ->map(fn (User $u) => $this->candidateRow($u, $role))
      ->all();
  }

  /** Satu calon pejabat (lihat officialCandidates), atau null bila bukan calon yang sah. */
  public function officialCandidate(string $role, User $pelaksana, int $candidateId): ?array
  {
    $user = $this->candidateQuery($role, $pelaksana)?->with(['position', 'department'])->find($candidateId);

    return $user ? $this->candidateRow($user, $role) : null;
  }

  /**
   * Jabatan yang memberi role tahap di unit kerja calon. Jabatan yang namanya
   * cocok dengan nama instansi (mis. "Kepala Dinas Sosial" untuk Dinas Sosial)
   * diurutkan lebih dulu sebagai saran. Saat mengganti pejabat ($outgoingId),
   * jabatan pejabat lama boleh dipilih (dialihkan) dan menjadi saran utama.
   *
   * @return array<int, array{id:int, name:string, holder:?string, handover:?string, suggested:bool}>
   */
  public function officialPositions(string $role, User $pelaksana, int $candidateId, ?int $outgoingId = null): array
  {
    $candidate = $this->candidateQuery($role, $pelaksana)?->with('department')->find($candidateId);
    if (! $candidate) {
      return [];
    }
    $outgoing = $outgoingId ? User::find($outgoingId) : null;

    $instansiWords = collect(preg_split('/\s+/', mb_strtolower($this->officialPoolRoot($role, $pelaksana)?->name ?? '')))
      ->filter(fn (string $w) => mb_strlen($w) > 3);
    $roles = $this->getRoleSynonyms($role);

    $rows = Position::whereIn('default_role', $roles)->orderBy('name')->get()
      ->filter(fn (Position $p) => in_array($p->roleForDepartment($candidate->department), $roles, true))
      ->map(function (Position $p) use ($candidate, $outgoing, $instansiWords) {
        $holder = $p->conflictingHolder($candidate->department_id, $candidate->id);
        $handover = $outgoing && ($p->id === $outgoing->position_id || $holder?->id === $outgoing->id);

        return [
          'id'       => $p->id,
          'name'     => $p->name,
          'holder'   => $handover ? null : $holder?->name,
          'handover' => $handover ? $outgoing->name : null,
          'score'    => $instansiWords->filter(fn (string $w) => str_contains(mb_strtolower($p->name), $w))->count(),
        ];
      })
      ->sortBy([['score', 'desc'], ['name', 'asc']])
      ->values();

    $best = $rows->first(fn (array $r) => $r['handover'] !== null)
      ?? $rows->first(fn (array $r) => $r['score'] > 0 && ! $r['holder']);

    return $rows->map(fn (array $r) => [
      'id'        => $r['id'],
      'name'      => $r['name'],
      'holder'    => $r['holder'],
      'handover'  => $r['handover'],
      'suggested' => $best !== null && $r['id'] === $best['id'],
    ])->all();
  }

  /** Pejabat yang kini mengisi tahap $role pada alur pelaksana, atau null bila bukan dia. */
  public function currentOfficial(string $role, User $pelaksana, ?string $destination, int $userId): ?User
  {
    $step = collect($this->simulateApprovals($pelaksana, $destination))
      ->where('status', 'found')->where('role', $role)->firstWhere('approver_id', $userId);

    return $step ? User::with(['position', 'department', 'roles'])->find($userId) : null;
  }

  /**
   * Tetapkan pegawai sebagai pejabat untuk tahap yang belum terisi pada alur
   * pelaksana, agar pengajuan berikutnya tidak perlu diatur ulang. Bila
   * jabatannya belum sesuai, sertakan $positionId: jabatan pegawai ikut diubah
   * sehingga role mengikuti jabatan (sama seperti form Pegawai).
   *
   * Dengan $replaceId, pegawai menggantikan pejabat yang kini mengisi tahap itu:
   * pejabat lama kembali menjadi Staf dan melepas jabatan yang memberi role-nya.
   *
   * @throws \RuntimeException Pesan siap tampil bila penetapan tidak diizinkan.
   */
  public function appointOfficial(User $actor, User $pelaksana, ?string $destination, string $role, int $candidateId, ?int $positionId = null, ?int $replaceId = null): User
  {
    $label = $this->roleLabel($role);

    $outgoing = null;
    if ($replaceId) {
      $outgoing = $this->currentOfficial($role, $pelaksana, $destination, $replaceId);
      if (! $outgoing) {
        throw new \RuntimeException("Pejabat {$label} sudah berubah. Muat ulang halaman lalu coba lagi.");
      }
    } else {
      $missing = collect($this->simulateApprovals($pelaksana, $destination))->where('status', 'not_found')->pluck('role');
      if (! $missing->contains($role)) {
        throw new \RuntimeException("Tahap {$label} sudah memiliki pejabat.");
      }
    }

    $candidate = $this->candidateQuery($role, $pelaksana)?->with(['position', 'department', 'roles'])->find($candidateId);
    if (! $candidate) {
      throw new \RuntimeException('Pegawai tidak berada di instansi pelaksana atau sudah memegang jabatan lain.');
    }

    $position = null;
    if ($positionId) {
      $position = collect($this->officialPositions($role, $pelaksana, $candidateId, $outgoing?->id))->firstWhere('id', $positionId);
      if (! $position) {
        throw new \RuntimeException("Jabatan tersebut bukan jabatan {$label}.");
      }
      if ($position['holder']) {
        throw new \RuntimeException("Jabatan {$position['name']} sudah dijabat oleh {$position['holder']}.");
      }
    } elseif (! $actor->hasRole('super_admin') && ! $this->fitsPosition($candidate, $role)) {
      // Sama seperti form Pegawai: hanya Super Admin yang bebas memberi role;
      // Admin OPD menetapkan lewat jabatan yang sesuai.
      throw new \RuntimeException("Pilih jabatan {$label} untuk {$candidate->name} terlebih dahulu.");
    }

    // Pengganti mewarisi role pejabat lama (mis. Camat untuk tahap Kepala OPD).
    $newRole = $outgoing?->roles->pluck('name')->first(fn (string $r) => in_array($r, $this->getRoleSynonyms($role), true)) ?? $role;

    $holder = User::roleConflictHolder($newRole, $candidate->department_id, $candidate->id);
    if ($holder && $holder->id !== $outgoing?->id) {
      throw new \RuntimeException("Role {$label} sudah dipegang oleh {$holder->name}.");
    }

    // Pejabat baru untuk role yang bisa dijabat beberapa orang (Kabid, Asisten) langsung
    // ditautkan sebagai atasan bawahannya, agar tidak perlu dipilih lagi.
    $steps = $outgoing ? [] : $this->simulateApprovals($pelaksana, $destination);
    $missingAt = collect($steps)->search(fn (array $s) => $s['role'] === $role && $s['status'] === 'not_found');
    $subordinate = $missingAt !== false && $this->isReassignable($role) ? $this->stepSubordinate($pelaksana, $steps, $missingAt) : null;

    $appointed = DB::transaction(function () use ($actor, $candidate, $position, $role, $newRole, $label, $outgoing, $subordinate) {
      if ($outgoing) {
        $this->releaseOfficial($actor, $outgoing, $role, $label, $candidate, $position['id'] ?? null);
      }

      if ($position) {
        $candidate->update(['position_id' => $position['id']]);
      }

      $oldRole = $candidate->roles->pluck('name')->implode(', ');
      $candidate->syncRoles([$newRole]);

      activity()
        ->performedOn($candidate)
        ->causedBy($actor)
        ->event('updated')
        ->withChanges(['old' => ['role' => $oldRole], 'attributes' => ['role' => $newRole]])
        ->log($outgoing
          ? "Pegawai {$candidate->name} ditetapkan sebagai {$label} menggantikan {$outgoing->name} dari pengajuan SPPD"
          : "Pegawai {$candidate->name} ditetapkan sebagai {$label} dari pengajuan SPPD");

      if ($outgoing) {
        $this->handOverSubordinates($actor, $outgoing, $candidate);
      } elseif ($subordinate) {
        // Tautan yang tidak sah dilewati saja: penetapan pejabat tetap berlaku.
        $candidate->load('roles');
        $link = $subordinate['link'];
        if ($link && $this->supervisorError($link['from'], $link['to']) === null) {
          $this->insertSupervisor($link['from'], $link['to']);
        }
        if ($this->supervisorError($subordinate['user'], $candidate) === null) {
          $this->insertSupervisor($subordinate['user'], $candidate);
        }
      }

      return $candidate;
    });

    $this->chainCache = [];

    return $appointed;
  }

  /** Role pejabat lama setelah diganti: Pimpinan DPRD kembali menjadi Anggota DPRD, selain itu Staf. */
  public function releasedRole(string $role): string
  {
    return $role === 'pimpinan_dprd' ? 'anggota_dprd' : 'staf';
  }

  /** Jabatan pejabat lama yang ikut dilepas saat diganti (jabatan yang memberi role-nya), atau null. */
  public function releasedPosition(User $outgoing, string $role, ?int $handoverPositionId = null): ?string
  {
    if (! $outgoing->position_id) {
      return null;
    }

    return $this->fitsPosition($outgoing, $role) || $outgoing->position_id === $handoverPositionId
      ? $outgoing->position?->name
      : null;
  }

  /**
   * Pengganti mengambil alih bawahan pejabat lama dan mewarisi atasannya; pejabat
   * lama (kini Staf) dikosongkan atasannya agar ditentukan ulang dari strukturnya.
   */
  private function handOverSubordinates(User $actor, User $outgoing, User $successor): void
  {
    $moved = User::where('atasan_id', $outgoing->id)->whereKeyNot($successor->id)->update(['atasan_id' => $successor->id]);

    $successor->update(['atasan_id' => $outgoing->atasan_id !== $successor->id ? $outgoing->atasan_id : null]);
    $outgoing->update(['atasan_id' => null]);

    if ($moved > 0) {
      activity()
        ->performedOn($successor)
        ->causedBy($actor)
        ->event('updated')
        ->log("{$moved} bawahan {$outgoing->name} dipindahkan ke {$successor->name} sebagai atasan langsung");
    }
  }

  private function releaseOfficial(User $actor, User $outgoing, string $role, string $label, User $successor, ?int $handoverPositionId): void
  {
    $released = $this->releasedPosition($outgoing, $role, $handoverPositionId);
    if ($released !== null) {
      $outgoing->update(['position_id' => null]);
    }

    $oldRole = $outgoing->roles->pluck('name')->implode(', ');
    $outgoing->syncRoles([$this->releasedRole($role)]);

    activity()
      ->performedOn($outgoing)
      ->causedBy($actor)
      ->event('updated')
      ->withChanges(['old' => ['role' => $oldRole], 'attributes' => ['role' => $this->releasedRole($role)]])
      ->log("Pegawai {$outgoing->name} tidak lagi menjabat {$label}, digantikan {$successor->name} dari pengajuan SPPD");
  }

  /** @return array{id:int, name:string, nip:?string, position:?string, unit:?string, fits:bool} */
  private function candidateRow(User $user, string $role): array
  {
    return [
      'id'       => $user->id,
      'name'     => $user->name,
      'nip'      => $user->nip,
      'position' => $user->position?->name,
      'unit'     => $user->department?->name,
      'fits'     => $this->fitsPosition($user, $role),
    ];
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
      ->with(['department:id,name', 'roles', 'position:id,name'])
      ->orderBy('name')
      ->get();
  }

  private function failMissingApprover(string $stepRole, string $status, SppdRequest $sppd): never
  {
    Log::warning("Could not resolve approver for role '{$stepRole}' ({$status}) for SPPD {$sppd->id}");

    $label = $this->roleLabel($stepRole);
    throw new \Exception($status === 'choose'
      ? "Gagal Mengajukan: Pejabat {$label} belum dipilih. Pilih pejabatnya pada Tahap 1 (pratinjau alur) terlebih dahulu."
      : "Gagal Mengajukan: Pejabat dengan jabatan '{$label}' tidak ditemukan di unit kerja Anda atau unit kerja induk. Harap hubungi Admin untuk melengkapi data Pejabat di bidang Anda terlebih dahulu agar alur pengajuan tidak terputus.");
  }

  /**
   * Pegawai aktif di instansi asal (lihat officialPoolRoot) yang boleh diangkat ke
   * role tahap: pemegang Staf (Pimpinan DPRD dipilih dari Anggota DPRD).
   */
  private function candidateQuery(string $role, User $pelaksana): ?\Illuminate\Database\Eloquent\Builder
  {
    $root = $this->officialPoolRoot($role, $pelaksana);
    if (! $root) {
      return null;
    }

    $fromRoles = $role === 'pimpinan_dprd' ? ['anggota_dprd'] : ['staf'];

    return User::whereIn('department_id', $this->zoneIds($root))
      ->where('is_active', true)
      ->whereHas('roles', fn ($q) => $q->whereIn('name', $fromRoles));
  }

  /** Jabatan pegawai di unit kerjanya memang memberi role ini (Position::roleForDepartment). */
  private function fitsPosition(User $user, string $role): bool
  {
    return in_array($user->position?->roleForDepartment($user->department), $this->getRoleSynonyms($role), true);
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
