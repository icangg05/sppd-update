<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Keterangan permission untuk halaman Role & Permission (Super Admin), dikelompokkan
 * menurut menu yang dibukanya. Hanya permission di sini yang ditampilkan; sisanya
 * (user.*, department.*, report.*, dll.) tersimpan di database tetapi belum dipakai
 * pemeriksaan akses mana pun, jadi disembunyikan agar tidak menyesatkan.
 */
final class PermissionCatalog
{
  /** @var array<string, array<string, array{0: string, 1: string}>> grup => [permission => [label, keterangan]] */
  public const GROUPS = [
    'Perjalanan Dinas' => [
      'sppd.create' => ['Buat SPPD', 'Membuat SPPD baru, mengedit perbaikan, dan membuat SPPD lanjutan.'],
      'sppd.delete' => ['Batalkan / hapus SPPD', 'SPPD pegawai dalam lingkup unit kerjanya.'],
      // sppd.sign sengaja tidak ditampilkan: panel "Menunggu Tanda Tangan" yang dibukanya
      // di beranda pejabat tidak pernah terisi (tanda tangan tidak pernah berstatus PENDING;
      // TTE terjadi saat menyetujui, diatur kolom TTE SPT/SPPD di Alur Persetujuan).
      'sppd.approve' => ['Menu Menunggu Persetujuan', 'Menampilkan menu Menunggu Persetujuan di grup List Telaah dan beranda versi pejabat. Siapa yang menyetujui tetap ditentukan Alur Persetujuan.'],
    ],
    'Menu Selanjutnya (Biaya Perjalanan)' => [
      'cost.view' => ['Buka menu Selanjutnya', 'Melihat dan mencetak dokumen SPPD/SPT, kuitansi, pengeluaran riil, rincian biaya, dan laporan.'],
      'cost.manage' => ['Kelola biaya perjalanan', 'Mengisi PPTK, Setuju Bayar, panjar, pengeluaran riil, rincian biaya, dan laporan perjalanan. Hanya berlaku bila Buka menu Selanjutnya juga dicentang.'],
    ],
    'Dokumen' => [
      'document.verify' => ['Cek Dokumen TTE', 'Memeriksa keaslian dokumen ber-TTE ke BSrE.'],
    ],
    'DPA' => [
      'budget.create' => ['Tambah DPA', 'Menambah DPA baru untuk instansinya.'],
      'budget.edit' => ['Ubah DPA', 'Mengubah DPA instansinya.'],
      'budget.delete' => ['Hapus DPA', 'Menghapus DPA instansinya.'],
    ],
  ];

  /** @return array<int, string> nama permission yang ditampilkan */
  public static function names(): array
  {
    return collect(self::GROUPS)->flatMap(fn (array $items) => array_keys($items))->all();
  }

  /**
   * Label permission katalog yang dimiliki, urut sesuai katalog (yang tersembunyi dilewati).
   *
   * @param  iterable<int, string>  $names
   * @return array<int, string>
   */
  public static function labelsFor(iterable $names): array
  {
    $names = collect($names);

    return collect(self::GROUPS)
      ->flatMap(fn (array $items) => $items)
      ->filter(fn ($_, string $name) => $names->contains($name))
      ->map(fn (array $item) => $item[0])
      ->values()
      ->all();
  }

  /**
   * Kelompokkan permission katalog yang ada di database. Permission di luar katalog
   * tidak ikut ditampilkan.
   *
   * @param  iterable<int, string>  $names
   * @return Collection<string, array<int, array{name: string, label: string, description: string}>>
   */
  public static function grouped(iterable $names): Collection
  {
    $names = collect($names);

    return collect(self::GROUPS)
      ->map(fn (array $items) => collect($items)
        ->filter(fn ($_, string $name) => $names->contains($name))
        ->map(fn (array $item, string $name) => ['name' => $name, 'label' => $item[0], 'description' => $item[1]])
        ->values()
        ->all())
      ->filter();
  }
}
