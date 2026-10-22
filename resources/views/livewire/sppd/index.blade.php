<div class="flex flex-col gap-4 p-1">

	@if ($errorMessage)
		<x-ui.alert title="SPPD lanjutan belum dapat dibuat">
			{!! $errorMessage !!}
			<x-slot:actions>
				<button type="button" wire:click="$set('errorMessage', null)" aria-label="Tutup peringatan"
					class="cursor-pointer rounded p-1 text-red-500 transition-colors hover:bg-red-100 hover:text-red-700">
					<i class="fa-solid fa-xmark text-sm"></i>
				</button>
			</x-slot:actions>
		</x-ui.alert>
	@endif

	{{-- Modal Konfirmasi Hapus SPPD: tombol Hapus baru aktif setelah hitung mundur
	     (juga dijaga di server). Hanya bisa ditutup lewat tombol. --}}
	<div class="contents" x-data="{
			wait: {{ $this::DELETE_WAIT }},
			left: {{ $this::DELETE_WAIT }},
			timer: null,
			start() {
				clearInterval(this.timer);
				this.left = this.wait;
				this.timer = setInterval(() => { if (--this.left <= 0) clearInterval(this.timer); }, 1000);
			},
		}" x-init="$watch('$wire.showDeleteModal', (open) => open ? start() : clearInterval(timer))">
		<x-ui.modal show="$wire.showDeleteModal" :closeable="false" title="Hapus SPPD?"
			description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-triangle-exclamation text-rose-600">
			<div class="space-y-3 text-sm text-slate-600">
				@if ($deleteStatus === 'in_progress')
					<p>Pengajuan SPPD atas nama <strong>{{ $deleteLabel ?? '-' }}</strong> masih dalam proses persetujuan dan akan <strong>dibatalkan serta dihapus permanen</strong>.</p>
				@else
					<p>SPPD atas nama <strong>{{ $deleteLabel ?? '-' }}</strong> akan <strong>dihapus permanen</strong> beserta seluruh datanya.</p>
				@endif

				@if (in_array($deleteStatus, ['approved', 'completed'], true))
					<div class="flex gap-2.5 rounded border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs text-rose-800">
						<i class="fa-solid fa-file-circle-xmark mt-0.5 text-rose-500"></i>
						<p>SPPD ini sudah disetujui. Dokumen SPT/SPPD bertanda tangan elektronik, laporan, dan rincian biayanya ikut terhapus. Dokumen yang sudah dicetak tidak bisa diverifikasi lagi lewat QR, dan anggaran yang terpakai kembali tersedia.</p>
					</div>
				@endif
			</div>

			<x-slot name="footer" class="space-y-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
				{{-- Sisa waktu tunggu sebelum tombol Hapus aktif. --}}
				<div class="h-1 overflow-hidden rounded-full bg-rose-100" aria-hidden="true">
					<div class="h-full rounded-full bg-rose-500 transition-[width] duration-1000 ease-linear motion-reduce:transition-none"
						:style="`width: ${(left / wait) * 100}%`"></div>
				</div>
				<div class="flex items-center gap-3">
					<x-ui.button variant="secondary" @click="$wire.showDeleteModal = false" wire:click="closeDeleteModal" wire:loading.attr="disabled" wire:target="deleteSppd"
						class="flex-1 justify-center">
						Batal
					</x-ui.button>
					<x-ui.button variant="danger" wire:click="deleteSppd" ::disabled="left > 0" wire:loading.attr="disabled" wire:target="deleteSppd"
						class="flex-1 justify-center">
						<span x-show="left > 0" class="inline-flex items-center gap-2" aria-live="polite">
							<i class="fa-solid fa-hourglass-half"></i>
							<span>Hapus dalam <span class="font-mono" x-text="left"></span> detik</span>
						</span>
						<span x-show="left <= 0" wire:loading.remove wire:target="deleteSppd" class="inline-flex items-center gap-2">
							<i class="fa-solid fa-trash"></i> Ya, Hapus
						</span>
						<span wire:loading wire:target="deleteSppd" class="inline-flex items-center gap-2">
							<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...
						</span>
					</x-ui.button>
				</div>
			</x-slot>
		</x-ui.modal>
	</div>

	{{-- Header Halaman --}}
	<div
		class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
		{{-- Watermark institusional (tipis, hanya karakter). --}}
		<i class="fa-solid fa-plane-departure pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
			aria-hidden="true"></i>

		<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
			<div class="min-w-0 leading-tight">
				@if ($isApprovalMode)
					<span
						class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-amber-700 ring-1 ring-inset ring-amber-600/15">
						<i class="fa-solid fa-clipboard-check text-[9px]"></i> Menunggu Tindakan
					</span>
					<h1 class="text-xl font-bold tracking-tight text-slate-800">Persetujuan</h1>
					<p class="mt-1 text-xs text-slate-500">Daftar SPPD yang menunggu persetujuan Anda</p>
				@else
					<span
						class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
						<i class="fa-solid fa-filter text-[9px]"></i> {{ $activeFilterLabel }}
					</span>
					<h1 class="text-xl font-bold tracking-tight text-slate-800">Daftar SPPD</h1>
					<p class="mt-1 text-xs text-slate-500">Kelola semua pengajuan perjalanan dinas secara real-time</p>
				@endif
			</div>
			@if (!$isApprovalMode)
			<div class="flex flex-col gap-2 md:flex-row md:items-center">
				@if ($isSuperAdmin)
					{{-- Super admin: semua jabatan via select-search --}}
					<div class="w-full md:w-64">
						<x-form.searchable-select wire:model.live="jabatan" name="jabatan" :options="$jabatanOptions"
							placeholder="Semua Jabatan" searchPlaceholder="Cari jabatan..." />
					</div>
				@else
					{{-- Tab jabatan dinamis sesuai jenis OPD user --}}
					<div class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded border border-slate-200">
						<button wire:click="filterByJabatan('')"
							class="px-3 py-1.5 text-xs font-semibold rounded transition-all duration-200 {{ $jabatan === '' ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
							Semua Jabatan
						</button>

						@foreach ($jabatanTabs as $tab)
							<button wire:click="filterByJabatan('{{ $tab }}')"
								class="px-3 py-1.5 text-xs font-semibold rounded transition-all duration-200 {{ $jabatan === $tab ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
								{{ $jabatanLabels[$tab] ?? $tab }}
							</button>
						@endforeach
					</div>
				@endif

					{{-- Filter tahun perjalanan --}}
					<div class="w-full md:w-40">
						<x-form.searchable-select wire:model.live="year" name="year" :options="$yearOptions"
							placeholder="Semua Tahun" searchPlaceholder="Cari tahun..." />
					</div>

				@if (auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
					<x-ui.button href="{{ route('sppd.create') }}" variant="primary" class="justify-center">
						<x-slot name="icon">
							<i class="fa-solid fa-plus text-xs"></i>
						</x-slot>
						Buat SPPD
					</x-ui.button>
				@endif
			</div>
		@endif
	</div>

	</div>

	{{-- Bar Filter: baris 1 pencarian (+ tahun di mode persetujuan) & Reset; baris 2 filter data. --}}
	<div class="dash-enter rounded border border-slate-200 bg-white p-4 shadow-sm">
		@php
			$hasActiveFilters = $isApprovalMode
				? $search !== '' || $year !== ''
				: $search !== '' || $status !== '' || $domain !== '' || $jabatan !== '' || $year !== '' || $filter !== ''
					|| $realisasi !== '' || $laporan !== '';
		@endphp
		<div class="flex flex-col gap-3 sm:flex-row">
			<x-form.input name="search" wire:model.live.debounce.300ms="search"
				icon="fa-solid fa-magnifying-glass" loadingTarget="search" wrapperClass="flex-1"
				placeholder="Cari pelaksana, maksud, atau nomor surat..." />
			@if ($isApprovalMode)
				{{-- Mode persetujuan tak punya baris tombol jabatan, jadi filter tahun ikut di bar ini. --}}
				<div class="w-full sm:w-40">
					<x-form.searchable-select wire:model.live="year" name="year" :options="$yearOptions"
						placeholder="Semua Tahun" searchPlaceholder="Cari tahun..." />
				</div>
			@endif
			<div class="flex items-center gap-2 shrink-0">
				<x-ui.button type="button" variant="secondary" wire:click="resetFilters" :disabled="!$hasActiveFilters">
					<x-slot:icon><i class="fa-solid fa-rotate-left text-xs text-slate-500"></i></x-slot:icon>
					Reset
				</x-ui.button>
			</div>
		</div>

		@if (!$isApprovalMode)
			{{-- Realisasi & Laporan hanya mencakup perjalanan disetujui yang sudah lewat (sesuai badge di kolom Status). --}}
			<div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
				<x-form.searchable-select wire:model.live="status" name="status" :options="$statusOptions"
					placeholder="Semua Status" searchPlaceholder="Cari status..." />
				<x-form.searchable-select wire:model.live="domain" name="domain" :options="$domainOptions"
					placeholder="Semua Domain" searchPlaceholder="Cari domain..." />
				<x-form.searchable-select wire:model.live="realisasi" name="realisasi" :options="$realisasiOptions"
					placeholder="Semua Realisasi" searchPlaceholder="Cari realisasi..." />
				<x-form.searchable-select wire:model.live="laporan" name="laporan" :options="$laporanOptions"
					placeholder="Semua Laporan" searchPlaceholder="Cari laporan..." />
			</div>
		@endif
	</div>



	{{-- Tabel Data (desktop / tablet) --}}
	<div class="dash-enter hidden md:block table-wrapper">
		{{-- min-w agar saat layar sempit/di-zoom kolom tetap lebar & wrapper scroll horizontal (bukan kolom gepeng).
		     Baris dirapatkan (py lebih kecil) khusus tabel ini tanpa mengubah .table global. --}}
		<table class="table min-w-225 [&_thead_th]:py-2.5 [&_tbody_td]:py-2">
			<thead>
				<tr>
					<th class="w-12 text-center">No.</th>
					<th>Pelaksana / Instansi</th>
					<th>Maksud Perjalanan</th>
					<th>Tanggal</th>
					<th class="w-28">Domain</th>
					<th class="w-28">Status</th>
					<th class="w-44 text-right">Aksi</th>
				</tr>
			</thead>

			<tbody>
				@forelse($sppds as $i => $sppd)
					<tr>
						<td class="text-center text-xs font-semibold text-slate-500">
							{{ $sppds->firstItem() + $i }}.
						</td>

						<td>
							<p class="font-semibold text-slate-800">
								{{ $sppd->user->name }}
							</p>

							@if (!auth()->user()->hasRole('super_admin'))
								<p class="text-[11px] text-primary-600 mt-0.5 font-medium">
									{{ $sppd->user->department?->name ?? '-' }}
								</p>
							@else
								<p class="mt-0.5 text-xs text-slate-500">
									{{ $sppd->budget?->department?->name ?? '-' }}
								</p>
							@endif
						</td>

						<td class="max-w-xs">
							<p class="line-clamp-2 font-medium leading-snug text-slate-700" title="{{ $sppd->purpose }}">
								{{ $sppd->purpose }}
							</p>

							<p class="mt-0.5 truncate text-xs text-slate-500">
								{{ $sppd->category?->name }}
								{{-- Nomor dokumen hanya dipakai Inspektorat. --}}
								@if ($sppd->showsDocumentNumber())
									·
									<span class="font-mono text-slate-500">
										{{ $sppd->document_number ?: 'Belum bernomor' }}
									</span>
								@endif
							</p>
						</td>

						<td class="whitespace-nowrap text-xs leading-normal">
							<p class="font-medium text-slate-700">
								{{ $sppd->start_date->translatedFormat('d F Y') }}
							</p>

							<p class="text-slate-500">
								s/d {{ $sppd->end_date->translatedFormat('d F Y') }}
							</p>
						</td>

						<td class="whitespace-nowrap">
							<span
								class="inline-block rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-xs font-medium text-slate-600">
								{{ $sppd->domain->shortLabel() }}
							</span>
						</td>

						<td class="whitespace-nowrap">
							@include('livewire.sppd.partials.status-cell', ['sppd' => $sppd])
						</td>

						<td class="whitespace-nowrap">
							@include('livewire.sppd.partials.row-actions', ['sppd' => $sppd, 'isApprovalMode' => $isApprovalMode])
						</td>
					</tr>

				@empty
					<tr>
						<td colspan="7" class="py-12 text-center text-slate-500">
							<div class="flex flex-col items-center justify-center gap-2">
								<i class="fa-solid fa-file-lines text-3xl text-slate-200"></i>
								<p class="text-sm">
									{{ $isApprovalMode ? 'Tidak ada SPPD yang menunggu persetujuan Anda.' : 'Belum ada data SPPD.' }}
								</p>
							</div>
						</td>
					</tr>
				@endforelse
			</tbody>
		</table>

		@if ($sppds->hasPages())
			<div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3">
				{{ $sppds->links() }}
			</div>
		@endif
	</div>

	{{-- Kartu Data (mobile) --}}
	<div class="md:hidden space-y-3">
		@forelse($sppds as $i => $sppd)
			<div class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
				<div class="absolute inset-y-0 left-0 w-1 bg-primary-500/80"></div>
				<div class="space-y-3 p-4 pl-5">
					{{-- Pelaksana + Domain --}}
					<div class="flex items-start justify-between gap-3">
						<div class="min-w-0">
							<p class="font-bold leading-tight text-slate-800">{{ $sppd->user->name }}</p>
							@if (!auth()->user()->hasRole('super_admin'))
								<p class="mt-0.5 text-[11px] font-medium text-primary-600">{{ $sppd->user->department?->name ?? '-' }}</p>
							@else
								<p class="mt-0.5 text-xs text-slate-500">{{ $sppd->budget?->department?->name ?? '-' }}</p>
							@endif
						</div>
						<span class="shrink-0 rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">
							{{ $sppd->domain->shortLabel() }}
						</span>
					</div>

					{{-- Maksud --}}
					<div>
						<p class="line-clamp-2 text-sm font-medium leading-snug text-slate-700" title="{{ $sppd->purpose }}">{{ $sppd->purpose }}</p>
						<p class="mt-1 truncate text-xs text-slate-500">
							{{ $sppd->category?->name }}@if ($sppd->showsDocumentNumber()) · <span class="font-mono">{{ $sppd->document_number ?: 'Belum bernomor' }}</span>@endif
						</p>
					</div>

					{{-- Tanggal --}}
					<div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
						<i class="fa-regular fa-calendar text-primary-500"></i>
						<span class="font-medium text-slate-600">{{ $sppd->start_date->translatedFormat('d M Y') }}</span>
						<i class="fa-solid fa-arrow-right text-[9px] text-slate-400"></i>
						<span>{{ $sppd->end_date->translatedFormat('d M Y') }}</span>
					</div>

					{{-- Status --}}
					<div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-2.5">
						<span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</span>
						<div class="flex flex-wrap gap-1">
							@include('livewire.sppd.partials.status-cell', ['sppd' => $sppd, 'stackClass' => 'items-start'])
						</div>
					</div>

					{{-- Aksi --}}
					<div class="border-t border-slate-100 pt-2.5">
						@include('livewire.sppd.partials.row-actions', ['sppd' => $sppd, 'isApprovalMode' => $isApprovalMode, 'wrapperClass' => 'flex flex-col gap-2'])
					</div>
				</div>
			</div>
		@empty
			<div class="rounded border border-slate-200 bg-white py-12 text-center shadow-sm">
				<div class="flex flex-col items-center gap-2 text-slate-500">
					<i class="fa-solid fa-file-lines text-3xl text-slate-200"></i>
					<p class="text-sm">{{ $isApprovalMode ? 'Tidak ada SPPD yang menunggu persetujuan Anda.' : 'Belum ada data SPPD.' }}</p>
				</div>
			</div>
		@endforelse

		@if ($sppds->hasPages())
			<div class="mt-1">{{ $sppds->links() }}</div>
		@endif
	</div>


</div>
