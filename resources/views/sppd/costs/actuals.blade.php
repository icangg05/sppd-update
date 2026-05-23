@extends('layouts.app')
@section('title', 'Laporan Pengeluaran Rill')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1">LAPORAN PENGELUARAN RILL</h1>
		</div>
		<x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="danger">Kembali</x-ui.button>
	</div>

	<div class="card p-4 mb-4 border-slate-200">
		<div class="flex items-center justify-between flex-wrap gap-4">
			<div class="flex items-center gap-3">
				<div>
					<p class="text-sm font-medium text-slate-600">Pejabat Pelaksana Teknis Kegiatan (PPTK)</p>
					@if ($sppd->pptk_id)
						<p class="text-sm font-bold text-slate-800 mt-0.5">{{ $sppd->pptk->name }}
							{{ $sppd->pptk->nip ? '— NIP. ' . $sppd->pptk->nip : '' }}</p>
					@else
						<p class="text-sm text-amber-600 font-semibold mt-0.5">⚠ Belum diatur — cetak laporan tidak tersedia</p>
					@endif
				</div>
			</div>
			<form action="{{ route('sppd.update-pptk', $sppd) }}" method="POST" class="flex items-center gap-2">
				@csrf @method('PUT')
				<x-form.select
					name="pptk_id"
					label=""
					class="text-sm py-1.5 min-w-55"
					required
				>
					<option value="">-- Pilih PPTK --</option>
					@foreach ($pptkCandidates as $candidate)
						<option value="{{ $candidate->id }}">
							{{ $candidate->name }}{{ $candidate->nip ? ' (' . $candidate->nip . ')' : '' }}
						</option>
					@endforeach
				</x-form.select>
				<x-ui.button type="submit">Simpan</x-ui.button>
			</form>
		</div>
	</div>


	@php
		$people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
		foreach ($sppd->followers as $f) {
		    $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
		}
	@endphp

	@foreach ($people as $person)
		@php
			$expenses = $sppd->actualExpenses->where('user_id', $person['id']);
			$total = $expenses->sum('amount');
		@endphp
		<div class="card p-0 mb-4 border-slate-200 overflow-hidden">
			<div class="p-4 flex items-center justify-between bg-slate-50 border-b border-slate-200">
				<div>
					<p class="text-sm font-medium text-slate-700">{{ $person['label'] }} : <span
							class="font-bold uppercase">{{ $person['name'] }}</span></p>
				</div>
				<div class="flex gap-2">
					<x-ui.button type="button" onclick="openExpenseModal('{{ $person['id'] }}', '{{ $person['name'] }}')" class="flex items-center gap-1">
						<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
						</svg>
						Tambah Data
					</x-ui.button>
					@if ($expenses->count() > 0 && $sppd->pptk_id)
						<a href="{{ route('sppd.stream.pengeluaran-riil', ['sppd' => $sppd, 'user_id' => $person['id']]) }}"
							target="_blank"
							class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-slate-700 px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
							</svg>
							Cetak Data
						</a>
					@elseif($expenses->count() > 0 && !$sppd->pptk_id)
						<button disabled title="PPTK harus diatur terlebih dahulu sebelum mencetak"
							class="bg-slate-100 border border-slate-300 text-slate-400 px-4 py-1.5 rounded text-sm font-semibold cursor-not-allowed flex items-center gap-1 opacity-60">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
							</svg>
							Cetak Data
						</button>
					@endif
				</div>
			</div>

			<div class="table-container shadow-none">
				<table class="w-full text-sm">
					<thead class="bg-slate-50 border-b border-slate-200">
						<tr>
							<th class="py-2 px-4 text-left w-16">No</th>
							<th class="py-2 px-4 text-left">Uraian</th>
							<th class="py-2 px-4 text-right w-48">Tarif</th>
							<th class="py-2 px-4 text-center w-32">#</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						@forelse($expenses as $i => $expense)
							<tr>
								<td class="py-2 px-4">{{ $loop->iteration }}</td>
								<td class="py-2 px-4">{{ $expense->description }}</td>
								<td class="py-2 px-4 text-right font-medium">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
								<td class="py-2 px-4 text-center">
									<div class="flex justify-center gap-1">
										<button
											onclick="openEditExpenseModal('{{ $expense->id }}', '{{ $expense->description }}', '{{ $expense->amount }}')"
											class="bg-orange-400 hover:bg-orange-500 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase transition-colors">Edit</button>
										<form action="{{ route('sppd.actual-expenses.destroy', [$sppd, $expense]) }}" method="POST"
											onsubmit="return confirm('Hapus data ini?')">
											@csrf @method('DELETE')
											<button type="submit"
												class="bg-rose-500 hover:bg-rose-600 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase transition-colors">Hapus</button>
										</form>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="4" class="py-8 text-center text-slate-400 italic">Belum ada data pengeluaran riil</td>
							</tr>
						@endforelse
					</tbody>
					@if ($total > 0)
						<tfoot class="bg-slate-50 border-t border-slate-200">
							<tr>
								<td colspan="2" class="py-2 px-4 font-bold text-right">Total</td>
								<td class="py-2 px-4 text-right font-bold text-primary-600">Rp {{ number_format($total, 0, ',', '.') }}</td>
								<td></td>
							</tr>
						</tfoot>
					@endif
				</table>
			</div>
		</div>
	@endforeach

	<div class="p-4 bg-white border border-slate-200 rounded-lg italic text-slate-600 text-sm">
		*Catatan : Untuk mencetak Laporan Pengeluaran Rill Silahkan Input Pejabat Pelaksana Teknis Kegiatan (PPTK)
	</div>

	{{-- Modal Tambah --}}
	<div id="expenseModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
		<div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
			<h3 class="text-lg font-bold text-slate-800 mb-1">Tambah Pengeluaran Riil</h3>
			<p id="expenseUserName" class="text-sm text-slate-500 mb-4"></p>
			<form method="POST" action="{{ route('sppd.actual-expenses.store', $sppd) }}">
				@csrf
				<input type="hidden" name="user_id" id="expenseUserId">
				<div class="mb-4">
					<x-form.input
						name="description"
						label="Uraian"
						placeholder="Contoh: Tiket pesawat pp"
						required
					/>
				</div>
				<div class="mb-4">
					<x-form.input
						type="number"
						name="amount"
						label="Tarif (Rp)"
						min="0"
						:step="1000"
						placeholder="0"
						required
					/>
				</div>
				<div class="flex justify-end gap-2">
					<x-ui.button type="button" variant="secondary" onclick="closeExpenseModal()">Batal</x-ui.button>
					<x-ui.button type="submit">Simpan</x-ui.button>
				</div>
			</form>
		</div>
	</div>

	{{-- Modal Edit --}}
	<div id="editExpenseModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
		<div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
			<h3 class="text-lg font-bold text-slate-800 mb-4">Edit Pengeluaran Riil</h3>
			<form id="editExpenseForm" method="POST">
				@csrf @method('PUT')
				<div class="mb-4">
					<x-form.input
						name="description"
						id="editExpenseDesc"
						label="Uraian"
						required
					/>
				</div>
				<div class="mb-4">
					<x-form.input
						type="number"
						name="amount"
						id="editExpenseAmount"
						label="Tarif (Rp)"
						min="0"
						:step="1000"
						required
					/>
				</div>
				<div class="flex justify-end gap-2">
					<x-ui.button type="button" variant="secondary" onclick="closeEditExpenseModal()">Batal</x-ui.button>
					<x-ui.button type="submit">Simpan</x-ui.button>
				</div>
			</form>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		function openExpenseModal(userId, userName) {
			document.getElementById('expenseUserId').value = userId;
			document.getElementById('expenseUserName').textContent = userName;
			document.getElementById('expenseModal').classList.remove('hidden');
			document.getElementById('expenseModal').classList.add('flex');
		}

		function closeExpenseModal() {
			document.getElementById('expenseModal').classList.add('hidden');
			document.getElementById('expenseModal').classList.remove('flex');
		}

		function openEditExpenseModal(expenseId, desc, amount) {
			document.getElementById('editExpenseDesc').value = desc;
			document.getElementById('editExpenseAmount').value = amount;
			document.getElementById('editExpenseForm').action = '{{ url('sppd/' . $sppd->id . '/actual-expenses') }}/' +
				expenseId;
			document.getElementById('editExpenseModal').classList.remove('hidden');
			document.getElementById('editExpenseModal').classList.add('flex');
		}

		function closeEditExpenseModal() {
			document.getElementById('editExpenseModal').classList.add('hidden');
			document.getElementById('editExpenseModal').classList.remove('flex');
		}
	</script>
@endpush
