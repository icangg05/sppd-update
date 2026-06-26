<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class WorkflowIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  // Konfirmasi hapus
  public bool $showDeleteModal = false;
  public ?int $deletingId = null;
  public ?string $deletingName = null;

  public function mount(): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);
  }

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->resetPage();
  }

  public function confirmDelete(int $id): void
  {
    $workflow = SppdWorkflow::find($id);
    if (! $workflow) {
      return;
    }

    $this->deletingId      = $workflow->id;
    $this->deletingName    = $workflow->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('workflow-delete-countdown');
  }

  public function closeDeleteModal(): void
  {
    $this->showDeleteModal = false;
    $this->deletingId      = null;
    $this->deletingName    = null;
  }

  public function delete(): void
  {
    $this->showDeleteModal = false;

    if (! $this->deletingId) {
      return;
    }

    $workflow = SppdWorkflow::findOrFail($this->deletingId);
    $name     = $workflow->name;
    $workflow->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Workflow \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $workflows = SppdWorkflow::orderBy('id')
      ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
      ->paginate(25)
      ->onEachSide(1);

    $roleLabels = Role::pluck('label', 'name')->all();

    return view('livewire.workflows.index', compact('workflows', 'roleLabels'))
      ->title('Workflow SPPD');
  }
}
