@extends('layouts.app')
@section('title', 'Laporan Pengeluaran Rill')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1">LAPORAN PENGELUARAN RILL</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	<div class="card p-4 mb-4 border-slate-200">
		<div class="flex items-center justify-between mb-4">
			<p class="text-sm font-medium text-slate-700">Pelaksana : <span class="font-bold uppercase">{{ $sppd->user->name }}</span></p>
			<button class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-slate-700 px-4 py-1 rounded text-sm font-semibold transition-colors flex items-center gap-1">
				<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
				Cetak Data
			</button>
		</div>

		<div class="flex items-center justify-between py-2 border-t border-slate-100 mb-4">
			<p class="text-sm text-slate-600">Penanda Tangan PPTK : <span class="font-bold uppercase">{{ $sppd->pptk->name ?? 'BELUM DIATUR' }}</span></p>
		</div>

		<div class="table-container shadow-none border border-slate-100 rounded-none">
			<table class="w-full text-sm">
				<thead class="bg-slate-50 border-y border-slate-200">
					<tr>
						<th class="py-2 px-4 text-left w-16">No</th>
						<th class="py-2 px-4 text-left">Uraian</th>
						<th class="py-2 px-4 text-right w-48">Tarif</th>
						<th class="py-2 px-4 text-center w-24">#</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					@forelse($sppd->actualExpenses as $i => $expense)
						<tr>
							<td class="py-2 px-4">{{ $i + 1 }}</td>
							<td class="py-2 px-4">{{ $expense->description }}</td>
							<td class="py-2 px-4 text-right font-medium">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
							<td class="py-2 px-4 text-center">
								<button class="text-orange-500 hover:text-orange-600"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="4" class="py-8 text-center text-slate-400 italic">Belum ada data pengeluaran riil</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>

	<div class="p-4 bg-white border border-slate-200 rounded-lg italic text-slate-600 text-sm">
		*Catatan : Untuk mencetak Laporan Pengeluaran Rill Silahkan Input Pejabat Pelaksana Teknis Kegiatan (PPTK)
	</div>
@endsection
