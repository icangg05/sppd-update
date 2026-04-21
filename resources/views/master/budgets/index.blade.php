@extends('layouts.app')
@section('title', 'Data Anggaran')
@section('page-title', 'Data Anggaran')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Data Anggaran</h1>
    <p class="page-subtitle">Kelola pos anggaran perjalanan dinas per instansi</p>
  </div>
  <a href="{{ route('master.budgets.create') }}" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
    Tambah Anggaran
  </a>
</div>

{{-- Filters --}}
<div class="card p-4 mb-4">
  <form method="GET" action="{{ route('master.budgets.index') }}" class="flex flex-col sm:flex-row gap-3">
    <div class="flex-1">
      <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Cari nama anggaran atau instansi...">
    </div>
    <select name="year" class="form-select w-full sm:w-36">
      <option value="">Semua Tahun</option>
      @foreach($years as $y)
        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-secondary">Cari</button>
    @if(request()->hasAny(['search', 'year']))
      <a href="{{ route('master.budgets.index') }}" class="btn-ghost">Reset</a>
    @endif
  </form>
</div>

{{-- Summary Cards --}}
@php
  $totalBudget = $budgets->sum('total_amount');
  $totalUsed = 0; // Will be calculated from sppd cost details
@endphp
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <div class="stat-card">
    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
    </div>
    <div>
      <p class="text-lg font-bold text-slate-900">{{ number_format($budgets->count()) }}</p>
      <p class="text-xs text-slate-500">Total Pos Anggaran</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
    </div>
    <div>
      <p class="text-lg font-bold text-slate-900">Rp {{ number_format($totalBudget, 0, ',', '.') }}</p>
      <p class="text-xs text-slate-500">Total Anggaran</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
    </div>
    <div>
      <p class="text-lg font-bold text-slate-900">{{ $budgets->sum('sppd_requests_count') }}</p>
      <p class="text-xs text-slate-500">Total SPPD Terkait</p>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>No</th>
        <th>Instansi</th>
        <th>Nama Pos Anggaran</th>
        <th>Tahun</th>
        <th class="text-right">Total Anggaran</th>
        <th class="text-center">SPPD</th>
      </tr>
    </thead>
    <tbody>
      @forelse($budgets as $i => $budget)
        <tr>
          <td class="text-slate-400">{{ $budgets->firstItem() + $i }}</td>
          <td class="font-medium text-slate-900">{{ $budget->department->name }}</td>
          <td>{{ $budget->name }}</td>
          <td>
            <span class="badge bg-slate-100 text-slate-700">{{ $budget->year }}</span>
          </td>
          <td class="text-right font-medium">Rp {{ number_format($budget->total_amount, 0, ',', '.') }}</td>
          <td class="text-center">
            <span class="text-sm font-medium text-slate-700">{{ $budget->sppd_requests_count }}</span>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center py-12 text-slate-400">Belum ada data anggaran</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($budgets->hasPages())
    <div class="px-4 py-3 border-t border-slate-200">{{ $budgets->links() }}</div>
  @endif
</div>
@endsection
