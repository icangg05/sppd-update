<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Rank;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class RankIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $groupFilter = '';

  // Golongan/ruang kepangkatan PNS (I/a sampai IV/e).
  public array $groupOptions = [
    'I/a', 'I/b', 'I/c', 'I/d',
    'II/a', 'II/b', 'II/c', 'II/d',
    'III/a', 'III/b', 'III/c', 'III/d',
    'IV/a', 'IV/b', 'IV/c', 'IV/d', 'IV/e',
  ];

  // Form tambah / edit
  public bool $showFormModal = false;
  public ?int $editingId = null;
  public string $name = '';
  public string $group = '';

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

  public function updatedGroupFilter(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search      = '';
    $this->groupFilter = '';
    $this->resetPage();
  }

  public function openCreateModal(): void
  {
    $this->reset(['name', 'group', 'editingId']);
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function openEditModal(int $id): void
  {
    $rank = Rank::findOrFail($id);

    $this->editingId = $rank->id;
    $this->name      = $rank->name;
    $this->group     = $rank->group ?? '';
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function save(): void
  {
    $this->validate([
      'name'  => ['required', 'string', 'max:255'],
      'group' => ['nullable', 'in:' . implode(',', $this->groupOptions)],
    ]);

    // Cegah duplikasi nama pangkat (case-insensitive).
    $existing = Rank::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($this->name))])->first();
    if ($existing && $existing->id !== $this->editingId) {
      $this->toastError("Pangkat \"{$existing->name}\" sudah terdaftar.");
      return;
    }

    $data = [
      'name'  => trim($this->name),
      'group' => $this->group !== '' ? $this->group : null,
    ];

    if ($this->editingId) {
      Rank::findOrFail($this->editingId)->update($data);
      $message = 'Pangkat berhasil diperbarui.';
    } else {
      Rank::create($data);
      $message = 'Pangkat baru berhasil ditambahkan.';
    }

    $this->reset(['name', 'group', 'editingId']);
    $this->showFormModal = false;
    $this->resetPage();
    $this->toastSuccess($message);
  }

  public function confirmDelete(int $id): void
  {
    $rank = Rank::find($id);
    if (! $rank) {
      return;
    }

    $this->deletingId      = $rank->id;
    $this->deletingName    = $rank->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('rank-delete-countdown');
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

    $rank = Rank::withCount('users')->findOrFail($this->deletingId);

    // Proteksi: pangkat yang masih dipakai pegawai tidak boleh dihapus.
    if ($rank->users_count > 0) {
      $this->closeDeleteModal();
      $this->toastError("Gagal: pangkat \"{$rank->name}\" masih dipakai {$rank->users_count} pegawai.");
      return;
    }

    $name = $rank->name;
    $rank->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Pangkat \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $query = Rank::withCount('users')->orderBy('group')->orderBy('name');

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    if ($this->groupFilter !== '') {
      $query->where('group', $this->groupFilter);
    }

    $ranks = $query->paginate(20)->onEachSide(1);

    return view('livewire.ranks.index', compact('ranks'))
      ->title('Kelola Data Pangkat');
  }
}
