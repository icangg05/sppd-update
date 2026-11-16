<?php

namespace App\Support;

/**
 * Uraian telaah (Persoalan, Fakta, Analisis) yang ditulis berupa poin, baik per baris
 * ("- …", "1. …", "a. …") maupun dalam satu baris ("-Surat A; -Surat B",
 * "1. Rapat; 2. Survei"), dipecah menjadi paragraf dan daftar agar tampil sebagai poin.
 * Teks yang tersimpan tidak diubah; ini hanya untuk tampilan.
 */
final class NarrativeText
{
  /** Tanda poin di awal baris. */
  private const BULLET = '[-•*–—·▪●◦○➢➤►✓✔]';

  /** Tanda poin yang tak lazim di tengah kalimat, jadi boleh memisah poin tanpa titik koma. */
  private const PURE_BULLET = '[•●▪◦➢➤►✓✔]';

  /** Awal poin: tanda poin atau nomor/huruf urut ("1.", "a)"). */
  private const ITEM_START = '(?:' . self::BULLET . '\s*|\d{1,2}[.)]\s+|[a-z][.)]\s+)';

  /**
   * Huruf pertama uraian kapital: awal teks, awal tiap baris, dan awal poin dalam satu
   * baris ("-A; -b" → "-A; -B"), setelah tanda poinnya bila ada.
   */
  public static function capitalize(?string $text): ?string
  {
    if ($text === null) {
      return null;
    }

    $upper = fn (array $m) => $m[1] . mb_strtoupper($m[2]);
    // Penanda poin possessive (?+): "a. Rincian" yang sudah kapital tidak berubah jadi "A. Rincian".
    $text = preg_replace_callback('/^(\s*' . self::ITEM_START . '?+)(\p{Ll})/mu', $upper, $text);

    return preg_replace_callback('/(;\s*' . self::ITEM_START . ')(\p{Ll})/u', $upper, $text);
  }

  private static function upperFirst(string $text): string
  {
    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
  }

  /**
   * @return list<array{type: 'p', text: string}|array{type: 'list', items: list<array{marker: string, text: string}>}>
   */
  public static function blocks(?string $text): array
  {
    $text = self::capitalize(trim((string) $text));
    if ($text === '') {
      return [];
    }

    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn (string $l) => $l !== ''));
    if (count($lines) === 1) {
      $lines = self::splitInline($lines[0]);
    }

    $blocks = [];
    foreach ($lines as $line) {
      $item = self::item($line);
      $last = array_key_last($blocks);

      if ($item === null) {
        $blocks[] = ['type' => 'p', 'text' => $line];
      } elseif ($last !== null && $blocks[$last]['type'] === 'list') {
        $blocks[$last]['items'][] = $item;
      } else {
        $blocks[] = ['type' => 'list', 'items' => [$item]];
      }
    }

    // Satu poin saja bukan daftar (mis. kalimat yang kebetulan diawali "1."): tampil apa adanya.
    if (count($blocks) === 1 && $blocks[0]['type'] === 'list' && count($blocks[0]['items']) === 1) {
      return [['type' => 'p', 'text' => $text]];
    }

    return $blocks;
  }

  /** @return array{marker: string, text: string}|null */
  private static function item(string $line): ?array
  {
    if (preg_match('/^' . self::BULLET . '\s*(\S.*)$/u', $line, $m)) {
      return ['marker' => '•', 'text' => self::upperFirst($m[1])];
    }
    // Huruf kecil saja: "A. Yani" atau "H. Abdullah" bukan poin.
    if (preg_match('/^(\d{1,2}[.)]|[a-z][.)])\s+(\S.*)$/u', $line, $m)) {
      return ['marker' => $m[1], 'text' => self::upperFirst($m[2])];
    }

    return null;
  }

  /**
   * Poin dalam satu baris. Bertanda: "-A; -B" (dipisah titik koma) atau "• A • B".
   * Bernomor/berhuruf: "1. A; 2. B" atau "1. A. 2. B" — pemisahnya tanda baca sebelum
   * nomor berikutnya, dan nomornya harus berurutan, agar "Pasal 5 ayat 2. …" tidak terpecah.
   *
   * @return list<string>
   */
  private static function splitInline(string $line): array
  {
    if (preg_match('/^' . self::BULLET . '/u', $line)) {
      return preg_split('/(?<=;)\s*(?=' . self::BULLET . ')|\s+(?=' . self::PURE_BULLET . ')/u', $line);
    }

    if (! preg_match('/^(1|a)([.)])\s/u', $line, $m)) {
      return [$line];
    }

    $numeric = $m[1] === '1';
    $token = $numeric ? '\d{1,2}' : '[a-z]';
    $close = preg_quote($m[2], '/');
    $pieces = preg_split('/(?<=[.;,:])\s+(?=' . $token . $close . '\s)/u', $line);

    $items = [];
    $expected = $m[1];
    foreach ($pieces as $piece) {
      if (preg_match('/^(' . $token . ')' . $close . '\s/u', $piece, $n) && $n[1] === (string) $expected) {
        $items[] = $piece;
        $expected = $numeric ? (string) ((int) $expected + 1) : chr(ord($expected) + 1);
      } else {
        // Bukan nomor berikutnya: bagian dari poin sebelumnya.
        $items[array_key_last($items)] .= ' ' . $piece;
      }
    }

    return $items;
  }
}
