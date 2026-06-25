@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
  <div class="mx-auto flex max-w-7xl flex-col gap-5 p-1">

    {{-- Welcome Hero Banner --}}
    <div
      class="flex flex-col justify-between gap-4 rounded border border-cyan-200 bg-linear-to-r from-cyan-50 to-white p-5 shadow-md md:flex-row md:items-center">
      <div class="flex flex-col gap-1.5">
        <p class="text-xs font-bold uppercase tracking-wider text-cyan-700">Selamat Datang Kembali</p>
        <h1 class="text-lg font-bold text-slate-800">
          Dashboard SPPD {{ auth()->user()->department?->name ?? 'Sistem' }}
        </h1>
        <p class="text-sm text-slate-600">
          {{ auth()->user()->roles->first()?->label ?? 'Pengguna' }} &mdash;
          pantau proses telaah, realisasi anggaran, dan laporan perjalanan dinas secara real-time.
        </p>
      </div>
      <div class="flex shrink-0 items-center gap-3">
        @can('sppd.create')
          <a wire:navigate href="{{ route('sppd.create') }}"
            class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-700 focus:ring-1 focus:ring-cyan-500">
            <i class="fa-solid fa-plus"></i> Pengajuan Baru
          </a>
        @endcan
        <a wire:navigate href="{{ route('sppd.index') }}"
          class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:ring-1 focus:ring-slate-300">
          Lihat Daftar SPPD <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
      </div>
    </div>

    {{-- Widget DPA tahun berjalan (semua role): ringkasan + per-item + modal rincian (lazy) --}}
    <livewire:dashboard.dpa-panel :summary="$dpa" :items="$dpaItems"
      :show-department="auth()->user()->hasRole('super_admin')"
      :title="auth()->user()->hasRole('super_admin') ? 'DPA Seluruh OPD' : 'DPA ' . (auth()->user()->department?->getRootDepartment()->name ?? 'OPD')" />

    {{-- Konten spesifik per archetype --}}
    @include('dashboard.partials.' . $archetype)

  </div>
@endsection
