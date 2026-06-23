<?php

namespace App\Support;

/**
 * Daftar token warna yang valid untuk badge (mis. warna role).
 *
 * Token di sini hanya nama warna Tailwind; kelas CSS konkretnya ditulis literal
 * di komponen blade (resources/views/components/ui/badge.blade.php) dan pemilih
 * warna (role-form), karena Tailwind v4 hanya memindai file blade.
 */
class BadgeColor
{
  public const DEFAULT = 'slate';

  /** @var array<int, string> */
  public const PALETTE = [
    'slate',
    'red',
    'orange',
    'amber',
    'yellow',
    'lime',
    'green',
    'emerald',
    'teal',
    'cyan',
    'sky',
    'blue',
    'indigo',
    'violet',
    'purple',
    'fuchsia',
    'pink',
    'rose',
  ];

  /**
   * @return array<int, string>
   */
  public static function tokens(): array
  {
    return self::PALETTE;
  }

  public static function valid(?string $color): string
  {
    return in_array($color, self::PALETTE, true) ? $color : self::DEFAULT;
  }
}
