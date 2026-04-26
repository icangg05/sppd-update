@extends('layouts.app')
@section('title', 'Kuitansi')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1">KUITANSI</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	<div class="card p-4 mb-4 border-slate-200">
		<div class="flex items-center justify-between flex-wrap gap-4">
			<div>
				<p class="text-sm font-medium text-slate-700">Pelaksana : <span class="font-bold uppercase">{{ $sppd->user->name }}</span></p>
			</div>
			<div class="flex gap-2">
				<button class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
					Input Kuitansi Panjar
				</button>
				<button class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-slate-700 px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
					Cetak Kuitansi Rampung
				</button>
			</div>
		</div>
	</div>

	<div class="p-4 bg-white border border-slate-200 rounded-lg italic text-slate-600 text-sm">
		*Catatan : Untuk mencetak kuitansi rampung Wajib Mengisi data Laporan Pengeluaran Rill dan Rincian Biaya Perjalanan Dinas
	</div>
@endsection
