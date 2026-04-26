@extends('layouts.app')
@section('title', 'Kelola SPT')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1 uppercase">SPT</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	<div class="card p-6 border-slate-200">
		<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
			<div class="space-y-4">
				<div class="p-4 bg-rose-50 border border-rose-100 rounded-lg">
					<p class="text-rose-600 text-xs italic font-medium leading-relaxed">
						Catatan :<br>
						Klik Tombol "Reset TTE" jika file SPT terdapat kesalahan (Tidak dapat didownload atau tidak terdapat barcode pada SPT)
					</p>
				</div>

				<div class="flex items-center gap-4 py-3 border-b border-slate-100">
					<span class="text-sm font-medium text-slate-500 w-32">Pelaksana</span>
					<span class="text-sm font-bold text-slate-800 uppercase">: {{ $sppd->user->name }}</span>
				</div>
			</div>

			<div class="space-y-4 flex flex-col items-end">
				<div class="flex items-center gap-4 w-full justify-end">
					<span class="text-sm font-medium text-slate-500">Tanggal SPT</span>
					<span class="text-sm font-bold text-slate-800">: {{ $sppd->spt_date?->format('d-m-Y') ?? $sppd->created_at->format('d-m-Y') }}</span>
				</div>

				<form action="{{ route('sppd.reset-tte', ['sppd' => $sppd->id, 'type' => 'spt']) }}" method="POST">
					@csrf
					<button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold transition-colors">
						Reset TTE
					</button>
				</form>
			</div>
		</div>

		<div class="flex justify-center border-t border-slate-100 pt-6">
			<a href="{{ route('sppd.stream.spt', $sppd) }}" target="_blank" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded text-sm font-bold transition-colors flex items-center gap-2 shadow-md">
				<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
				CETAK SPT
			</a>
		</div>
	</div>
@endsection
