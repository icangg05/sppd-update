@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
{{-- Stat Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
  <div class="stat-card">
    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
    </div>
    <div>
      <p class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</p>
      <p class="text-xs text-slate-500">Total SPPD</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
    </div>
    <div>
      <p class="text-2xl font-bold text-slate-900">{{ $stats['draft'] }}</p>
      <p class="text-xs text-slate-500">Draft</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
    </div>
    <div>
      <p class="text-2xl font-bold text-slate-900">{{ $stats['in_progress'] }}</p>
      <p class="text-xs text-slate-500">Proses</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
    </div>
    <div>
      <p class="text-2xl font-bold text-slate-900">{{ $stats['approved'] }}</p>
      <p class="text-xs text-slate-500">Disetujui</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" /></svg>
    </div>
    <div>
      <p class="text-2xl font-bold text-slate-900">{{ $stats['completed'] }}</p>
      <p class="text-xs text-slate-500">Selesai</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
    </div>
    <div>
      <p class="text-2xl font-bold text-slate-900">{{ $stats['rejected'] }}</p>
      <p class="text-xs text-slate-500">Ditolak</p>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  {{-- Recent SPPD --}}
  <div class="xl:col-span-2 table-container">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
      <h3 class="font-semibold text-slate-900">SPPD Terbaru</h3>
      <a href="{{ route('sppd.index') }}" class="text-sm text-primary-500 hover:text-primary-700 font-medium">Lihat semua →</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Pelaksana</th>
          <th>Maksud</th>
          <th>Tanggal</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($recentSppd as $item)
          <tr class="cursor-pointer" onclick="window.location='{{ route('sppd.show', $item) }}'">
            <td>
              <p class="font-medium text-slate-900">{{ $item->user->name }}</p>
              <p class="text-xs text-slate-400">{{ $item->budget?->department?->name ?? '-' }}</p>
            </td>
            <td class="max-w-[200px]">
              <p class="truncate">{{ $item->purpose }}</p>
              <p class="text-xs text-slate-400">{{ $item->category?->name }}</p>
            </td>
            <td class="whitespace-nowrap text-xs">
              {{ $item->start_date->format('d M') }} - {{ $item->end_date->format('d M Y') }}
            </td>
            <td>
              <span class="badge-{{ $item->status->value }}">{{ $item->status->label() }}</span>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center py-8 text-slate-400">Belum ada data SPPD</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pending Approvals --}}
  <div class="table-container">
    <div class="px-5 py-4 border-b border-slate-200">
      <h3 class="font-semibold text-slate-900">Menunggu Persetujuan</h3>
    </div>
    <div class="divide-y divide-slate-100">
      @forelse($pendingApprovals as $approval)
        <a href="{{ route('sppd.show', $approval->sppdRequest) }}" class="block px-5 py-3 hover:bg-slate-50 transition-colors">
          <p class="font-medium text-sm text-slate-900">{{ $approval->sppdRequest->user->name }}</p>
          <p class="text-xs text-slate-500 truncate">{{ $approval->sppdRequest->purpose }}</p>
          <div class="flex items-center gap-2 mt-1">
            <span class="badge-pending">Step {{ $approval->step_order }}</span>
            <span class="text-[11px] text-slate-400">{{ $approval->role_label }}</span>
          </div>
        </a>
      @empty
        <div class="px-5 py-8 text-center text-slate-400 text-sm">
          Tidak ada yang perlu disetujui
        </div>
      @endforelse
    </div>
  </div>
</div>
@endsection
