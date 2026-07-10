@extends('layouts.app')

@section('title', 'Detail Anggaran')
@section('page-title', 'Detail Data Anggaran')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 p-1">

  {{-- Header Halaman --}}
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
      <div class="flex size-11 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
        <i class="fa-solid fa-file-invoice-dollar text-lg"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Informasi DPA</h1>
        <p class="mt-0.5 text-sm text-slate-500">Rincian lengkap dokumen pelaksanaan anggaran.</p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary">
        <x-slot name="icon"><i class="fa-solid fa-arrow-left"></i></x-slot>
        Kembali
      </x-ui.button>
      @can('budget.edit')
        <x-ui.button href="{{ route('master.budgets.edit', $budget->id) }}" variant="primary">
          <x-slot name="icon"><i class="fa-solid fa-pen-to-square"></i></x-slot>
          Edit Data
        </x-ui.button>
      @endcan
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Kolom Kiri: Tabel Informasi Detail --}}
    <div class="lg:col-span-2 space-y-6">
      <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/50 px-4 py-3.5">
          <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
            <i class="fa-solid fa-file-invoice"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-800">Detail Atribut DPA</h3>
            <p class="text-xs text-slate-500">Klasifikasi program, kegiatan, dan kode anggaran.</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm border-collapse">
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr>
                <th class="w-1/3 p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider">SKPD / Instansi</th>
                <td class="p-4 text-sm font-bold text-slate-900">{{ $budget->department->name }}</td>
              </tr>
              <tr>
                <th class="p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider">Tahun Anggaran</th>
                <td class="p-4 font-mono font-semibold text-slate-800">{{ $budget->year }}</td>
              </tr>
              <tr>
                <th class="p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider">Jenis Anggaran</th>
                <td class="p-4 font-medium text-slate-800">{{ $budget->type ?? '-' }}</td>
              </tr>
              <tr>
                <th class="p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider">Program Utama</th>
                <td class="p-4 font-semibold text-primary-800 whitespace-normal leading-normal">{{ $budget->program }}</td>
              </tr>
              <tr>
                <th class="p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider">Sub Kegiatan</th>
                <td class="p-4 text-slate-600 whitespace-normal leading-relaxed">{{ $budget->activity }}</td>
              </tr>
              <tr>
                <th class="p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider">Kode Rekening</th>
                <td class="p-4">
                  <span class="inline-block rounded bg-slate-100 px-2.5 py-1 text-xs font-mono font-medium text-slate-600 border border-slate-200/60">
                    {{ $budget->account_code }}
                  </span>
                </td>
              </tr>
              <tr>
                <th class="p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider">Mata Anggaran / Sumber</th>
                <td class="p-4">
                  <span class="inline-flex items-center rounded bg-emerald-50 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                    {{ $budget->source }}
                  </span>
                </td>
              </tr>
              <tr>
                <th class="p-4 bg-slate-50/30 text-xs font-bold uppercase text-slate-500 tracking-wider align-top">Uraian Penjelasan</th>
                <td class="p-4 text-slate-600 whitespace-normal leading-relaxed align-top font-medium">{{ $budget->description }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Kolom Kanan: Ringkasan & Progress Penyerapan Finansial --}}
    <div class="space-y-6">
      <div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/50 px-4 py-3.5">
          <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
            <i class="fa-solid fa-chart-pie"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-800">Ringkasan Keuangan</h3>
            <p class="text-xs text-slate-500">Pagu, realisasi, dan penyerapan.</p>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Pagu Anggaran</p>
            <p class="text-xl font-bold text-slate-900 font-mono">Rp {{ number_format($budget->total_amount, 0, ',', '.') }}</p>
          </div>

          <div class="pt-4 border-t border-slate-100">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Realisasi</p>
            <p class="text-lg font-bold text-primary-600 font-mono">Rp {{ number_format($budget->realization, 0, ',', '.') }}</p>
          </div>

          <div class="pt-4 border-t border-slate-100">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sisa Anggaran DPA</p>
            <p class="text-lg font-bold font-mono {{ $budget->balance < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
              Rp {{ number_format($budget->balance, 0, ',', '.') }}
            </p>
          </div>

          {{-- Progress Bar Penyerapan --}}
          <div class="pt-4 border-t border-slate-100">
            <div class="flex justify-between items-center text-xs font-bold mb-2">
              <span class="text-slate-500 uppercase text-[10px] tracking-wider">Persentase Penyerapan</span>
              <span class="text-slate-800 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200/60">{{ number_format($budget->realization_percentage, 1, ',', '.') }}%</span>
            </div>
            <x-ui.budget-bar :percentage="$budget->realization_percentage" height="h-3" />
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
