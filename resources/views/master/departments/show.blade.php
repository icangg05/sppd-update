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
            class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
            <i class="fa-solid fa-circle-info text-[9px]"></i>
            {{ $department->parent_id ? 'Unit Kerja' : 'Instansi' }}
          </span>
          <h1 class="text-xl font-bold tracking-tight text-slate-800">{{ $department->parent_id ? 'Profil Unit Kerja' : 'Profil Instansi' }}</h1>
          <p class="mt-1 text-xs text-slate-500">Rincian atribut data instansi atau unit kerja terkait</p>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
          @if ($backUrl)
            <x-ui.button :href="$backUrl" variant="secondary" class="shrink-0">
              <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
              Kembali
            </x-ui.button>
          @endif
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
            {{-- Kode & tipe selalu tampil; kode kosong ditandai "-". --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-0.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $department->parent_id ? 'Kode Unit Kerja' : 'Kode Singkatan OPD' }}</label>
                <div
                  class="px-2 py-1 bg-slate-50 border border-slate-200 rounded font-mono text-[11px] text-slate-700 inline-block">
                  {{ $department->code ?: '-' }}
                </div>
              </div>

              <div class="space-y-0.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $department->parent_id ? 'Jenis Unit' : 'Tipe Instansi' }}</label>
                <div>
                  <span
                    class="inline-flex items-center rounded px-2 py-0.5 font-bold border uppercase text-[10px] {{ $department->type->badgeClasses() }}">
                    {{ $department->type->label() }}
                  </span>
                </div>
              </div>
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

        {{-- Sub-unit: Kelurahan, Puskesmas, atau Asisten/Bagian/Subbagian. Susunannya diatur Super Admin. --}}
        @if ($department->children->isNotEmpty())
          <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/50 px-4 py-3.5">
              <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
                <i class="fa-solid fa-sitemap"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-800">Sub-unit <span class="font-mono text-slate-500">({{ $department->children->count() }})</span></h3>
                <p class="text-xs text-slate-500">Penambahan, pemindahan, dan penghapusan sub-unit diatur oleh Super Admin.</p>
              </div>
            </div>

            <ul class="divide-y divide-slate-100">
              @foreach ($department->children as $child)
                @php $open = $canOpen($child); @endphp
                <li class="flex items-center gap-3 px-4 py-2.5">
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ $child->name }}</p>
                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11px] text-slate-500">
                      @if ($child->type)
                        <x-ui.badge bg="" text="" class="border {{ $child->type->badgeClasses() }}">{{ $child->type->label() }}</x-ui.badge>
                      @endif
                      <span><i class="fa-solid fa-users mr-1 text-slate-400"></i><span class="font-mono">{{ $child->users_count }}</span> pegawai</span>
                      @if ($child->children_count)
                        <span><i class="fa-solid fa-sitemap mr-1 text-slate-400"></i><span class="font-mono">{{ $child->children_count }}</span> sub-unit</span>
                      @endif
                      {{-- Kelurahan/Puskesmas mengelola data sendiri lewat admin unitnya. --}}
                      @unless ($open)
                        <span class="italic">Dikelola admin unit ini</span>
                      @endunless
                    </div>
                  </div>
                  @if ($open)
                    <x-ui.button :href="route('master.departments.show', $child->id)" variant="secondary" size="sm" class="shrink-0">
                      Lihat
                    </x-ui.button>
                  @endif
                </li>
              @endforeach
            </ul>
          </div>
        @endif
      </div>

      {{-- Sektor Samping: Penanggung Jawab & Statistik (1 Kolom) --}}
      <div class="space-y-6">

        {{-- Card Statistik Internal Pegawai: untuk semua unit agar kolom samping tidak kosong. --}}
        <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/50 px-4 py-3.5">
            <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
              <i class="fa-solid fa-users"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Statistik Aparatur</h3>
          </div>
          <div class="space-y-2 p-3">
            <div class="flex items-center justify-between p-2 bg-slate-50 border border-slate-200 rounded">
              <div class="flex items-center gap-2 text-slate-600 text-xs font-medium">
                <i class="fa-solid fa-users text-primary-600 text-xs"></i>
                <span>{{ $department->parent_id ? 'Pegawai di unit ini' : 'Total Pegawai' }}</span>
              </div>
              <span
                class="text-sm font-mono font-black text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-200">
                {{ $department->users_count }}
              </span>
            </div>
            @if ($department->children->isNotEmpty())
              <div class="flex items-center justify-between p-2 bg-slate-50 border border-slate-200 rounded">
                <div class="flex items-center gap-2 text-slate-600 text-xs font-medium">
                  <i class="fa-solid fa-sitemap text-primary-600 text-xs"></i>
                  <span>Sub-unit</span>
                </div>
                <span
                  class="text-sm font-mono font-black text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-200">
                  {{ $department->children->count() }}
                </span>
              </div>
            @endif
          </div>
        </div>

      </div>
    </div>
  </div>
@endsection
