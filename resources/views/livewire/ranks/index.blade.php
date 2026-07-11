<div class="p-1 space-y-4">

  {{-- Header Halaman (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-ranking-star pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-database text-[9px]"></i>
          Master Pangkat
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ $ranks->total() }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Kelola Data Pangkat</h1>
        <p class="mt-1 text-xs text-slate-500">Tambah, ubah, dan hapus master pangkat beserta golongannya</p>
      </div>

      <x-ui.button type="button" wire:click="openCreateModal" variant="primary" class="shrink-0 font-bold">
        <i class="fa-solid fa-plus text-xs"></i> Tambah Pangkat
      </x-ui.button>
    </div>
  </div>

  {{-- Filter --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-3">
    <div class="flex flex-col sm:flex-row items-center gap-2">
      <div class="relative flex-1 w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
          <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
        </div>
        <input type="text" wire:model.live.debounce.400ms="search"
          class="block w-full rounded border border-slate-300 bg-slate-50 py-1.5 pl-8 pr-8 text-xs focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 outline-none transition"
          placeholder="Cari nama pangkat...">
        <div wire:loading wire:target="search"
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-primary-500">
          <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
        </div>
      </div>

      @php
        $groupFilterOptions = collect($groupOptions)
          ->map(fn($g) => ['value' => $g, 'label' => 'Golongan ' . $g])
          ->prepend(['value' => '', 'label' => 'Semua Golongan'])
          ->all();
      @endphp
      <div class="w-full sm:w-48">
        <x-form.searchable-select wire:model.live="groupFilter" name="groupFilter" :options="$groupFilterOptions"
          placeholder="Semua Golongan" searchPlaceholder="Cari golongan..." class="bg-slate-50 py-1.5 text-xs" />
      </div>

      @php $canReset = $search !== '' || $groupFilter !== ''; @endphp
      <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
        <x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $canReset"
          class="px-3 py-1.5 text-xs font-medium text-slate-600 {{ $canReset ? '' : 'opacity-50 cursor-not-allowed' }}">
          <i class="fa-solid fa-rotate-right"></i> Reset
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Tabel --}}
  <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden"
    wire:loading.class="opacity-60" wire:target="search,groupFilter">
    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3 w-12 text-center">No.</th>
            <th class="py-2.5 px-4">Nama Pangkat</th>
            <th class="py-2.5 px-4 w-32 text-center">Golongan</th>
            <th class="py-2.5 px-4 w-24 text-center">Pegawai</th>
            <th class="py-2.5 px-4 w-28 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @forelse($ranks as $i => $rank)
            <tr wire:key="rank-{{ $rank->id }}" class="transition-colors hover:bg-slate-50/50">
              <td class="py-2.5 px-3 text-center text-slate-500 font-medium">
                {{ $ranks->firstItem() + $i }}.
              </td>
              <td class="py-2.5 px-4 font-semibold text-slate-900">{{ $rank->name }}</td>
              <td class="py-2.5 px-4 text-center">
                @if($rank->group)
                  <x-ui.badge color="indigo">Golongan {{ $rank->group }}</x-ui.badge>
                @else
                  <span class="text-slate-300">—</span>
                @endif
              </td>
              <td class="py-2.5 px-4 text-center">
                @if($rank->users_count > 0)
                  <a href="{{ route('master.users.index', ['rank_id' => $rank->hashid()]) }}" wire:navigate
                    class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-semibold text-primary-700 ring-1 ring-primary-600/20 transition hover:bg-primary-100"
                    title="Lihat pegawai dengan pangkat ini">
                    <i class="fa-solid fa-users text-[10px]"></i> {{ $rank->users_count }}
                  </a>
                @else
                  <span class="text-slate-500 font-medium">0</span>
                @endif
              </td>
              <td class="py-2.5 px-4">
                <div class="flex items-center justify-center gap-1.5">
                  <button type="button" wire:click="openEditModal({{ $rank->id }})"
                    class="inline-flex items-center justify-center rounded border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 transition hover:bg-amber-100"
                    title="Edit">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                  </button>
                  <button type="button" wire:click="confirmDelete({{ $rank->id }})"
                    class="inline-flex items-center justify-center rounded border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-100"
                    title="Hapus">
                    <i class="fa-solid fa-trash-can text-[10px]"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="py-10 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <i class="fa-solid fa-ranking-star text-2xl opacity-40"></i>
                  <p class="font-medium">Belum ada data pangkat</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($ranks->hasPages())
      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
        {{ $ranks->links() }}
      </div>
    @endif
  </div>

  {{-- Modal Tambah / Edit Pangkat --}}
  <x-ui.modal show="$wire.showFormModal" :title="$editingId ? 'Edit Pangkat' : 'Tambah Pangkat Baru'"
    :description="$editingId ? 'Perbarui data pangkat' : 'Tambahkan pangkat baru ke master data'"
    icon="fa-solid fa-ranking-star text-primary-600" :closeable="false">
    <form wire:submit="save" class="space-y-4">
      <x-form.input name="name" label="Nama Pangkat" wire:model="name" required
        placeholder="Contoh: Penata Muda" />

      @php
        $groupFormOptions = collect($groupOptions)
          ->map(fn($g) => ['value' => $g, 'label' => 'Golongan ' . $g])
          ->all();
      @endphp
      <x-form.searchable-select wire:model="group" name="group" label="Golongan" :options="$groupFormOptions"
        placeholder="— Pilih Golongan (opsional) —" searchPlaceholder="Cari golongan..." />

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" x-on:click="$wire.showFormModal = false">Batal</x-ui.button>
        <x-ui.button type="submit">
          <span wire:loading.remove wire:target="save">
            <i class="fa-solid fa-floppy-disk"></i> {{ $editingId ? 'Simpan Perubahan' : 'Simpan' }}
          </span>
          <span wire:loading wire:target="save"><i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...</span>
        </x-ui.button>
      </div>
    </form>
  </x-ui.modal>

  {{-- Modal Konfirmasi Hapus --}}
  <x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Pangkat"
    description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash-can text-rose-600"
    :closeable="false">
    <div class="space-y-4"
      x-data="{
        remaining: 10,
        timer: null,
        startCountdown() {
          this.remaining = 10;
          clearInterval(this.timer);
          this.timer = setInterval(() => {
            if (this.remaining > 0) this.remaining--;
            if (this.remaining <= 0) clearInterval(this.timer);
          }, 1000);
        },
      }"
      x-on:rank-delete-countdown.window="startCountdown()"
      x-init="if ($wire.showDeleteModal) startCountdown()">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus pangkat
        <span class="font-bold text-slate-800">{{ $deletingName ?? 'ini' }}</span>?
        Data yang sudah dihapus tidak dapat dikembalikan.
      </p>

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" wire:click="closeDeleteModal">Tutup</x-ui.button>
        <x-ui.button type="button" variant="danger" wire:click="delete"
          x-bind:disabled="remaining > 0"
          x-bind:class="remaining > 0 ? 'opacity-50 cursor-not-allowed' : ''">
          <span x-show="remaining > 0"><i class="fa-solid fa-hourglass-half"></i> Tunggu <span x-text="remaining"></span>s</span>
          <span x-show="remaining <= 0" x-cloak>
            <span wire:loading.remove wire:target="delete"><i class="fa-solid fa-trash-can"></i> Hapus</span>
            <span wire:loading wire:target="delete"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
          </span>
        </x-ui.button>
      </div>
    </div>
  </x-ui.modal>

</div>
