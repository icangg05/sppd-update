<?php

namespace App\Livewire\Sppd;

use App\Enums\ApprovalStatus;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdCategory;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\ApproverNotifier;
use App\Services\QueueHealthService;
use App\Services\SppdWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
  public string $purpose = 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta';
  public string $problem = 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta';
  public string $facts = 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta';
  public string $analysis = 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta';

  public string $start_date;
  public string $end_date;
  public string $transport_type = 'Darat';
  public string $transport_name = 'Mobil';
  public string $departure_place = 'Kantor Kominfo';
  public string $urgency = 'Biasa';

  public string $spt_date = '2026-04-21';
  public string $sppd_date = '2026-04-23';
  public ?string $document_number = null;
  public $attachment;

  // Followers
  public array $followers = [];
  public array $follower_positions = [];
  public string $searchFollower = '';

  // Destinations
  public array $destinations = [];

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
      $this->departure_place = $sppd->departure_place ?? '';
      $this->urgency = $sppd->urgency ?? 'Biasa';
      $this->spt_date = $sppd->spt_date ? $sppd->spt_date->format('Y-m-d') : '';
      $this->sppd_date = $sppd->sppd_date ? $sppd->sppd_date->format('Y-m-d') : '';
      $this->document_number = $sppd->document_number;

      $this->followers = $sppd->followers->pluck('user_id')->toArray();
      $this->follower_positions = $sppd->followers->pluck('travel_position', 'user_id')->toArray();

      $this->destinations = [];
      foreach ($sppd->destinations as $dest) {
        if ($this->domain === 'dalam_daerah') {
          $this->destinations[] = [
            'province_id' => $dest->province_id,
            'regency_id' => $dest->regency_id,
            'address' => '',
            'address_only' => $dest->address,
          ];
        } else {
          $this->destinations[] = [
            'province_id' => $dest->province_id,
            'regency_id' => $dest->regency_id,
            'address' => $dest->address,
            'address_only' => '',
          ];
        }
      }

      $user = $sppd->user;
    } else {
      $this->user_id = (int) request('user_id');
      $this->domain = request('domain', 'dalam_daerah');

      $this->start_date = date('Y-m-d');
      $this->end_date = date('Y-m-d');

      $user = User::with('department')->find($this->user_id);
      if (! $user) {
        redirect()->route('sppd.create')->with('error', 'Pegawai tidak ditemukan.');
        return;
      }

      $isInspektorat = str_contains(strtolower($user->department?->name ?? ''), 'inspektorat');
      if ($isInspektorat) {
        $this->document_number = '090 / isi_nomor_surat_tugas / ST / INSP./ 2026';
      }

      $seSultra = Province::where('name', config('sppd.default_province_name'))->first();
      $this->destinations = [
        [
          'province_id' => ($this->domain === 'lddp' && $seSultra) ? $seSultra->id : '',
          'regency_id' => '',
          'address' => '',
          'address_only' => '',
        ]
      ];
    }

    // Security check in mount
    if (! Auth::user()->hasRole('super_admin')) {
      $dept = Auth::user()->department;
      if ($dept) {
        $allowedIds = $dept->getAllRelatedIds();
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
    if (preg_match('/^destinations\.(\d+)\.province_id$/', $name, $m)) {
      $this->destinations[(int) $m[1]]['regency_id'] = '';
    }
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

    $seSultra = Province::where('name', config('sppd.default_province_name'))->first();
    $this->destinations[] = [
      'province_id' => ($this->domain === 'lddp' && $seSultra) ? $seSultra->id : '',
      'regency_id' => '',
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

    $this->validate([
      'budget_id' => 'required|exists:budgets,id',
      'category_id' => 'required|exists:sppd_categories,id',
      'purpose' => 'required|string|max:1000',
      'problem' => 'nullable|string',
      'facts' => 'nullable|string',
      'analysis' => 'nullable|string',
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
      'transport_type' => 'nullable|string|max:255',
      'transport_name' => 'nullable|string|max:255',
      'departure_place' => 'nullable|string|max:255',
      'urgency' => 'nullable|string|max:255',
      'spt_date' => 'nullable|date',
      'sppd_date' => 'nullable|date',
      'document_number' => 'nullable|string|max:255',
      'attachment' => 'nullable|file|max:2048', // 2MB
      'destinations' => 'required|array|min:1',
      'destinations.*.province_id' => 'required_if:domain,lddp,ldlp',
      'destinations.*.regency_id' => 'required_if:domain,lddp,ldlp',
      'destinations.*.address' => 'required_if:domain,lddp,ldlp|string|max:500',
      'destinations.*.address_only' => 'required_if:domain,dalam_daerah|string|max:500',
      'followers' => 'nullable|array',
      'followers.*' => 'exists:users,id',
    ]);

    $this->showConfirmModal = true;
  }

  public function closeConfirmModal(): void
  {
    $this->showConfirmModal = false;
  }

  public function submit()
  {
    // Gerbang otoritatif: tolak bila worker queue tidak berjalan, sehingga
    // notifikasi WhatsApp ke approver dijamin dapat dikirim.
    if (! app(QueueHealthService::class)->isWorkerHealthy()) {
      $this->toastError($this->queueDownMessage());
      $this->showConfirmModal = false;
      return;
    }

    $user = User::with('department')->findOrFail($this->user_id);
    $isInspektorat = str_contains(strtolower($user->department?->name ?? ''), 'inspektorat');

    // Validasi tambahan untuk Inspektorat
    if ($isInspektorat) {
      foreach ($this->followers as $fId) {
        if (empty($this->follower_positions[$fId])) {
          $this->addError('follower_positions.' . $fId, 'Jabatan pengikut harus diisi.');
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
            $attachmentPath = $this->attachment->store(date('Y') . '/dokumen_pendukung', 'public');
          }

          $reviserId = $sppd->reviser_id;

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

          $sppd->destinations()->delete();
          $sppd->followers()->delete();

          // Save destinations
          foreach ($this->destinations as $dest) {
            if ($this->domain === 'dalam_daerah') {
              $sultra = Province::where('name', config('sppd.default_province_name'))->first();
              $kendari = Regency::where('name', 'LIKE', '%' . config('sppd.home_base_regency_keyword') . '%')->where('province_id', $sultra?->id)->first();

              $sppd->destinations()->create([
                'address' => $dest['address_only'],
                'province_id' => $sultra?->id,
                'regency_id' => $kendari?->id,
              ]);
            } else {
              $sppd->destinations()->create([
                'province_id' => $dest['province_id'] ?? null,
                'regency_id' => $dest['regency_id'] ?? null,
                'address' => $dest['address'] ?? null,
              ]);
            }
          }

          // Save followers
          if (! empty($this->followers)) {
            foreach ($this->followers as $userId) {
              $sppd->followers()->create([
                'user_id' => $userId,
                'travel_position' => $this->follower_positions[$userId] ?? null,
              ]);
            }
          }

          $sppd->temp_reviser_id = $reviserId;
        } else {
          $attachmentPath = null;
          if ($this->attachment) {
            $attachmentPath = $this->attachment->store(date('Y') . '/dokumen_pendukung', 'public');
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

          // Save destinations
          foreach ($this->destinations as $dest) {
            if ($this->domain === 'dalam_daerah') {
              $sultra = Province::where('name', config('sppd.default_province_name'))->first();
              $kendari = Regency::where('name', 'LIKE', '%' . config('sppd.home_base_regency_keyword') . '%')->where('province_id', $sultra?->id)->first();

              $sppd->destinations()->create([
                'address' => $dest['address_only'],
                'province_id' => $sultra?->id,
                'regency_id' => $kendari?->id,
              ]);
            } else {
              $sppd->destinations()->create([
                'province_id' => $dest['province_id'] ?? null,
                'regency_id' => $dest['regency_id'] ?? null,
                'address' => $dest['address'] ?? null,
              ]);
            }
          }

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
          $reviserId = $sppd->temp_reviser_id;
          $approval = null;
          if ($reviserId) {
            $approval = $sppd->approvals()
              ->where('approver_id', $reviserId)
              ->where('status', ApprovalStatus::PENDING)
              ->first();
          }
          if (! $approval) {
            $approval = $sppd->approvals()
              ->where('status', ApprovalStatus::PENDING)
              ->orderBy('step_order', 'asc')
              ->first();
          }

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
        $allowedIds = $dept->getAllRelatedIds();
        if (! $allowedIds->contains($pelaksana->department_id)) {
          abort(403, 'Unauthorized action.');
        }
      } elseif ($pelaksana->department_id !== Auth::user()->department_id) {
        abort(403, 'Unauthorized action.');
      }
    }

    // Budget query — DPA mengikuti OPD induk pelaksana, bukan department
    // akun yang login. Akun admin OPD induk bisa mengakses banyak department,
    // sehingga scoping harus berdasarkan OPD root pelaksana yang dipilih.
    $budgetQuery = Budget::with('department')->where('year', now()->year);
    $pelaksanaDept = $pelaksana->department;
    if ($pelaksanaDept) {
      $budgetQuery->whereIn('department_id', $pelaksanaDept->getRootDepartment()->getAllRelatedIds());
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
      // Pengikut harus sesuai department pelaksana (lingkup OPD pelaksana).
      $pelaksanaDept = $pelaksana->department;
      if ($pelaksanaDept) {
        $userQuery->whereIn('department_id', $pelaksanaDept->getRootDepartment()->getAllRelatedIds());
      } else {
        $userQuery->where('department_id', $pelaksana->department_id);
      }
    } else {
      $dept = Auth::user()->department;
      if ($dept) {
        $userQuery->whereIn('department_id', $dept->getAllRelatedIds());
      } else {
        $userQuery->where('department_id', Auth::user()->department_id);
      }
    }

    if ($this->searchFollower !== '') {
      $userQuery->where('name', 'like', '%' . $this->searchFollower . '%');
    }
    $users = $userQuery->orderBy('name')->get();

    $provinces = Province::orderBy('name')->get();

    // Active followers
    $activeFollowerIds = SppdRequest::whereIn('user_id', $users->pluck('id'))
      ->where(function ($q) {
        $q->where('status', SppdStatus::IN_PROGRESS)
          ->orWhere(function ($q2) {
            $q2->where('status', SppdStatus::APPROVED)
              ->where('end_date', '>=', today());
          });
      })
      ->pluck('user_id')
      ->unique()
      ->toArray();

    // Workflow simulation
    $workflowService = app(SppdWorkflowService::class);
    $steps = $workflowService->simulateApprovals($pelaksana, $this->domain);

    $isInspektorat = str_contains(strtolower($pelaksana->department?->name ?? ''), 'inspektorat');

    return view('livewire.sppd.create-details', compact(
      'pelaksana',
      'budgets',
      'categories',
      'users',
      'provinces',
      'steps',
      'activeFollowerIds',
      'isInspektorat'
    ))->title('Detail Perjalanan Dinas');
  }
}
