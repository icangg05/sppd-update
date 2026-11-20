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

  // Golongan/ruang kepangkatan PNS (I/a s.d. IV/e) + golongan PPPK (I s.d. XVII).
  public array $groupOptions = [
    'I/a', 'I/b', 'I/c', 'I/d',
    'II/a', 'II/b', 'II/c', 'II/d',
    'III/a', 'III/b', 'III/c', 'III/d',
    'IV/a', 'IV/b', 'IV/c', 'IV/d', 'IV/e',
    'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX',
    'X', 'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII',
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

    $data = [
      'name'  => trim($this->name),
      'group' => $this->group !== '' ? $this->group : null,
    ];

    // Nama pangkat boleh sama asal golongannya berbeda (mis. PNS & PPPK); yang dicegah hanya
    // pasangan nama + golongan yang sama. Nama dibandingkan tanpa membedakan huruf besar/kecil.
    $existing = Rank::whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
      ->when($data['group'] === null, fn ($q) => $q->whereNull('group'), fn ($q) => $q->where('group', $data['group']))
      ->first();
    if ($existing && $existing->id !== $this->editingId) {
      $label = $existing->group ? "{$existing->name} (Gol. {$existing->group})" : $existing->name;
      $this->toastError("Pangkat \"{$label}\" sudah terdaftar.");
      return;
    }

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
    // Golongan terendah → tertinggi mengikuti urutan $groupOptions (PNS I/a–IV/e, lalu PPPK I–XVII),
    // bukan urutan abjad (yang menaruh IX sebelum VI). Tanpa golongan di akhir. CASE dipakai agar
    // berjalan di MySQL maupun SQLite (FIELD() hanya ada di MySQL).
    $groupColumn = (new Rank)->getConnection()->getQueryGrammar()->wrap('group');
    $query = Rank::withCount('users')
      ->orderByRaw(
        "CASE {$groupColumn} " . str_repeat('WHEN ? THEN ? ', count($this->groupOptions)) . 'ELSE ? END',
        [...collect($this->groupOptions)->flatMap(fn ($group, $i) => [$group, $i])->all(), count($this->groupOptions)],
      )
      ->orderBy('name');

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    if ($this->groupFilter !== '') {
      $query->where('group', $this->groupFilter);
    }

    $ranks = $query->paginate(25)->onEachSide(1);

    return view('livewire.ranks.index', compact('ranks'))
      ->title('Kelola Data Pangkat');
  }
}
