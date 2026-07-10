@extends('layouts.app')
@section('title', 'Kuitansi')

@section('content')
	@php
		$people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
		foreach ($sppd->followers as $f) {
		    $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
		}
		$hasActualExpenses = $sppd->actualExpenses->count() > 0;
		$hasCostDetails = $sppd->costDetails->count() > 0;
		$hasExpenses = $hasActualExpenses && $hasCostDetails;
		$hasBendahara = $bendahara !== null;
	@endphp

	<div class="p-1 space-y-6" x-data="{
		printDate: '{{ date('Y-m-d') }}',
		showBulkModal: false,
		bulkAmount: '',
		amounts: {
			@foreach($people as $person)
				@php
					$receipt = $sppd->advanceReceipts->where('user_id', $person['id'])->first();
				@endphp
				'{{ $person['id'] }}': '{{ $receipt?->amount ?? '' }}',
			@endforeach
		},
		applyBulk() {
			for (let id in this.amounts) {
				if (this.amounts[id] === '' || this.amounts[id] === null || this.amounts[id] == 0) {
					this.amounts[id] = this.bulkAmount;
				}
			}
			this.showBulkModal = false;
			this.bulkAmount = '';
		}
	}">

		{{-- Header --}}
		<div class="flex items-center justify-between">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
					<i class="fa-solid fa-file-invoice-dollar mr-2 text-emerald-600"></i>Kuitansi Perjalanan
				</h1>
			</div>
			<a wire:navigate href="{{ route('sppd.next', $sppd) }}"
				class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
				<i class="fa-solid fa-arrow-left"></i> Kembali
			</a>
		</div>

		{{-- Alert Bendahara --}}
		@if (!$hasBendahara)
			<div class="rounded border-l-4 border-amber-500 bg-amber-50 p-4 text-amber-800 text-xs font-medium">
				<p class="font-bold uppercase"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Bendahara Belum Ditetapkan</p>
				<p class="mt-1">Instansi <strong>{{ $sppd->user->department->name ?? '-' }}</strong> memerlukan pegawai dengan
					jabatan "Bendahara Pengeluaran" untuk mencetak kuitansi.</p>
			</div>
		@endif

		{{-- Pengaturan Tanggal Cetak & Panjar Massal --}}
		<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded border border-slate-200 shadow-sm">
			<div class="flex flex-col sm:flex-row sm:items-center gap-4">
				<div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
					<i class="fa-solid fa-calendar-day text-emerald-600 text-sm"></i>
					<span>Pilih Tanggal Cetak Kuitansi:</span>
				</div>
				<div class="w-full sm:w-48">
					<input type="date" x-model="printDate"
						class="w-full rounded border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:ring-emerald-500 shadow-sm" />
				</div>
			</div>
			@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
			<div class="flex items-center gap-2">
				<button type="button" @click="showBulkModal = true"
					class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 transition shadow-sm">
					<i class="fa-solid fa-layer-group text-slate-500"></i>
					Input Panjar Massal
				</button>
			</div>
			@endif
		</div>

		{{-- Daftar Personel (Bulk Form) --}}
		<form method="POST" action="{{ route('sppd.advance-receipts.store', $sppd) }}">
			@csrf
			<div class="overflow-x-auto rounded border border-slate-200 bg-white shadow-sm">
				<table class="w-full text-left border-collapse">
					<thead>
						<tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
							<th class="py-3 px-4 text-center w-12">No.</th>
							<th class="py-3 px-4">Pegawai</th>
							<th class="py-3 px-4 w-56">Nominal Kuitansi Panjar (Rp)</th>
							<th class="py-3 px-4 text-right w-64">Aksi Cetak</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100 text-xs">
						@foreach ($people as $index => $person)
							@php
								$receipt = $sppd->advanceReceipts->where('user_id', $person['id'])->first();
								$canPrint = $hasExpenses && $hasBendahara;
							@endphp
							<tr class="hover:bg-slate-50 transition-colors">
								<td class="py-3 px-4 text-center font-semibold text-slate-500">
									{{ $index + 1 }}.
								</td>
								<td class="py-3 px-4">
									<div class="flex items-center gap-2">
										@if ($person['label'] === 'Pelaksana')
											<span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
												Pelaksana
											</span>
										@else
											<span class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
												Pengikut
											</span>
										@endif
										<span class="font-bold text-slate-800 uppercase">{{ $person['name'] }}</span>
									</div>
								</td>
								<td class="py-3 px-4">
									<div class="flex flex-col gap-1">
										<input type="number" name="amounts[{{ $person['id'] }}]" x-model="amounts['{{ $person['id'] }}']"
											{{ !auth()->user()->hasAnyRole(['admin_opd', 'super_admin']) ? 'disabled bg-slate-50 border-slate-200 text-slate-500 cursor-not-allowed' : '' }}
											class="w-full max-w-[180px] rounded border border-slate-300 px-2 py-1 text-xs text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 shadow-sm"
											placeholder="Input Nominal Panjar">
										@if ($receipt && $receipt->amount > 0)
											<span class="text-[9px] text-slate-500 font-mono pl-1">
												No: {{ $receipt->receipt_number }}
											</span>
										@endif
									</div>
								</td>
								<td class="py-3 px-4 text-right">
									<div class="flex items-center justify-end gap-1.5">
										{{-- Cetak Panjar --}}
										@if ($receipt && $receipt->amount > 0 && $hasBendahara)
											<a :href="'{{ route('sppd.stream.kuitansi-panjar', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate"
												target="_blank"
												class="text-nowrap inline-flex items-center gap-1 rounded bg-amber-600 px-2.5 py-1.5 text-[10px] font-bold text-white hover:bg-amber-700 shadow-sm transition">
												<i class="fa-solid fa-print"></i> Cetak Panjar
											</a>
										@endif

										{{-- Cetak Rampung --}}
										@if ($canPrint)
											<a :href="'{{ route('sppd.stream.kuitansi-rampung', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate"
												target="_blank"
												class="text-nowrap inline-flex items-center gap-1 rounded bg-primary-600 px-2.5 py-1.5 text-[10px] font-bold text-white hover:bg-primary-700 shadow-sm transition">
												<i class="fa-solid fa-file-invoice"></i> Cetak Rampung
											</a>
										@else
											<button type="button" disabled
												class="text-nowrap inline-flex items-center gap-1 rounded bg-slate-100 px-2.5 py-1.5 text-[10px] font-bold text-slate-500 cursor-not-allowed border border-slate-200">
												<i class="fa-solid fa-lock"></i> Cetak Rampung
											</button>
										@endif
									</div>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
			<div class="mt-4 flex flex-col sm:flex-row gap-4 items-center justify-between bg-slate-50 p-3 rounded border border-slate-200">
				<span class="text-[11px] text-slate-500 font-medium">
					<i class="fa-solid fa-circle-info text-slate-500 mr-1"></i>
					Kosongkan nominal panjar untuk menghapus data kuitansi panjar pegawai tersebut.
				</span>
				<button type="submit"
					class="inline-flex items-center gap-1.5 rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 shadow transition w-full sm:w-auto justify-center">
					<i class="fa-solid fa-floppy-disk"></i> Simpan Semua Panjar
				</button>
			</div>
			@endif
		</form>

		<div class="flex items-start gap-4 rounded border border-primary-200 bg-primary-50 p-4 shadow-sm">
			<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
				<i class="fa-solid fa-circle-info text-lg"></i>
			</div>
			<div>
				<h4 class="text-xs font-bold uppercase text-primary-900">Informasi Penting</h4>
				<p class="mt-1 text-[11px] font-medium text-primary-800 leading-relaxed">
					Untuk dapat mencetak <strong>Kuitansi Rampung</strong>, pastikan seluruh data berikut telah dilengkapi:
				</p>
				<ul class="mt-2 flex flex-wrap gap-2 text-[11px]">
					<li class="flex items-center gap-1.5 {{ $hasActualExpenses ? 'text-emerald-700' : 'text-rose-600' }}">
						<i class="fa-solid {{ $hasActualExpenses ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-400' }}"></i>
						Laporan Pengeluaran Rill
					</li>
					<li class="flex items-center gap-1.5 {{ $hasCostDetails ? 'text-emerald-700' : 'text-rose-600' }}">
						<i class="fa-solid {{ $hasCostDetails ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-400' }}"></i>
						Rincian Biaya Perjalanan
					</li>
					<li class="flex items-center gap-1.5 {{ $hasBendahara ? 'text-emerald-700' : 'text-rose-600' }}">
						<i class="fa-solid {{ $hasBendahara ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-400' }}"></i>
						Bendahara Pengeluaran Aktif
					</li>
				</ul>
			</div>
		</div>

		{{-- Modal Input Panjar Sekaligus --}}
		<x-ui.modal show="showBulkModal" title="Input Panjar Sekaligus" icon="fa-solid fa-layer-group text-emerald-600">
			<div class="space-y-4">
				<div class="rounded bg-slate-50 p-3 border border-slate-200 text-xs text-slate-600 leading-relaxed">
					<p class="font-bold uppercase mb-1">
						<i class="fa-solid fa-circle-info mr-1 text-slate-500"></i> Aturan Penerapan:
					</p>
					Nominal ini akan diterapkan ke **semua pegawai**. Pegawai yang nominal panjarnya **sudah terisi** akan otomatis dilewati (skip).
				</div>

				<div>
					<label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Nominal Panjar (Rp)</label>
					<input type="number" x-model="bulkAmount"
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
						placeholder="Contoh: 1500000" required>
				</div>

				<div class="flex justify-end gap-2 pt-2">
					<button type="button" @click="showBulkModal = false; bulkAmount = ''"
						class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
						Batal
					</button>
					<button type="button" @click="applyBulk"
						class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition">
						Terapkan Nominal
					</button>
				</div>
			</div>
		</x-ui.modal>
	</div>
@endsection
