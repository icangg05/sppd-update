<?php

namespace App\Livewire;

use App\Enums\PositionRequestStatus;
use App\Enums\PositionScope;
use App\Helpers\SmartTitle;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Position;
use App\Models\PositionRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PositionRequestIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $statusFilter = '';

  // Form pengajuan
  public bool $showCreateModal = false;
  public string $name = '';
  public string $reason = '';

  // Form verifikasi (super admin)
  public bool $showVerifyModal = false;
  public ?int $selectedId = null;
  public string $verifyScope = PositionScope::NONE->value;
  public string $verifyNote = '';

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedStatusFilter(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search       = '';
    $this->statusFilter = '';
    $this->resetPage();
  }

  public function openCreateModal(): void
  {
    $this->reset(['name', 'reason']);
    $this->resetValidation();
    $this->showCreateModal = true;
  }

  public function submit(): void
  {
    $this->validate([
      'name'   => ['required', 'string', 'max:255'],
      'reason' => ['nullable', 'string', 'max:500'],
    ]);

    // Pastikan jabatan unik / tidak duplikat.
    if ($existing = Position::findByName($this->name)) {
      $this->toastError("Jabatan \"{$existing->name}\" sudah terdaftar. Tidak perlu diajukan lagi.");
      return;
    }

    if (PositionRequest::pendingByName($this->name)) {
      $this->toastError('Jabatan dengan nama serupa sedang menunggu verifikasi. Mohon tunggu hasilnya.');
      return;
    }

    PositionRequest::create([
      'requested_by'  => auth()->id(),
      'department_id' => auth()->user()->department_id,
      'name'          => trim($this->name),
      'reason'        => $this->reason !== '' ? trim($this->reason) : null,
      'status'        => PositionRequestStatus::PENDING,
    ]);

    $this->reset(['name', 'reason']);
    $this->showCreateModal = false;
    $this->resetPage();
    $this->dispatch('position-request-updated');
    $this->toastSuccess('Pengajuan jabatan terkirim dan menunggu verifikasi Super Admin.');
  }

  public function openVerifyModal(int $id): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    $request = PositionRequest::pending()->findOrFail($id);

    $this->selectedId  = $request->id;
    $this->verifyScope = PositionScope::NONE->value;
    $this->verifyNote  = '';
    $this->resetValidation();
    $this->showVerifyModal = true;
  }

  public function approve(): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    $this->validate([
      'verifyScope' => ['required', 'in:' . implode(',', array_column(PositionScope::cases(), 'value'))],
      'verifyNote'  => ['nullable', 'string', 'max:500'],
    ]);

    $request = PositionRequest::pending()->findOrFail($this->selectedId);

    // Cek ulang duplikat sebelum benar-benar membuat jabatan.
    if ($existing = Position::findByName($request->name)) {
      $request->update([
        'status'      => PositionRequestStatus::REJECTED,
        'position_id' => $existing->id,
        'reviewed_by' => auth()->id(),
        'reviewed_at' => now(),
        'review_note' => "Otomatis ditolak: jabatan \"{$existing->name}\" sudah ada.",
      ]);

      $this->closeVerify();
      $this->dispatch('position-request-updated');
      $this->toastWarning("Jabatan \"{$existing->name}\" ternyata sudah ada. Pengajuan ditolak otomatis.");
      return;
    }

    $position = Position::create([
      'name'             => SmartTitle::convert($request->name),
      'level'            => 1000,
      'uniqueness_scope' => $this->verifyScope,
    ]);

    $request->update([
      'status'      => PositionRequestStatus::APPROVED,
      'position_id' => $position->id,
      'reviewed_by' => auth()->id(),
      'reviewed_at' => now(),
      'review_note' => $this->verifyNote !== '' ? trim($this->verifyNote) : null,
    ]);

    $this->closeVerify();
    $this->dispatch('position-request-updated');
    $this->toastSuccess("Jabatan \"{$position->name}\" disetujui dan ditambahkan.");
  }

  public function reject(): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    $this->validate([
      'verifyNote' => ['nullable', 'string', 'max:500'],
    ]);

    $request = PositionRequest::pending()->findOrFail($this->selectedId);

    $request->update([
      'status'      => PositionRequestStatus::REJECTED,
      'reviewed_by' => auth()->id(),
      'reviewed_at' => now(),
      'review_note' => $this->verifyNote !== '' ? trim($this->verifyNote) : null,
    ]);

    $this->closeVerify();
    $this->dispatch('position-request-updated');
    $this->toastSuccess('Pengajuan jabatan ditolak.');
  }

  private function closeVerify(): void
  {
    $this->showVerifyModal = false;
    $this->selectedId      = null;
    $this->verifyNote      = '';
  }

  public function render()
  {
    $user         = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');

    $query = PositionRequest::with(['requester', 'department', 'reviewer'])
      ->latest();

    if (! $isSuperAdmin) {
      $query->where('requested_by', $user->id);
    }

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    if ($this->statusFilter !== '') {
      $query->where('status', $this->statusFilter);
    }

    $requests   = $query->paginate(20);
    $statuses   = PositionRequestStatus::cases();
    $scopes     = PositionScope::cases();
    $selected   = $this->selectedId ? PositionRequest::find($this->selectedId) : null;

    return view('livewire.position-requests.index', compact(
      'requests',
      'statuses',
      'scopes',
      'selected',
      'isSuperAdmin'
    ))->title('Pengajuan Jabatan');
  }
}
