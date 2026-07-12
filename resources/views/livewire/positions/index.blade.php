<div class="p-1 space-y-4">

  {{-- Header Halaman (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-id-badge pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-database text-[9px]"></i>
          Master Jabatan
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ $positions->total() }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Kelola Data Jabatan</h1>
        <p class="mt-1 text-xs text-slate-500">Tambah, ubah, dan hapus master jabatan beserta cakupan keunikannya</p>
      </div>

      <x-ui.button type="button" wire:click="openCreateModal" variant="primary" class="shrink-0 font-bold">
        <i class="fa-solid fa-plus text-xs"></i> Tambah Jabatan
      </x-ui.button>
    </div>
  </div>

  {{-- Filter --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-4">
    <div class="flex flex-col gap-3 sm:flex-row">
      <x-form.input wire:model.live.debounce.400ms="search" icon="fa-solid fa-magnifying-glass"
        loadingTarget="search" wrapperClass="flex-1 w-full"
        placeholder="Cari nama jabatan..." />

      @php
        $scopeOptions = collect($scopes)
          ->map(fn($s) => ['value' => $s->value, 'label' => $s->label()])
          ->prepend(['value' => '', 'label' => 'Semua Cakupan'])
          ->all();
      @endphp
      <div class="w-full sm:w-56">
        <x-form.searchable-select wire:model.live="scopeFilter" name="scopeFilter" :options="$scopeOptions"
          placeholder="Semua Cakupan" searchPlaceholder="Cari cakupan..." />
      </div>

      @php $canReset = $search !== '' || $scopeFilter !== ''; @endphp
      <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
        <x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $canReset">
          <x-slot:icon><i class="fa-solid fa-rotate-right text-xs text-slate-500"></i></x-slot:icon>
          Reset
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Tabel --}}
  <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden"
    wire:loading.class="opacity-60" wire:target="search,scopeFilter">
    <div class="overflow-x-auto">
      <table class="table-stack w-full text-left whitespace-nowrap border-collapse md:[&_thead_th]:py-2.5 md:[&_tbody_td]:py-2">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3 w-12 text-center">No.</th>
            <th class="py-2.5 px-4">Nama Jabatan</th>
            <th class="py-2.5 px-4 w-20 text-center">Level</th>
            <th class="py-2.5 px-4">Cakupan Keunikan</th>
            <th class="py-2.5 px-4 w-24 text-center">Pemangku</th>
            <th class="py-2.5 px-4 w-28 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @php
            $scopeColors = [
              \App\Enums\PositionScope::NONE->value       => 'slate',
              \App\Enums\PositionScope::DEPARTMENT->value => 'amber',
              \App\Enums\PositionScope::SYSTEM->value      => 'rose',
            ];
          @endphp
          @forelse($positions as $i => $position)
            @php $scopeValue = ($position->uniqueness_scope ?? \App\Enums\PositionScope::NONE)->value; @endphp
            <tr wire:key="position-{{ $position->id }}" class="transition-colors hover:bg-slate-50/50">
              <td class="stack-hide py-2.5 px-3 text-center text-slate-500 font-medium">
                {{ $positions->firstItem() + $i }}.
              </td>
              <td data-label="Nama Jabatan" class="py-2.5 px-4 font-semibold text-slate-900">{{ $position->name }}</td>
              <td data-label="Level" class="py-2.5 px-4 text-center text-slate-500 font-medium">{{ $position->level }}</td>
              <td data-label="Cakupan" class="py-2.5 px-4">
                <x-ui.badge :color="$scopeColors[$scopeValue] ?? 'slate'">
                  {{ ($position->uniqueness_scope ?? \App\Enums\PositionScope::NONE)->label() }}
                </x-ui.badge>
              </td>
              <td data-label="Pemangku" class="py-2.5 px-4 text-center">
                @if($position->users_count > 0)
                  <a href="{{ route('master.users.index', ['position_id' => $position->hashid()]) }}" wire:navigate
                    class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-semibold text-primary-700 ring-1 ring-primary-600/20 transition hover:bg-primary-100"
                    title="Lihat pegawai dengan jabatan ini">
                    <i class="fa-solid fa-users text-[10px]"></i> {{ $position->users_count }}
                  </a>
                @else
                  <span class="text-slate-500 font-medium">0</span>
                @endif
              </td>
              <td data-label="Aksi" class="py-2.5 px-4">
                <div class="flex items-center justify-center gap-1.5">
                  <button type="button" wire:click="openEditModal({{ $position->id }})"
                    class="inline-flex items-center justify-center rounded border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 transition hover:bg-amber-100"
                    title="Edit">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                  </button>
                  <button type="button" wire:click="confirmDelete({{ $position->id }})"
                    class="inline-flex items-center justify-center rounded border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-100"
                    title="Hapus">
                    <i class="fa-solid fa-trash-can text-[10px]"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-10 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <i class="fa-solid fa-id-badge text-2xl opacity-40"></i>
                  <p class="font-medium">Belum ada data jabatan</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($positions->hasPages())
      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
        {{ $positions->links() }}
      </div>
    @endif
  </div>

  {{-- Modal Tambah / Edit Jabatan --}}
  <x-ui.modal show="$wire.showFormModal" :title="$editingId ? 'Edit Jabatan' : 'Tambah Jabatan Baru'"
    :description="$editingId ? 'Perbarui data jabatan' : 'Tambahkan jabatan baru ke master data'"
    icon="fa-solid fa-id-badge text-primary-600" :closeable="false">
    <form wire:submit="save" class="space-y-4">
      <x-form.input name="name" label="Nama Jabatan" wire:model="name" required
        placeholder="Contoh: Analis Sumber Daya Manusia Aparatur" />

      <x-form.input type="number" name="level" label="Level" wire:model="level" required min="0" max="9999"
        hint="Urutan jabatan — semakin kecil semakin tinggi (mis. Walikota 1, Staf 1000)." />

      @php
        $scopeFormOptions = collect($scopes)
          ->map(fn($s) => ['value' => $s->value, 'label' => $s->label()])
          ->all();
      @endphp
      <x-form.searchable-select wire:model="scope" name="scope" label="Cakupan Keunikan" :options="$scopeFormOptions"
        placeholder="— Pilih Cakupan —" searchPlaceholder="Cari cakupan..." required />

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

  {{-- Modal Konfirmasi Hapus — hanya bisa ditutup lewat tombol --}}
  <x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Jabatan"
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
      x-on:position-delete-countdown.window="startCountdown()"
      x-init="if ($wire.showDeleteModal) startCountdown()">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus jabatan
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
