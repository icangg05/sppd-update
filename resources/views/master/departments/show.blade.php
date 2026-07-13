@extends('layouts.app')
@section('title', 'Detail Profil OPD')
@section('page-title', 'Detail Profil OPD')

@section('content')
  <div class="mx-auto max-w-6xl space-y-6 p-1">

    {{-- Header Halaman (title card) --}}
    <div
      class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
      {{-- Watermark institusional (tipis, hanya karakter). --}}
      <i class="fa-solid fa-folder-open pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
        aria-hidden="true"></i>

      <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="min-w-0 leading-tight">
          <span
            class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
            <i class="fa-solid fa-circle-info text-[9px]"></i>
            Profil Instansi
          </span>
          <h1 class="text-xl font-bold tracking-tight text-slate-800">Profil OPD</h1>
          <p class="mt-1 text-xs text-slate-500">Rincian atribut data instansi atau unit kerja terkait</p>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
          <x-ui.button href="{{ route('master.departments.index') }}" variant="secondary" class="shrink-0">
            <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
            Kembali
          </x-ui.button>
          <x-ui.button href="{{ route('master.departments.edit', $department->id) }}" variant="primary" class="shrink-0">
            <x-slot name="icon"><i class="fa-solid fa-pen-to-square text-xs"></i></x-slot>
            Edit Profil
          </x-ui.button>
        </div>
      </div>
    </div>

    {{-- Dashboard Detail Grid Layout --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

      {{-- Sektor Utama: Lembar Informasi Instansi (Mengambil 2 Kolom) --}}
      <div class="md:col-span-2 space-y-6">
        <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/50 px-4 py-3.5">
            <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
              <i class="fa-solid fa-circle-info"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-800">Informasi Pokok Struktur</h3>
              <p class="text-xs text-slate-500">Identitas, hierarki, dan kop surat resmi.</p>
            </div>
          </div>

          <div class="p-4 space-y-3 text-xs">
            {{-- Baris Grid Horizontal untuk Parameter Ringkas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              @if (!$department->parent_id)
                <div class="space-y-0.5">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kode Singkatan
                    OPD</label>
                  <div
                    class="px-2 py-1 bg-slate-50 border border-slate-200 rounded font-mono text-[11px] text-slate-700 inline-block">
                    {{ $department->code }}
                  </div>
                </div>

                <div class="space-y-0.5">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tipe Instansi</label>
                  <div>
                    <span
                      class="inline-flex items-center rounded px-2 py-0.5 font-bold border uppercase text-[10px] {{ $department->type->badgeClasses() }}">
                      {{ $department->type->label() }}
                    </span>
                  </div>
                </div>
              @endif
            </div>

            {{-- Blok Nama Instansi --}}
            <div class="space-y-0.5 pt-1">
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Instansi / Unit
                Kerja</label>
              <p class="text-sm font-bold text-slate-900 tracking-wide">{{ $department->name }}</p>
            </div>

            {{-- Blok Instansi Induk (Jika Ada) --}}
            @if ($department->parent)
              <div class="space-y-0.5 border-t border-slate-100 pt-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Induk Instansi
                  Pengampu</label>
                <p class="text-xs font-medium text-slate-700 flex items-center gap-1.5">
                  <i class="fa-solid fa-network-wired text-slate-500 text-[10px]"></i> {{ $department->parent->name }}
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
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kop Resmi Surat Dinas
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
                      <p class="text-xs text-slate-500 italic font-medium"><i class="fa-solid fa-image-slash mr-1"></i>Belum
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
                      <p class="text-xs text-slate-500 italic font-medium"><i class="fa-solid fa-image-slash mr-1"></i>Belum
                        diatur.</p>
                    @endif
                  </div>
                </div>
              </div>
            @elseif ($inheritedKop)
              <div class="space-y-1 border-t border-slate-100 pt-3">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
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
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kop Resmi Surat
                  Dinas</label>
                <p class="text-xs text-slate-500 italic font-medium"><i class="fa-solid fa-image-slash mr-1"></i>Belum ada
                  gambar kop surat resmi yang diatur pada unit kerja ini.</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Sektor Samping: Penanggung Jawab & Statistik (1 Kolom) --}}
      <div class="space-y-6">

        {{-- Card Statistik Internal Pegawai --}}
        @if (!$department->parent_id)
          <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/50 px-4 py-3.5">
              <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
                <i class="fa-solid fa-users"></i>
              </div>
              <h3 class="text-sm font-bold text-slate-800">Statistik Aparatur</h3>
            </div>
            <div class="p-3">
              <div class="flex items-center justify-between p-2 bg-slate-50 border border-slate-200 rounded">
                <div class="flex items-center gap-2 text-slate-600 text-xs font-medium">
                  <i class="fa-solid fa-users text-primary-600 text-xs"></i>
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
