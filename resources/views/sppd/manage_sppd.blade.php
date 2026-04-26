@extends('layouts.app')
@section('title', 'Kelola SPPD')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1 uppercase">SPPD</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	<div class="card p-6 border-slate-200">
		<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
				<div class="space-y-2">
					{{-- Main Pelaksana --}}
					<div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-100 rounded-lg group">
						<div class="flex items-center gap-3">
							<span class="text-xs font-bold text-slate-400 uppercase w-20">Pelaksana :</span>
							<span class="text-sm font-bold text-slate-800 uppercase">{{ $sppd->user->name }}</span>
						</div>
						<a href="{{ route('sppd.stream.sppd', $sppd) }}" target="_blank"
							class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-1.5 rounded text-[10px] font-bold uppercase transition-all flex items-center gap-2">
							<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
							</svg>
							Cetak SPPD
						</a>
					</div>

					{{-- Followers --}}
					@foreach ($sppd->followers as $f)
						<div class="flex items-center justify-between p-3 bg-white border border-slate-100 rounded-lg group">
							<div class="flex items-center gap-3">
								<span class="text-xs font-bold text-slate-400 uppercase w-20">Pengikut :</span>
								<span class="text-sm font-semibold text-slate-700 uppercase">{{ $f->user->name }}</span>
							</div>
							<a href="{{ route('sppd.stream.sppd', ['sppd' => $sppd->id, 'user_id' => $f->user_id]) }}" target="_blank"
								class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-1.5 rounded text-[10px] font-bold uppercase transition-all flex items-center gap-2">
								<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
									<path stroke-linecap="round" stroke-linejoin="round"
										d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
								</svg>
								Cetak SPPD
							</a>
						</div>
					@endforeach
				</div>
			</div>

			<div class="space-y-4 flex flex-col items-end">
				<div class="flex items-center gap-4 w-full justify-end">
					<span class="text-sm font-medium text-slate-500">Tanggal SPPD</span>
					<span class="text-sm font-bold text-slate-800">:
						{{ $sppd->sppd_date?->format('d-m-Y') ?? $sppd->created_at->format('d-m-Y') }}</span>
				</div>

				<form action="{{ route('sppd.reset-tte', ['sppd' => $sppd->id, 'type' => 'sppd']) }}" method="POST">
					@csrf
					<button type="submit"
						class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold transition-colors shadow-sm">
						Reset TTE
					</button>
				</form>
			</div>
		</div>

		{{-- Bottom Info Section (Modernized) --}}
		<div class="mt-8 pt-6 border-t border-slate-100 flex items-center gap-4 text-xs text-slate-400 italic">
			<svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
					d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
			</svg>
			Sistem akan menghasilkan dokumen PDF yang sudah siap dicetak atau ditandatangani secara elektronik.
		</div>
	</div>
@endsection
