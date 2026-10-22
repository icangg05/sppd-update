<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Opsi pegawai untuk x-form.searchable-select mode backend (searchMethod): dicari
 * di server per kata kunci dan dibatasi, agar instansi dengan ribuan pegawai
 * tidak mengirim seluruh daftar ke browser.
 */
trait SearchesEmployees
{
  /**
   * @return array{options: array<int, array{value: string, label: string, name: string, nip: ?string, position: ?string}>, more: bool}
   */
  protected function employeeOptions(Builder $query, string $term, int $limit = 25): array
  {
    $term = trim($term);

    $users = $query
      ->with('position:id,name')
      ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
        ->where('name', 'like', "%{$term}%")
        ->orWhere('nip', 'like', "%{$term}%")))
      ->orderBy('name')
      ->limit($limit + 1)
      ->get(['id', 'name', 'nip', 'position_id']);

    return [
      'options' => $users->take($limit)->map(fn ($u) => [
        'value' => (string) $u->id,
        'label' => static::employeeLabel($u),
      ] + static::employeeDetails($u))->values()->all(),
      'more' => $users->count() > $limit,
    ];
  }

  /** Label pegawai di pilihan: nama beserta NIP bila ada. */
  protected static function employeeLabel($user): string
  {
    return $user->name . ($user->nip ? " (NIP. {$user->nip})" : '');
  }

  /**
   * Data pegawai yang ikut di setiap opsi, untuk pratinjau di klien (Alpine)
   * tanpa request tambahan saat opsi dipilih.
   *
   * @return array{name: string, nip: ?string, position: ?string}
   */
  protected static function employeeDetails($user): array
  {
    return ['name' => $user->name, 'nip' => $user->nip, 'position' => $user->position?->name];
  }
}
