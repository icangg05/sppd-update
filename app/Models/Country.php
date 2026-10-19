<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Negara tujuan perjalanan dinas luar negeri. */
class Country extends Model
{
  protected $fillable = ['iso2', 'name', 'aliases'];

  public function cities(): HasMany
  {
    return $this->hasMany(ForeignCity::class);
  }

  /**
   * Opsi dropdown negara: nama resmi sebagai nilai & label, nama lain sebagai
   * kata kunci pencarian (mis. "Cina" menemukan "Tiongkok").
   */
  public static function selectOptions(string $valueColumn = 'name'): array
  {
    return static::orderBy('name')->get()
      ->map(fn (self $c) => [
        'value' => (string) $c->{$valueColumn},
        'label' => $c->name,
        'keywords' => trim(($c->iso2 ?? '') . ' ' . ($c->aliases ?? '')),
      ])
      ->all();
  }
}
