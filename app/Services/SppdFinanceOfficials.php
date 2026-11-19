<?php

namespace App\Services;

use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * PPTK, KPA (Setuju Bayar), dan Bendahara untuk dokumen biaya SPPD.
 *
 * Dipilih di Portal Dokumen (setOnSppd) untuk SPPD itu, sekaligus disimpan di pegawai pelaksana
 * (users.pptk_id/kpa_id/kpa_label/bendahara_id), jadi tidak hilang kecuali diedit. Saat SPPD
 * berikutnya pegawai itu diajukan, pilihan tersimpan dicatat ke SPPD baru (attributesFor); dokumen
 * biaya memakai catatan SPPD, sehingga pergantian pilihan tidak mengubah SPPD yang sudah ada.
 *
 * Kandidat: pegawai aktif di seluruh struktur instansi pelaksana (Department::instansiTreeIds).
 * Bendahara hanya pegawai yang jabatannya diawali "Bendahara"; label tercetaknya = nama jabatan itu.
 * Label KPA bawaan "Kuasa Pengguna Anggaran" dan bisa diedit.
 */
class SppdFinanceOfficials
{
  public const DEFAULT_KPA_LABEL = 'Kuasa Pengguna Anggaran';

  /** Awalan nama jabatan pegawai yang bisa dipilih sebagai bendahara. */
  public const BENDAHARA_POSITION_PREFIX = 'Bendahara';

  /** @var array<string, array{title: string, full: string, icon: string, desc: ?string, documents: array<int, string>}> */
  public const ROLES = [
    'pptk' => [
      'title' => 'PPTK',
      'full' => 'Pejabat Pelaksana Teknis Kegiatan',
      'icon' => 'fa-user-tie',
      'desc' => 'Menandatangani kolom Menyetujui pada pengeluaran riil dan rincian biaya.',
      'documents' => ['riil', 'rincian'],
    ],
    'kpa' => [
      'title' => 'Setuju Bayar (KPA)',
      'full' => 'Kuasa Pengguna Anggaran',
      'icon' => 'fa-stamp',
      'desc' => 'Menandatangani kolom Setuju Bayar / Mengetahui pada kuitansi, pengeluaran riil, dan rincian biaya.',
      'documents' => ['kuitansi', 'riil', 'rincian'],
    ],
    'bendahara' => [
      'title' => 'Bendahara',
      'full' => 'Bendahara',
      'icon' => 'fa-wallet',
      'desc' => null,
      'documents' => ['kuitansi', 'rincian'],
    ],
  ];

  /** Dokumen biaya yang memuat pejabat keuangan (tab pratinjau di modal pilih). */
  public const DOCUMENTS = [
    'kuitansi' => 'Kuitansi',
    'riil' => 'Pengeluaran Riil',
    'rincian' => 'Rincian Biaya',
  ];

  public static function isRole(?string $role): bool
  {
    return $role !== null && array_key_exists($role, self::ROLES);
  }

  /** @var array<int, array<int, int>> unit kerja per department_id pelaksana */
  private array $unitIdsCache = [];

  /**
   * Lingkup calon pejabat: seluruh struktur instansi pelaksana (Setda → Asisten → Bagian,
   * Dinkes → Puskesmas), bukan hanya zona datanya. Pegawai di luar struktur ini = lintas OPD.
   *
   * @return array<int, int>
   */
  public function instansiUnitIds(User $pelaksana): array
  {
    $department = $pelaksana->department;
    if (! $department) {
      return array_filter([$pelaksana->department_id]);
    }

    return $this->unitIdsCache[$department->id] ??= $department->instansiTreeIds()->all();
  }

  /** Calon pejabat: pegawai aktif di instansi pelaksana, tanpa akun administratif & Tenaga Mitra. */
  public function candidates(User $pelaksana, string $role): Builder
  {
    return User::where('is_active', true)
      ->whereIn('department_id', $this->instansiUnitIds($pelaksana))
      ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin_opd', 'super_admin']))
      ->exceptTenagaMitra()
      ->when($role === 'bendahara', fn (Builder $q) => $q->whereHas('position',
        fn ($p) => $p->where('name', 'like', self::BENDAHARA_POSITION_PREFIX . '%')));
  }

