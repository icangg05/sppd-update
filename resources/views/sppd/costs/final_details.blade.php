@extends('layouts.app')
@section('title', 'Rincian Biaya Perjalanan Dinas')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1 uppercase">LAPORAN RINCIAN BIAYA PERJALANAN DINAS</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	<div class="card p-0 mb-4 border-slate-200 overflow-hidden">
		<div class="p-4 flex items-center justify-between bg-slate-50 border-b border-slate-200">
			<p class="text-sm font-medium text-slate-700">Pelaksana : <span class="font-bold uppercase">{{ $sppd->user->name }}</span></p>
			<div class="flex gap-2">
				<button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
					Tambah Biaya
				</button>
				<button class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
					Cetak Data
				</button>
			</div>
		</div>

		<div class="table-container shadow-none">
			<table class="w-full text-sm">
				<thead class="bg-slate-50 border-b border-slate-200">
					<tr>
						<th class="py-3 px-4 text-left w-12">No</th>
						<th class="py-3 px-4 text-left">Kategori Biaya</th>
						<th class="py-3 px-4 text-left">Keterangan</th>
						<th class="py-3 px-4 text-center w-20">Item</th>
						<th class="py-3 px-4 text-right w-40">Tarif</th>
						<th class="py-3 px-4 text-right w-40">Total</th>
						<th class="py-3 px-4 text-center w-32">Foto</th>
						<th class="py-3 px-4 text-center w-24">#</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					@forelse($sppd->costDetails as $i => $cost)
						<tr>
							<td class="py-3 px-4">{{ $i + 1 }}</td>
							<td class="py-3 px-4"><span class="bg-slate-100 px-2 py-0.5 rounded text-xs">Biaya Lainnya</span></td>
							<td class="py-3 px-4">{{ $cost->description }}</td>
							<td class="py-3 px-4 text-center">{{ $cost->quantity }}</td>
							<td class="py-3 px-4 text-right">Rp {{ number_format($cost->unit_cost, 0, ',', '.') }}</td>
							<td class="py-3 px-4 text-right font-bold">Rp {{ number_format($cost->unit_cost * $cost->quantity, 0, ',', '.') }}</td>
							<td class="py-3 px-4 text-center">
								<div class="w-24 h-16 bg-slate-100 border border-slate-200 rounded mx-auto flex items-center justify-center overflow-hidden">
									<svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
								</div>
							</td>
							<td class="py-3 px-4 text-center">
								<div class="flex flex-col gap-1">
									<button class="bg-orange-400 hover:bg-orange-500 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase transition-colors flex items-center justify-center gap-1">
										<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
										Edit
									</button>
									<button class="bg-rose-500 hover:bg-rose-600 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase transition-colors flex items-center justify-center gap-1">
										<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
										Hapus
									</button>
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="8" class="py-12 text-center text-slate-400 italic">Belum ada data rincian biaya perjalanan dinas</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
@endsection
