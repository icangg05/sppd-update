<?php

namespace App\Livewire\Sppd;

use App\Enums\ApprovalStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Country;
use App\Models\ForeignCity;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdFollower;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\ApproverNotifier;
use App\Services\ImageCompressor;
use App\Services\QueueHealthService;
use App\Services\SppdWorkflowService;
use App\Support\DocumentFolder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class SppdCreateDetails extends Component
{
  use WithFileUploads;
  use InteractsWithToast;

  // Route inputs
  public int $user_id;
  public string $domain;
  public ?int $sppd_id = null;

  // Form inputs
  public ?int $budget_id = null;
  public ?int $category_id = null;
  public string $purpose = '';
  public string $problem = '';
  public string $facts = '';
  public string $analysis = '';

  public string $start_date;
  public string $end_date;
  public string $transport_type = '';
  public string $transport_name = '';
  /** Pilihan dropdown kendaraan; OTHER_TRANSPORT membuka isian manual `transport_name`. */
  public string $transport_choice = '';
  public string $departure_place = '';
  public string $urgency = 'Biasa';

  public string $spt_date = '';
  public string $sppd_date = '';
  public ?string $document_number = null;
  public $attachment;

  // Followers
  public array $followers = [];
  public array $follower_positions = [];
  public string $searchFollower = '';

  // Destinations
  public array $destinations = [];

  /** Nilai pilihan kota luar negeri "Kota lain (ketik manual)". */
  public const OTHER_CITY = '__other__';

  /** Pilihan nama kendaraan per jenis angkutan (tercetak di dokumen SPPD). */
  public const TRANSPORT_OPTIONS = [
    'Darat' => ['Mobil', 'Bus', 'Motor', 'Kereta Api'],
    'Laut' => ['Kapal', 'Speedboat'],
    'Udara' => ['Pesawat', 'Helikopter'],
  ];

  /** Nilai pilihan kendaraan "Lainnya (ketik manual)". */
  public const OTHER_TRANSPORT = '__other__';

  /** Memo daftar kota per negara selama satu request (dipanggil per baris tujuan saat render). */
  protected array $citiesByCountry = [];

  // Modal Confirmation Control
  public bool $showConfirmModal = false;

  public function mount(): void
  {
    abort_unless(Auth::user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Hanya Admin OPD atau Super Admin yang dapat mengisi detail SPPD.');

    $sppdId = request('sppd_id') ? (int) request('sppd_id') : null;

    if ($sppdId) {
      $sppd = SppdRequest::with(['destinations', 'followers', 'user.department'])->findOrFail($sppdId);
      $this->sppd_id = $sppd->id;
      $this->user_id = $sppd->user_id;
      $this->domain = $sppd->domain->value;

      $this->budget_id = $sppd->budget_id;
      $this->category_id = $sppd->category_id;
      $this->purpose = $sppd->purpose;
      $this->problem = $sppd->problem ?? '';
      $this->facts = $sppd->facts ?? '';
      $this->analysis = $sppd->analysis ?? '';
      $this->start_date = $sppd->start_date->format('Y-m-d');
      $this->end_date = $sppd->end_date->format('Y-m-d');
      $this->transport_type = $sppd->transport_type ?? '';
      $this->transport_name = $sppd->transport_name ?? '';
      $this->transport_choice = $this->transportChoiceFor($this->transport_name);
      $this->departure_place = $sppd->departure_place ?? '';
      $this->urgency = $sppd->urgency ?? 'Biasa';
      $this->spt_date = $sppd->spt_date ? $sppd->spt_date->format('Y-m-d') : '';
      $this->sppd_date = $sppd->sppd_date ? $sppd->sppd_date->format('Y-m-d') : '';
      $this->document_number = $sppd->document_number;

      $this->followers = $sppd->followers->pluck('user_id')->toArray();
      $this->follower_positions = $sppd->followers->pluck('travel_position', 'user_id')->toArray();

      $this->destinations = [];
      foreach ($sppd->destinations as $dest) {
        $isDalamDaerah = $this->domain === 'dalam_daerah';
        $this->destinations[] = [
          'province_id' => $dest->province_id,
          'regency_id' => $dest->regency_id,
          'country' => $dest->country ?? '',
          'city' => $dest->city ?? '',
          'city_choice' => $this->cityChoiceFor($dest->country, $dest->city),
          'address' => $isDalamDaerah ? '' : $dest->address,
          'address_only' => $isDalamDaerah ? $dest->address : '',
        ];
      }

      $user = $sppd->user;
    } else {
      $this->user_id = (int) request('user_id');
      $this->domain = request('domain', 'dalam_daerah');

      // Tanggal perjalanan sengaja kosong sampai dipilih sendiri: tanggal bawaan membuat
      // pengikut yang SPPD-nya kebetulan beririsan tampak bentrok padahal belum tentu.
      $this->start_date = '';
      $this->end_date = '';
      $this->spt_date = date('Y-m-d');
      $this->sppd_date = date('Y-m-d');

      $user = User::with('department')->find($this->user_id);
      if (! $user) {
        redirect()->route('sppd.create')->with('error', 'Pegawai tidak ditemukan.');
        return;
      }

      $isInspektorat = (bool) $user->department?->usesManualNumbering();
      if ($isInspektorat) {
        $this->document_number = '090 / isi_nomor_surat_tugas / ST / INSP./ 2026';
      }

      $this->destinations = [$this->emptyDestination()];

      // SPPD aktif lain tidak menghalangi: yang dicek adalah bentrok tanggal saat diajukan.

      // Alur wajib lengkap (semua tahap punya pejabat pasti) sebelum mengisi detail.
      if (! SppdWorkflowService::isComplete(app(SppdWorkflowService::class)->simulateApprovals($user, $this->domain))) {
        redirect()->route('sppd.create', ['user_id' => $user->getRouteKey(), 'domain' => $this->domain])
          ->with('error', 'Alur persetujuan ' . $user->name . ' belum lengkap. Lengkapi atau pilih pejabat pada tahap yang masih kosong terlebih dahulu.');
        return;
      }
    }

    // Security check in mount
    if (! Auth::user()->hasRole('super_admin')) {
      $dept = Auth::user()->department;
      if ($dept) {
        $allowedIds = $dept->getScopedRelatedIds();
        if (! $allowedIds->contains($user->department_id)) {
          abort(403, 'Unauthorized action.');
        }
      } elseif ($user->department_id !== Auth::user()->department_id) {
        abort(403, 'Unauthorized action.');
      }
    }
  }

  /**
   * Saat provinsi sebuah tujuan diganti, kosongkan kabupaten/kota-nya agar tidak
   * tertinggal pilihan dari provinsi sebelumnya (yang kini tidak valid).
   */
  public function updated($name, $value): void
  {
    // Nama kendaraan mengikuti jenis angkutan; kosongkan bila tidak lagi cocok
    // (termasuk isian manual, karena ditulis untuk jenis angkutan sebelumnya).
    if ($name === 'transport_type' && ! in_array($this->transport_choice, $this->transportNames(), true)) {
      $this->transport_choice = '';
      $this->transport_name = '';
    }

    if ($name === 'transport_choice') {
      $this->transport_name = $value === self::OTHER_TRANSPORT ? '' : (string) $value;
    }

    if (preg_match('/^destinations\.(\d+)\.province_id$/', $name, $m)) {
      $this->destinations[(int) $m[1]]['regency_id'] = '';
    }

    // Luar negeri: ganti negara mengosongkan kota; pilihan kota dari daftar
    // langsung mengisi `city`, sedangkan "Kota lain" membuka isian manual.
    if (preg_match('/^destinations\.(\d+)\.country$/', $name, $m)) {
      $this->destinations[(int) $m[1]]['city'] = '';
      $this->destinations[(int) $m[1]]['city_choice'] = '';
    }

    if (preg_match('/^destinations\.(\d+)\.city_choice$/', $name, $m)) {
      $this->destinations[(int) $m[1]]['city'] = $value === self::OTHER_CITY ? '' : (string) $value;
    }
  }

  /** Nama kendaraan yang sesuai dengan jenis angkutan terpilih. */
  public function transportNames(): array
  {
    return self::TRANSPORT_OPTIONS[$this->transport_type] ?? [];
  }

  /** Pilihan dropdown untuk data tersimpan: kendaraan dari daftar, atau "Lainnya" bila diketik manual. */
  protected function transportChoiceFor(string $name): string
  {
    if ($name === '') {
      return '';
    }

    return in_array($name, $this->transportNames(), true) ? $name : self::OTHER_TRANSPORT;
  }

  /** Nama kota (urut abjad) milik negara tujuan luar negeri. */
  public function getCitiesForCountry(?string $country): array
  {
    if (blank($country)) {
      return [];
    }

    return $this->citiesByCountry[$country] ??= ForeignCity::whereHas('country', fn ($q) => $q->where('name', $country))
      ->orderBy('name')
      ->pluck('name')
      ->all();
  }

  /** Pilihan dropdown kota untuk data tersimpan: kota dari daftar, atau "Kota lain" bila diketik manual. */
  protected function cityChoiceFor(?string $country, ?string $city): string
  {
    if (blank($city)) {
      return '';
    }

    $match = collect($this->getCitiesForCountry($country))
      ->first(fn ($name) => mb_strtolower($name) === mb_strtolower($city));

    return $match ?? self::OTHER_CITY;
  }

  public function getRegenciesForProvince($provinceId)
  {
    if (empty($provinceId)) {
      return collect();
    }

    $query = Regency::where('province_id', $provinceId)->orderBy('name');

    if ($this->domain === 'lddp') {
      $query->where('name', 'NOT LIKE', '%' . config('sppd.home_base_regency_keyword') . '%');
    }

    return $query->get();
  }

  public function addDestination(): void
  {
    if (count($this->destinations) >= 4) {
      return;
    }

    $this->destinations[] = $this->emptyDestination();
  }

  /** Baris tujuan kosong; LDDP otomatis memakai provinsi induk. */
  protected function emptyDestination(): array
  {
    $seSultra = $this->domain === 'lddp'
      ? Province::where('name', config('sppd.default_province_name'))->first()
      : null;

    return [
      'province_id' => $seSultra?->id ?? '',
      'regency_id' => '',
      'country' => '',
      'city' => '',
      'city_choice' => '',
      'address' => '',
      'address_only' => '',
    ];
  }

  public function removeDestination(int $index): void
  {
    if (count($this->destinations) <= 1) {
      return;
    }

    unset($this->destinations[$index]);
    $this->destinations = array_values($this->destinations);
  }

  public function openConfirmModal(): void
  {
    // Umpan balik dini: jangan buka modal konfirmasi bila worker queue mati,
    // karena notifikasi ke pejabat tidak akan terkirim.
    if (! app(QueueHealthService::class)->isWorkerHealthy()) {
      $this->toastError($this->queueDownMessage());
      return;
    }

    try {
      $this->validate([
        'budget_id' => 'required|exists:budgets,id',
        'category_id' => 'required|exists:sppd_categories,id',
        'purpose' => 'required|string|max:1000',
        'problem' => 'nullable|string',
        'facts' => 'nullable|string',
        'analysis' => 'nullable|string',
        'start_date' => ['required', 'date', $this->workdayRule('berangkat')],
        'end_date' => ['required', 'date', 'after_or_equal:start_date', $this->workdayRule('kembali')],
        'transport_type' => ['nullable', Rule::in(array_keys(self::TRANSPORT_OPTIONS))],
        'transport_name' => $this->transport_choice === self::OTHER_TRANSPORT
          ? ['required', 'string', 'max:255']
          : ['nullable', Rule::in($this->transportNames())],
        'departure_place' => 'nullable|string|max:255',
        'urgency' => 'nullable|string|max:255',
        'spt_date' => 'nullable|date',
        'sppd_date' => 'nullable|date',
        'document_number' => 'nullable|string|max:255',
        // Gambar & berkas lain punya batas sendiri (Pengaturan Aplikasi).
        'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:' . app(AppSettings::class)->uploadMaxKb(ImageCompressor::isImage($this->attachment))],
        'destinations' => 'required|array|min:1',
        'destinations.*.province_id' => 'required_if:domain,lddp,ldlp',
        'destinations.*.regency_id' => 'required_if:domain,lddp,ldlp',
        'destinations.*.country' => ['required_if:domain,luar_negeri', Rule::exists('countries', 'name')],
        'destinations.*.city' => 'required_if:domain,luar_negeri|string|max:150',
        'destinations.*.address' => 'required_if:domain,lddp,ldlp,luar_negeri|string|max:500',
        'destinations.*.address_only' => 'required_if:domain,dalam_daerah|string|max:500',
        'followers' => 'nullable|array',
        'followers.*' => 'exists:users,id',
      ], [
        'destinations.*.country.required_if' => 'Negara tujuan wajib dipilih.',
        'destinations.*.country.exists' => 'Negara tujuan tidak valid.',
        'destinations.*.city.required_if' => 'Kota tujuan wajib diisi.',
        'transport_name.in' => 'Nama kendaraan tidak sesuai dengan jenis angkutan.',
        'transport_name.required' => 'Nama kendaraan wajib diketik.',
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      // Toast ringkas selain error inline, lalu lempar ulang agar modal tetap
      // tertahan dan pesan @error tetap tampil di masing-masing field.
      $count = $e->validator->errors()->count();
      $this->toastError(
        "Ada {$count} isian yang belum lengkap atau tidak valid. Silakan periksa kembali formulir.",
        'Gagal Mengajukan',
      );
      throw $e;
    }

    if (! $this->sppd_id && ! SppdWorkflowService::isComplete(app(SppdWorkflowService::class)->simulateApprovals(User::findOrFail($this->user_id), $this->domain))) {
      $this->toastError('Alur persetujuan belum lengkap. Kembali ke Tahap 1 untuk melengkapi pejabatnya.', 'Gagal Mengajukan');
      return;
    }

    if ($busy = $this->busyTravelerMessage()) {
      $this->addError('start_date', $busy);
      $this->toastError($busy, 'Gagal Mengajukan');
      return;
    }

    $this->showConfirmModal = true;
  }

  public function closeConfirmModal(): void
  {
    $this->showConfirmModal = false;
  }

  /**
   * Pesan penolakan bila tanggal perjalanan bentrok dengan SPPD lain milik pelaksana atau
   * pengikut (di OPD mana pun), atau null. SPPD aktif yang tanggalnya tidak bentrok boleh.
   */
  private function busyTravelerMessage(): ?string
  {
    $period = $this->travelPeriod();
    if (! $period) {
      return null;
    }

    $travelers = $this->travelers();
    $clashes = SppdRequest::clashingForUsers($travelers, $period[0], $period[1], $this->sppd_id);

    foreach ($travelers as $traveler) {
      if (isset($clashes[$traveler->id])) {
        return "{$traveler->name} {$clashes[$traveler->id]->clashMessage()}";
      }
    }

    return null;
  }

  /** Pelaksana lalu pengikut sesuai urutan dipilih. */
  private function travelers(): Collection
  {
    $ids = [$this->user_id, ...array_map('intval', $this->followers)];

    return User::whereIn('id', $ids)->get()
      ->sortBy(fn (User $u) => array_search($u->id, $ids, true))
      ->values();
  }

  /**
   * Rentang tanggal yang sudah terpakai SPPD lain milik pelaksana atau pengikut terpilih,
   * untuk dinonaktifkan di kalender. Pengecekan otoritatif tetap busyTravelerMessage().
   *
   * @return list<array{start:string, end:string, label:string}>
   */
  private function blockedRanges(): array
  {
    $travelers = $this->travelers();
    $trips = SppdRequest::scheduledForUsers($travelers, $this->sppd_id);

    $ranges = [];
    foreach ($travelers as $traveler) {
      foreach ($trips[$traveler->id] ?? [] as $trip) {
        $ranges[$trip->id] ??= [
          'start' => $trip->start_date->format('Y-m-d'),
          'end' => $trip->end_date->format('Y-m-d'),
          'label' => "{$trip->periodLabel(true)} ({$traveler->name})",
        ];
      }
    }

    return array_values($ranges);
  }

  /** Tanggal berangkat & kembali yang valid (kembali tidak sebelum berangkat), atau null. */
  private function travelPeriod(): ?array
  {
    try {
      $start = Carbon::createFromFormat('!Y-m-d', $this->start_date);
      $end = Carbon::createFromFormat('!Y-m-d', $this->end_date);
    } catch (\Throwable) {
      return null;
    }

    return $start && $end && $end->gte($start) ? [$start, $end] : null;
  }

  /** Dalam daerah tidak menghitung Sabtu & Minggu, jadi tidak boleh berangkat/kembali di hari itu. */
  private function workdayRule(string $moment): \Closure
  {
    return function (string $attribute, mixed $value, \Closure $fail) use ($moment) {
      if (! SppdDomain::tryFrom($this->domain)?->skipsWeekends()) {
        return;
      }

      try {
        $date = Carbon::parse($value);
      } catch (\Throwable) {
        return; // Format tanggal sudah ditangani aturan 'date'.
      }

      if ($date->isWeekend()) {
        $fail("Perjalanan dalam daerah tidak bisa {$moment} pada hari {$date->translatedFormat('l')}, karena Sabtu & Minggu tidak dihitung. Pilih hari kerja.");
      }
    };
  }

  public function submit()
  {
    // Gerbang otoritatif: tolak bila worker queue tidak berjalan, sehingga
    // notifikasi Telegram ke approver dijamin dapat dikirim.
    if (! app(QueueHealthService::class)->isWorkerHealthy()) {
      $this->toastError($this->queueDownMessage());
      $this->showConfirmModal = false;
      return;
    }

    // Gerbang akhir: SPPD di OPD lain bisa saja terbit setelah modal konfirmasi dibuka.
    if (! $this->travelPeriod()) {
      $this->toastError('Tanggal kembali tidak boleh sebelum tanggal berangkat.', 'Gagal Mengajukan');
      $this->showConfirmModal = false;
      return;
    }
    if ($busy = $this->busyTravelerMessage()) {
      $this->toastError($busy, 'Gagal Mengajukan');
      $this->showConfirmModal = false;
      return;
    }

    $user = User::with('department')->findOrFail($this->user_id);
    $isInspektorat = (bool) $user->department?->usesManualNumbering();

    // Validasi tambahan untuk Inspektorat
    if ($isInspektorat) {
      foreach ($this->followers as $fId) {
        $jabatan = $this->follower_positions[$fId] ?? '';
        if (! in_array($jabatan, SppdFollower::TRAVEL_POSITIONS, true)) {
          $this->addError('follower_positions.' . $fId, $jabatan === '' ? 'Jabatan pengikut harus diisi.' : 'Jabatan pengikut tidak dikenal.');
          $this->toastError('Jabatan setiap pengikut wajib dipilih dari daftar sebelum mengajukan.', 'Data Belum Lengkap');
          $this->showConfirmModal = false;
          return;
        }
      }
    }

    try {
      DB::transaction(function () use (&$sppd) {
        if ($this->sppd_id) {
          $sppd = SppdRequest::findOrFail($this->sppd_id);

          $attachmentPath = $sppd->attachment;
          if ($this->attachment) {
            $attachmentPath = app(ImageCompressor::class)->storeWithOriginalName($this->attachment, DocumentFolder::path('dokumen-pendukung'));
          }

          // Ringkasan relasi (tujuan & pengikut) untuk dicatat ke log aktivitas,
          // karena perubahannya tidak terekam otomatis (tabel terpisah, di-recreate).
          $summarizeRelations = function (SppdRequest $s): array {
            return [
              'destinations' => $s->destinations()->with('regency')->orderBy('id')->get()
                ->map(fn ($d) => collect([$d->address, $d->place_name])->filter()->implode(', '))
                ->implode(' | '),
              'followers' => $s->followers()->with('user')->orderBy('id')->get()
                ->map(fn ($f) => $f->user?->name ?? ('#' . $f->user_id))
                ->implode(', '),
            ];
          };
          $relationsBefore = $summarizeRelations($sppd);
          $activityMaxBefore = (int) \Spatie\Activitylog\Models\Activity::max('id');

          $sppd->update([
            'budget_id' => $this->budget_id,
            'category_id' => $this->category_id,
            'purpose' => $this->purpose,
            'problem' => $this->problem,
            'facts' => $this->facts,
            'analysis' => $this->analysis,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'transport_type' => $this->transport_type,
            'transport_name' => $this->transport_name,
            'departure_place' => $this->departure_place,
            'domain' => $this->domain,
            'urgency' => $this->urgency,
            'sppd_date' => $this->sppd_date,
            'spt_date' => $this->spt_date,
            'document_number' => $this->document_number,
            'attachment' => $attachmentPath,
            'revision_note' => null,
            'reviser_id' => null,
          ]);

          // Data dokumen berubah: TTE lama tidak berlaku (pengaman bila belum direset saat revisi).
          $sppd->resetDigitalSignatures();

          $sppd->destinations()->delete();
          $sppd->followers()->delete();

          $this->saveDestinations($sppd);

          // Save followers
          if (! empty($this->followers)) {
            foreach ($this->followers as $userId) {
              $sppd->followers()->create([
                'user_id' => $userId,
                'travel_position' => $this->follower_positions[$userId] ?? null,
              ]);
            }
          }

          // Catat perubahan relasi (tujuan/pengikut) ke log aktivitas bila berubah.
          $relationsAfter = $summarizeRelations($sppd);
          $relOld = [];
          $relNew = [];
          foreach (['destinations', 'followers'] as $key) {
            if ($relationsBefore[$key] !== $relationsAfter[$key]) {
              $relOld[$key] = $relationsBefore[$key];
              $relNew[$key] = $relationsAfter[$key];
            }
          }
          if (! empty($relNew)) {
            // Gabungkan ke log otomatis dari update() agar tetap satu kesatuan.
            $autoLog = \Spatie\Activitylog\Models\Activity::where('subject_type', SppdRequest::class)
              ->where('subject_id', $sppd->id)
              ->where('id', '>', $activityMaxBefore)
              ->latest('id')
              ->first();

            if ($autoLog) {
              $changes = $autoLog->attribute_changes;
              $autoLog->attribute_changes = [
                'old'        => array_merge((array) data_get($changes, 'old', []), $relOld),
                'attributes' => array_merge((array) data_get($changes, 'attributes', []), $relNew),
              ];
              $autoLog->save();
            } else {
              // Tidak ada perubahan field utama → buat log baru khusus relasi.
              activity()
                ->performedOn($sppd)
                ->event('updated')
                ->withChanges(['old' => $relOld, 'attributes' => $relNew])
                ->log('SPPD ' . ($sppd->document_number ?: '#' . $sppd->id) . ' diperbarui');
            }
          }
        } else {
          $attachmentPath = null;
          if ($this->attachment) {
            $attachmentPath = app(ImageCompressor::class)->storeWithOriginalName($this->attachment, DocumentFolder::path('dokumen-pendukung'));
          }

          $sppd = SppdRequest::create([
            'user_id' => $this->user_id,
            'creator_id' => Auth::id(),
            'budget_id' => $this->budget_id,
            'category_id' => $this->category_id,
            'purpose' => $this->purpose,
            'problem' => $this->problem,
            'facts' => $this->facts,
            'analysis' => $this->analysis,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'transport_type' => $this->transport_type,
            'transport_name' => $this->transport_name,
            'departure_place' => $this->departure_place,
            'domain' => $this->domain,
            'urgency' => $this->urgency,
            'sppd_date' => $this->sppd_date,
            'spt_date' => $this->spt_date,
            'status' => SppdStatus::IN_PROGRESS,
            'document_number' => $this->document_number,
            'attachment' => $attachmentPath,
          ]);

          $this->saveDestinations($sppd);

          // Save followers
          if (! empty($this->followers)) {
            foreach ($this->followers as $userId) {
              $sppd->followers()->create([
                'user_id' => $userId,
                'travel_position' => $this->follower_positions[$userId] ?? null,
              ]);
            }
          }

          // Generate approvals
          $workflowService = app(SppdWorkflowService::class);
          $success = $workflowService->generateApprovals($sppd);

          if (! $success) {
            throw new \Exception('Sistem belum memiliki aturan Workflow untuk instansi, peran pemohon, atau tujuan tersebut.');
          }
        }
      });

      // Dispatch notification
      if (isset($sppd)) {
        if ($this->sppd_id) {
          // Tahap pertama yang masih menunggu = yang sekarang giliran: pejabat yang meminta
          // revisi, atau tahap penanda tangan sebelumnya bila TTE-nya direset.
          $approval = $sppd->approvals()
            ->where('status', ApprovalStatus::PENDING)
            ->orderBy('step_order', 'asc')
            ->first();

          if ($approval) {
            $this->notifyApproverRevision($sppd, $approval);
          }

          return redirect()->route('sppd.show', $sppd)
            ->with('success', 'Perbaikan SPPD berhasil dikirim. Silakan menunggu proses persetujuan.');
        } else {
          $firstApproval = $sppd->approvals()
            ->orderBy('step_order', 'asc')
            ->where('status', ApprovalStatus::PENDING)
            ->first();

          if ($firstApproval) {
            $this->notifyApprover($sppd, $firstApproval);
          }

          return redirect()->route('sppd.show', $sppd)
            ->with('success', 'SPPD berhasil dibuat dan diajukan. Silakan menunggu proses persetujuan.');
        }
      }
    } catch (\Exception $e) {
      $this->toastError($e->getMessage());
      $this->showConfirmModal = false;
    }
  }

  /**
   * Pratinjau alur: susunan pejabat yang sudah tetap untuk SPPD yang diperbaiki,
   * atau simulasi untuk pengajuan baru (selalu lengkap, lihat mount).
   */
  protected function workflowSteps(User $pelaksana): array
  {
    if ($this->sppd_id) {
      return SppdApproval::with('approver')
        ->where('sppd_request_id', $this->sppd_id)
        ->orderBy('step_order')
        ->get()
        ->map(fn (SppdApproval $approval) => [
          'index'         => $approval->step_order,
          'role_label'    => $approval->role_label,
          'approver_name' => $approval->approver?->name ?? '-',
          'status'        => 'found',
          'candidates'    => [],
        ])
        ->all();
    }

    return app(SppdWorkflowService::class)->simulateApprovals($pelaksana, $this->domain);
  }

  /** Simpan baris tujuan sesuai domain perjalanan. */
  protected function saveDestinations(SppdRequest $sppd): void
  {
    foreach ($this->destinations as $dest) {
      if ($this->domain === 'dalam_daerah') {
        // Dalam daerah selalu bertujuan di tempat kedudukan (provinsi & kota induk).
        $sultra = Province::where('name', config('sppd.default_province_name'))->first();
        $kendari = Regency::where('name', 'LIKE', '%' . config('sppd.home_base_regency_keyword') . '%')->where('province_id', $sultra?->id)->first();

        $sppd->destinations()->create([
          'address' => $dest['address_only'],
          'province_id' => $sultra?->id,
          'regency_id' => $kendari?->id,
        ]);
      } elseif ($this->domain === 'luar_negeri') {
        $sppd->destinations()->create([
          'country' => $dest['country'] ?? null,
          'city' => $dest['city'] ?? null,
          'address' => $dest['address'] ?? null,
        ]);
      } else {
        $sppd->destinations()->create([
          'province_id' => $dest['province_id'] ?? null,
          'regency_id' => $dest['regency_id'] ?? null,
          'address' => $dest['address'] ?? null,
        ]);
      }
    }
  }

  protected function queueDownMessage(): string
  {
    return 'Pengajuan ditolak: layanan antrian (queue worker) sedang tidak berjalan, '
      . 'sehingga notifikasi ke pejabat tidak dapat dikirim. Hubungi administrator sistem '
      . 'dan coba lagi setelah layanan aktif.';
  }

  protected function notifyApprover(SppdRequest $sppd, $approval): void
  {
    app(ApproverNotifier::class)->notifyNewRequest($sppd, $approval);
  }

  protected function notifyApproverRevision(SppdRequest $sppd, $approval): void
  {
    app(ApproverNotifier::class)->notifyRevision($sppd, $approval);
  }

  public function render()
  {
    $pelaksana = User::with('department')->findOrFail($this->user_id);

    // Security check
    if (! Auth::user()->hasRole('super_admin')) {
      $dept = Auth::user()->department;
      if ($dept) {
        $allowedIds = $dept->getScopedRelatedIds();
        if (! $allowedIds->contains($pelaksana->department_id)) {
          abort(403, 'Unauthorized action.');
        }
      } elseif ($pelaksana->department_id !== Auth::user()->department_id) {
        abort(403, 'Unauthorized action.');
      }
    }

    // Budget query — DPA mengikuti zona data OPD pelaksana, bukan department
    // akun yang login. Akun admin OPD induk bisa mengakses banyak department,
    // sehingga scoping harus berdasarkan root zona pelaksana yang dipilih.
    $budgetQuery = Budget::with('department')->where('year', now()->year);
    $pelaksanaDept = $pelaksana->department;
    if ($pelaksanaDept) {
      $budgetQuery->whereIn('department_id', $pelaksanaDept->getScopeRootDepartment()->getScopedRelatedIds());
    } else {
      $budgetQuery->where('department_id', $pelaksana->department_id);
    }
    $budgets = $budgetQuery->get();

    $categories = SppdCategory::all();

    // Followers candidate query
    $userQuery = User::where('is_active', true)
      // Akun administratif (super_admin & admin_opd) bukan pengikut perjalanan.
      ->whereDoesntHave('roles', function ($q) {
        $q->whereIn('name', ['super_admin', 'admin_opd']);
      });
    if (Auth::user()->hasRole('super_admin')) {
      // Pengikut harus sesuai department pelaksana (zona data OPD pelaksana).
      $pelaksanaDept = $pelaksana->department;
      if ($pelaksanaDept) {
        $userQuery->whereIn('department_id', $pelaksanaDept->getScopeRootDepartment()->getScopedRelatedIds());
      } else {
        $userQuery->where('department_id', $pelaksana->department_id);
      }
    } else {
      $dept = Auth::user()->department;
      if ($dept) {
        $userQuery->whereIn('department_id', $dept->getScopedRelatedIds());
      } else {
        $userQuery->where('department_id', Auth::user()->department_id);
      }
    }

    // Cocokkan status DPRD pengikut dengan pelaksana: pelaksana anggota DPRD
    // hanya boleh mengikutsertakan sesama anggota DPRD, dan sebaliknya.
    $dprdMember = fn ($q) => $q->whereHas('roles', fn ($r) => $r->whereIn('name', User::DPRD_ROLES));
    if ($pelaksana->isDprdMember()) {
      $userQuery->where($dprdMember);
    } else {
      $userQuery->whereNot($dprdMember);
    }

    // Tampilkan default 100 pegawai; selebihnya dicari lewat pencarian. Selalu
    // dibatasi agar tidak memuat seluruh pegawai se-OPD sekaligus (berat).
    if ($this->searchFollower !== '') {
      $userQuery->where('name', 'like', '%' . $this->searchFollower . '%');
    }
    $users = $userQuery->with('roles')->orderBy('name')->limit(100)->get();

    // Pengikut yang sudah dipilih dimuat terpisah (jumlahnya kecil) dan selalu tampil paling
    // atas daftar, juga saat tidak cocok dengan pencarian, agar terlihat siapa saja yang ikut.
    $selectedFollowerUsers = $this->followers
      ? User::with('roles')->whereIn('id', $this->followers)->orderBy('name')->get()
      : collect();
    $users = $selectedFollowerUsers
      ->concat($users->whereNotIn('id', $selectedFollowerUsers->pluck('id')))
      ->reject(fn (User $u) => $u->id === $pelaksana->id)
      ->values();

    $provinces = Province::orderBy('name')->get();
    $countries = Country::selectOptions();

    // Pegawai yang tanggalnya bentrok dengan SPPD lain, di OPD mana pun, tidak bisa dipilih sebagai pengikut.
    $period = $this->travelPeriod();
    $busyFollowers = $period ? SppdRequest::clashingForUsers($users, $period[0], $period[1], $this->sppd_id) : [];

    // SPPD aktif lain tiap calon pengikut yang tidak bentrok: hanya keterangan, tetap bisa dipilih.
    $followerTrips = SppdRequest::activeForUsers($users, $this->sppd_id);

    // Tanggal yang terpakai SPPD lain pelaksana/pengikut: dinonaktifkan di kalender.
    $blockedDates = $this->blockedRanges();

    // SPPD aktif lain milik pelaksana: acuan memilih tanggal yang tidak bentrok.
    $otherTrips = SppdRequest::activeForUsers(collect([$pelaksana]), $this->sppd_id)[$pelaksana->id] ?? collect();
    $clashingTripIds = $period
      ? $otherTrips->filter(fn (SppdRequest $trip) => $trip->start_date->lte($period[1]) && $trip->end_date->gte($period[0]))->pluck('id')->all()
      : [];

    $steps = $this->workflowSteps($pelaksana);

    $isInspektorat = (bool) $pelaksana->department?->usesManualNumbering();

    // Edit Perbaikan: catatan revisi ditampilkan di atas form.
    $editingSppd = $this->sppd_id ? SppdRequest::with(['reviser', 'approvals'])->find($this->sppd_id) : null;

    return view('livewire.sppd.create-details', compact(
      'pelaksana',
      'budgets',
      'categories',
      'users',
      'provinces',
      'countries',
      'steps',
      'selectedFollowerUsers',
      'busyFollowers',
      'followerTrips',
      'otherTrips',
      'clashingTripIds',
      'blockedDates',
      'isInspektorat',
      'editingSppd'
    ))->title('Detail Perjalanan Dinas');
  }
}
