@extends('layouts.app')
@section('title', 'Laporan Perjalanan')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1 uppercase">LAPORAN HASIL PERJALANAN DINAS</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	<div class="card p-6 border-slate-200">
		<div class="mb-6">
			<p class="text-sm font-medium text-slate-700">Pelaksana : <span class="font-bold uppercase">{{ $sppd->user->name }}</span></p>
			<p class="text-xs text-slate-500">Maksud Perjalanan : {{ $sppd->purpose }}</p>
		</div>

		<form action="#" method="POST">
			@csrf
			<div class="space-y-4">
				<div>
					<label class="form-label font-bold text-slate-700">Hasil Perjalanan / Laporan Narasi</label>
					<textarea name="content" class="form-textarea" rows="15" placeholder="Masukkan detail hasil perjalanan dinas secara lengkap...">{{ $sppd->report->content ?? '' }}</textarea>
				</div>
				<div class="flex justify-end gap-3">
					<button type="submit" class="btn-primary px-8">Simpan Laporan</button>
					<button type="button" class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-slate-700 px-6 py-2 rounded font-semibold transition-colors flex items-center gap-2">
						<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
						Cetak Laporan
					</button>
				</div>
			</div>
		</form>
	</div>
@endsection
