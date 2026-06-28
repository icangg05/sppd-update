@extends('layouts.app')
@section('title', 'Laporan Pengeluaran Rill')

@section('content')
	@php
		$people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
		foreach ($sppd->followers as $f) {
		    $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
		}
	@endphp

	<div class="p-1 space-y-6" x-data="{
    printDate: '{{ date('Y-m-d') }}',
    showBulkModal: false,
    showAddModal: false,
    showEditModal: false,

    // Add modal state
    addUserId: '',
    addUserName: '',

    // Edit modal state
    editExpenseId: '',
    editDescription: '',
    editAmount: '',
    editActionUrl: '',

    // Bulk modal state
    bulkDescription: '',
    bulkAmount: '',
    selectedUserIds: [
        @foreach ($people as $person)
        '{{ $person['id'] }}', @endforeach
    ],
    allUsers: [
        @foreach ($people as $person)
        { id: '{{ $person['id'] }}', name: {{ json_encode($person['name']) }} }, @endforeach
    ],

    toggleSelectAll() {
        if (this.selectedUserIds.length === this.allUsers.length) {
            this.selectedUserIds = [];
        } else {
            this.selectedUserIds = this.allUsers.map(u => u.id);
        }
    },

    openAddModal(userId, userName) {
        this.addUserId = userId;
        this.addUserName = userName;
        this.showAddModal = true;
    },

    openEditModal(expenseId, desc, amount) {
        this.editExpenseId = expenseId;
        this.editDescription = desc;
        this.editAmount = amount;
        this.editActionUrl = '{{ url('sppd/' . $sppd->id . '/actual-expenses') }}/' + expenseId;
        this.showEditModal = true;
    }
}">

		{{-- Header --}}
		<div class="flex items-center justify-between">
			<div>
				<h1
					class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
					<i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>Laporan Pengeluaran Rill
				</h1>
			</div>
			<a wire:navigate href="{{ route('sppd.next', $sppd) }}"
				class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
				<i class="fa-solid fa-arrow-left"></i> Kembali
			</a>
		</div>

		{{-- PPTK Selection (pencarian server-side, lingkup OPD induk pelaksana) --}}
		<livewire:sppd.pptk-selector :sppd="$sppd" :key="'pptk-' . $sppd->id" />

		{{-- Alert Info --}}
		<div
			class="flex items-start gap-3 rounded-lg border border-cyan-200 bg-cyan-50 p-4 text-[11px] text-cyan-800 shadow-xs">
			<i class="fa-solid fa-circle-info mt-0.5 text-cyan-600"></i>
			<p>Pengaturan <strong>PPTK</strong> wajib dipilih sebelum Anda dapat mencetak dokumen Laporan Pengeluaran Rill.
			</p>
		</div>

		{{-- Toolbar: Tanggal Cetak & Input Massal --}}
		<div
			class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
			{{-- Kiri: Info jumlah + Pilih Tanggal Cetak --}}
			<div class="flex flex-col sm:flex-row sm:items-center gap-4">
				<div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
					<i class="fa-solid fa-people-group text-emerald-600 text-sm"></i>
					<span>Total: {{ $people->count() }} Pegawai</span>
				</div>

				<div class="hidden sm:block h-4 w-px bg-slate-300"></div>

				<div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
					<i class="fa-solid fa-calendar-day text-emerald-600 text-sm"></i>
					<span class="text-slate-500">Tanggal Cetak:</span>
					<input type="date" x-model="printDate"
						class="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:ring-emerald-500 shadow-sm" />
				</div>
			</div>
			{{-- Kanan: Tombol Input Sekaligus --}}
			@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
			<div>
				<button type="button" @click="showBulkModal = true"
					class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 transition shadow-sm cursor-pointer hover:scale-[1.02] active:scale-[0.98]">
					<i class="fa-solid fa-layer-group text-slate-400"></i>
					Input Sekaligus
				</button>
			</div>
			@endif
		</div>

		{{-- Table Section --}}
		<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
			<table class="w-full text-left border-collapse text-xs">
				<thead>
					<tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
						<th class="py-3 px-4 text-center w-12 font-bold">No.</th>
						<th class="py-3 px-4 w-60 font-bold">Pegawai</th>
						<th class="py-3 px-4 font-bold">Daftar Pengeluaran Riil</th>
						<th class="py-3 px-4 w-44 text-right font-bold">Total Pengeluaran</th>
						<th class="py-3 px-4 w-44 text-right font-bold">Aksi</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					@foreach ($people as $index => $person)
						@php
							$expenses = $sppd->actualExpenses->where('user_id', $person['id']);
							$total = $expenses->sum('amount');
						@endphp
						<tr class="hover:bg-slate-50 transition-colors">
							<td class="py-3 px-4 text-center font-semibold text-slate-500">
								{{ $index + 1 }}.
							</td>
							<td class="py-3 px-4">
								<div class="flex flex-col gap-1">
									<div class="flex items-center gap-2">
										@if ($person['label'] === 'Pelaksana')
											<span
												class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
												Pelaksana
											</span>
										@else
											<span
												class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10">
												Pengikut
											</span>
										@endif
										<span class="font-bold text-slate-800 uppercase">{{ $person['name'] }}</span>
									</div>
								</div>
							</td>
							<td class="py-3 px-4">
								<div class="space-y-1.5 max-w-xl">
									@forelse($expenses as $expense)
										<div
											class="group flex items-center justify-between bg-slate-50 hover:bg-slate-100 border border-slate-400 rounded px-2.5 py-1.5 transition-all">
											<div class="flex items-center gap-2">
												<span class="text-slate-700 font-medium">{{ $expense->description }}</span>
												<span class="text-[10px] text-slate-400 font-mono">| Rp
													{{ number_format($expense->amount, 0, ',', '.') }}</span>
											</div>
											@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
											<div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
												<button type="button"
													@click="openEditModal('{{ $expense->id }}', {{ json_encode($expense->description) }}, '{{ $expense->amount }}')"
													class="text-amber-600 hover:text-amber-800 p-0.5 cursor-pointer" title="Edit">
													<i class="fa-solid fa-pen-to-square text-[10px]"></i>
												</button>
												<form action="{{ route('sppd.actual-expenses.destroy', [$sppd, $expense]) }}" method="POST"
													onsubmit="return confirm('Hapus pengeluaran ini?')">
													@csrf @method('DELETE')
													<button type="submit" class="text-rose-600 hover:text-rose-800 p-0.5 cursor-pointer" title="Hapus">
														<i class="fa-solid fa-trash text-[10px]"></i>
													</button>
												</form>
											</div>
											@endif
										</div>
									@empty
										<span class="text-slate-400 italic text-[11px] pl-1">Belum ada data pengeluaran</span>
									@endforelse
								</div>
							</td>
							<td class="py-3 px-4 text-right">
								<span class="font-bold {{ $total > 0 ? 'text-emerald-700 font-mono text-xs' : 'text-slate-400' }}">
									Rp {{ number_format($total, 0, ',', '.') }}
								</span>
							</td>
							<td class="py-3 px-4 text-right">
								<div class="flex items-center justify-end gap-1.5">
									@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
									<button type="button"
										@click="openAddModal('{{ $person['id'] }}', {{ json_encode($person['name']) }})"
										class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2 py-1 text-[10px] font-bold text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer hover:scale-[1.03] active:scale-[0.97]">
										<i class="fa-solid fa-plus text-[8px]"></i> Tambah
									</button>
									@endif
									@if ($expenses->count() > 0)
										<a
											:href="'{{ route('sppd.stream.pengeluaran-riil', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' +
											'&date=' + printDate"
											target="_blank"
											class="inline-flex items-center gap-1 rounded bg-slate-600 px-2 py-1 text-[10px] font-bold text-white hover:bg-slate-700 shadow-xs transition {{ !$sppd->pptk_id ? 'opacity-50 pointer-events-none cursor-not-allowed' : 'hover:scale-[1.03] active:scale-[0.97]' }}">
											<i class="fa-solid fa-print text-[8px]"></i> Cetak
										</a>
