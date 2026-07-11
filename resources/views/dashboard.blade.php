@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
  <div class="mx-auto flex max-w-7xl flex-col gap-5 p-1">

    {{-- Sapaan ringkas + aksi utama (halaman kerja, bukan banner pemasaran) --}}
    <div
      class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/60 p-5 shadow-sm">
      {{-- Watermark institusional (sangat tipis, hanya karakter — bukan aksen baru). --}}
      <i class="fa-solid fa-plane-departure pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
        aria-hidden="true"></i>
      {{-- Ornamen garis vertikal (selaras laman lain) --}}
      <span class="pointer-events-none absolute inset-y-3 left-0 w-1 rounded-r bg-linear-to-b from-emerald-400/40 to-primary-400/40"
        aria-hidden="true"></span>

      <div class="relative flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div class="flex flex-col items-start gap-1.5">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[11px] font-semibold text-primary-700 ring-1 ring-inset ring-primary-600/15">
            <i class="fa-solid fa-user-shield text-[10px]"></i>
            {{ auth()->user()->roles->first()?->label ?? 'Pengguna' }}
          </span>
          <h2 class="text-2xl font-bold tracking-tight text-balance text-slate-800">
            Dashboard SPPD {{ auth()->user()->department?->name ?? 'Sistem' }}
          </h2>
          <p class="max-w-prose text-sm text-pretty text-slate-600">
            Pantau proses telaah, realisasi anggaran, dan laporan perjalanan dinas.
          </p>
        </div>
        <div class="flex shrink-0 items-center gap-3">
          @can('sppd.create')
            <x-ui.button href="{{ route('sppd.create') }}" variant="primary">
              <x-slot:icon><i class="fa-solid fa-plus"></i></x-slot:icon>
              Pengajuan Baru
            </x-ui.button>
          @endcan
          <x-ui.button href="{{ route('sppd.index') }}" variant="secondary">
            Lihat Daftar SPPD
          </x-ui.button>
        </div>
      </div>
    </div>

    {{-- Untuk approver & pemohon, tugas utama (antrean persetujuan/TTE, SPPD saya)
         didahulukan di atas widget anggaran; admin melihat anggaran lebih dulu. --}}
    @if ($archetype === 'admin')
      <livewire:dashboard.dpa-panel :summary="$dpa" :items="$dpaItems"
        :show-department="auth()->user()->hasRole('super_admin')"
        :title="auth()->user()->hasRole('super_admin') ? 'DPA Seluruh OPD' : 'DPA ' . (auth()->user()->department?->getScopeRootDepartment()->name ?? 'OPD')" />

      @include('dashboard.partials.' . $archetype)
    @else
      @include('dashboard.partials.' . $archetype)

      <livewire:dashboard.dpa-panel :summary="$dpa" :items="$dpaItems"
        :show-department="auth()->user()->hasRole('super_admin')"
        :title="auth()->user()->hasRole('super_admin') ? 'DPA Seluruh OPD' : 'DPA ' . (auth()->user()->department?->getScopeRootDepartment()->name ?? 'OPD')" />
    @endif

  </div>
@endsection
