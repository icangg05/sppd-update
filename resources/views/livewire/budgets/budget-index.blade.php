<div class="p-1 space-y-6">

	{{-- Header Halaman --}}
	<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
		<div class="flex items-center gap-3">
			<div class="p-2 bg-primary-100 rounded">
				<i class="fa-solid fa-file-invoice-dollar text-primary-600 text-lg"></i>
			</div>
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-primary-500 inline-block pb-1">
					Daftar Anggaran (DPA)
				</h1>
				<p class="mt-1 text-xs text-slate-500 font-medium">
					Tahun Anggaran {{ $year !== '' ? $year : 'Semua' }}
				</p>
			</div>
		</div>

		@can('budget.create')
			<x-ui.button href="{{ route('master.budgets.create') }}"
				class="inline-flex items-center gap-2 rounded bg-primary-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-primary-200 transition hover:bg-primary-700 hover:shadow-lg">
				<x-slot name="icon">
					<i class="fa-solid fa-plus"></i>
				</x-slot>
				Tambah Data
			</x-ui.button>
		@endcan
	</div>

	{{-- Filter --}}
	<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
		<div class="flex flex-col sm:flex-row gap-3">

			{{-- Search Input --}}
			<div class="relative flex-1">
				<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
					<i class="fa-solid fa-magnifying-glass text-xs"></i>
				</div>
				<input type="text" wire:model.live.debounce.400ms="search"
					class="block w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-9 pr-9 text-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 outline-none transition"
					placeholder="Cari program, kegiatan, kode rekening, atau uraian...">
				<div wire:loading wire:target="search"
					class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-primary-500">
					<i class="fa-solid fa-spinner fa-spin text-xs"></i>
				</div>
			</div>

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

			{{-- Year Searchable Select --}}
			@php
				$yearOptions = collect(range(date('Y'), date('Y') - 4))
					->map(fn($y) => ['value' => (string) $y, 'label' => (string) $y])
					->prepend(['value' => '', 'label' => 'Semua TA'])
					->all();
			@endphp
			<div class="w-full sm:w-32">
				<x-form.searchable-select wire:model.live="year" name="year"
					:options="$yearOptions" placeholder="Semua TA"
					searchPlaceholder="Cari tahun..." class="bg-slate-50" />
			</div>

			{{-- Reset (selalu tampil, nonaktif saat tidak ada filter) --}}
			@php $hasFilter = $search !== '' || $source !== '' || $department_id !== '' || $year !== (string) date('Y'); @endphp
			<x-ui.button wire:click="resetFilters" type="button" variant="secondary" :disabled="! $hasFilter"
				class="rounded-lg px-4 py-2 font-semibold text-slate-600 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
				<i class="fa-solid fa-rotate-right"></i> Reset
			</x-ui.button>
		</div>
	</div>

	{{-- Table Container --}}
	<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden"
		wire:loading.class="opacity-60" wire:target="search,year,source,department_id">

		<div class="overflow-x-auto">
			<table class="w-full text-left whitespace-nowrap">
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
						<th class="py-3 px-4 text-center w-28">Aksi</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100 text-slate-700">
					@forelse($budgets as $budget)
						<tr class="hover:bg-slate-50/50 transition-colors">
							<td class="py-3.5 px-4 text-center text-xs font-semibold text-slate-500">
								{{ $loop->iteration + ($budgets->currentPage() - 1) * $budgets->perPage() }}.
							</td>

							<td class="py-3.5 px-4 max-w-56 whitespace-normal">
								<div class="text-sm font-bold text-slate-900 leading-tight">{{ $budget->department?->name ?? '-' }}</div>
								@if ($budget->department?->parent)
									<div class="text-[11px] text-slate-500 leading-tight mt-0.5">{{ $budget->department->parent->name }}</div>
								@endif
							</td>

							<td class="py-3.5 px-4 text-center text-xs font-mono font-medium text-slate-500">
								{{ $budget->year }}
							</td>

							<td class="py-3.5 px-4 min-w-64 max-w-sm whitespace-normal">
								<div class="text-xs font-bold text-primary-700 leading-tight mb-1">{{ $budget->program }}</div>
								<div class="text-[11px] text-slate-500 leading-relaxed">{{ $budget->activity }}</div>
							</td>

							<td class="py-3.5 px-4 whitespace-nowrap">
								<span
									class="inline-block rounded bg-slate-100 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 border border-slate-200/60">
									{{ $budget->account_code }}
								</span>
							</td>

							<td class="py-3.5 px-4 min-w-52 max-w-xs whitespace-normal">
								<div class="text-sm font-medium text-slate-700 leading-normal">{{ $budget->description }}</div>
							</td>

							<td class="py-3.5 px-4 text-right font-semibold text-slate-900 text-sm">
								Rp {{ number_format($budget->total_amount, 0, ',', '.') }}
							</td>

							<td class="py-3.5 px-4 text-right font-semibold text-xs">
								@if ($budget->balance < 0)
									<span
										class="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 text-rose-700 ring-1 ring-inset ring-rose-600/10">
										Rp {{ number_format($budget->balance, 0, ',', '.') }}
									</span>
								@else
									<span
										class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
										Rp {{ number_format($budget->balance, 0, ',', '.') }}
									</span>
								@endif
							</td>

							<td class="py-3.5 px-4 text-center">
								<div class="flex items-center justify-center gap-1">
									{{-- Detail --}}
									<a wire:navigate href="{{ route('master.budgets.show', $budget->id) }}"
										class="rounded-xl border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-primary-50 hover:text-primary-600 transition-colors"
										title="Detail Anggaran">
										<i class="fa-solid fa-eye text-xs"></i>
									</a>

									{{-- Edit --}}
									@can('budget.edit')
										<a wire:navigate href="{{ route('master.budgets.edit', $budget->id) }}"
											class="rounded-xl border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-amber-50 hover:text-amber-600 transition-colors"
											title="Edit Anggaran">
											<i class="fa-solid fa-pen-to-square text-xs"></i>
										</a>
									@endcan

									{{-- Hapus --}}
									@can('budget.delete')
										<button type="button" wire:click="confirmDelete({{ $budget->id }})"
											class="rounded-xl border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-colors"
											title="Hapus Anggaran">
											<i class="fa-solid fa-trash-can text-xs"></i>
										</button>
									@endcan
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="9" class="py-12 text-center">
								<div class="flex flex-col items-center justify-center text-slate-500">
									<i class="fa-solid fa-folder-open text-3xl mb-3 opacity-50"></i>
									<p class="text-sm font-medium">Belum ada data anggaran yang ditemukan</p>
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