  /**
   * Hasil pencarian kandidat untuk modal pilih (nama/NIP), dibatasi agar instansi besar tidak
   * memuat ribuan pegawai sekaligus.
   *
   * @return Collection<int, User>
   */
  public function search(User $pelaksana, string $role, string $term = '', int $limit = 25): Collection
  {
    $term = trim($term);

    return $this->candidates($pelaksana, $role)
      ->with(['position:id,name', 'department:id,name'])
      ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
        ->where('name', 'like', "%{$term}%")
        ->orWhere('nip', 'like', "%{$term}%")))
      ->orderBy('name')
      ->limit($limit)
      ->get();
  }

  public function isCandidate(User $pelaksana, string $role, ?int $userId): bool
  {
    return $userId !== null && $this->candidates($pelaksana, $role)->whereKey($userId)->exists();
  }

  /**
   * Pilihan tersimpan pegawai pelaksana.
   *
   * @return array<string, array{user: ?User, label: ?string, warning: ?string}>
   */
  public function remembered(User $pelaksana): array
  {
    $pelaksana->loadMissing(['pptkChoice.position', 'pptkChoice.department', 'kpaChoice.position', 'kpaChoice.department',
      'bendaharaChoice.position', 'bendaharaChoice.department']);
    $chosen = ['pptk' => $pelaksana->pptkChoice, 'kpa' => $pelaksana->kpaChoice, 'bendahara' => $pelaksana->bendaharaChoice];

    return collect($chosen)->map(fn (?User $official, string $role) => [
      'user' => $official,
      'label' => match ($role) {
        'kpa' => $official ? ($pelaksana->kpa_label ?: self::DEFAULT_KPA_LABEL) : null,
        'bendahara' => $official ? $this->bendaharaLabel($official) : null,
        default => null,
      },
      'warning' => $this->warningFor($official, $pelaksana, $role),
    ])->all();
  }

  /** Peringatan bila pejabat pilihan tidak lagi sesuai (tetap tersimpan sampai diedit). */
  public function warningFor(?User $official, User $pelaksana, string $role): ?string
  {
    if (! $official) {
      return null;
    }

    return match (true) {
      ! $official->is_active => 'Pegawai ini sudah nonaktif. Pilih pengganti.',
      ! in_array((int) $official->department_id, $this->instansiUnitIds($pelaksana), true) => 'Pegawai ini sudah di luar instansi pelaksana. Pilih pengganti.',
      $role === 'bendahara' && ! str_starts_with((string) $official->position?->name, self::BENDAHARA_POSITION_PREFIX) => 'Jabatan pegawai ini bukan lagi bendahara. Periksa pilihannya.',
      default => null,
    };
  }

  /** Label bendahara yang tercetak: nama jabatan pegawainya. */
  public function bendaharaLabel(User $bendahara): string
  {
    return $bendahara->position?->name ?: self::BENDAHARA_POSITION_PREFIX;
  }

  /** Simpan satu pilihan untuk pegawai pelaksana (null = kosongkan). Tercatat di log aktivitas pegawai. */
  public function remember(User $pelaksana, string $role, ?User $official, ?string $label = null): void
  {
    $pelaksana->update(match ($role) {
      'pptk' => ['pptk_id' => $official?->id],
      'kpa' => ['kpa_id' => $official?->id, 'kpa_label' => $official ? $this->kpaLabel($label) : null],
      'bendahara' => ['bendahara_id' => $official?->id],
    });
  }

  /**
   * Kolom SPPD dari pilihan tersimpan pelaksana, untuk dicatat saat SPPD diajukan. Peran yang belum
   * dipilih tidak ikut, jadi isi SPPD yang sudah ada (mis. saat perbaikan) tidak terhapus.
   *
   * @return array<string, mixed>
   */
  public function attributesFor(User $pelaksana): array
  {
    $chosen = $this->remembered($pelaksana);
    $attributes = [];
    if ($chosen['pptk']['user']) {
      $attributes['pptk_id'] = $chosen['pptk']['user']->id;
    }
    if ($chosen['kpa']['user']) {
      $attributes += ['kpa_id' => $chosen['kpa']['user']->id, 'kpa_label' => $chosen['kpa']['label']];
    }
    if ($chosen['bendahara']['user']) {
      $attributes += ['bendahara_id' => $chosen['bendahara']['user']->id, 'bendahara_label' => $chosen['bendahara']['label']];
    }

    return $attributes;
  }

  /** Ubah satu pejabat SPPD (Portal Dokumen), sekaligus jadi pilihan tersimpan pegawai pelaksana. */
  public function setOnSppd(SppdRequest $sppd, string $role, ?User $official, ?string $label = null): void
  {
    $sppd->update(match ($role) {
      'pptk' => ['pptk_id' => $official?->id],
      'kpa' => ['kpa_id' => $official?->id, 'kpa_label' => $official ? $this->kpaLabel($label) : null],
      'bendahara' => ['bendahara_id' => $official?->id, 'bendahara_label' => $official ? $this->bendaharaLabel($official) : null],
    });

    if ($sppd->user) {
      $this->remember($sppd->user, $role, $official, $label);
    }
  }

  /**
   * Pratinjau blok tanda tangan dokumen biaya yang memuat peran ini, dengan $official sebagai pejabat
   * peran itu (pilihan di modal yang belum disimpan) dan pejabat lain dari SPPD. Judul kolom mengikuti
   * SppdController (stream kuitansi, pengeluaran riil, rincian biaya); slot peran yang diedit disorot.
   *
   * @return array<int, array{key: string, tab: string, heading: string, align: string, sections: array<int, array{divider: ?string, slots: array<int, array>}>}>
   */
  public function previewDocuments(SppdRequest $sppd, string $role, ?User $official, ?string $label = null): array
  {
    $sppd->loadMissing(['user', 'pptk', 'kpa.position', 'bendahara.position']);
    $pick = fn (string $r, ?User $current) => $r === $role ? $official : $current;
    $pptk = $pick('pptk', $sppd->pptk);
    $kpa = $pick('kpa', $sppd->kpa);
    $bendahara = $pick('bendahara', $sppd->bendahara);
    // Label kosong = SPPD lama: label bawaan per dokumen (lihat SppdController).
    $kpaLabel = $role === 'kpa' ? $this->kpaLabel($label) : $sppd->kpa_label;
    $bendaharaLabel = ($role === 'bendahara' ? ($official ? $this->bendaharaLabel($official) : null) : $sppd->bendahara_label)
      ?? 'Bendahara Pengeluaran';
    $kpaRole = $kpaLabel ?? $kpa?->position?->name ?? 'Kepala Dinas';

    $slot = fn (?string $slotRole, array $caption, string $title, ?User $person, bool $upper = false) => [
      'role' => $slotRole,
      'caption' => $caption,
      'title' => $title,
      'name' => $person?->name,
      'nip' => $person?->nip,
      'highlight' => $slotRole === $role,
      // Label KPA ikut isian modal seketika (x-text), tanpa menunggu render ulang.
      'live' => $slotRole === 'kpa' && $role === 'kpa',
      'upper' => $upper,
    ];
    $penerima = $sppd->user;

    $documents = [
      'kuitansi' => ['heading' => 'KUITANSI', 'align' => 'left', 'sections' => [
        ['divider' => null, 'slots' => [
          $slot('kpa', ['SETUJU BAYAR'], mb_strtoupper($kpaLabel ?? 'Pengguna Anggaran'), $kpa, true),
          $slot('bendahara', [], mb_strtoupper($bendaharaLabel), $bendahara, true),
          $slot(null, [], 'YANG MENERIMA', $penerima),
        ]],
      ]],
      'riil' => ['heading' => 'DAFTAR PENGELUARAN RIIL', 'align' => 'center', 'sections' => [
        ['divider' => null, 'slots' => [
          $slot('pptk', ['Menyetujui :'], 'Pejabat Pelaksana Teknis Kegiatan', $pptk),
          $slot(null, [], 'Yang Melakukan Perjalanan Dinas', $penerima),
        ]],
        ['divider' => null, 'slots' => [
          $slot('kpa', ['Mengetahui :'], $kpaRole, $kpa),
        ]],
      ]],
      'rincian' => ['heading' => 'RINCIAN BIAYA PERJALANAN DINAS', 'align' => 'left', 'sections' => [
        ['divider' => null, 'slots' => [
          $slot('bendahara', ['Telah dibayarkan Sejumlah', 'Rp. …'], $bendaharaLabel, $bendahara),
          $slot(null, ['Kendari, …', 'Telah Menerima Jumlah Uang Sebesar,', 'Rp. …'], 'Yang Menerima', $penerima),
        ]],
        ['divider' => 'PERHITUNGAN SPPD RAMPUNG', 'slots' => [
          $slot('kpa', ['Setuju bayar:'], $kpaRole, $kpa),
          $slot('pptk', ['Mengetahui/Menyetujui'], 'Pejabat Pelaksana Teknis Kegiatan', $pptk),
        ]],
      ]],
    ];

    return collect(self::ROLES[$role]['documents'])
      ->map(fn (string $key) => ['key' => $key, 'tab' => self::DOCUMENTS[$key]] + $documents[$key])
      ->all();
  }

  private function kpaLabel(?string $label): string
  {
    return mb_substr(trim((string) $label), 0, 150) ?: self::DEFAULT_KPA_LABEL;
  }
}
