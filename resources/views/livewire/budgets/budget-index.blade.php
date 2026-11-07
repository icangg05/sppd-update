<div class="p-1 space-y-4">

	{{-- Header Halaman (title card) --}}
	<div
		class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
		{{-- Watermark institusional (tipis, hanya karakter). --}}
		<i class="fa-solid fa-file-invoice-dollar pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
			aria-hidden="true"></i>

		<div class="relative flex flex-col justify-between gap-4 md:flex-row md:items-center">
			<div class="min-w-0 leading-tight">
				<span
					class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
					<i class="fa-solid fa-coins text-[9px]"></i>
					Dokumen Anggaran
					<span class="ml-1 tabular-nums text-primary-600/70">· {{ $budgets->total() }}</span>
				</span>
				<h1 class="text-xl font-bold tracking-tight text-slate-800">Daftar Anggaran (DPA)</h1>
				<p class="mt-1 text-xs text-slate-500">
					Dokumen Pelaksanaan Anggaran {{ $year !== '' ? 'tahun anggaran ' . $year : 'untuk semua tahun anggaran' }}
				</p>
			</div>

			@can('budget.create')
				<x-ui.button href="{{ route('master.budgets.create') }}" variant="primary" class="shrink-0 font-bold">
					<x-slot name="icon">
						<i class="fa-solid fa-plus text-xs"></i>
					</x-slot>
					Tambah Data
				</x-ui.button>
			@endcan
		</div>
	</div>

	{{-- Filter --}}
	<div class="rounded border border-slate-200 bg-white p-4 shadow-sm">
		<div class="flex flex-col sm:flex-row gap-3">

			{{-- Search Input --}}
			<x-form.input wire:model.live.debounce.400ms="search" icon="fa-solid fa-magnifying-glass"
				loadingTarget="search" wrapperClass="flex-1" class="bg-slate-50"
				placeholder="Cari program, kegiatan, kode rekening, atau uraian..." />

			{{-- Department Searchable Select (Super Admin) --}}
			@if (auth()->user()->hasRole('super_admin'))
				@php
					$departmentFilterOptions = collect($departments)
						->map(fn($d) => ['value' => $d->id, 'label' => $d->name])
						->prepend(['value' => '', 'label' => 'Semua Instansi'])
						->all();
				@endphp
				<div class="w-full sm:w-56">
					<x-form.searchable-select wire:model.live="department_id" name="department_id"
						:options="$departmentFilterOptions" placeholder="Semua Instansi"
						searchPlaceholder="Cari instansi..." class="bg-slate-50" />
				</div>
			@endif

			{{-- Source Searchable Select --}}
			@php
				$sourceOptions = [
					['value' => '', 'label' => 'Semua Sumber'],
					['value' => 'APBD', 'label' => 'APBD'],
					['value' => 'APBD-P', 'label' => 'APBD-P'],
					['value' => 'APBN', 'label' => 'APBN'],
				];
			@endphp
			<div class="w-full sm:w-40">
				<x-form.searchable-select wire:model.live="source" name="source"
					:options="$sourceOptions" placeholder="Semua Sumber"
					searchPlaceholder="Cari sumber..." class="bg-slate-50" />
			</div>

			{{-- Year Searchable Select — hanya tahun yang punya DPA --}}
			@php
				$yearOptions = collect($years)
					->map(fn($y) => ['value' => $y, 'label' => $y])
					->prepend(['value' => '', 'label' => 'Semua TA'])
					->all();
			@endphp
			<div class="w-full sm:w-32">
				<x-form.searchable-select wire:model.live="year" name="year"
					:options="$yearOptions" placeholder="Semua TA"
					searchPlaceholder="Cari tahun..." class="bg-slate-50" />
			</div>

			{{-- Reset (selalu tampil, nonaktif saat tidak ada filter) --}}
			@php $hasFilter = $search !== '' || $source !== '' || $department_id !== '' || $year !== $defaultYear; @endphp
			<x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $hasFilter"
				class="whitespace-nowrap">
				<x-slot:icon><i class="fa-solid fa-rotate-right text-xs text-slate-500"></i></x-slot:icon>
				Reset
			</x-ui.button>
		</div>
	</div>

	{{-- Table Container --}}
	<div class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden"
		wire:loading.class="opacity-60" wire:target="search,year,source,department_id">

		<div class="overflow-x-auto">
			<table class="table-stack w-full text-left whitespace-nowrap md:[&_thead_th]:py-2.5 md:[&_tbody_td]:py-2">
				<thead
					class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
					<tr>
						<th class="py-3 px-4 w-12 text-center">No.</th>
						<th class="py-3 px-4 min-w-44 max-w-56">SKPD / Instansi</th>
						<th class="py-3 px-4 text-center w-16">TA</th>
						<th class="py-3 px-4 min-w-64">Program / Kegiatan</th>
						<th class="py-3 px-4 whitespace-nowrap">Kode Rekening</th>
						<th class="py-3 px-4 min-w-52">Uraian Anggaran</th>
						<th class="py-3 px-4 text-right w-32">Pagu Total</th>
						<th class="py-3 px-4 text-right w-32">Sisa Pagu</th>
						<th class="py-3 px-4 text-right w-28">Aksi</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100 text-slate-700">
					@forelse($budgets as $budget)
						<tr class="hover:bg-slate-50/50 transition-colors">
							<td class="stack-hide py-3.5 px-4 text-center text-xs font-semibold text-slate-500">
								{{ $loop->iteration + ($budgets->currentPage() - 1) * $budgets->perPage() }}.
							</td>

							<td data-label="SKPD / Instansi" class="stack-block py-3.5 px-4 max-w-56 whitespace-normal">
								<div class="text-sm font-bold text-slate-900 leading-tight">{{ $budget->department?->name ?? '-' }}</div>
								@if ($budget->department?->parent)
									<div class="text-[11px] text-slate-500 leading-tight mt-0.5">{{ $budget->department->parent->name }}</div>
								@endif
							</td>

							<td data-label="TA" class="py-3.5 px-4 text-center text-xs font-mono font-medium text-slate-500">
								{{ $budget->year }}
							</td>

							<td data-label="Program / Kegiatan" class="stack-block py-3.5 px-4 min-w-64 max-w-sm whitespace-normal">
								<div class="text-xs font-bold text-primary-700 leading-tight mb-1">{{ $budget->program }}</div>
								<div class="text-[11px] text-slate-500 leading-relaxed">{{ $budget->activity }}</div>
							</td>

							<td data-label="Kode Rekening" class="py-3.5 px-4 whitespace-nowrap">
								<span
									class="inline-block rounded bg-slate-100 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 border border-slate-200/60">
									{{ $budget->account_code }}
								</span>
							</td>

							<td data-label="Uraian" class="stack-block py-3.5 px-4 min-w-52 max-w-xs whitespace-normal">
								<div class="text-sm font-medium text-slate-700 leading-normal">{{ $budget->description }}</div>
							</td>

							<td data-label="Pagu Total" class="py-3.5 px-4 text-right font-mono text-sm font-semibold text-slate-900">
								Rp {{ number_format($budget->total_amount, 0, ',', '.') }}
							</td>

							<td data-label="Sisa Pagu" class="py-3.5 px-4 text-right text-xs font-semibold">
								@if ($budget->balance < 0)
									<span
										class="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 font-mono text-rose-700 ring-1 ring-inset ring-rose-600/10">
										Rp {{ number_format($budget->balance, 0, ',', '.') }}
									</span>
								@else
									<span
										class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 font-mono text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
										Rp {{ number_format($budget->balance, 0, ',', '.') }}
									</span>
								@endif
							</td>

							{{-- Aksi ikon bergaya sama dengan tabel Data Pegawai; keterangan lewat tooltip. --}}
							<td data-label="Aksi" class="py-3.5 px-4 text-right">
								@php $actionBtn = 'inline-flex size-7 shrink-0 items-center justify-center rounded border text-xs transition focus:outline-none focus-visible:ring-2'; @endphp
								<div class="flex flex-wrap items-center justify-end gap-1">
									<a wire:navigate href="{{ route('master.budgets.show', $budget->id) }}"
										title="Detail Anggaran" aria-label="Detail Anggaran"
										class="{{ $actionBtn }} border-slate-300 bg-slate-100 text-slate-600 shadow-2xs hover:bg-slate-200 focus-visible:ring-slate-400/40">
										<i class="fa-solid fa-eye"></i>
									</a>

									@can('budget.edit')
										<a wire:navigate href="{{ route('master.budgets.edit', $budget->id) }}"
											title="Edit Anggaran" aria-label="Edit Anggaran"
											class="{{ $actionBtn }} border-amber-300 bg-amber-50 text-amber-600 shadow-2xs hover:bg-amber-100 focus-visible:ring-amber-500/40">
											<i class="fa-solid fa-pen-to-square"></i>
										</a>
									@endcan

									@can('budget.delete')
										<button type="button" wire:click="confirmDelete({{ $budget->id }})"
											title="Hapus Anggaran" aria-label="Hapus Anggaran"
											class="{{ $actionBtn }} cursor-pointer border-red-200 bg-red-50 text-red-600 hover:bg-red-100 focus-visible:ring-red-500/40">
											<i class="fa-solid fa-trash"></i>
										</button>
									@endcan
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="9" class="px-4 py-16">
								<div class="mx-auto flex max-w-sm flex-col items-center text-center">
									<div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
										<i class="fa-solid fa-file-invoice-dollar text-xl"></i>
									</div>

									@if ($hasFilter)
										<p class="mt-4 text-sm font-semibold text-slate-700">Tidak ada anggaran yang cocok</p>
										<p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci, tahun, sumber, atau instansi yang difilter.</p>
										<x-ui.button wire:click="resetFilters" type="button" variant="secondary" size="sm" class="mt-4">
											<i class="fa-solid fa-rotate-right text-xs"></i> Reset filter
										</x-ui.button>
									@else
										<p class="mt-4 text-sm font-semibold text-slate-700">Belum ada data anggaran</p>
										<p class="mt-1 text-xs text-slate-500">Tambahkan data DPA agar dapat dipakai saat pembuatan SPPD.</p>
										@can('budget.create')
											<x-ui.button href="{{ route('master.budgets.create') }}" variant="primary" size="sm" class="mt-4">
												<i class="fa-solid fa-plus text-xs"></i> Tambah Data
											</x-ui.button>
										@endcan
									@endif
								</div>
							</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		{{-- Pagination --}}
		@if ($budgets->hasPages())
			<div class="border-t border-slate-200 bg-slate-50/50 px-4 py-3">
				{{ $budgets->links() }}
			</div>
		@endif
	</div>

	{{-- Modal Konfirmasi Hapus — tombol Hapus aktif setelah 10 detik --}}
	<x-ui.modal show="$wire.showDeleteModal" title="Konfirmasi Hapus Anggaran"
		description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash-can text-rose-600"
		:closeable="false">
		<div class="space-y-4"
			x-data="{
				remaining: 10,
				timer: null,
				startCountdown() {
					this.remaining = 10;
					clearInterval(this.timer);
					this.timer = setInterval(() => {
						if (this.remaining > 0) this.remaining--;
						if (this.remaining <= 0) clearInterval(this.timer);
					}, 1000);
				},
			}"
			x-on:budget-delete-countdown.window="startCountdown()"
			x-init="if ($wire.showDeleteModal) startCountdown()">
			<p class="text-sm text-slate-600">
				Yakin ingin menghapus data anggaran
				<span class="font-bold text-slate-800">{{ $deletingName ?? 'ini' }}</span>?
				Data yang sudah dihapus tidak dapat dikembalikan.
			</p>

			<div class="flex items-center justify-end gap-2 pt-1">
				<x-ui.button type="button" variant="secondary" wire:click="closeDeleteModal">Tutup</x-ui.button>
				<x-ui.button type="button" variant="danger" wire:click="delete"
					x-bind:disabled="remaining > 0"
					x-bind:class="remaining > 0 ? 'opacity-50 cursor-not-allowed' : ''">
					<span x-show="remaining > 0"><i class="fa-solid fa-hourglass-half"></i> Tunggu <span x-text="remaining"></span>s</span>
					<span x-show="remaining <= 0" x-cloak>
						<span wire:loading.remove wire:target="delete"><i class="fa-solid fa-trash-can"></i> Hapus</span>
						<span wire:loading wire:target="delete"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
					</span>
				</x-ui.button>
			</div>
		</div>
	</x-ui.modal>

</div>
