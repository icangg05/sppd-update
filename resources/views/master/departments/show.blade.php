@extends('layouts.app')
@section('title', 'Detail Profil OPD')
@section('page-title', 'Detail Profil OPD')

@section('content')
  <div class="p-1 space-y-4">

    {{-- Header Halaman Compact --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
      <div class="flex items-center gap-2.5">
        <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
          <i class="fa-solid fa-folder-open text-base"></i>
        </div>
        <div>
          <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Profil OPD</h1>
          <p class="text-[11px] text-slate-500 font-medium">Informasi menyeluruh rincian atribut data instansi atau unit
            kerja terkait</p>
        </div>
      </div>

      <div class="flex items-center gap-1.5 self-end sm:self-auto">
        @if (auth()->user()->hasRole('super_admin'))
          <x-ui.button href="{{ route('master.departments.index') }}" variant="secondary"
            class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
          </x-ui.button>
        @endif
        <a wire:navigate href="{{ route('master.departments.edit', $department->id) }}"
          class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
          <i class="fa-solid fa-pen-to-square text-[11px]"></i> Edit Profil
        </a>
      </div>
    </div>

    {{-- Dashboard Detail Grid Layout --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

      {{-- Sektor Utama: Lembar Informasi Instansi (Mengambil 2 Kolom) --}}
      <div class="md:col-span-2 space-y-4">
        <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-3 border-b border-slate-200 bg-slate-50/50">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
              <i class="fa-solid fa-circle-info text-cyan-500"></i>Informasi Pokok Struktur
            </h3>
          </div>

          <div class="p-4 space-y-3 text-xs">
            {{-- Baris Grid Horizontal untuk Parameter Ringkas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              @if (!$department->parent_id)
                <div class="space-y-0.5">
                  <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kode Singkatan
                    OPD</label>
                  <div
                    class="px-2 py-1 bg-slate-50 border border-slate-200 rounded font-mono text-[11px] text-slate-700 inline-block">
                    {{ $department->code }}
                  </div>
                </div>

                <div class="space-y-0.5">
                  <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tipe Instansi</label>
                  <div>
                    <span
                      class="inline-flex items-center rounded bg-cyan-50 px-2 py-0.5 font-bold text-cyan-700 border border-cyan-100 uppercase text-[10px]">
                      {{ $department->type->label() }}
                    </span>
                  </div>
                </div>
              @endif
            </div>

            {{-- Blok Nama Instansi --}}
            <div class="space-y-0.5 pt-1">
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Instansi / Unit
                Kerja</label>
              <p class="text-sm font-bold text-slate-900 tracking-wide">{{ $department->name }}</p>
            </div>

            {{-- Blok Instansi Induk (Jika Ada) --}}
            @if ($department->parent)
              <div class="space-y-0.5 border-t border-slate-100 pt-2">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Induk Instansi
                  Pengampu</label>
                <p class="text-xs font-medium text-slate-700 flex items-center gap-1.5">
                  <i class="fa-solid fa-network-wired text-slate-400 text-[10px]"></i> {{ $department->parent->name }}
                </p>
              </div>
            @endif

            {{-- Bagian Pratinjau Kop Surat Resmi --}}
            @php
              $isDprd = $department->type === \App\Enums\DepartmentType::DPRD;
              $inheritedKop = $department->getInheritedLetterhead();
              $inheritedKopSecond = $department->getInheritedLetterheadSecond();
            @endphp
            @if ($isDprd)
              {{-- DPRD memakai dua kop: Kop Utama (SPPD) & Kop Kedua (SPT) --}}
              <div class="space-y-2 border-t border-slate-100 pt-3">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kop Resmi Surat Dinas
                  DPRD</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {{-- Kop Utama / SPPD --}}
                  <div class="space-y-1">
                    <span
                      class="inline-flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                      Kop Utama / SPPD
                      @if (empty($department->letterhead) && $inheritedKop)
                        <span
                          class="rounded bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 text-[9px] font-bold">Warisan
                          dari Induk</span>
                      @endif
                    </span>
                    @if ($inheritedKop)
                      <div class="p-2 bg-slate-50 border border-slate-200 rounded flex justify-center">
                        <img src="{{ asset('storage/' . $inheritedKop) }}" alt="kop-surat-utama"
                          class="max-h-16 rounded border border-slate-300/60 p-1 bg-white shadow-sm">
                      </div>
                    @else
                      <p class="text-xs text-slate-400 italic font-medium"><i class="fa-solid fa-image-slash mr-1"></i>Belum
                        diatur.</p>
                    @endif
                  </div>

                  {{-- Kop Kedua / SPT --}}
                  <div class="space-y-1">
                    <span
                      class="inline-flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                      Kop Kedua / SPT
                      @if (empty($department->letterhead_second) && $inheritedKopSecond)
                        <span
                          class="rounded bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 text-[9px] font-bold">Warisan
                          dari Induk</span>
                      @endif
                    </span>
                    @if ($inheritedKopSecond)
                      <div class="p-2 bg-slate-50 border border-slate-200 rounded flex justify-center">
                        <img src="{{ asset('storage/' . $inheritedKopSecond) }}" alt="kop-surat-kedua"
                          class="max-h-16 rounded border border-slate-300/60 p-1 bg-white shadow-sm">
                      </div>
                    @else
                      <p class="text-xs text-slate-400 italic font-medium"><i class="fa-solid fa-image-slash mr-1"></i>Belum
                        diatur.</p>
                    @endif
                  </div>
                </div>
              </div>
            @elseif ($inheritedKop)
              <div class="space-y-1 border-t border-slate-100 pt-3">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                  Kop Resmi Surat Dinas
                  @if (empty($department->letterhead))
                    <span
                      class="rounded bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 text-[9px] font-bold">Warisan
                      dari Induk</span>
                  @endif
                </label>
                <div class="p-2 bg-slate-50 border border-slate-200 rounded flex justify-center max-w-xl">
                  <img src="{{ asset('storage/' . $inheritedKop) }}" alt="kop-surat"
                    class="max-h-16 rounded border border-slate-300/60 p-1 bg-white shadow-sm">
                </div>
              </div>
            @elseif(!$department->parent_id)
              <div class="space-y-0.5 border-t border-slate-100 pt-3">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kop Resmi Surat
                  Dinas</label>
                <p class="text-xs text-slate-400 italic font-medium"><i class="fa-solid fa-image-slash mr-1"></i>Belum ada
                  gambar kop surat resmi yang diatur pada unit kerja ini.</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Sektor Samping: Penanggung Jawab & Statistik (1 Kolom) --}}
      <div class="space-y-4">

        {{-- Card Profil Pimpinan --}}
        <div class="bg-white rounded border border-slate-200 border-t-2 border-t-cyan-500 shadow-sm overflow-hidden">
          <div class="p-3 border-b border-slate-200 bg-slate-50/50">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
              <i class="fa-solid fa-user-tie text-cyan-500"></i>Pimpinan Unit Kerja
            </h3>
          </div>

          <div class="p-4 text-xs">
            @if ($department->head)
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 bg-cyan-100 text-cyan-700 rounded border border-cyan-200 flex items-center justify-center font-black text-sm shrink-0 shadow-inner">
                  {{ strtoupper(substr($department->head->name, 0, 1)) }}
                </div>
                <div class="space-y-0.5 min-w-0">
                  <p class="font-bold text-slate-900 truncate tracking-wide text-xs">{{ $department->head->name }}</p>
                  <p class="text-[11px] font-semibold text-slate-400 truncate uppercase">
                    {{ $department->head->position?->name ?? 'Pimpinan / Kepala' }}</p>
                  <div
                    class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded font-mono text-[10px] text-slate-600 mt-1">
                    <i class="fa-solid fa-id-card text-[10px] text-slate-400"></i> NIP: {{ $department->head->nip ?? '-' }}
                  </div>
                </div>
              </div>
            @else
              <div class="text-center py-4 space-y-2">
                <div
                  class="w-9 h-9 bg-slate-100 text-slate-400 rounded flex items-center justify-center mx-auto border border-slate-200">
                  <i class="fa-solid fa-user-slash text-sm"></i>
                </div>
                <div class="space-y-0.5">
                  <p class="font-medium text-slate-500">Pimpinan belum ditentukan</p>
                  <a wire:navigate href="{{ route('master.departments.edit', $department->id) }}"
                    class="inline-block text-[11px] font-bold text-cyan-600 hover:text-cyan-700">
                    Atur Pimpinan Sekarang &rarr;
                  </a>
                </div>
              </div>
            @endif
          </div>
        </div>

        {{-- Card Statistik Internal Pegawai --}}
        @if (!$department->parent_id)
          <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-3 border-b border-slate-200 bg-slate-50/50">
              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Statistik Aparatur</h3>
            </div>
            <div class="p-3">
              <div class="flex items-center justify-between p-2 bg-slate-50 border border-slate-200 rounded">
                <div class="flex items-center gap-2 text-slate-600 text-xs font-medium">
                  <i class="fa-solid fa-users text-cyan-600 text-xs"></i>
                  <span>Total Pegawai</span>
                </div>
                <span
                  class="text-sm font-mono font-black text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-200">
                  {{ $department->users->count() }}
                </span>
              </div>
            </div>
          </div>
        @endif

      </div>
    </div>
  </div>
@endsection
