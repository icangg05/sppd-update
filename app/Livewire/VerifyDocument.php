<?php

namespace App\Livewire;

use App\Enums\SignatureStatus;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\Tte\BsreVerificationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Verifikasi keaslian dokumen TTE.
 *
 * Dua mode:
 *  - Landing (route 'verify.index'): admin mengunggah berkas PDF ber-TTE,
 *    lalu sistem mengirimkannya ke API BSrE dan menampilkan hasilnya.
 *  - Target QR (route 'verify.document', /verify/{type}/{sppd}/{hash}): URL yang
 *    di-encode pada QR dokumen menampilkan hasil cek berbasis catatan internal,
 *    plus tombol verifikasi ke BSrE atas berkas tersimpan.
 *
 * Hanya dapat diakses saat login oleh super_admin & admin_opd.
 */
#[Layout('layouts.app')]
class VerifyDocument extends Component
{
  use InteractsWithToast;
  use WithFileUploads;

  // Mode halaman: 'upload' (landing) atau 'qr' (target QR).
  public string $mode = 'upload';

  // ── Mode upload ──
  // Berkas PDF yang diunggah untuk diverifikasi.
  public $file = null;

  // Sudah submit verifikasi? (membedakan tampilan awal vs hasil)
  public bool $checked = false;

  // Hasil verifikasi kriptografis dari BSrE.
  public ?array $bsreResult = null;

  // ── Mode QR (cek catatan internal) ──
  public ?string $type = null;

  public ?SppdRequest $sppd = null;

  // Hash dari QR (cocok dengan yang di-generate saat dokumen dibuat).
  public ?string $hash = null;

  // Apakah hash cocok — menentukan dokumen sah/tidak.
  public bool $valid = false;

  // Untuk SPPD: pegawai (utama/pengikut) yang cocok dengan hash.
  public ?User $traveler = null;

  // Tanda tangan elektronik terkait dokumen ini (jika ada).
  public ?SppdDigitalSignature $signature = null;

  public function mount(?string $type = null, ?SppdRequest $sppd = null, ?string $hash = null): void
  {
    abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin_opd']), 403);

    // Mode target QR — parameter lengkap dari URL.
    if ($type && $sppd && $hash) {
      $this->mode = 'qr';
      $this->type = $type;
      $this->sppd = $sppd;
      $this->hash = $hash;
      $this->verify();
    }
  }

  /**
   * Saat berkas baru dipilih: pratinjau langsung tampil, hasil lama dibersihkan.
   */
  public function updatedFile(): void
  {
    $this->reset(['checked', 'bsreResult']);
    $this->resetValidation();
  }

  /**
   * Mode upload: kirim berkas PDF yang diunggah ke BSrE untuk verifikasi.
   */
  public function verifyUpload(BsreVerificationService $bsre): void
  {
    $this->validate(
      ['file' => 'required|file|mimes:pdf|max:12288'],
      [],
      ['file' => 'berkas PDF'],
    );

    $this->checked = true;

    $this->bsreResult = $bsre->verify(
      file_get_contents($this->file->getRealPath()),
      $this->file->getClientOriginalName(),
    );

    $this->toastForResult($this->bsreResult);
  }

  /**
   * Tampilkan toast sesuai hasil verifikasi BSrE.
   */
  protected function toastForResult(array $result): void
  {
    if (! ($result['ok'] ?? false)) {
      $this->toastError($result['error'] ?? 'Verifikasi gagal.', 'Verifikasi BSrE');
      return;
    }

    match ($result['valid']) {
      true    => $this->toastSuccess($result['summary'] ?? 'Dokumen terverifikasi dan sah.', 'Dokumen Valid'),
      false   => $this->toastError($result['summary'] ?? 'Dokumen tidak valid.', 'Dokumen Tidak Valid'),
      default => $this->toastInfo($result['summary'] ?? 'Verifikasi selesai.', 'Verifikasi BSrE'),
    };
  }

  /**
   * Kosongkan berkas & hasil untuk memulai pengecekan baru.
   */
  public function resetUpload(): void
  {
    $this->reset(['file', 'checked', 'bsreResult']);
    $this->resetValidation();
  }

  /**
   * Cocokkan hash QR dengan hash yang seharusnya, lalu ambil data TTE.
   */
  protected function verify(): void
  {
    $docNumber = (string) $this->sppd->document_number;

    if ($this->type === 'spt') {
      // SPT: md5(nomor_dokumen . sppd_id) — satu dokumen per pengajuan.
      $this->valid = hash_equals(md5($docNumber . $this->sppd->id), (string) $this->hash);
      $this->traveler = $this->sppd->user;
    } else {
      // SPPD: md5(nomor_dokumen . user_id) — dokumen dicetak per pelaku perjalanan
      // (pegawai utama + setiap pengikut), jadi cari pegawai yang cocok.
      $candidates = collect([$this->sppd->user])
        ->merge($this->sppd->followers->map->user)
        ->filter();

      foreach ($candidates as $candidate) {
        if (hash_equals(md5($docNumber . $candidate->id), (string) $this->hash)) {
          $this->valid = true;
          $this->traveler = $candidate;
          break;
        }
      }
    }

    if ($this->valid) {
      $this->signature = $this->resolveSignature();
    }
  }

  protected function resolveSignature(): ?SppdDigitalSignature
  {
    $documentType = $this->type === 'sppd' && $this->traveler
      ? 'sppd_' . $this->traveler->id
      : $this->type;

    $signature = $this->sppd->digitalSignatures()
      ->where('document_type', $documentType)
      ->latest('created_at')
      ->first();

    // Fallback ke document_type 'sppd' lama (sebelum skema per-pegawai).
    if (! $signature && $this->type === 'sppd') {
      $signature = $this->sppd->digitalSignatures()
        ->where('document_type', 'sppd')
        ->latest('created_at')
        ->first();
    }

    return $signature;
  }

  public function getIsSignedProperty(): bool
  {
    return $this->signature?->status === SignatureStatus::SIGNED;
  }

  public function getTypeLabelProperty(): string
  {
    return $this->type === 'spt' ? 'Surat Perintah Tugas (SPT)' : 'Surat Perjalanan Dinas (SPPD)';
  }

  /**
   * Apakah verifikasi kriptografis ke BSrE tersedia untuk dokumen ini.
   * Butuh: konfigurasi BSrE aktif + berkas PDF tersimpan.
   */
  public function getCanVerifyBsreProperty(): bool
  {
    return app(BsreVerificationService::class)->isEnabled()
      && (bool) $this->signature?->signed_file_path;
  }

  /**
   * Mode QR: kirim berkas PDF tersimpan ke BSrE untuk verifikasi kriptografis.
   */
  public function verifyWithBsre(BsreVerificationService $bsre): void
  {
    if (! $this->canVerifyBsre) {
      return;
    }

    $path = $this->signature->signed_file_path;
    $disk = Storage::disk(config('tte.storage.disk'));

    if (! $disk->exists($path)) {
      $this->bsreResult = [
        'ok' => false,
        'available' => true,
        'error' => 'Berkas dokumen tidak ditemukan pada penyimpanan.',
      ];
      $this->toastError($this->bsreResult['error'], 'Verifikasi BSrE');
      return;
    }

    $this->bsreResult = $bsre->verify($disk->get($path), basename($path));
    $this->toastForResult($this->bsreResult);
  }

  public function render()
  {
    return view('livewire.verify-document');
  }
}
