<div class="p-1 space-y-6" x-data>

  {{-- Header Halaman --}}
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
      <div class="flex size-11 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
        <i class="fa-solid fa-sitemap text-lg"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
          {{ $isSuperAdmin ? 'Data Instansi' : 'Manajemen Unit Kerja' }}
        </h1>
        <p class="mt-0.5 text-sm text-slate-500">
          {{ $isSuperAdmin ? 'Kelola OPD, kecamatan, kelurahan, dan unit kerja induk.' : 'Kelola struktur Bidang & Seksi di lingkup ' . auth()->user()->department?->name . '.' }}
        </p>
      </div>
    </div>

    <x-ui.button href="{{ route('master.departments.create') }}" variant="primary" class="shrink-0 font-bold">
      <x-slot name="icon"><i class="fa-solid fa-plus text-xs"></i></x-slot>
      {{ $isSuperAdmin ? 'Tambah Instansi' : 'Tambah Struktur' }}
    </x-ui.button>
  </div>

  {{-- Filter Header Compact Grid --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-3">
    <div class="flex flex-col sm:flex-row items-center gap-2">

      {{-- Input Pencarian (live) --}}
      <div class="relative flex-1 w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
          <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
        </div>
        <input type="text" wire:model.live.debounce.400ms="search"
          class="block w-full rounded border border-slate-300 bg-slate-50 py-1.5 pl-8 pr-8 text-xs outline-none transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/30"
          placeholder="Cari nama unit kerja atau kode urusan...">
        <div wire:loading wire:target="search"
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-primary-500">
          <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
        </div>
      </div>

      {{-- Searchable Select Tipe (Khusus Super Admin) --}}
      @if($isSuperAdmin)
        @php
          $typeOptions = collect($types)
            ->map(fn($t) => ['value' => $t->value, 'label' => $t->label()])
            ->prepend(['value' => '', 'label' => 'Semua Tipe'])
            ->all();
        @endphp
        <div class="w-full sm:w-44">
          <x-form.searchable-select wire:model.live="type" name="type" :options="$typeOptions"
            placeholder="Semua Tipe" searchPlaceholder="Cari tipe..." class="bg-slate-50 py-1.5 text-xs" />
        </div>
      @endif

      {{-- Tombol Reset --}}
      @if($search !== '' || $type !== '')
        <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
          <x-ui.button wire:click="resetFilters" type="button" variant="secondary"
            class="px-3 py-1.5 text-xs font-medium text-slate-600">
            <i class="fa-solid fa-rotate-right"></i> Reset
          </x-ui.button>
        </div>
      @endif
    </div>
  </div>

  {{-- Table Container --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm"
    wire:loading.class="opacity-60" wire:target="search,type">
    <div class="overflow-x-clip">
      <table class="w-full text-left border-collapse">
        <thead
          class="sticky top-13 lg:top-16 z-10 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 shadow-sm [&_th]:pt-5 [&_th]:pb-3">
          <tr>
            <th class="py-2.5 px-3 w-12 text-center">No</th>
            <th class="py-2.5 px-4">Nama Unit Kerja / Struktur</th>
            <th class="py-2.5 px-4 w-28">Kode Unit</th>
            <th class="py-2.5 px-4">Pimpinan Kepala</th>
            @if($isSuperAdmin)
              <th class="py-2.5 px-4 w-28">Tipe</th>
              <th class="py-2.5 px-4">Unit Induk</th>
              <th class="py-2.5 px-3 w-20 text-center">Pegawai</th>
              <th class="py-2.5 px-3 w-20 text-center">Sub-Unit</th>
            @endif
            <th class="py-2.5 px-4 w-24 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
          @forelse($departments as $i => $dept)
            {{-- Deteksi baris aktif instansi pengguna login --}}
            @php
              $isOwnDept = !$isSuperAdmin && $dept->id == auth()->user()->department_id;
            @endphp
            <tr wire:key="dept-{{ $dept->id }}"
              class="transition-colors {{ $isOwnDept ? 'bg-primary-50/40 font-semibold' : 'hover:bg-slate-50/50' }}">
              <td class="py-2.5 px-3 text-center text-slate-500 font-medium">
                {{ $departments->firstItem() + $i }}
              </td>

              <td class="py-2.5 px-4">
                <div class="flex items-center">
                  {{-- Indentasi Pohon Struktur --}}
                  @if(isset($dept->tree_level) && $dept->tree_level > 0)
                    @for($j = 0; $j < $dept->tree_level; $j++)
                      <span class="w-4 h-px bg-slate-300 inline-block mr-1.5 last:bg-slate-400"></span>
                    @endfor
                    <i class="fa-solid fa-angles-right text-[10px] text-slate-300 mr-2"></i>
                  @endif
                  <span class="{{ $isOwnDept ? 'text-primary-700 font-bold' : 'text-slate-900' }}">
                    {{ $dept->name }}
                  </span>
                  @unless($dept->has_kop)
                    <span
                      class="ml-2 inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 border border-amber-200"
                      title="Unit ini belum memiliki kop surat, dan tidak mewarisi kop dari instansi induk.">
                      <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Belum ada kop
                    </span>
                  @endunless
                </div>
              </td>

              <td class="py-2.5 px-4">
                <span
                  class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-600 border border-slate-200/50">
                  {{ $dept->code ?? '—' }}
                </span>
              </td>

              <td class="py-2.5 px-4 text-slate-600 font-medium">
                {{ $dept->head?->name ?? '—' }}
              </td>

              @if($isSuperAdmin)
                <td class="py-2.5 px-4">
                  <span
                    class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 border border-slate-200">
                    {{ $dept->type->label() }}
                  </span>
                </td>
                <td class="py-2.5 px-4 text-slate-500 text-[11px] truncate max-w-40">
                  {{ $dept->parent?->name ?? '—' }}
                </td>
                <td class="py-2.5 px-3 text-center font-mono font-semibold text-slate-700">
                  {{ $dept->users_count }}
                </td>
                <td class="py-2.5 px-3 text-center font-mono font-semibold text-slate-700">
                  {{ $dept->children_count }}
                </td>
              @endif

              <td class="py-2.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  {{-- Tombol Detail --}}
                  <a wire:navigate href="{{ route('master.departments.show', $dept->id) }}"
                    class="rounded border border-slate-200 bg-white p-1 text-slate-500 transition-colors hover:bg-primary-50 hover:text-primary-600 hover:border-primary-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40"
                    title="Detail">
                    <i class="fa-solid fa-eye text-[10px]"></i>
                  </a>

                  {{-- Tombol Edit --}}
                  <a wire:navigate href="{{ route('master.departments.edit', $dept->id) }}"
                    class="rounded border border-slate-200 bg-white p-1 text-slate-500 transition-colors hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40"
                    title="Edit">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                  </a>

                  {{-- Tombol Hapus kondisional --}}
                  @if($isSuperAdmin || ($dept->parent_id !== null && !$isOwnDept))
                    <button type="button" wire:click="confirmDelete({{ $dept->id }})"
                      class="rounded border border-slate-200 bg-white p-1 text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/40"
                      title="Hapus">
                      <i class="fa-solid fa-trash-can text-[10px]"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $isSuperAdmin ? '9' : '5' }}" class="px-4 py-16">
                <div class="mx-auto flex max-w-sm flex-col items-center text-center">
                  <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                    <i class="fa-solid fa-folder-tree text-xl"></i>
                  </div>

                  @if($search !== '' || $type !== '')
                    <p class="mt-4 text-sm font-semibold text-slate-700">Tidak ada unit kerja yang cocok</p>
                    <p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci atau hapus filter tipe yang aktif.</p>
                    <x-ui.button wire:click="resetFilters" type="button" variant="secondary" size="sm" class="mt-4">
                      <i class="fa-solid fa-rotate-right text-xs"></i> Reset filter
                    </x-ui.button>
                  @else
                    <p class="mt-4 text-sm font-semibold text-slate-700">Belum ada data unit kerja</p>
                    <p class="mt-1 text-xs text-slate-500">
                      Tambahkan {{ $isSuperAdmin ? 'instansi/OPD' : 'struktur bidang atau seksi' }} untuk mulai menyusun struktur.
                    </p>
                    <x-ui.button href="{{ route('master.departments.create') }}" variant="primary" size="sm" class="mt-4">
                      <i class="fa-solid fa-plus text-xs"></i> {{ $isSuperAdmin ? 'Tambah Instansi' : 'Tambah Struktur' }}
                    </x-ui.button>
                  @endif
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination Ringkas --}}
    @if($departments->hasPages())
      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
        {{ $departments->links() }}
      </div>
    @endif
  </div>

  {{-- Modal Konfirmasi Hapus — hanya bisa ditutup lewat tombol (tidak closeable) --}}
  <x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Unit Kerja"
    description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash-can text-rose-600"
    :closeable="false">
    <div class="space-y-4">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus
        <span class="font-bold text-slate-800">{{ $deletingName ?? 'unit kerja ini' }}</span>?
        Data yang sudah dihapus tidak dapat dikembalikan.
      </p>

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" wire:click="closeDeleteModal">
          Tutup
        </x-ui.button>
        <x-ui.button type="button" variant="danger" wire:click="delete">
          <span wire:loading.remove wire:target="delete"><i class="fa-solid fa-trash-can"></i> Hapus</span>
          <span wire:loading wire:target="delete"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
        </x-ui.button>
      </div>
    </div>
  </x-ui.modal>

</div>
