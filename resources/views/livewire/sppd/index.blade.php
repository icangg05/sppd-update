<div class="flex flex-col gap-4 p-1">

	@if ($errorMessage)
		<div
			class="flex items-center justify-between gap-3 rounded border border-red-200 bg-red-50 p-4 text-xs text-red-800 shadow-sm transition-all duration-300">
			<div class="flex items-start gap-2 flex-1">
				<i class="fa-solid fa-triangle-exclamation text-red-600 text-sm shrink-0 mt-0.5"></i>
				<span class="font-medium leading-relaxed">
					{{ $errorMessage }}
					@if (!empty($simulatedSteps))
						<button type="button" wire:click="$set('showWorkflowModal', true)"
							class="font-bold text-red-700 hover:text-red-950 underline cursor-pointer ml-1">
							Cek Detail Alur Pejabat
						</button>
					@endif
				</span>
			</div>
			<button type="button" wire:click="$set('errorMessage', null)"
				class="text-red-400 hover:text-red-600 shrink-0 cursor-pointer">
				<i class="fa-solid fa-xmark text-sm"></i>
			</button>
		</div>
	@endif

	{{-- Modal Detail Alur Pejabat --}}
	<x-ui.modal show="$wire.showWorkflowModal" :closeable="true" title="Detail Alur Pejabat Penandatangan"
		icon="fa-solid fa-route text-primary-600">
		@if (!empty($simulatedSteps))
			<div class="space-y-4">
				<p class="text-xs text-slate-500">Berikut adalah daftar alur persetujuan pejabat struktural untuk perjalanan dinas
					ini. Pastikan semua pejabat sudah ditentukan di unit kerja terkait.</p>

				<div class="flow-root my-2 px-1">
					<ul role="list" class="-mb-8">
						@foreach ($simulatedSteps as $idx => $step)
							<li>
								<div class="relative pb-8">
									@if ($idx !== count($simulatedSteps) - 1)
										<span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
									@endif
									<div class="relative flex space-x-3">
										<div>
											<span
												class="flex size-8 items-center justify-center rounded-full ring-8 ring-white {{ $step['status'] === 'found' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
												@if ($step['status'] === 'found')
													<i class="fa-solid fa-check text-xs"></i>
												@else
													<i class="fa-solid fa-xmark text-xs"></i>
												@endif
											</span>
										</div>
										<div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
											<div>
												<p class="text-xs font-bold text-slate-800">
													{{ $step['role_label'] }}
												</p>
												<p class="text-xs text-slate-500 mt-0.5">
													Nama: <span
														class="{{ $step['status'] === 'found' ? 'font-medium text-slate-700' : 'font-bold text-rose-600' }}">{{ $step['approver_name'] }}</span>
												</p>
											</div>
											<div class="whitespace-nowrap text-right text-[10px]">
												@if ($step['status'] === 'found')
													<span
														class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
														Aktif
													</span>
												@else
													<span
														class="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 text-[10px] font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
														Belum Diatur
													</span>
												@endif
											</div>
										</div>
									</div>
								</div>
							</li>
						@endforeach
					</ul>
				</div>
			</div>
		@endif

		<x-slot name="footer">
			<button type="button" wire:click="$set('showWorkflowModal', false)"
				class="w-full rounded border border-slate-300 bg-white py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 cursor-pointer">
				Tutup
			</button>
		</x-slot>
	</x-ui.modal>

	{{-- Modal Konfirmasi Hapus SPPD --}}
	<x-ui.modal show="$wire.showDeleteModal" :closeable="false" title="Hapus SPPD?"
		description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-triangle-exclamation text-rose-600">
		<p class="text-sm text-slate-600">
			@if (auth()->user()->hasRole('super_admin'))
				Pengajuan SPPD atas nama <strong>{{ $deleteLabel ?? '-' }}</strong> akan <strong>dihapus secara permanen</strong> beserta seluruh datanya. Lanjutkan?
			@else
				Pengajuan SPPD atas nama <strong>{{ $deleteLabel ?? '-' }}</strong> akan <strong>dibatalkan dan dihapus permanen</strong>. Lanjutkan?
			@endif
		</p>

		<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
			<button type="button" wire:click="closeDeleteModal" wire:loading.attr="disabled" wire:target="deleteSppd"
				class="flex-1 rounded border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 disabled:opacity-50">
				Batal
			</button>
			<button type="button" wire:click="deleteSppd" wire:loading.attr="disabled" wire:target="deleteSppd"
				class="flex-1 inline-flex items-center justify-center gap-2 rounded bg-rose-600 py-2.5 text-xs font-bold text-white shadow transition hover:bg-rose-700 disabled:opacity-50">
				<span wire:loading.remove wire:target="deleteSppd" class="inline-flex items-center gap-2">
					<i class="fa-solid fa-trash"></i> Ya, Hapus
				</span>
				<span wire:loading wire:target="deleteSppd" class="inline-flex items-center gap-2">
					<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...
				</span>
			</button>
		</x-slot>
	</x-ui.modal>

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
						class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-amber-700 ring-1 ring-inset ring-amber-600/15">
						<i class="fa-solid fa-clipboard-check text-[9px]"></i> Menunggu Tindakan
					</span>
					<h1 class="text-xl font-bold tracking-tight text-slate-800">Persetujuan</h1>
					<p class="mt-1 text-xs text-slate-500">Daftar SPPD yang menunggu persetujuan Anda</p>
				@else
					<span
						class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
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

	{{-- Bar Filter --}}
	<div class="dash-enter rounded border border-slate-200 bg-white p-4 shadow-sm">
		<div class="flex flex-col gap-3 sm:flex-row">
			<x-form.input name="search" wire:model.live.debounce.300ms="search"
				icon="fa-solid fa-magnifying-glass" loadingTarget="search" wrapperClass="flex-1"
				placeholder="Cari pelaksana, maksud, atau nomor surat..." />
			@if (!$isApprovalMode)
				<div class="w-full sm:w-44">
					<x-form.searchable-select wire:model.live="status" name="status" :options="$statusOptions"
						placeholder="Semua Status" searchPlaceholder="Cari status..." />
				</div>
				<div class="w-full sm:w-44">
					<x-form.searchable-select wire:model.live="domain" name="domain" :options="$domainOptions"
						placeholder="Semua Domain" searchPlaceholder="Cari domain..." />
				</div>
			@endif

			@php
				$hasActiveFilters = $isApprovalMode
					? $search !== ''
					: $search !== '' || $status !== '' || $domain !== '' || $jabatan !== '' || $filter !== '';
			@endphp
			<div class="flex items-center gap-2 shrink-0">
				<x-ui.button type="button" variant="secondary" wire:click="resetFilters" :disabled="!$hasActiveFilters">
					<x-slot:icon><i class="fa-solid fa-rotate-left text-xs text-slate-500"></i></x-slot:icon>
					Reset
				</x-ui.button>
			</div>
		</div>
	</div>



	{{-- Tabel Data (desktop / tablet) --}}
	<div class="dash-enter hidden md:block table-wrapper">
		<table class="table">
			<thead>
				<tr>
					<th class="w-12 text-center">No.</th>
					<th>Pelaksana / Instansi</th>
					<th>Maksud Perjalanan</th>
					<th>Tanggal</th>
					<th class="w-28">Domain</th>
					<th class="w-28">Status</th>
					<th class="w-40 text-right">Aksi</th>
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
							<p class="truncate font-medium text-slate-700" title="{{ $sppd->purpose }}">
								{{ $sppd->purpose }}
							</p>

							<p class="mt-0.5 truncate text-xs text-slate-500">
								{{ $sppd->category?->name }}
								·
								<span class="font-mono text-slate-500">
									{{ $sppd->document_number ?? 'Belum bernomor' }}
								</span>
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
							{{ $sppd->category?->name }} · <span class="font-mono">{{ $sppd->document_number ?? 'Belum bernomor' }}</span>
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
							@include('livewire.sppd.partials.status-cell', ['sppd' => $sppd, 'stackClass' => 'w-full'])
						</div>
					</div>

					{{-- Aksi --}}
					<div class="border-t border-slate-100 pt-2.5">
						@include('livewire.sppd.partials.row-actions', ['sppd' => $sppd, 'isApprovalMode' => $isApprovalMode, 'wrapperClass' => 'grid grid-cols-2 gap-2'])
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
