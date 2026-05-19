@extends('layouts.app')
@section('title', 'Rincian Biaya Perjalanan Dinas')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title text-green-600 border-b-2 border-green-600 w-fit pb-1 uppercase">LAPORAN RINCIAN BIAYA PERJALANAN DINAS</h1>
		</div>
		<a href="{{ route('sppd.next', $sppd) }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1 rounded text-sm transition-colors">Kembali</a>
	</div>

	@php
		$people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
		foreach ($sppd->followers as $f) {
			$people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
		}
		$categories = \App\Enums\CostCategory::cases();
	@endphp

	@foreach($people as $person)
		@php
			$costs = $sppd->costDetails->where('user_id', $person['id']);
			$grandTotal = $costs->sum('total');
		@endphp
		<div class="card p-0 mb-4 border-slate-200 overflow-hidden">
			<div class="p-4 flex items-center justify-between bg-slate-50 border-b border-slate-200">
				<p class="text-sm font-medium text-slate-700">{{ $person['label'] }} : <span class="font-bold uppercase">{{ $person['name'] }}</span></p>
				<div class="flex gap-2">
					<button onclick="openCostModal('{{ $person['id'] }}', '{{ $person['name'] }}')"
						class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-1.5 rounded text-sm font-semibold transition-colors flex items-center gap-1">
						<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
						Tambah Biaya
					</button>
				</div>
			</div>

			<div class="table-container shadow-none">
				<table class="w-full text-sm">
					<thead class="bg-slate-50 border-b border-slate-200">
						<tr>
							<th class="py-3 px-4 text-left w-12">No</th>
							<th class="py-3 px-4 text-left">Kategori</th>
							<th class="py-3 px-4 text-left">Keterangan</th>
							<th class="py-3 px-4 text-center w-20">Item</th>
							<th class="py-3 px-4 text-right w-40">Tarif</th>
							<th class="py-3 px-4 text-right w-40">Total</th>
							<th class="py-3 px-4 text-center w-24">Foto</th>
							<th class="py-3 px-4 text-center w-24">#</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						@forelse($costs as $cost)
							<tr>
								<td class="py-3 px-4">{{ $loop->iteration }}</td>
								<td class="py-3 px-4"><span class="bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $cost->cost_category->label() }}</span></td>
								<td class="py-3 px-4">
									{{ $cost->description }}
									@if($cost->airline_name)
										<br><span class="text-xs text-slate-400">Maskapai: {{ $cost->airline_name }}</span>
									@endif
									@if($cost->ticket_number)
										<span class="text-xs text-slate-400">| Tiket: {{ $cost->ticket_number }}</span>
									@endif
								</td>
								<td class="py-3 px-4 text-center">{{ $cost->quantity }}</td>
								<td class="py-3 px-4 text-right">Rp {{ number_format($cost->unit_cost, 0, ',', '.') }}</td>
								<td class="py-3 px-4 text-right font-bold">Rp {{ number_format($cost->total, 0, ',', '.') }}</td>
								<td class="py-3 px-4 text-center">
									@if($cost->receipt_photo)
										<a href="{{ asset('storage/' . $cost->receipt_photo) }}" target="_blank">
											<img src="{{ asset('storage/' . $cost->receipt_photo) }}" class="w-16 h-12 object-cover rounded border mx-auto" alt="Bukti">
										</a>
									@else
										<div class="w-16 h-12 bg-slate-100 border border-slate-200 rounded mx-auto flex items-center justify-center">
											<svg class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
										</div>
									@endif
								</td>
								<td class="py-3 px-4 text-center">
									<div class="flex flex-col gap-1">
										<button onclick='openEditCostModal(@json($cost))' class="bg-orange-400 hover:bg-orange-500 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase transition-colors">Edit</button>
										<form action="{{ route('sppd.cost-details.destroy', [$sppd, $cost]) }}" method="POST" onsubmit="return confirm('Hapus data ini?')">
											@csrf @method('DELETE')
											<button type="submit" class="bg-rose-500 hover:bg-rose-600 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase transition-colors w-full">Hapus</button>
										</form>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="8" class="py-12 text-center text-slate-400 italic">Belum ada data rincian biaya perjalanan dinas</td>
							</tr>
						@endforelse
					</tbody>
					@if($grandTotal > 0)
					<tfoot class="bg-slate-50 border-t border-slate-200">
						<tr>
							<td colspan="5" class="py-3 px-4 font-bold text-right">Grand Total</td>
							<td class="py-3 px-4 text-right font-bold text-primary-600">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
							<td colspan="2"></td>
						</tr>
					</tfoot>
					@endif
				</table>
			</div>
		</div>
	@endforeach

	{{-- Modal Tambah Biaya --}}
	<div id="costModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
		<div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
			<h3 class="text-lg font-bold text-slate-800 mb-1">Tambah Rincian Biaya</h3>
			<p id="costUserName" class="text-sm text-slate-500 mb-4"></p>
			<form method="POST" action="{{ route('sppd.cost-details.store', $sppd) }}" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="user_id" id="costUserId">
				<div class="grid grid-cols-2 gap-4 mb-4">
					<div class="col-span-2">
						<label class="form-label">Kategori Biaya</label>
						<select name="cost_category" class="form-select" required>
							@foreach($categories as $cat)
								<option value="{{ $cat->value }}">{{ $cat->label() }}</option>
							@endforeach
						</select>
					</div>
					<div class="col-span-2">
						<label class="form-label">Keterangan</label>
						<input type="text" name="description" class="form-input" required placeholder="Keterangan biaya">
					</div>
					<div>
						<label class="form-label">Nama Maskapai <span class="text-slate-400">(opsional)</span></label>
						<input type="text" name="airline_name" class="form-input" placeholder="Garuda, Lion Air, dll">
					</div>
					<div>
						<label class="form-label">No. Tiket <span class="text-slate-400">(opsional)</span></label>
						<input type="text" name="ticket_number" class="form-input" placeholder="Nomor tiket">
					</div>
					<div>
						<label class="form-label">Tarif Satuan (Rp)</label>
						<input type="number" name="unit_cost" class="form-input" min="0" step="1000" required placeholder="0">
					</div>
					<div>
						<label class="form-label">Jumlah (Item)</label>
						<input type="number" name="quantity" class="form-input" min="1" value="1" required>
					</div>
					<div class="col-span-2">
						<label class="form-label">Upload Bukti/Nota <span class="text-slate-400">(opsional, max 20MB)</span></label>
						<input type="file" name="receipt_photo" class="form-input" accept="image/*">
					</div>
				</div>
				<div class="flex justify-end gap-2">
					<button type="button" onclick="closeCostModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-sm transition-colors">Batal</button>
					<button type="submit" class="btn-primary px-6">Simpan</button>
				</div>
			</form>
		</div>
	</div>

	{{-- Modal Edit Biaya --}}
	<div id="editCostModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
		<div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
			<h3 class="text-lg font-bold text-slate-800 mb-4">Edit Rincian Biaya</h3>
			<form id="editCostForm" method="POST" enctype="multipart/form-data">
				@csrf @method('PUT')
				<div class="grid grid-cols-2 gap-4 mb-4">
					<div class="col-span-2">
						<label class="form-label">Kategori Biaya</label>
						<select name="cost_category" id="editCostCategory" class="form-select" required>
							@foreach($categories as $cat)
								<option value="{{ $cat->value }}">{{ $cat->label() }}</option>
							@endforeach
						</select>
					</div>
					<div class="col-span-2">
						<label class="form-label">Keterangan</label>
						<input type="text" name="description" id="editCostDesc" class="form-input" required>
					</div>
					<div>
						<label class="form-label">Nama Maskapai</label>
						<input type="text" name="airline_name" id="editCostAirline" class="form-input">
					</div>
					<div>
						<label class="form-label">No. Tiket</label>
						<input type="text" name="ticket_number" id="editCostTicket" class="form-input">
					</div>
					<div>
						<label class="form-label">Tarif Satuan (Rp)</label>
						<input type="number" name="unit_cost" id="editCostUnitCost" class="form-input" min="0" step="1000" required>
					</div>
					<div>
						<label class="form-label">Jumlah (Item)</label>
						<input type="number" name="quantity" id="editCostQty" class="form-input" min="1" required>
					</div>
					<div class="col-span-2">
						<label class="form-label">Upload Bukti/Nota Baru <span class="text-slate-400">(opsional)</span></label>
						<input type="file" name="receipt_photo" class="form-input" accept="image/*">
					</div>
				</div>
				<div class="flex justify-end gap-2">
					<button type="button" onclick="closeEditCostModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-sm transition-colors">Batal</button>
					<button type="submit" class="btn-primary px-6">Simpan</button>
				</div>
			</form>
		</div>
	</div>
