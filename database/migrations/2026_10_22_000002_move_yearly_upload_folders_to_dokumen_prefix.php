<?php

use App\Support\DocumentFolder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Folder unggahan per tahun di disk public berganti nama: {tahun}/... → dokumen-{tahun}/...
 * (lihat App\Support\DocumentFolder). Hanya berkas yang tercatat di database yang
 * dipindahkan, lalu path-nya diperbarui — folder tahun yang kosong setelahnya dihapus.
 * Log aktivitas lama sengaja tidak diubah (catatan riwayat).
 */
return new class extends Migration
{
  private const COLUMNS = [
    'sppd_requests'           => ['attachment'],
    'sppd_reports'            => ['report_file', 'documentation_file'],
    'sppd_cost_details'       => ['receipt_photo'],
    'sppd_digital_signatures' => ['signed_file_path'],
  ];

  public function up(): void
  {
    $this->movePaths('/^(\d{4})\//', DocumentFolder::PREFIX . '$1/');
  }

  public function down(): void
  {
    $this->movePaths('/^' . preg_quote(DocumentFolder::PREFIX, '/') . '(\d{4})\//', '$1/');
  }

  private function movePaths(string $pattern, string $replacement): void
  {
    $disk = Storage::disk('public');
    $sourceFolders = [];

    foreach (self::COLUMNS as $table => $columns) {
      foreach ($columns as $column) {
        DB::table($table)->whereNotNull($column)->orderBy('id')->select('id', $column)
          ->each(function ($row) use ($disk, $table, $column, $pattern, $replacement, &$sourceFolders) {
            $old = $row->{$column};
            if (! preg_match($pattern, $old)) {
              return;
            }

            $new = preg_replace($pattern, $replacement, $old);
            if ($disk->exists($old) && ! $disk->exists($new)) {
              $disk->move($old, $new);
            }

            DB::table($table)->where('id', $row->id)->update([$column => $new]);
            $sourceFolders[strtok($old, '/')] = true;
          });
      }
    }

    // Folder lama dihapus hanya bila sudah tidak berisi berkas apa pun.
    foreach (array_keys($sourceFolders) as $folder) {
      if ($disk->allFiles($folder) === []) {
        $disk->deleteDirectory($folder);
      }
    }
  }
};
