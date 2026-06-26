<?php

namespace App\Livewire;

use App\Jobs\BackupDatabaseJob;
use App\Livewire\Concerns\InteractsWithToast;
use App\Services\DatabaseBackupService;
use App\Services\QueueHealthService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DatabaseBackup extends Component
{
  use InteractsWithToast;

  /** Sedang menunggu worker menyelesaikan backup yang baru di-dispatch. */
  public bool $processing = false;
  public ?int $dispatchedAt = null;
  public string $expectedFile = '';

  public function mount(): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);
  }

  /**
   * Cek worker lebih awal saat tombol diklik: bila worker mati, tolak langsung
   * tanpa membuka modal konfirmasi. Bila sehat, kirim event agar modal +
   * hitung mundur 10 detik dibuka di sisi klien.
   */
  public function requestBackup(QueueHealthService $health): void
  {
    if (! $health->isWorkerHealthy()) {
      $this->toastError($this->queueDownMessage());
      return;
    }

    $this->dispatch('open-backup-confirm');
  }

  public function createBackup(QueueHealthService $health, DatabaseBackupService $service): void
  {
    // Gerbang otoritatif: tolak bila worker mati (mis. mati selama 10 detik jeda).
    if (! $health->isWorkerHealthy()) {
      $this->toastError($this->queueDownMessage());
      return;
    }

    $this->expectedFile = $service->currentWeekFilename();
    $this->dispatchedAt = now()->timestamp;
    $this->processing = true;

    BackupDatabaseJob::dispatch();

    $this->toastInfo('Backup sedang diproses di latar belakang. Daftar akan diperbarui otomatis setelah selesai.');
  }

  protected function queueDownMessage(): string
  {
    return 'Backup ditolak: layanan antrian (queue worker) sedang tidak berjalan. '
      . 'Hubungi administrator sistem dan coba lagi setelah layanan aktif.';
  }

  /** Dipanggil via wire:poll saat $processing untuk mendeteksi backup selesai. */
  public function pollBackup(DatabaseBackupService $service): void
  {
    if (! $this->processing) {
      return;
    }

    $path = $service->path($this->expectedFile);
    if ($path && filemtime($path) >= $this->dispatchedAt) {
      $this->processing = false;
      $this->toastSuccess('Backup minggu ini berhasil dibuat.');
      return;
    }

    // Berhenti menunggu setelah 90 detik (kemungkinan worker bermasalah).
    if (now()->timestamp - $this->dispatchedAt > 90) {
      $this->processing = false;
      $this->toastError('Backup belum selesai dalam waktu wajar. Periksa status worker, lalu muat ulang halaman.');
    }
  }

  public function download(string $name, DatabaseBackupService $service)
  {
    $path = $service->path($name);
    if (! $path) {
      $this->toastError('File backup tidak ditemukan.');
      return null;
    }

    return response()->download($path);
  }

  public function deleteBackup(string $name, DatabaseBackupService $service): void
  {
    if ($service->delete($name)) {
      $this->toastSuccess('Backup dihapus.');
    } else {
      $this->toastError('Gagal menghapus backup.');
    }
  }

  public function render(DatabaseBackupService $service)
  {
    $database = config('database.connections.mysql.database');
    $sizeRow = DB::select(
      'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS mb
       FROM information_schema.tables WHERE table_schema = ?',
      [$database]
    );

    return view('livewire.database-backup', [
      'backups' => $service->all(),
      'database' => $database,
      'sizeMb' => $sizeRow[0]->mb ?? 0,
      'tableCount' => count(DB::select('SHOW TABLES')),
    ])->title('Backup Database');
  }
}
