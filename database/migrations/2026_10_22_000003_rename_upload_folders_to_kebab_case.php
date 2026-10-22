<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Nama folder unggahan di disk public memakai tanda hubung, bukan garis bawah:
 * kop_surat → kop-surat, dokumen-2026/doc_tte → dokumen-2026/doc-tte, dst.
 * Path di database diperbarui. Folder lama yang berkasnya tercatat di database
 * dipindah seluruhnya (termasuk berkas yang tak dirujuk) lalu dihapus.
 * Nama berkas tidak diubah; log aktivitas lama juga tidak (catatan riwayat).
 */
return new class extends Migration
{
  private const FOLDERS = [
    'dokumen_pendukung'      => 'dokumen-pendukung',
    'laporan_perjalanan'     => 'laporan-perjalanan',
    'dokumentasi_perjalanan' => 'dokumentasi-perjalanan',
    'nota_perjalanan'        => 'nota-perjalanan',
    'doc_tte'                => 'doc-tte',
    'kop_surat'              => 'kop-surat',
  ];

  private const COLUMNS = [
    'sppd_requests'           => ['attachment'],
    'sppd_reports'            => ['report_file', 'documentation_file'],
    'sppd_cost_details'       => ['receipt_photo'],
    'sppd_digital_signatures' => ['signed_file_path'],
    'departments'             => ['letterhead', 'letterhead_second'],
  ];

  public function up(): void
  {
    $this->renameFolders(self::FOLDERS);
  }

  public function down(): void
  {
    $this->renameFolders(array_flip(self::FOLDERS));
  }

  /** @param  array<string, string>  $folders  nama folder lama => baru */
  private function renameFolders(array $folders): void
  {
    $disk = Storage::disk('public');
    $moved = [];

    foreach (self::COLUMNS as $table => $columns) {
      foreach ($columns as $column) {
        DB::table($table)->whereNotNull($column)->orderBy('id')->select('id', $column)
          ->each(function ($row) use ($disk, $table, $column, $folders, &$moved) {
            // Hanya segmen folder yang diganti, nama berkas (segmen terakhir) tetap.
            $segments = explode('/', $row->{$column});
            $file = array_pop($segments);
            $renamed = array_map(fn (string $segment) => $folders[$segment] ?? $segment, $segments);
            if ($renamed === $segments) {
              return;
            }

            $old = $row->{$column};
            $new = implode('/', [...$renamed, $file]);
            if ($disk->exists($old) && ! $disk->exists($new)) {
              $disk->move($old, $new);
            }

            DB::table($table)->where('id', $row->id)->update([$column => $new]);
            $moved[implode('/', $segments)] = implode('/', $renamed);
          });
      }
    }

    // Sisa berkas di folder lama (tak dirujuk database) ikut dipindah, lalu folder lama dihapus.
    foreach ($moved as $from => $to) {
      foreach ($disk->allFiles($from) as $path) {
        $target = $to . substr($path, strlen($from));
        if (! $disk->exists($target)) {
          $disk->move($path, $target);
        }
      }

      if ($disk->allFiles($from) === []) {
        $disk->deleteDirectory($from);
      }
    }
  }
};
