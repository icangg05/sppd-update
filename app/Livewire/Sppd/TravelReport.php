<?php

namespace App\Livewire\Sppd;

use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use App\Services\AppSettings;
use App\Services\ImageCompressor;
use App\Support\DocumentFolder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Laporan Hasil Perjalanan Dinas. Unggah dokumen laporan + foto dokumentasi
 * dan simpan tanpa reload via Livewire. total_expense diambil dari akumulasi
 * pengeluaran riil saat menyimpan.
 */
class TravelReport extends Component
{
  use AuthorizesSppdAccess, InteractsWithToast, WithFileUploads;

  public SppdRequest $sppd;
  public string $reportDate = '';
  public $reportFile = null;
  public $documentationFile = null;

  public function mount(SppdRequest $sppd): void
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $this->sppd = $sppd;
    // Default hari ini, tapi tidak sebelum perjalanan selesai (tanggal kembali).
    $this->reportDate = $sppd->report?->report_date?->format('Y-m-d')
      ?? max(now()->format('Y-m-d'), $this->earliestReportDate());
  }

  /** Laporan baru bisa dibuat sejak tanggal kembali perjalanan. */
  public function earliestReportDate(): string
  {
    return $this->sppd->end_date->format('Y-m-d');
  }

  public function canManage(): bool
  {
    return Auth::user()->hasAnyRole(['admin_opd', 'super_admin']);
  }

  public function save(AppSettings $settings): void
  {
    abort_unless($this->canManage(), 403, 'Aksi ini tidak diizinkan.');
    abort_unless(in_array($this->sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    $report = $this->sppd->report;

    $this->validate([
      'reportDate' => 'required|date|after_or_equal:'.$this->earliestReportDate(),
      'reportFile' => ($report?->report_file ? 'nullable' : 'required').'|file|mimes:pdf,doc,docx|max:'.$settings->uploadMaxKb(false),
      'documentationFile' => ($report?->documentation_file ? 'nullable' : 'required').'|image|max:'.$settings->uploadMaxKb(true),
    ], [
      'reportDate.after_or_equal' => 'Tanggal laporan tidak boleh sebelum perjalanan selesai ('.$this->sppd->end_date->translatedFormat('d F Y').').',
    ], [
      'reportDate' => 'tanggal laporan',
      'reportFile' => 'file laporan',
      'documentationFile' => 'foto dokumentasi',
    ]);

    $data = [
      'report_date' => $this->reportDate,
      'total_expense' => $this->sppd->actualExpenses()->sum('amount'),
    ];

    if ($this->reportFile) {
      if ($report?->report_file) {
        Storage::disk('public')->delete($report->report_file);
      }
      $data['report_file'] = app(ImageCompressor::class)->storeWithOriginalName($this->reportFile, DocumentFolder::path('laporan-perjalanan'));
    }

    if ($this->documentationFile) {
      if ($report?->documentation_file) {
        Storage::disk('public')->delete($report->documentation_file);
      }
      $data['documentation_file'] = app(ImageCompressor::class)->storeWithOriginalName($this->documentationFile, DocumentFolder::path('dokumentasi-perjalanan'));
    }

    $this->sppd->report()->updateOrCreate(['sppd_request_id' => $this->sppd->id], $data);

    $this->reset('reportFile', 'documentationFile');
    $this->toastSuccess('Laporan perjalanan berhasil disimpan.');
  }

  public function render()
  {
    $this->sppd->load(['user', 'report']);

    return view('livewire.sppd.travel-report');
  }
}
