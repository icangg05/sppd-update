<?php

namespace App\Livewire\Sppd;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SppdIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  public ?string $errorMessage = null;

  // Konfirmasi hapus SPPD: tombol hapus baru aktif setelah hitung mundur DELETE_WAIT detik.
  public const DELETE_WAIT = 10;
  public bool $showDeleteModal = false;
  #[Locked]
  public ?int $deleteId = null;
  public ?string $deleteLabel = null;
  /** Status SPPD yang akan dihapus (untuk peringatan di modal). */
  public ?string $deleteStatus = null;
  /** Waktu modal dibuka (ms), agar hitung mundur juga dijaga di server. */
  #[Locked]
  public ?int $deleteRequestedAt = null;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $status = '';

  #[Url(keep: true)]
  public string $domain = '';

  #[Url(keep: true)]
  public string $jabatan = '';

  #[Url(keep: true)]
  public string $year = '';

  #[Url(keep: true)]
  public string $filter = '';

  /** '' | 'sudah' | 'belum' — sama dengan badge Realisasi di kolom Status. */
  #[Url(keep: true)]
  public string $realisasi = '';

  /** '' | 'sudah' | 'belum' — sama dengan badge Upload Laporan di kolom Status. */
  #[Url(keep: true)]
  public string $laporan = '';

  private const SESSION_KEY = 'sppd.index.filters';

  public static function jabatanLabels(): array
  {
    return [
      ''              => 'Semua Jabatan',
      'walikota'      => 'Walikota & Wakil',
      'kepala_opd'    => 'Kepala OPD',
      'eselon_staf'   => 'Eselon III, IV & Staf',
      'sekda_asisten' => 'Sekda, Asisten & Kabag',
      'staf_setda'    => 'Kasubag & Staf',
      'camat'         => 'Camat & Sekcam',
      'lurah'         => 'Lurah',
      'kapus'         => 'Kepala Puskesmas',
      'anggota_dprd'  => 'Anggota DPRD',
      'staff_dprd'    => 'Staff DPRD',
      'sekwan'        => 'Sekwan',
    ];
  }

  /**
   * Daftar tab filter jabatan yang ditampilkan sesuai jenis OPD (akar),
   * meniru perilaku sistem lama (sppd-2026) yang dibedakan per jenis_skpd.
   *
   * @return list<string>
   */
  public static function jabatanTabsFor(?DepartmentType $type): array
  {
    return match ($type) {
      DepartmentType::DPRD      => ['anggota_dprd', 'staff_dprd', 'sekwan'],
      DepartmentType::SETDA,
      DepartmentType::ASISTEN   => ['walikota', 'sekda_asisten', 'staf_setda'],
      DepartmentType::KECAMATAN => ['camat', 'eselon_staf'],
      DepartmentType::KELURAHAN => ['lurah', 'eselon_staf'],
      DepartmentType::PUSKESMAS => ['kapus', 'eselon_staf'],
      DepartmentType::DINKES    => ['kepala_opd', 'eselon_staf'],
      default                   => ['kepala_opd', 'eselon_staf'],
    };
  }

  /**
   * Jenis OPD akar milik user (telusuri ke parent teratas).
   */
  protected function rootDepartmentType(): ?DepartmentType
  {
    return Auth::user()->department?->getRootDepartment()?->type;
  }

  /**
   * Tab jabatan untuk user saat ini. Puskesmas (anak Dinkes) memakai konteksnya
   * sendiri — filter "Kepala Puskesmas" muncul di puskesmas, bukan di Dinkes induknya.
   */
  protected function currentJabatanTabs(): array
  {
    if (Auth::user()->department?->type === DepartmentType::PUSKESMAS) {
      return self::jabatanTabsFor(DepartmentType::PUSKESMAS);
    }

    return self::jabatanTabsFor($this->rootDepartmentType());
  }

  /**
   * Filter yang diingat lintas halaman: hanya Jabatan & Tahun (kartu judul). Filter di bar
   * (pencarian, status, domain, realisasi, laporan) kembali kosong setiap pindah halaman.
   */
  public static function savedFilters(): array
  {
    return Arr::only(session(self::SESSION_KEY, []), ['jabatan', 'year']);
  }

  public function isApprovalMode(): bool
  {
    return $this->filter === 'approval';
  }

  public function mount(): void
  {
    if ($this->isApprovalMode()) {
      return;
    }

    $saved = self::savedFilters();

    if ($this->jabatan === '' && ! empty($saved['jabatan'])) {
      $this->jabatan = $saved['jabatan'];
    }

    if ($this->year === '' && ! empty($saved['year'])) {
      $this->year = $saved['year'];
    }
  }

  public function activeFilterLabel(): string
  {
    return self::jabatanLabels()[$this->jabatan] ?? 'Semua Jabatan';
  }

  protected function persistFilters(): void
  {
    if ($this->isApprovalMode()) {
      return;
    }

    session([self::SESSION_KEY => [
      'jabatan' => $this->jabatan,
      'year'    => $this->year,
    ]]);
  }

  public function filterByJabatan(string $value): void
  {
    $this->jabatan = $value;
    $this->persistFilters();
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->year = '';
    $this->errorMessage = null;

    if (! $this->isApprovalMode()) {
      $this->status = '';
      $this->domain = '';
      $this->jabatan = '';
      $this->filter = '';
      $this->realisasi = '';
      $this->laporan = '';
      session()->forget(self::SESSION_KEY);
    }

    $this->resetPage();
  }

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedStatus(): void
  {
    $this->resetPage();
  }

  public function updatedDomain(): void
  {
    $this->resetPage();
  }

  public function updatedJabatan(): void
  {
    $this->persistFilters();
    $this->resetPage();
  }

  public function updatedYear(): void
  {
    $this->persistFilters();
    $this->resetPage();
  }

  public function updatedRealisasi(): void
  {
    $this->resetPage();
  }

  public function updatedLaporan(): void
  {
    $this->resetPage();
  }

  /** Sudah realisasi = pengeluaran riil dan rincian biaya sama-sama terisi. */
  private function whereRealized(Builder $query): Builder
  {
    return $query->whereHas('actualExpenses')->whereHas('costDetails');
  }

  /** Sudah upload laporan = tanggal, file laporan, dan foto dokumentasi lengkap. */
  private function whereReported(Builder $query): Builder
  {
    return $query->whereHas('report', fn ($r) => $r->whereNotNull('report_date')
      ->whereNotNull('report_file')
      ->whereNotNull('documentation_file'));
  }

  /**
   * Selesai = disetujui, perjalanan sudah lewat, sudah realisasi & upload laporan (syarat yang
   * sama dengan tombol SPPD Lanjutan). Status `completed` sendiri tidak pernah diisi alur.
   */
  private function whereFinished(Builder $query): Builder
  {
    $query->whereIn('status', [SppdStatus::APPROVED, SppdStatus::COMPLETED])
      ->whereDate('end_date', '<', today());

    return $this->whereReported($this->whereRealized($query));
  }

  /**
   * Filter Status mengikuti badge di kolom Status (partials/status-cell), satu SPPD satu status:
   * Revisi = masih proses dengan catatan revisi; Disetujui tidak mencakup yang sudah Selesai.
   */
  private function applyStatusFilter(Builder $query, string $status): void
  {
    $hasRevision = fn ($q) => $q->whereNotNull('revision_note')->where('revision_note', '<>', '');

    match ($status) {
      'in_progress' => $query->where('status', SppdStatus::IN_PROGRESS)->whereNot($hasRevision),
      'revision'    => $query->where('status', SppdStatus::IN_PROGRESS)->where($hasRevision),
      'approved'    => $query->where('status', SppdStatus::APPROVED)->whereNot(fn ($q) => $this->whereFinished($q)),
      'completed'   => $this->whereFinished($query),
      default       => $query->where('status', $status),
    };
  }

  /**
   * SPPD boleh dihapus (apa pun statusnya): Super Admin semua, Admin OPD hanya
   * SPPD pegawai dalam lingkup unit kerjanya.
   */
  private function canDelete(SppdRequest $sppd): bool
  {
    $user = Auth::user();
    if ($user->hasRole('super_admin')) {
      return true;
    }
    if (! $user->hasRole('admin_opd')) {
      return false;
    }

    $allowedIds = $user->department ? $user->department->getScopedRelatedIds() : collect([$user->department_id]);

    return $allowedIds->contains($sppd->user?->department_id);
  }

  /** Buka modal konfirmasi sebelum menghapus SPPD. */
  public function confirmDelete(int $id): void
  {
    $sppd = SppdRequest::with('user')->find($id);

    if (! $sppd || ! $this->canDelete($sppd)) {
      return;
    }

    $this->deleteId = $id;
    $this->deleteLabel = $sppd->user?->name;
    $this->deleteStatus = $sppd->status->value;
    $this->deleteRequestedAt = now()->getTimestampMs();
    $this->showDeleteModal = true;
  }

  /**
   * Tutup modal hapus. Nama & status SPPD sengaja dibiarkan agar isi modal tidak
   * berubah selama animasi tutup; keduanya diisi ulang saat confirmDelete berikutnya.
   */
  public function closeDeleteModal(): void
  {
    $this->showDeleteModal = false;
    $this->deleteId = null;
    $this->deleteRequestedAt = null;
  }

  public function deleteSppd(): void
  {
    $id = $this->deleteId;
    $requestedAt = $this->deleteRequestedAt;

    if (! $id) {
      $this->closeDeleteModal();
      return;
    }

    // Hitung mundur di modal juga dijaga di server (toleransi setengah detik).
    if (! $requestedAt || now()->getTimestampMs() - $requestedAt < (self::DELETE_WAIT * 1000 - 500)) {
      $this->toastError('Tunggu hitung mundur selesai sebelum menghapus.');
      return;
    }

    $this->closeDeleteModal();

    $sppd = SppdRequest::with('user')->findOrFail($id);
    if (! $this->canDelete($sppd)) {
      $this->toastError('Anda tidak memiliki hak untuk menghapus SPPD ini.');
      return;
    }

    $wasInProgress = $sppd->status === SppdStatus::IN_PROGRESS;
    $sppd->delete();
    $this->toastSuccess($wasInProgress ? 'Pengajuan SPPD berhasil dibatalkan dan dihapus.' : 'SPPD berhasil dihapus.');
  }

  public function render()
  {
    $query = SppdRequest::with(['user.department', 'category', 'budget.department', 'report', 'actualExpenses', 'costDetails']);
    $isApprovalMode = $this->isApprovalMode();

    if ($isApprovalMode) {
      $pendingSppdIds = SppdApproval::readyForApprover(Auth::id())
        ->pluck('sppd_request_id');
      $query->whereIn('id', $pendingSppdIds);
    } else {
      // Batasi data hanya untuk department user (beserta sub-department) — selain super admin.
      if (! Auth::user()->hasRole('super_admin')) {
        $dept = Auth::user()->department;
        if ($dept) {
          $allowedIds = $dept->getScopedRelatedIds();
          $query->whereHas('user', function ($q) use ($allowedIds) {
            $q->whereIn('department_id', $allowedIds);
          });
        } else {
          $query->whereHas('user', function ($q) {
            $q->where('department_id', Auth::user()->department_id);
          });
        }
      }

      if ($this->status !== '') {
        $this->applyStatusFilter($query, $this->status);
      }

      if ($this->domain !== '') {
        $query->where('domain', $this->domain);
      }

      // Realisasi & laporan hanya berlaku untuk perjalanan yang sudah disetujui dan sudah
      // lewat — sama dengan kapan badge-nya tampil di kolom Status (partials/status-cell).
      if ($this->realisasi !== '' || $this->laporan !== '') {
        $query->whereIn('status', [SppdStatus::APPROVED, SppdStatus::COMPLETED])
          ->whereDate('end_date', '<', today());
      }

      if ($this->realisasi === 'sudah') {
        $this->whereRealized($query);
      } elseif ($this->realisasi === 'belum') {
        $query->whereNot(fn ($q) => $this->whereRealized($q));
      }

      if ($this->laporan === 'sudah') {
        $this->whereReported($query);
      } elseif ($this->laporan === 'belum') {
        $query->whereNot(fn ($q) => $this->whereReported($q));
      }

      if ($this->jabatan !== '') {
        $jabatan = $this->jabatan;
        $query->whereHas('user', function ($q) use ($jabatan) {
          if ($jabatan === 'walikota') {
            $q->role(['walikota', 'wakil_walikota']);
          } elseif ($jabatan === 'kepala_opd') {
            $q->role('kepala_opd');
          } elseif ($jabatan === 'eselon_ii') {
            $q->role(['sekda', 'asisten', 'kepala_opd', 'sekwan']);
          } elseif ($jabatan === 'eselon_staf') {
            $q->role(['staf', 'admin_opd', 'sekretaris_opd', 'kasubid_kasubag', 'kabid_irban_kabag']);
          } elseif ($jabatan === 'eselon_iv') {
            $q->role(['kasubid_kasubag', 'sekcam', 'lurah', 'kapus']);
          } elseif ($jabatan === 'sekda_asisten') {
            $q->role(['sekda', 'asisten', 'kabid_irban_kabag']);
          } elseif ($jabatan === 'staf_setda') {
            $q->role(['kasubid_kasubag', 'staf']);
          } elseif ($jabatan === 'camat') {
            // Camat/Lurah/Kapus memakai role Kepala OPD di zona unitnya (data sudah
            // dibatasi per zona); role khusus lama tetap dihitung.
            $q->role(['camat', 'sekcam', 'kepala_opd']);
          } elseif ($jabatan === 'lurah') {
            $q->role(['lurah', 'kepala_opd']);
          } elseif ($jabatan === 'kapus') {
            $q->role(['kapus', 'kepala_opd']);
          } elseif ($jabatan === 'staf') {
            $q->role('staf');
          } elseif ($jabatan === 'anggota_dprd') {
            $q->role(User::DPRD_ROLES);
          } elseif ($jabatan === 'staff_dprd') {
            $q->role('staf')->whereHas('department', function ($d) {
              $d->where(function ($sub) {
                $sub->where('type', DepartmentType::DPRD)
                  ->orWhereHas('parent', function ($p) {
                    $p->where('type', DepartmentType::DPRD);
                  });
              });
            });
          } elseif ($jabatan === 'sekwan') {
            $q->role('sekwan');
          }
        });
      }
    }

    if ($this->year !== '') {
      $query->whereYear('start_date', $this->year);
    }

    if ($this->search !== '') {
      $search = $this->search;
      $query->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
      });
    }

    // Urut tanggal perjalanan terbaru (kolom Tanggal); tanggal sama → pengajuan terbaru dulu.
    $sppds = $query->orderByDesc('start_date')->latest()->paginate(15)->onEachSide(1);
    $domains = SppdDomain::cases();
    $title = $isApprovalMode ? 'Persetujuan' : 'Daftar SPPD';

    $activeFilterLabel = $this->activeFilterLabel();

    // Super admin memakai select-search (semua jabatan), selain itu tab sesuai jenis OPD.
    $isSuperAdmin = Auth::user()->hasRole('super_admin');
    $jabatanLabels = self::jabatanLabels();
    $jabatanTabs = $this->currentJabatanTabs();
    $jabatanOptions = collect($jabatanLabels)
      ->map(fn($label, $value) => ['value' => $value, 'label' => $label])
      ->values()
      ->all();

    // Urut tahapan; label sama dengan badge di kolom Status (lihat applyStatusFilter).
    $statusOptions = [
      ['value' => '', 'label' => 'Semua Status'],
      ['value' => 'in_progress', 'label' => 'Dalam Proses Pengajuan'],
      ['value' => 'revision', 'label' => 'Revisi'],
      ['value' => 'approved', 'label' => 'Disetujui'],
      ['value' => 'completed', 'label' => 'Selesai'],
      ['value' => 'rejected', 'label' => 'Ditolak'],
    ];

    // Tahun diambil dari data yang ada agar tidak menawarkan tahun kosong.
    $yearOptions = collect([['value' => '', 'label' => 'Semua Tahun']])
      ->concat(
        SppdRequest::query()
          ->selectRaw('DISTINCT YEAR(start_date) as y')
          ->orderByDesc('y')
          ->pluck('y')
          ->map(fn($y) => ['value' => (string) $y, 'label' => (string) $y])
      )
      ->values()
      ->all();

    $domainOptions = collect([['value' => '', 'label' => 'Semua Domain']])
      ->concat(collect($domains)->map(fn($dom) => [
        'value' => $dom->value,
        'label' => $dom->label(),
      ]))
      ->values()
      ->all();

    $realisasiOptions = [
      ['value' => '', 'label' => 'Semua Realisasi'],
      ['value' => 'sudah', 'label' => 'Sudah Realisasi'],
      ['value' => 'belum', 'label' => 'Belum Realisasi'],
    ];

    $laporanOptions = [
      ['value' => '', 'label' => 'Semua Laporan'],
      ['value' => 'sudah', 'label' => 'Sudah Upload Laporan'],
      ['value' => 'belum', 'label' => 'Belum Upload Laporan'],
    ];

    return view('livewire.sppd.index', compact(
      'sppds',
      'domains',
      'isApprovalMode',
      'activeFilterLabel',
      'isSuperAdmin',
      'jabatanLabels',
      'jabatanTabs',
      'jabatanOptions',
      'statusOptions',
      'domainOptions',
      'yearOptions',
      'realisasiOptions',
      'laporanOptions',
    ))->title($title);
  }

  /** Set pesan error inline sekaligus tampilkan toast (dipakai startSppdLanjutan). */
  private function failLanjutan(string $message): void
  {
    $this->errorMessage = $message;
    $this->toastError('Tidak dapat melanjutkan. Periksa pesan kesalahan di atas.');
  }

  /**
   * SPPD Lanjutan: buka Tahap 1 Buat SPPD dengan pelaksana yang sama agar domain perjalanan
   * dipilih dulu (domain SPPD sebelumnya terpilih sebagai bawaan). Cek perjalanan aktif, alur,
   * pejabat, dan kop surat dijalankan di sana sesuai domain yang dipilih.
   */
  public function startSppdLanjutan(int $sppdId): void
  {
    abort_unless(Auth::user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');

    $this->errorMessage = null;

    $sppd = SppdRequest::with('user')->find($sppdId);
    if (! $sppd) {
      $this->failLanjutan('Data SPPD tidak ditemukan.');
      return;
    }

    $user = $sppd->user;
    if (! $user) {
      $this->failLanjutan('Pegawai pelaksana tidak ditemukan.');
      return;
    }

    $this->redirect(
      route('sppd.create', [
        'user_id' => $user->getRouteKey(),
        'domain' => $sppd->domain->value,
      ]),
      navigate: true
    );
  }
}
