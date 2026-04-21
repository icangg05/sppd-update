<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
  protected $fillable = ['key', 'value'];

  /**
   * Get a setting value by key.
   */
  public static function getValue(string $key, mixed $default = null): mixed
  {
    return static::where('key', $key)->value('value') ?? $default;
  }

  /**
   * Set a setting value by key.
   */
  public static function setValue(string $key, mixed $value): static
  {
    return static::updateOrCreate(['key' => $key], ['value' => $value]);
  }
}
