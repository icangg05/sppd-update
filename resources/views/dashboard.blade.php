@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
  <div class="mx-auto flex max-w-7xl flex-col gap-5 p-1">

    {{-- Sapaan ringkas + aksi utama (halaman kerja, bukan banner pemasaran) --}}
    <div
      class="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center">
      <div class="flex flex-col gap-1">
        <h2 class="font-serif text-2xl font-bold tracking-tight text-slate-800">
          Dashboard SPPD {{ auth()->user()->department?->name ?? 'Sistem' }}
        </h2>
        <p class="text-sm text-slate-600">
          {{ auth()->user()->roles->first()?->label ?? 'Pengguna' }} &mdash;
          pantau proses telaah, realisasi anggaran, dan laporan perjalanan dinas.
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
