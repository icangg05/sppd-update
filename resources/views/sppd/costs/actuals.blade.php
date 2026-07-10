@extends('layouts.app')
@section('title', 'Laporan Pengeluaran Rill')

@section('content')
	@php
		$people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
		foreach ($sppd->followers as $f) {
		    $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
		}
		$grandTotal = $sppd->actualExpenses->sum('amount');
	@endphp

	{{-- hasPptk: sumber kebenaran reaktif untuk mengunci/membuka tombol cetak.
	     Nilai awal dari server, lalu diperbarui oleh event `pptk-saved` yang
	     dikirim komponen Livewire PptkSelector saat PPTK disimpan. --}}
	<div class="space-y-6 p-1" x-data="{
	    printDate: '{{ date('Y-m-d') }}',
	    hasPptk: {{ $sppd->pptk_id ? 'true' : 'false' }},
	    showBulkModal: false,
	    showAddModal: false,
	    showEditModal: false,

	    addUserId: '',
	    addUserName: '',

	    editExpenseId: '',
	    editDescription: '',
	    editAmount: '',
	    editActionUrl: '',

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
	        this.selectedUserIds = this.selectedUserIds.length === this.allUsers.length
	            ? []
	            : this.allUsers.map(u => u.id);
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
	}" @pptk-saved.window="hasPptk = ($event.detail?.hasPptk ?? true)">

		{{-- Header --}}
		<header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div class="flex items-center gap-3">
				<div class="flex size-11 shrink-0 items-center justify-center rounded bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
					<i class="fa-solid fa-hand-holding-dollar text-lg"></i>
				</div>
				<div>
					<h1 class="text-xl font-bold tracking-tight text-balance text-slate-900 sm:text-2xl">Laporan pengeluaran riil</h1>
					<p class="mt-0.5 text-sm text-slate-500">Catat pengeluaran riil pegawai dan cetak dokumennya per personel.</p>
				</div>
			</div>
			<x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="secondary" class="shrink-0">
				<x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
				Kembali
			</x-ui.button>
		</header>

		{{-- Pemilih PPTK: pencarian client-side via komponen searchable-select,
		     lingkup kandidat dibatasi OPD induk pelaksana di server. --}}
		<livewire:sppd.pptk-selector :sppd="$sppd" :key="'pptk-' . $sppd->id" />

		{{-- Alert status: reaktif mengikuti hasPptk (terkunci → siap cetak). --}}
		<div x-show="!hasPptk" x-transition
			class="flex items-start gap-3 rounded border border-amber-200 bg-amber-50 p-4 text-[11px] text-amber-800 shadow-2xs">
			<i class="fa-solid fa-lock mt-0.5 text-amber-600"></i>
			<p><strong>PPTK</strong> wajib dipilih dan disimpan sebelum tombol cetak Laporan Pengeluaran Riil dapat digunakan.</p>
		</div>
		<div x-show="hasPptk" x-cloak x-transition
			class="flex items-start gap-3 rounded border border-emerald-200 bg-emerald-50 p-4 text-[11px] text-emerald-800 shadow-2xs">
			<i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
			<p>PPTK sudah diatur. Tombol cetak per pegawai sekarang aktif.</p>
		</div>

		{{-- Toolbar: ringkasan, tanggal cetak & input massal --}}
		<div class="flex flex-col gap-4 rounded border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
			<div class="flex flex-col gap-4 sm:flex-row sm:items-center">
				<div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
					<i class="fa-solid fa-people-group text-sm text-emerald-600"></i>
					<span>{{ $people->count() }} pegawai</span>
				</div>

				<div class="hidden h-4 w-px bg-slate-200 sm:block"></div>

				<div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
					<i class="fa-solid fa-coins text-sm text-emerald-600"></i>
					<span class="text-slate-500">Total riil:</span>
					<span class="font-mono tabular-nums text-emerald-700">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
				</div>

				<div class="hidden h-4 w-px bg-slate-200 sm:block"></div>

				<div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
					<i class="fa-solid fa-calendar-day text-sm text-emerald-600"></i>
					<label for="printDate" class="text-slate-500">Tanggal cetak:</label>
					<input id="printDate" type="date" x-model="printDate"
						class="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 shadow-2xs outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30" />
				</div>
			</div>

			@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
				<x-ui.button variant="dark" size="sm" @click="showBulkModal = true" class="w-full sm:w-auto">
					<x-slot name="icon"><i class="fa-solid fa-layer-group text-xs text-slate-400"></i></x-slot>
					Input sekaligus
				</x-ui.button>
			@endif
		</div>

		{{-- Tabel pengeluaran per pegawai --}}
		<div class="overflow-x-auto rounded border border-slate-200 bg-white shadow-sm">
			<table class="w-full border-collapse text-left text-xs">
				<thead>
					<tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
						<th class="w-12 px-4 py-3 text-center font-bold">No.</th>
						<th class="w-60 px-4 py-3 font-bold">Pegawai</th>
						<th class="px-4 py-3 font-bold">Daftar pengeluaran riil</th>
						<th class="w-44 px-4 py-3 text-right font-bold">Total</th>
						<th class="w-44 px-4 py-3 text-right font-bold">Aksi</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					@foreach ($people as $index => $person)
						@php
							$expenses = $sppd->actualExpenses->where('user_id', $person['id']);
							$total = $expenses->sum('amount');
						@endphp
						<tr class="transition-colors hover:bg-slate-50/70">
							<td class="px-4 py-3 text-center font-semibold text-slate-400 tabular-nums">{{ $index + 1 }}</td>
							<td class="px-4 py-3">
								<div class="flex items-center gap-2">
									@if ($person['label'] === 'Pelaksana')
										<span class="inline-flex items-center rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Pelaksana</span>
									@else
										<span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-slate-500 ring-1 ring-inset ring-slate-400/20">Pengikut</span>
									@endif
									<span class="font-bold uppercase text-slate-800">{{ $person['name'] }}</span>
								</div>
							</td>
							<td class="px-4 py-3">
								<div class="max-w-xl space-y-1.5">
									@forelse($expenses as $expense)
										<div class="group flex items-center justify-between gap-2 rounded border border-slate-200 bg-slate-50 px-2.5 py-1.5 transition-colors hover:bg-slate-100">
											<div class="flex items-center gap-2">
												<span class="font-medium text-slate-700">{{ $expense->description }}</span>
												<span class="font-mono text-[10px] tabular-nums text-slate-500">| Rp {{ number_format($expense->amount, 0, ',', '.') }}</span>
											</div>
											@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
												<div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
													<button type="button"
														@click="openEditModal('{{ $expense->id }}', {{ json_encode($expense->description) }}, '{{ $expense->amount }}')"
														class="cursor-pointer rounded p-0.5 text-amber-600 transition-colors hover:bg-amber-50 hover:text-amber-800" title="Edit">
														<i class="fa-solid fa-pen-to-square text-[10px]"></i>
													</button>
													<form action="{{ route('sppd.actual-expenses.destroy', [$sppd, $expense]) }}" method="POST"
														onsubmit="return confirm('Hapus pengeluaran ini?')">
														@csrf @method('DELETE')
														<button type="submit" class="cursor-pointer rounded p-0.5 text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-800" title="Hapus">
															<i class="fa-solid fa-trash text-[10px]"></i>
														</button>
													</form>
												</div>
											@endif
										</div>
									@empty
										<span class="pl-1 text-[11px] italic text-slate-400">Belum ada data pengeluaran</span>
									@endforelse
								</div>
							</td>
							<td class="px-4 py-3 text-right">
								<span class="font-mono text-xs font-bold tabular-nums {{ $total > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
									Rp {{ number_format($total, 0, ',', '.') }}
								</span>
							</td>
							<td class="px-4 py-3">
								<div class="flex items-center justify-end gap-1.5">
									@if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
										<button type="button"
											@click="openAddModal('{{ $person['id'] }}', {{ json_encode($person['name']) }})"
											class="inline-flex cursor-pointer items-center gap-1 rounded bg-emerald-600 px-2 py-1 text-[10px] font-bold text-white shadow-2xs transition hover:bg-emerald-700 hover:scale-[1.03] active:scale-[0.97]">
											<i class="fa-solid fa-plus text-[8px]"></i> Tambah
										</button>
									@endif
									@if ($expenses->count() > 0)
										{{-- Cetak: terkunci reaktif hingga PPTK tersimpan (hasPptk). --}}
										<a
											:href="'{{ route('sppd.stream.pengeluaran-riil', ['sppd' => $sppd, 'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($person['id'])]) }}' + '&date=' + printDate"
											target="_blank"
											:class="hasPptk ? 'hover:bg-primary-700 hover:scale-[1.03] active:scale-[0.97]' : 'pointer-events-none cursor-not-allowed opacity-50'"
											class="inline-flex items-center gap-1 rounded bg-primary-600 px-2 py-1 text-[10px] font-bold text-white shadow-2xs transition"
											:title="hasPptk ? 'Cetak dokumen' : 'Atur PPTK terlebih dahulu'">
											<i class="fa-solid text-[8px]" :class="hasPptk ? 'fa-print' : 'fa-lock'"></i>
											<span x-text="hasPptk ? 'Cetak' : 'Terkunci'"></span>
										</a>
									@else
										<button type="button" disabled
											class="inline-flex cursor-not-allowed items-center gap-1 rounded border border-slate-200 bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-400">
											<i class="fa-solid fa-print text-[8px]"></i> Cetak
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
		<x-ui.modal show="showAddModal" title="Tambah pengeluaran riil" icon="fa-solid fa-plus text-emerald-600">
			<p class="mb-4 rounded border border-slate-100 bg-slate-50 p-2.5 text-xs font-semibold text-slate-600">
				Pegawai: <span class="font-bold uppercase text-slate-800" x-text="addUserName"></span>
			</p>

			<form method="POST" :action="'{{ route('sppd.actual-expenses.store', $sppd) }}'">
				@csrf
				<input type="hidden" name="user_id" :value="addUserId">
				<div class="mb-4">
					<label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Uraian</label>
					<input type="text" name="description" required placeholder="Contoh: Tiket pesawat"
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
				</div>
				<div class="mb-4">
					<label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Tarif (Rp)</label>
					<input type="number" name="amount" required placeholder="0"
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm tabular-nums outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
				</div>
				<div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
					<button type="button" @click="showAddModal = false"
						class="cursor-pointer rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">Batal</button>
					<button type="submit"
						class="cursor-pointer rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 active:scale-95">Simpan</button>
				</div>
			</form>
		</x-ui.modal>

		{{-- Modal Edit --}}
		<x-ui.modal show="showEditModal" title="Edit pengeluaran riil" icon="fa-solid fa-pen-to-square text-amber-600">
			<form :action="editActionUrl" method="POST">
				@csrf @method('PUT')
				<div class="mb-4">
					<label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Uraian</label>
					<input type="text" name="description" x-model="editDescription" required
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
				</div>
				<div class="mb-4">
					<label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Tarif (Rp)</label>
					<input type="number" name="amount" x-model="editAmount" required
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm tabular-nums outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
				</div>
				<div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
					<button type="button" @click="showEditModal = false"
						class="cursor-pointer rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">Batal</button>
					<button type="submit"
						class="cursor-pointer rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 active:scale-95">Simpan</button>
				</div>
			</form>
		</x-ui.modal>

		{{-- Modal Input Sekaligus --}}
		<x-ui.modal show="showBulkModal" title="Input pengeluaran sekaligus" icon="fa-solid fa-layer-group text-emerald-600">
			<div class="space-y-4">
				<div class="rounded border border-slate-200 bg-slate-50 p-3 text-[11px] leading-relaxed text-slate-600">
					<p class="mb-1 font-bold uppercase"><i class="fa-solid fa-circle-info mr-1 text-slate-400"></i> Petunjuk</p>
					Nominal ini ditambahkan ke <strong>semua pegawai yang dicentang</strong>. Anda dapat memilih atau membatalkan pilihan pegawai di bawah.
				</div>

				<form method="POST" action="{{ route('sppd.actual-expenses.store', $sppd) }}">
					@csrf
					<div class="mb-4">
						<label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Uraian / deskripsi</label>
						<input type="text" name="description" x-model="bulkDescription" required placeholder="Contoh: Uang harian"
							class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
					</div>
					<div class="mb-4">
						<label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Tarif (Rp)</label>
						<input type="number" name="amount" x-model="bulkAmount" required placeholder="0"
							class="w-full rounded border border-slate-300 px-3 py-2 text-sm tabular-nums outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
					</div>

					<div class="mb-4">
						<div class="mb-2 flex items-center justify-between">
							<span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Pilih pegawai penerima</span>
							<button type="button" @click="toggleSelectAll()"
								class="cursor-pointer text-[10px] font-bold text-emerald-600 transition hover:text-emerald-800">
								<span x-text="selectedUserIds.length === allUsers.length ? 'Batal pilih semua' : 'Pilih semua'"></span>
							</button>
						</div>
						<div class="max-h-48 space-y-2 overflow-y-auto rounded border border-slate-200 bg-slate-50/50 p-3">
							<template x-for="user in allUsers" :key="user.id">
								<label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-xs transition hover:bg-slate-100">
									<input type="checkbox" name="user_ids[]" :value="user.id" x-model="selectedUserIds"
										class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
									<span class="font-medium uppercase text-slate-700" x-text="user.name"></span>
								</label>
							</template>
						</div>
					</div>

					<div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
						<button type="button" @click="showBulkModal = false; bulkDescription = ''; bulkAmount = ''"
							class="cursor-pointer rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">Batal</button>
						<button type="submit" :disabled="selectedUserIds.length === 0"
							class="cursor-pointer rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50">Terapkan & simpan</button>
					</div>
				</form>
			</div>
		</x-ui.modal>
	</div>
@endsection
