@extends('layouts.app')
@section('title', 'Kuitansi')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1">KUITANSI</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	@php
		$people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
		foreach ($sppd->followers as $f) {
			$people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
		}
		$hasExpenses = $sppd->actualExpenses->count() > 0 || $sppd->costDetails->count() > 0;
	@endphp

	<div class="space-y-4">
		@foreach($people as $person)
			@php
				$receipt = $sppd->advanceReceipts->where('user_id', $person['id'])->first();
			@endphp
			<div class="card p-4 border-slate-200">
				<div class="flex items-center justify-between flex-wrap gap-4">
					<div>
						<p class="text-sm font-medium text-slate-700">{{ $person['label'] }} : <span class="font-bold uppercase">{{ $person['name'] }}</span></p>
						@if($receipt)
							<p class="text-xs text-slate-500 mt-1">No. Kuitansi: {{ $receipt->receipt_number }} — Panjar: <span class="font-bold text-emerald-600">Rp {{ number_format($receipt->amount, 0, ',', '.') }}</span></p>
						@endif
					</div>
					<div class="flex gap-2">
						{{-- Input/Edit Panjar --}}
						<button onclick="openPanjarModal('{{ $person['id'] }}', '{{ $person['name'] }}', '{{ $receipt?->amount ?? 0 }}')"
							class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
							{{ $receipt ? 'Edit Panjar' : 'Input Kuitansi Panjar' }}
						</button>

						{{-- Cetak Kuitansi Rampung --}}
						@if($hasExpenses && $receipt)
							<a href="{{ route('sppd.stream.kuitansi-rampung', ['sppd' => $sppd, 'user_id' => $person['id']]) }}" target="_blank"
								class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
								Cetak Kuitansi Rampung
							</a>
						@else
							<button disabled class="bg-slate-100 border border-slate-300 text-slate-400 px-4 py-1.5 rounded text-sm font-semibold cursor-not-allowed flex items-center gap-1">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
								Cetak Kuitansi Rampung
							</button>
						@endif
					</div>
				</div>
			</div>
		@endforeach
	</div>

	<div class="p-4 bg-white border border-slate-200 rounded-lg italic text-slate-600 text-sm mt-4">
		*Catatan : Untuk mencetak kuitansi rampung Wajib Mengisi data Laporan Pengeluaran Rill dan Rincian Biaya Perjalanan Dinas
	</div>

	{{-- Modal Input Panjar --}}
	<div id="panjarModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
		<div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
			<h3 class="text-lg font-bold text-slate-800 mb-1">Input Kuitansi Panjar</h3>
			<p id="panjarUserName" class="text-sm text-slate-500 mb-4"></p>

			<form id="panjarForm" method="POST" action="{{ route('sppd.advance-receipts.store', $sppd) }}">
				@csrf
				<input type="hidden" name="user_id" id="panjarUserId">

				<div class="mb-4">
					<label class="form-label">Jumlah Panjar (Rp)</label>
					<input type="number" name="amount" id="panjarAmount" class="form-input" min="0" step="1000" required placeholder="0">
				</div>

				<div class="flex justify-end gap-2">
					<button type="button" onclick="closePanjarModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-sm transition-colors">Batal</button>
					<button type="submit" class="btn-primary px-6">Simpan</button>
				</div>
			</form>
		</div>
	</div>
@endsection

@push('scripts')
<script>
	function openPanjarModal(userId, userName, currentAmount) {
		document.getElementById('panjarUserId').value = userId;
		document.getElementById('panjarUserName').textContent = userName;
		document.getElementById('panjarAmount').value = currentAmount > 0 ? currentAmount : '';
		document.getElementById('panjarModal').classList.remove('hidden');
		document.getElementById('panjarModal').classList.add('flex');
	}
	function closePanjarModal() {
		document.getElementById('panjarModal').classList.add('hidden');
		document.getElementById('panjarModal').classList.remove('flex');
	}
</script>
@endpush
