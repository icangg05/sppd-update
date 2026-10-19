<?php

namespace App\Models;

use App\Helpers\SmartTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdDestination extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'province_id',
    'regency_id',
    'country',
    'city',
    'address',
  ];

  public function sppdRequest(): BelongsTo
  {
    return $this->belongsTo(SppdRequest::class);
  }

  public function province(): BelongsTo
  {
    return $this->belongsTo(Province::class);
  }

  public function regency(): BelongsTo
  {
    return $this->belongsTo(Regency::class);
  }

  // Alamat & kota diketik bebas: huruf pertama tiap kata selalu kapital, baik saat
  // disimpan maupun dibaca (agar data lama ikut rapi di dokumen).
  public function getAddressAttribute(?string $value): ?string
  {
    return SmartTitle::capitalizeWords($value);
  }

  public function setAddressAttribute(?string $value): void
  {
    $this->attributes['address'] = SmartTitle::capitalizeWords($value);
  }

  public function getCityAttribute(?string $value): ?string
  {
    return SmartTitle::capitalizeWords($value);
  }

  public function setCityAttribute(?string $value): void
  {
    $this->attributes['city'] = SmartTitle::capitalizeWords($value);
  }

  /** Tujuan luar negeri memakai negara/kota (teks), bukan relasi provinsi/kabupaten. */
  public function isForeign(): bool
  {
    return filled($this->country);
  }

  /**
   * Nama tempat ringkas untuk daftar & dokumen: kabupaten/kota untuk tujuan
   * dalam negeri, "Kota, Negara" untuk luar negeri.
   */
  public function getPlaceNameAttribute(): ?string
  {
    if ($this->isForeign()) {
      return collect([$this->city, $this->country])->filter()->implode(', ');
    }

    return $this->regency?->name;
  }

  /**
   * Tempat tujuan untuk kalimat dokumen (SPT/SPPD): alamat spesifik, ditambah
   * "Kota, Negara" untuk luar negeri.
   */
  public function getFullAddressAttribute(): string
  {
    $parts = [$this->address];
    if ($this->isForeign()) {
      $parts[] = $this->place_name;
    }

    return collect($parts)->filter()->implode(', ');
  }

  /** Lokasi lengkap untuk halaman detail: "Provinsi, Kabupaten/Kota" atau "Kota, Negara". */
  public function getLocationLabelAttribute(): string
  {
    if ($this->isForeign()) {
      return $this->place_name;
    }

    return collect([$this->province?->name, $this->regency?->name])->filter()->implode(', ');
  }
}