@endsection

@push('scripts')
<script>
	function openCostModal(userId, userName) {
		document.getElementById('costUserId').value = userId;
		document.getElementById('costUserName').textContent = userName;
		document.getElementById('costModal').classList.remove('hidden');
		document.getElementById('costModal').classList.add('flex');
	}
	function closeCostModal() {
		document.getElementById('costModal').classList.add('hidden');
		document.getElementById('costModal').classList.remove('flex');
	}
	function openEditCostModal(cost) {
		document.getElementById('editCostCategory').value = cost.cost_category;
		document.getElementById('editCostDesc').value = cost.description;
		document.getElementById('editCostAirline').value = cost.airline_name || '';
		document.getElementById('editCostTicket').value = cost.ticket_number || '';
		document.getElementById('editCostUnitCost').value = cost.unit_cost;
		document.getElementById('editCostQty').value = cost.quantity;
		document.getElementById('editCostForm').action = '{{ url("sppd/" . $sppd->id . "/cost-details") }}/' + cost.id;
		document.getElementById('editCostModal').classList.remove('hidden');
		document.getElementById('editCostModal').classList.add('flex');
	}
	function closeEditCostModal() {
		document.getElementById('editCostModal').classList.add('hidden');
		document.getElementById('editCostModal').classList.remove('flex');
	}
</script>
@endpush