@else
<button type="button" disabled
											class="inline-flex items-center gap-1 rounded bg-slate-100 border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-400 cursor-not-allowed">
											<i class="fa-solid fa-lock text-[8px]"></i> Cetak
										</button>
@endif
								</div>
							</td>
						</tr>
@endforeach
				</tbody>
			</table>
		</div>

		{{-- Modal Tambah --}}
		<x-ui.modal show="showAddModal" title="Tambah Pengeluaran Riil" icon="fa-solid fa-plus text-emerald-600">
			<div>
				<p class="mb-4 text-xs font-semibold text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
					Pegawai: <span class="font-bold text-slate-800 uppercase" x-text="addUserName"></span>
				</p>

				<form method="POST" :action="'{{ route('sppd.actual-expenses.store', $sppd) }}
											    '">
											@csrf
											<input type="hidden" name="user_id" :value="addUserId">
											<div class="mb-4">
												<label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Uraian</label>
												<input type="text" name="description"
													class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
													placeholder="Contoh: Tiket pesawat" required>
											</div>
											<div class="mb-4">
												<label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Tarif (Rp)</label>
												<input type="number" name="amount"
													class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
													placeholder="0" required>
											</div>
											<div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
												<button type="button" @click="showAddModal = false"
													class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
												<button type="submit"
													class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer">Simpan</button>
											</div>
											</form>
								</div>
								</x-ui.modal>

								{{-- Modal Edit --}}
								<x-ui.modal show="showEditModal" title="Edit Pengeluaran Riil"
									icon="fa-solid fa-pen-to-square text-amber-600">
									<form :action="editActionUrl" method="POST">
										@csrf @method('PUT')
										<div class="mb-4">
											<label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Uraian</label>
											<input type="text" name="description" x-model="editDescription"
												class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
												required>
										</div>
										<div class="mb-4">
											<label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Tarif (Rp)</label>
											<input type="number" name="amount" x-model="editAmount"
												class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
												required>
										</div>
										<div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
											<button type="button" @click="showEditModal = false"
												class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
											<button type="submit"
												class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer">Simpan</button>
										</div>
									</form>
								</x-ui.modal>

								{{-- Modal Input Pengeluaran Sekaligus --}}
								<x-ui.modal show="showBulkModal" title="Input Pengeluaran Sekaligus"
									icon="fa-solid fa-layer-group text-emerald-600">
									<div class="space-y-4">
										<div class="rounded bg-slate-50 p-3 border border-slate-200 text-[11px] text-slate-600 leading-relaxed">
											<p class="font-bold uppercase mb-1">
												<i class="fa-solid fa-circle-info mr-1 text-slate-500"></i> Petunjuk:
											</p>
											Nominal pengeluaran ini akan ditambahkan ke **semua pegawai yang dicentang**. Anda dapat memilih atau
											membatalkan
											pilihan pegawai di bawah.
										</div>

										<form method="POST" action="{{ route('sppd.actual-expenses.store', $sppd) }}">
											@csrf
											<div class="mb-4">
												<label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Uraian / Deskripsi</label>
												<input type="text" name="description" x-model="bulkDescription"
													class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
													placeholder="Contoh: Uang Harian" required>
											</div>
											<div class="mb-4">
												<label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Tarif (Rp)</label>
												<input type="number" name="amount" x-model="bulkAmount"
													class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
													placeholder="0" required>
											</div>

											<div class="mb-4">
												<div class="flex items-center justify-between mb-2">
													<span class="text-[10px] font-bold uppercase text-slate-500">Pilih Pegawai Penerima</span>
													<button type="button" @click="toggleSelectAll()"
														class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800 transition cursor-pointer">
														<span x-text="selectedUserIds.length === allUsers.length ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
													</button>
												</div>
												<div class="max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-2 bg-slate-50/50">
													<template x-for="user in allUsers" :key="user.id">
														<label
															class="flex items-center gap-2 cursor-pointer py-1 px-1.5 rounded hover:bg-slate-100 transition text-xs">
															<input type="checkbox" name="user_ids[]" :value="user.id" x-model="selectedUserIds"
																class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
															<span class="font-medium text-slate-700 uppercase" x-text="user.name"></span>
														</label>
													</template>
												</div>
											</div>

											<div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
												<button type="button" @click="showBulkModal = false; bulkDescription = ''; bulkAmount = ''"
													class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
													Batal
												</button>
												<button type="submit" :disabled="selectedUserIds.length === 0"
													class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
													Terapkan & Simpan
												</button>
											</div>
										</form>
									</div>
								</x-ui.modal>
		</div>
	@endsection
