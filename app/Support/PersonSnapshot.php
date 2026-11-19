<?php

namespace App\Support;

use App\Enums\DprdJabatan;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;

/**
 * Data orang (nama, NIP, pangkat, jabatan, role) seperti saat SPPD disetujui. Dibekukan agar
 * dokumen & halaman SPPD yang sudah disetujui tidak ikut berubah ketika pegawai diganti
 * jabatannya (mis. Ganti pejabat) atau profilnya diedit. Disimpan sebagai JSON di
 * sppd_approvals.approver_snapshot, sppd_requests.pelaksana_snapshot, sppd_followers.snapshot.
 *
 * Helper-nya meniru User (jabatanLabel, nipOrJabatanLabel, isDprdMember, ...) agar template
 * cukup mengganti objeknya.
 */
final class PersonSnapshot
{
  /** @param list<string> $roleNames */
  public function __construct(
    public readonly ?string $name,
    public readonly ?string $nip,
    public readonly ?string $rankName,
    public readonly ?string $rankGroup,
    public readonly ?string $positionName,
    public readonly ?int $positionLevel,
    public readonly array $roleNames,
    public readonly ?string $roleLabel,
    public readonly ?string $dprdJabatan,
  ) {}

  public static function fromUser(User $user): self
  {
    $user->loadMissing(['position', 'rank', 'roles']);

    return new self(
      name: $user->name,
      nip: $user->nip,
      rankName: $user->rank?->name,
      rankGroup: $user->rank?->group,
      positionName: $user->position?->name,
      positionLevel: $user->position?->level,
      roleNames: $user->roles->pluck('name')->values()->all(),
      roleLabel: $user->roles->isNotEmpty() ? $user->primaryRoleLabel() : null,
      dprdJabatan: $user->dprd_jabatan,
    );
  }

  public static function fromArray(array $data): self
  {
    return new self(
      name: $data['name'] ?? null,
      nip: $data['nip'] ?? null,
      rankName: $data['rank_name'] ?? null,
      rankGroup: $data['rank_group'] ?? null,
      positionName: $data['position_name'] ?? null,
      positionLevel: isset($data['position_level']) ? (int) $data['position_level'] : null,
      roleNames: array_values($data['role_names'] ?? []),
      roleLabel: $data['role_label'] ?? null,
      dprdJabatan: $data['dprd_jabatan'] ?? null,
    );
  }

  /** Snapshot tersimpan bila ada, selain itu data akun saat ini (null bila keduanya kosong). */
  public static function resolve(?array $stored, ?User $user): ?self
  {
    if (! empty($stored)) {
      return self::fromArray($stored);
    }

    return $user ? self::fromUser($user) : null;
  }

  public function toArray(): array
  {
    return [
      'name' => $this->name,
      'nip' => $this->nip,
      'rank_name' => $this->rankName,
      'rank_group' => $this->rankGroup,
      'position_name' => $this->positionName,
      'position_level' => $this->positionLevel,
      'role_names' => $this->roleNames,
      'role_label' => $this->roleLabel,
      'dprd_jabatan' => $this->dprdJabatan,
    ];
  }

  /** Sama dengan User::jabatanLabel(): nama jabatan, atau label role bila jabatan kosong. */
  public function jabatanLabel(string $fallback = '-'): string
  {
    return $this->positionName ?? $this->roleLabel ?? $fallback;
  }

  /** Sama dengan User::nipOrJabatanLabel(): NIP → "Anggota DPRD" → jabatan. */
  public function nipOrJabatanLabel(): string
  {
    if (filled($this->nip)) {
      return $this->nip;
    }

    return $this->isDprdMember() ? 'Anggota DPRD' : $this->jabatanLabel();
  }

  /** @param string|list<string> $roles */
  public function hasRole(string|array $roles): bool
  {
    return array_intersect((array) $roles, $this->roleNames) !== [];
  }

  public function isDprdMember(): bool
  {
    return $this->hasRole(User::DPRD_ROLES);
  }

  /** Teks Pangkat/Golongan, mis. "Penata, Gol. III/c" (null bila keduanya kosong). */
  public function pangkatGolongan(): ?string
  {
    return Rank::pangkatGolongan($this->rankName, $this->rankGroup);
  }

  /** Bobot golongan untuk pengurutan (0 bila tanpa pangkat), lihat Rank::weight(). */
  public function rankWeight(): int
  {
    return filled($this->rankGroup) ? (new Rank(['group' => $this->rankGroup]))->weight() : 0;
  }

  public function levelOrDefault(): int
  {
    return $this->positionLevel ?? Position::DEFAULT_LEVEL;
  }

  /** Jabatan DPRD seperti tercetak di SPT/SPPD, mis. "KETUA DPRD Kota Kendari". */
  public function dprdJabatanLabel(): string
  {
    return DprdJabatan::tryFrom($this->dprdJabatan ?? '')?->label(true) ?? $this->dprdJabatan ?? 'ANGGOTA DPRD';
  }

  /** Peringkat jabatan DPRD (0 tertinggi); tanpa jabatan DPRD paling akhir. */
  public function dprdLevel(): int
  {
    return DprdJabatan::tryFrom($this->dprdJabatan ?? '')?->level() ?? PHP_INT_MAX;
  }
}
