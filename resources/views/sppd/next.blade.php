@extends('layouts.app')
@section('title', 'Selanjutnya - Portal Dokumen')

@section('content')
<div class="p-1 space-y-10">

  {{-- Header Halaman --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-folder-open pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 md:flex-row md:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-regular fa-user text-[9px]"></i> {{ $sppd->user->name }}
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Portal Dokumen</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola dokumen administrasi perjalanan dinas &mdash; sebelum & sesudah
          pelaksanaan.</p>
      </div>
      <x-ui.button href="{{ route('sppd.index') }}" variant="secondary">
        <x-slot name="icon"><i class="fa-solid fa-arrow-left"></i></x-slot>
        Kembali ke Daftar
      </x-ui.button>
    </div>
  </div>

  <div class="max-w-6xl space-y-12">

    {{-- Bagian Dokumen Sebelum --}}
    <section class="dash-enter">
      <div class="flex items-center gap-4 mb-8">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest whitespace-nowrap">
          <i class="fa-solid fa-file-circle-plus mr-2 text-orange-400"></i> Dokumen Sebelum Perjalanan
        </h3>
        <div class="h-px w-full bg-slate-200"></div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
          $beforeDocs = [
            ['route' => 'sppd.manage-sppd', 'title' => 'Surat Perintah Perjalanan Dinas', 'desc' => 'Kelola & Cetak Dokumen SPPD', 'icon' => 'fa-file-lines'],
            ['route' => 'sppd.manage-spt', 'title' => 'Surat Perintah Tugas', 'desc' => 'Kelola & Cetak Dokumen SPT', 'icon' => 'fa-file-signature'],
            ['route' => 'sppd.receipts', 'title' => 'Kuitansi', 'desc' => 'Input Panjar & Cetak Kuitansi', 'icon' => 'fa-file-invoice-dollar'],
          ];
        @endphp

        @foreach($beforeDocs as $doc)
          <x-sppd.doc-card :href="route($doc['route'], $sppd)" :title="$doc['title']" :desc="$doc['desc']"
            :icon="$doc['icon']" tone="orange" />
        @endforeach
      </div>
    </section>

    {{-- Bagian Dokumen Sesudah --}}
    <section class="dash-enter">
      <div class="flex items-center gap-4 mb-8">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest whitespace-nowrap">
          <i class="fa-solid fa-file-circle-check mr-2 text-primary-600"></i> Dokumen Sesudah Perjalanan
        </h3>
        <div class="h-px w-full bg-slate-200"></div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
          $afterDocs = [
            ['route' => 'sppd.actual-expenses', 'title' => 'Laporan Pengeluaran Rill', 'desc' => 'Input Biaya Aktual', 'icon' => 'fa-hand-holding-dollar'],
            ['route' => 'sppd.final-costs', 'title' => 'Rincian Biaya Perjalanan', 'desc' => 'Input Detail Pengeluaran', 'icon' => 'fa-calculator'],
            ['route' => 'sppd.report-input', 'title' => 'Laporan Perjalanan', 'desc' => 'Input Narasi Hasil', 'icon' => 'fa-pen-to-square'],
          ];
        @endphp

        @foreach($afterDocs as $doc)
          <x-sppd.doc-card :href="route($doc['route'], $sppd)" :title="$doc['title']" :desc="$doc['desc']"
            :icon="$doc['icon']" tone="primary" />
        @endforeach
      </div>
    </section>

  </div>
</div>
@endsection
