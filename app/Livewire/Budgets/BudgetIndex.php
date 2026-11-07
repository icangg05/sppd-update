<?php

namespace App\Livewire\Budgets;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Budget;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class BudgetIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $year = '';

  #[Url(keep: true)]
  public string $source = '';

  #[Url(keep: true)]
  public string $department_id = '';

  // Konfirmasi hapus
  public bool $showDeleteModal = false;
  public ?int $deletingId = null;
  public ?string $deletingName = null;

  public function mount(): void
  {
    // Tahun dari URL yang tidak punya DPA diganti tahun bawaan, karena tidak ada di pilihan filter.
    $years = $this->availableYears();
    if ($this->year === '' || ! in_array($this->year, $years, true)) {
      $this->year = $this->defaultYear($years);
    }
  }

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedYear(): void
  {
    $this->resetPage();
  }

  public function updatedSource(): void
  {
    $this->resetPage();
  }

  public function updatedDepartmentId(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search        = '';
    $this->source        = '';
    $this->department_id = '';
    $this->year          = $this->defaultYear();
    $this->resetPage();
  }

  /** Anggaran dalam lingkup pengguna: Admin OPD hanya melihat anggaran instansinya sendiri. */
  private function scopedBudgets(): Builder
  {
    $query = Budget::query();

    if (! $this->currentUser()->hasRole('super_admin')) {
      $query->where('department_id', $this->currentUser()->department_id);
    }

    return $query;
  }

  /**
   * Tahun anggaran yang punya DPA dalam lingkup pengguna, terbaru dulu.
   *
   * @return array<int, string>
   */
  private function availableYears(): array
  {
    return $this->scopedBudgets()
      ->distinct()
      ->orderByDesc('year')
      ->pluck('year')
      ->map(fn ($year) => (string) $year)
      ->all();
  }

  /**
   * Tahun bawaan filter: tahun berjalan bila ada DPA-nya, selain itu tahun terbaru
   * yang ada, atau semua tahun ('') bila belum ada DPA sama sekali.
   *
   * @param  array<int, string>|null  $years
   */
  private function defaultYear(?array $years = null): string
  {
    $years ??= $this->availableYears();
    $current = (string) date('Y');

    return in_array($current, $years, true) ? $current : ($years[0] ?? '');
  }

  /**
   * Pengguna login sebagai App\Models\User konkret — agar method domain
   * (hasRole, department, dll.) dikenali analisis statis & autocomplete.
   */
  private function currentUser(): User
  {
    /** @var User $user */
    $user = Auth::user();

    return $user;
  }

  public function confirmDelete(int $id): void
  {
    $budget = Budget::find($id);
    if (! $budget) {
      return;
    }

    // Pastikan admin OPD hanya bisa menghapus anggaran instansinya sendiri.
    if (! $this->currentUser()->hasRole('super_admin') && $budget->department_id !== $this->currentUser()->department_id) {
      $this->toastError('Anda tidak memiliki akses untuk menghapus data anggaran ini.');
      return;
    }

    $this->deletingId      = $budget->id;
    $this->deletingName    = $budget->activity ?: ($budget->account_code ?: "Anggaran #{$budget->id}");
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('budget-delete-countdown');
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

    $budget = Budget::find($this->deletingId);
    if (! $budget) {
      $this->closeDeleteModal();
      return;
    }

    // Pastikan admin OPD hanya bisa menghapus anggaran instansinya sendiri.
    if (! $this->currentUser()->hasRole('super_admin') && $budget->department_id !== $this->currentUser()->department_id) {
      $this->closeDeleteModal();
      $this->toastError('Anda tidak memiliki akses untuk menghapus data anggaran ini.');
      return;
    }

    try {
      $budget->delete();
    } catch (\Throwable $e) {
      $this->closeDeleteModal();
      $this->toastError('Data anggaran tidak dapat dihapus karena masih terkait dengan data lain.');
      return;
    }

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess('Data anggaran berhasil dihapus.');
  }

  public function render()
  {
    $user  = $this->currentUser();
    $query = $this->scopedBudgets()->with('department.parent');

    if ($this->search !== '') {
      $s = $this->search;
      $query->where(function ($q) use ($s) {
        $q->where('description', 'like', "%{$s}%")
          ->orWhere('program', 'like', "%{$s}%")
          ->orWhere('activity', 'like', "%{$s}%")
          ->orWhere('account_code', 'like', "%{$s}%")
          ->orWhere('type', 'like', "%{$s}%")
          ->orWhere('source', 'like', "%{$s}%");
      });
    }

    if ($this->year !== '') {
      $query->where('year', $this->year);
    }

    if ($this->source !== '') {
      $query->where('source', $this->source);
    }

    if ($this->department_id !== '' && $user->hasRole('super_admin')) {
      $query->where('department_id', $this->department_id);
    }

    $budgets = $query->latest()->paginate(15);

    $departments = $user->hasRole('super_admin')
      ? Department::orderBy('name')->get()
      : collect();

    // Pilihan filter tahun hanya tahun yang punya DPA.
    $years = $this->availableYears();
    $defaultYear = $this->defaultYear($years);

    /** @var \Illuminate\View\View $view */
    $view = view('livewire.budgets.budget-index', compact('budgets', 'departments', 'years', 'defaultYear'));

    // title() = macro Livewire pada Illuminate\View\View (dikenali via _ide_helper.php).
    return $view->title('DPA | Data Anggaran');
  }
}
