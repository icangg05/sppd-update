@extends('layouts.app')
@section('title', $isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja')
@section('page-title', $isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja')

@section('content')
  <div class="p-1 space-y-4">

    {{-- Header Halaman Compact --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
      <div class="flex items-center gap-2.5">
        <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
          <i class="fa-solid fa-sitemap text-base"></i>
        </div>
        <div>
          <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">
            {{ $isSuperAdmin ? 'Data Instansi' : 'Manajemen Unit Kerja' }}
          </h1>
          <p class="text-[11px] text-slate-500 font-medium">
            {{ $isSuperAdmin ? 'Kelola OPD, kecamatan, kelurahan, dan unit kerja induk' : 'Kelola struktur Bidang & Seksi di lingkup ' . auth()->user()->department?->name }}
          </p>
        </div>
      </div>

      <x-ui.button href="{{ route('master.departments.create') }}"
        class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
        <i class="fa-solid fa-plus text-[10px]"></i>
        {{ $isSuperAdmin ? 'Tambah Instansi' : 'Tambah Struktur' }}
      </x-ui.button>
    </div>

    {{-- Filter Header Compact Grid --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden p-3">
      <form method="GET" action="{{ route('master.departments.index') }}"
        class="flex flex-col sm:flex-row items-center gap-2">

        {{-- Input Pencarian --}}
        <div class="relative flex-1 w-full">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
          </div>
          <input type="text" name="search" value="{{ request('search') }}"
            class="block w-full rounded border border-slate-300 bg-slate-50 py-1.5 pl-8 pr-3 text-xs focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition"
            placeholder="Cari nama unit kerja atau kode urusan...">
        </div>

        {{-- Dropdown Tipe (Khusus Super Admin) --}}
        @if($isSuperAdmin)
          <div class="relative w-full sm:w-44">
            <select name="type"
              class="block w-full appearance-none rounded border border-slate-300 bg-slate-50 py-1.5 pl-3 pr-8 text-xs focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition">
              <option value="">Semua Tipe</option>
              @foreach($types as $t)
                <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
              @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
              <i class="fa-solid fa-chevron-down text-[10px]"></i>
            </div>
          </div>
        @endif

        {{-- Tombol Submit & Reset --}}
        <div class="flex items-center gap-1 w-full sm:w-auto shrink-0">
          <x-ui.button type="submit" variant="dark" class="w-full sm:w-auto px-4 py-1.5 text-xs font-semibold">
            Cari
          </x-ui.button>
          @if(request()->hasAny(['search', 'type']))
            <x-ui.button href="{{ route('master.departments.index') }}" variant="secondary" class="px-3 py-1.5 text-xs font-medium text-slate-600">
              Reset
            </x-ui.button>
          @endif
        </div>
      </form>
    </div>

    {{-- Table Container --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left whitespace-nowrap border-collapse">
          <thead
            class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
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
              <tr class="transition-colors {{ $isOwnDept ? 'bg-cyan-50/40 font-semibold' : 'hover:bg-slate-50/50' }}">
                <td class="py-2.5 px-3 text-center text-slate-400 font-medium">
                  {{ ($departments instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $departments->firstItem() + $i : $i + 1 }}
                </td>

                <td class="py-2.5 px-4">
                  <div class="flex items-center">
                    {{-- Indentasi Pohon Struktur Non-Superadmin --}}
                    {{-- Indentasi Pohon Struktur --}}
                    @if(isset($dept->tree_level) && $dept->tree_level > 0)
                      @for($j = 0; $j < $dept->tree_level; $j++)
                        <span class="w-4 h-px bg-slate-300 inline-block mr-1.5 last:bg-slate-400"></span>
                      @endfor
                      <i class="fa-solid fa-angles-right text-[10px] text-slate-300 mr-2"></i>
                    @endif
                    <span class="{{ $isOwnDept ? 'text-cyan-700 font-bold' : 'text-slate-900' }}">
                      {{ $dept->name }}
                    </span>
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
                  <td class="py-2.5 px-4 text-slate-500 text-[11px] truncate max-w-[160px]">
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
                      class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-cyan-50 hover:text-cyan-600 transition-colors"
                      title="Detail">
                      <i class="fa-solid fa-eye text-[10px]"></i>
                    </a>

                    {{-- Tombol Edit --}}
                    <a wire:navigate href="{{ route('master.departments.edit', $dept->id) }}"
                      class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-colors"
                      title="Edit">
                      <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                    </a>

                    {{-- Tombol Hapus Form kondisional --}}
                    @if($isSuperAdmin || ($dept->parent_id !== null && !$isOwnDept))
                      <form action="{{ route('master.departments.destroy', $dept->id) }}" method="POST" class="inline m-0"
                        onsubmit="return confirm('Yakin ingin menghapus data unit kerja ini?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                          class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                          title="Hapus">
                          <i class="fa-solid fa-trash-can text-[10px]"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ $isSuperAdmin ? '9' : '5' }}" class="py-10 text-center text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-1.5">
                    <i class="fa-solid fa-folder-tree text-2xl opacity-40"></i>
                    <p class="font-medium">Belum ada data unit kerja yang ditemukan</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination Ringkas --}}
      @if($departments instanceof \Illuminate\Pagination\LengthAwarePaginator && $departments->hasPages())
        <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
          {{ $departments->links() }}
        </div>
      @endif
    </div>

  </div>
@endsection