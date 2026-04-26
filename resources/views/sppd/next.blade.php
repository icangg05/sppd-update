@extends('layouts.app')
@section('title', 'Selanjutnya - Portal Dokumen')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Selanjutnya</h1>
			<p class="page-subtitle">Portal Pengelolaan Dokumen SPPD - {{ $sppd->user->name }}</p>
		</div>
		<a href="{{ route('sppd.index') }}" class="btn-secondary">← Kembali ke Daftar</a>
	</div>

	<div class="space-y-12 max-w-6xl mx-auto">
		{{-- Dokumen Sebelum Perjalanan --}}
		<section>
			<div class="flex items-center gap-4 mb-8">
				<h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] whitespace-nowrap">Dokumen Sebelum Perjalanan</h3>
				<div class="h-px w-full bg-gradient-to-r from-slate-200 to-transparent"></div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
				{{-- SPPD --}}
				<a href="{{ route('sppd.manage-sppd', $sppd) }}" class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
					<div class="flex items-start gap-5">
						<div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-orange-100 group-hover:scale-110 transition-transform duration-300">
							<svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
							</svg>
						</div>
						<div class="flex-1">
							<p class="font-bold text-slate-800 text-sm leading-snug group-hover:text-primary-600 transition-colors uppercase tracking-wide">Surat Perintah Perjalanan Dinas</p>
							<p class="text-[10px] text-slate-400 mt-2 font-medium">Kelola & Cetak Dokumen SPPD</p>
						</div>
					</div>
					<div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
						<svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
					</div>
				</a>

				{{-- SPT --}}
				<a href="{{ route('sppd.manage-spt', $sppd) }}" class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
					<div class="flex items-start gap-5">
						<div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-orange-100 group-hover:scale-110 transition-transform duration-300">
							<svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
							</svg>
						</div>
						<div class="flex-1">
							<p class="font-bold text-slate-800 text-sm leading-snug group-hover:text-primary-600 transition-colors uppercase tracking-wide">Surat Perintah Tugas</p>
							<p class="text-[10px] text-slate-400 mt-2 font-medium">Kelola & Cetak Dokumen SPT</p>
						</div>
					</div>
					<div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
						<svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
					</div>
				</a>

				{{-- Kuitansi --}}
				<a href="{{ route('sppd.receipts', $sppd) }}" class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
					<div class="flex items-start gap-5">
						<div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-orange-100 group-hover:scale-110 transition-transform duration-300">
							<svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
							</svg>
						</div>
						<div class="flex-1">
							<p class="font-bold text-slate-800 text-sm leading-snug group-hover:text-primary-600 transition-colors uppercase tracking-wide">Kuitansi</p>
							<p class="text-[10px] text-slate-400 mt-2 font-medium">Input Panjar & Cetak Kuitansi Final</p>
						</div>
					</div>
					<div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
						<svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
					</div>
				</a>
			</div>
		</section>

		{{-- Dokumen Sesudah Perjalanan --}}
		<section>
			<div class="flex items-center gap-4 mb-8">
				<h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] whitespace-nowrap">Dokumen Sesudah Perjalanan</h3>
				<div class="h-px w-full bg-gradient-to-r from-slate-200 to-transparent"></div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
				{{-- Laporan Pengeluaran Rill --}}
				<a href="{{ route('sppd.actual-expenses', $sppd) }}" class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
					<div class="flex items-start gap-5">
						<div class="w-14 h-14 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary-100 group-hover:scale-110 transition-transform duration-300">
							<svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
							</svg>
						</div>
						<div class="flex-1">
							<p class="font-bold text-slate-800 text-sm leading-snug group-hover:text-primary-600 transition-colors uppercase tracking-wide">Laporan Pengeluaran Rill</p>
							<p class="text-[10px] text-slate-400 mt-2 font-medium">Input Biaya Aktual Tanpa Bukti</p>
						</div>
					</div>
					<div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
						<svg class="w-4 h-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
					</div>
				</a>

				{{-- Rincian Biaya --}}
				<a href="{{ route('sppd.final-costs', $sppd) }}" class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
					<div class="flex items-start gap-5">
						<div class="w-14 h-14 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary-100 group-hover:scale-110 transition-transform duration-300">
							<svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
							</svg>
						</div>
						<div class="flex-1">
							<p class="font-bold text-slate-800 text-sm leading-snug group-hover:text-primary-600 transition-colors uppercase tracking-wide">Rincian Biaya Perjalanan Dinas</p>
							<p class="text-[10px] text-slate-400 mt-2 font-medium">Input Detail Seluruh Pengeluaran</p>
						</div>
					</div>
					<div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
						<svg class="w-4 h-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
					</div>
				</a>

				{{-- Laporan Perjalanan --}}
				<a href="{{ route('sppd.report-input', $sppd) }}" class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
					<div class="flex items-start gap-5">
						<div class="w-14 h-14 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary-100 group-hover:scale-110 transition-transform duration-300">
							<svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
							</svg>
						</div>
						<div class="flex-1">
							<p class="font-bold text-slate-800 text-sm leading-snug group-hover:text-primary-600 transition-colors uppercase tracking-wide">Laporan Perjalanan</p>
							<p class="text-[10px] text-slate-400 mt-2 font-medium">Input Narasi Hasil Kegiatan</p>
						</div>
					</div>
					<div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
						<svg class="w-4 h-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
					</div>
				</a>
			</div>
		</section>
	</div>
@endsection
