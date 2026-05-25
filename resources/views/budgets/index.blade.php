@extends('layouts.app')

@section('title', 'DPA - Data Anggaran')
@section('page-title', 'Dokumen Pelaksanaan Anggaran (DPA)')

@section('content')
  <div class="p-1 space-y-6">

    {{-- Header Halaman --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="p-2 bg-cyan-100 rounded">
          <i class="fa-solid fa-file-invoice-dollar text-cyan-600 text-lg"></i>
        </div>
        <div>
          <h1
            class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
            Daftar Anggaran (DPA)
          </h1>
          <p class="mt-1 text-xs text-slate-500 font-medium">Tahun Anggaran {{ $year }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <x-ui.button type="button" variant="secondary" onclick="window.location.reload()"
          class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
          <x-slot name="icon">
            <i class="fa-solid fa-arrows-rotate"></i>
          </x-slot>
          Refresh
        </x-ui.button>
        @can('budget.create')
          <x-ui.button href="{{ route('master.budgets.create') }}"
            class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
            <x-slot name="icon">
              <i class="fa-solid fa-plus"></i>
            </x-slot>
            Tambah Data
          </x-ui.button>
        @endcan
      </div>
    </div>

    {{-- Table Container --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">

      {{-- Filter Header --}}
      <div class="p-4 border-b border-slate-200 bg-slate-50/50">
        <form action="{{ route('master.budgets.index') }}" method="GET"
          class="flex flex-col sm:flex-row items-center gap-3">

          {{-- Search Input --}}
          <div class="relative flex-1 w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input type="text" name="search" value="{{ request('search') }}"
              class="block w-full rounded border border-slate-300 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition"
              placeholder="Cari program, kegiatan, atau uraian...">
          </div>

          {{-- Year Dropdown --}}
          <div class="relative w-full sm:w-36">
            <select name="year"
              class="block w-full appearance-none rounded border border-slate-300 bg-slate-50 py-2 pl-3 pr-10 text-sm focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition">
              @for ($y = date('Y'); $y >= 2019; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
              @endfor
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
              <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
          </div>

          {{-- Submit Button --}}
          <button type="submit"
            class="w-full sm:w-auto inline-flex items-center justify-center rounded bg-slate-800 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900">
            Filter
          </button>
        </form>
      </div>

      {{-- Table Grid --}}
      <div class="overflow-x-auto">
        <table class="w-full text-left whitespace-nowrap">
          <thead
            class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 w-12 text-center">No.</th>
              @if (auth()->user()->hasRole('super_admin'))
                <th class="py-3 px-4">SKPD / Instansi</th>
              @endif
              <th class="py-3 px-4 text-center">TA</th>
              <th class="py-3 px-4">Program / Kegiatan</th>
              <th class="py-3 px-4">Kode Rekening</th>
              <th class="py-3 px-4">Uraian Anggaran</th>
              <th class="py-3 px-4 text-right">Pagu Total</th>
              <th class="py-3 px-4 text-right">Sisa Pagu</th>
              <th class="py-3 px-4 text-center w-28">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            @forelse($budgets as $budget)
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="py-3.5 px-4 text-center text-xs font-semibold text-slate-400">
                  {{ $loop->iteration }}.
                </td>

                @if (auth()->user()->hasRole('super_admin'))
                  <td class="py-3.5 px-4 text-sm font-bold text-slate-900">
                    {{ $budget->department->name }}
                  </td>
                @endif

                <td class="py-3.5 px-4 text-center text-xs font-mono font-medium text-slate-500">
                  {{ $budget->year }}
                </td>

                <td class="py-3.5 px-4 max-w-xs whitespace-normal">
                  {{-- Sub-rumpun warna Cyan yang soft untuk penanda program --}}
                  <div class="text-xs font-bold text-cyan-700 leading-tight mb-1">{{ $budget->program }}</div>
                  <div class="text-[11px] text-slate-500 leading-relaxed">{{ $budget->activity }}</div>
                </td>

                <td class="py-3.5 px-4">
                  <span
                    class="inline-block rounded bg-slate-100 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 border border-slate-200/60">
                    {{ $budget->account_code }}
                  </span>
                </td>

                <td class="py-3.5 px-4 max-w-xs whitespace-normal">
                  <div class="text-sm font-medium text-slate-700 leading-normal">{{ $budget->description }}</div>
                </td>

                <td class="py-3.5 px-4 text-right font-semibold text-slate-900 text-sm">
                  Rp {{ number_format($budget->total_amount, 0, ',', '.') }}
                </td>

                <td class="py-3.5 px-4 text-right font-semibold text-xs">
                  @if ($budget->balance < 0)
                    <span
                      class="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 text-rose-700 ring-1 ring-inset ring-rose-600/10">
                      Rp {{ number_format($budget->balance, 0, ',', '.') }}
                    </span>
                  @else
                    <span
                      class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                      Rp {{ number_format($budget->balance, 0, ',', '.') }}
                    </span>
                  @endif
                </td>

                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    {{-- Detail --}}
                    <a href="{{ route('master.budgets.show', $budget->id) }}"
                      class="rounded border border-slate-200 bg-white p-1.5 text-slate-400 hover:bg-cyan-50 hover:text-cyan-600 transition-colors"
                      title="Detail Anggaran">
                      <i class="fa-solid fa-eye text-xs"></i>
                    </a>

                    {{-- Edit --}}
                    @can('budget.edit')
                      <a href="{{ route('master.budgets.edit', $budget->id) }}"
                        class="rounded border border-slate-200 bg-white p-1.5 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-colors"
                        title="Edit Anggaran">
                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                      </a>
                    @endcan

                    {{-- Hapus --}}
                    @can('budget.delete')
                      <form action="{{ route('master.budgets.destroy', $budget->id) }}" method="POST" class="inline m-0"
                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data anggaran ini?');">
                        @csrf @method('DELETE')
                        <button type="submit"
                          class="rounded border border-slate-200 bg-white p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                          title="Hapus Anggaran">
                          <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                      </form>
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ auth()->user()->hasRole('super_admin') ? '9' : '8' }}" class="py-12 text-center">
                  <div class="flex flex-col items-center justify-center text-slate-400">
                    <i class="fa-solid fa-folder-open text-3xl mb-3 opacity-50"></i>
                    <p class="text-sm font-medium">Belum ada data anggaran yang ditemukan</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if ($budgets->hasPages())
        <div class="border-t border-slate-200 bg-slate-50/50 px-4 py-3">
          {{ $budgets->links() }}
        </div>
      @endif
    </div>

  </div>
@endsection