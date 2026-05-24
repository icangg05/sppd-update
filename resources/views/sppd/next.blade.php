@extends('layouts.app')
@section('title', 'Selanjutnya - Portal Dokumen')

@section('content')
<div class="p-1 space-y-10">

  {{-- Header Halaman --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-slate-900">Portal Dokumen</h1>
      <p class="text-sm text-slate-500 mt-1">Pengelolaan administrasi SPPD untuk: <span class="font-semibold text-slate-700">{{ $sppd->user->name }}</span></p>
    </div>
    <a href="{{ route('sppd.index') }}" class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
    </a>
  </div>

  <div class="max-w-6xl space-y-12">

    {{-- Bagian Dokumen Sebelum --}}
    <section>
      <div class="flex items-center gap-4 mb-8">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">
          <i class="fa-solid fa-file-circle-plus mr-2 text-orange-400"></i> Dokumen Sebelum Perjalanan
        </h3>
        <div class="h-px w-full bg-slate-200"></div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
          $beforeDocs = [
            ['route' => 'sppd.manage-sppd', 'title' => 'Surat Perintah Perjalanan Dinas', 'desc' => 'Kelola & Cetak Dokumen SPPD', 'icon' => 'fa-file-lines', 'color' => 'orange'],
            ['route' => 'sppd.manage-spt', 'title' => 'Surat Perintah Tugas', 'desc' => 'Kelola & Cetak Dokumen SPT', 'icon' => 'fa-file-signature', 'color' => 'orange'],
            ['route' => 'sppd.receipts', 'title' => 'Kuitansi', 'desc' => 'Input Panjar & Cetak Kuitansi', 'icon' => 'fa-file-invoice-dollar', 'color' => 'orange'],
          ];
        @endphp

        @foreach($beforeDocs as $doc)
          <a href="{{ route($doc['route'], $sppd) }}" class="group relative flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-md transition-all hover:border-{{ $doc['color'] }}-300 hover:shadow-lg hover:-translate-y-1">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-{{ $doc['color'] }}-100 text-{{ $doc['color'] }}-600 group-hover:scale-105 transition-transform">
              <i class="fa-solid {{ $doc['icon'] }} text-lg"></i>
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-800 leading-snug group-hover:text-{{ $doc['color'] }}-700">{{ $doc['title'] }}</p>
              <p class="mt-1 text-[11px] font-medium text-slate-400 uppercase">{{ $doc['desc'] }}</p>
            </div>
            <i class="fa-solid fa-chevron-right absolute right-5 top-5 text-slate-300 opacity-0 transition group-hover:opacity-100"></i>
          </a>
        @endforeach
      </div>
    </section>

    {{-- Bagian Dokumen Sesudah --}}
    <section>
      <div class="flex items-center gap-4 mb-8">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">
          <i class="fa-solid fa-file-circle-check mr-2 text-cyan-600"></i> Sesudah Perjalanan
        </h3>
        <div class="h-px w-full bg-slate-200"></div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
          $afterDocs = [
            ['route' => 'sppd.actual-expenses', 'title' => 'Laporan Pengeluaran Rill', 'desc' => 'Input Biaya Aktual', 'icon' => 'fa-hand-holding-dollar', 'color' => 'cyan'],
            ['route' => 'sppd.final-costs', 'title' => 'Rincian Biaya Perjalanan', 'desc' => 'Input Detail Pengeluaran', 'icon' => 'fa-calculator', 'color' => 'cyan'],
            ['route' => 'sppd.report-input', 'title' => 'Laporan Perjalanan', 'desc' => 'Input Narasi Hasil', 'icon' => 'fa-pen-to-square', 'color' => 'cyan'],
          ];
        @endphp

        @foreach($afterDocs as $doc)
          <a href="{{ route($doc['route'], $sppd) }}" class="group relative flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-md transition-all hover:border-{{ $doc['color'] }}-300 hover:shadow-lg hover:-translate-y-1">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-{{ $doc['color'] }}-100 text-{{ $doc['color'] }}-600 group-hover:scale-105 transition-transform">
              <i class="fa-solid {{ $doc['icon'] }} text-lg"></i>
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-800 leading-snug group-hover:text-{{ $doc['color'] }}-700">{{ $doc['title'] }}</p>
              <p class="mt-1 text-[11px] font-medium text-slate-400 uppercase">{{ $doc['desc'] }}</p>
            </div>
            <i class="fa-solid fa-chevron-right absolute right-5 top-5 text-slate-300 opacity-0 transition group-hover:opacity-100"></i>
          </a>
        @endforeach
      </div>
    </section>

  </div>
</div>
@endsection
