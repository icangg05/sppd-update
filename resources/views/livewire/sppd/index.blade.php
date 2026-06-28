<div class="flex flex-col gap-4 p-1">

	@if ($errorMessage)
		<div
			class="flex items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-xs text-red-800 shadow-sm transition-all duration-300">
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
		icon="fa-solid fa-route text-cyan-600">
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
														class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
														Aktif
													</span>
												@else
													<span
														class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
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
				class="w-full rounded-lg border border-slate-300 bg-white py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 cursor-pointer">
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
				class="flex-1 rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 disabled:opacity-50">
				Batal
			</button>
			<button type="button" wire:click="deleteSppd" wire:loading.attr="disabled" wire:target="deleteSppd"
				class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 py-2.5 text-xs font-bold text-white shadow transition hover:bg-rose-700 disabled:opacity-50">
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
	<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
		<div class="leading-tight">
			@if ($isApprovalMode)
				<h1 class="text-lg font-bold text-slate-800">Persetujuan</h1>
				<p class="text-xs text-slate-500 mt-0.5">Daftar SPPD yang menunggu persetujuan Anda</p>
			@else
				<h1 class="text-lg font-bold text-slate-800 flex flex-wrap items-center gap-2">
					<span>Daftar SPPD</span>
					<span class="text-slate-300 font-normal">|</span>
					<span class="text-cyan-600 underline">{{ $activeFilterLabel }}</span>
				</h1>
				<p class="text-xs text-slate-500 mt-0.5">Kelola semua pengajuan perjalanan dinas secara real-time</p>
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
					<div class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-lg border border-slate-200">
						<button wire:click="filterByJabatan('')"
							class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200 {{ $jabatan === '' ? 'bg-white text-cyan-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
							Semua Jabatan
						</button>

						@foreach ($jabatanTabs as $tab)
							<button wire:click="filterByJabatan('{{ $tab }}')"
								class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200 {{ $jabatan === $tab ? 'bg-white text-cyan-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
								{{ $jabatanLabels[$tab] ?? $tab }}
							</button>
						@endforeach
					</div>
				@endif

				@if (auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
					<x-ui.button href="{{ route('sppd.create') }}" wire:navigate
						class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-700 justify-center">
						<x-slot name="icon">
							<i class="fa-solid fa-plus text-xs"></i>
						</x-slot>
						Buat SPPD
					</x-ui.button>
				@endif
			</div>
		@endif
	</div>

	{{-- Bar Filter --}}
	<div class="rounded border border-slate-200 bg-white p-4 shadow-md">
		<div class="flex flex-col gap-3 sm:flex-row">
			<div class="flex-1">
				<x-form.input
					name="search"
					wire:model.live.debounce.300ms="search"
					placeholder="Cari pelaksana, maksud, atau nomor surat..."
					wrapperClass="w-full" />
			</div>
			@if (!$isApprovalMode)
				<div class="w-full sm:w-44">
					<select name="status" wire:model.live="status"
						class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500">
						<option value="">Semua Status</option>
						@foreach ($statuses as $st)
							<option value="{{ $st->value }}">{{ \App\Helpers\SmartTitle::convert($st->label()) }}</option>
						@endforeach
					</select>
				</div>
				<div class="w-full sm:w-44">
					<select name="domain" wire:model.live="domain"
						class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500">
						<option value="">Semua Domain</option>
						@foreach ($domains as $dom)
							<option value="{{ $dom->value }}">{{ $dom->label() }}</option>
						@endforeach
					</select>
				</div>
			@endif

			<div class="flex items-center gap-2 shrink-0">
				@if (
					$isApprovalMode
						? $search !== ''
						: $search !== '' || $status !== '' || $domain !== '' || $jabatan !== '' || $filter !== '')
					<button type="button" wire:click="resetFilters"
						class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
						<i class="fa-solid fa-rotate-left text-xs text-slate-400"></i>
						Reset
					</button>
				@else
					<button type="button" disabled
						class="inline-flex items-center gap-2 rounded border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-300 cursor-not-allowed">
						<i class="fa-solid fa-rotate-left text-xs text-slate-300"></i>
						Reset
					</button>
				@endif
			</div>
		</div>
	</div>



	{{-- Tabel Data --}}
	<div class="table-wrapper">
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
					@php
						$statusBadge = match ($sppd->status->value) {
						    'draft' => ['bg' => 'bg-amber-50 border-amber-200', 'text' => 'text-amber-700', 'label' => 'Masuk'],
						    'in_progress' => $sppd->revision_note
						        ? ['bg' => 'bg-orange-50 border-orange-200', 'text' => 'text-orange-700', 'label' => 'Revisi']
						        : ['bg' => 'bg-blue-50 border-blue-200', 'text' => 'text-blue-700', 'label' => 'Proses'],
						    'approved', 'completed', 'verified', 'signed' => [
						        'bg' => 'bg-emerald-50 border-emerald-200',
						        'text' => 'text-emerald-700',
						        'label' => 'Selesai',
						    ],
						    'rejected' => ['bg' => 'bg-red-50 border-red-200', 'text' => 'text-red-700', 'label' => 'Ditolak'],
						    'pending', 'revision' => [
						        'bg' => 'bg-orange-50 border-orange-200',
						        'text' => 'text-orange-700',
						        'label' => 'Revisi',
						    ],
						    'returned' => ['bg' => 'bg-pink-50 border-pink-200', 'text' => 'text-pink-700', 'label' => 'Kembali'],
						    default => [
						        'bg' => 'bg-slate-50 border-slate-200',
						        'text' => 'text-slate-700',
						        'label' => $sppd->status->label(),
						    ],
						};

						$allGreen = false;
						if (in_array($sppd->status->value, ['approved', 'completed'])) {
						    $isPastEndDate = today()->greaterThan($sppd->end_date);
						    $isRealized = $sppd->actualExpenses->isNotEmpty() && $sppd->costDetails->isNotEmpty();
						    $isReported =
						        $sppd->report &&
						        $sppd->report->report_date &&
						        $sppd->report->report_file &&
						        $sppd->report->documentation_file;

						    $allGreen = $isPastEndDate && $isRealized && $isReported;
						}
					@endphp

					<tr>
						<td class="text-center text-xs font-semibold text-slate-400">
							{{ $sppds->firstItem() + $i }}.
						</td>

						<td>
							<p class="font-semibold text-slate-800">
								{{ $sppd->user->name }}
							</p>

							@if (!auth()->user()->hasRole('super_admin'))
								<p class="text-[11px] text-cyan-600 mt-0.5 font-medium">
									{{ $sppd->user->department?->name ?? '-' }}
								</p>
							@else
								<p class="mt-0.5 text-xs text-slate-400">
									{{ $sppd->budget?->department?->name ?? '-' }}
								</p>
							@endif
						</td>

						<td class="max-w-xs">
							<p class="truncate font-medium text-slate-700" title="{{ $sppd->purpose }}">
								{{ $sppd->purpose }}
							</p>

							<p class="mt-0.5 truncate text-xs text-slate-400">
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

							<p class="text-slate-400">
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
							@if (in_array($sppd->status->value, ['approved', 'completed']))
								@if (today()->lessThanOrEqualTo($sppd->end_date))
									<span
										class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide">
										Perjalanan Disetujui
									</span>
								@else
									<div class="flex flex-col gap-1 w-[180px]">
										<span
											class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide text-center whitespace-normal leading-tight">
											Perjalanan Selesai dan Masukkan Laporan
										</span>

										@if ($sppd->actualExpenses->isNotEmpty() && $sppd->costDetails->isNotEmpty())
											<span
												class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide text-center">
												Sudah Realisasi
											</span>
										@else
											<span
												class="inline-block rounded border border-red-200 bg-red-50 px-1.5 py-0.5 text-[9px] font-bold text-red-700 tracking-wide text-center">
												Belum Realisasi
											</span>
										@endif

										@if ($sppd->report && $sppd->report->report_date && $sppd->report->report_file && $sppd->report->documentation_file)
											<span
												class="inline-block rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 tracking-wide text-center">
												Sudah Upload Laporan
											</span>
										@else
											<span
												class="inline-block rounded border border-red-200 bg-red-50 px-1.5 py-0.5 text-[9px] font-bold text-red-700 tracking-wide text-center">
												Belum Upload Laporan
											</span>
										@endif
									</div>
								@endif
							@else
								<span
									class="inline-block rounded border px-1.5 py-0.5 text-[9px] font-bold tracking-wide {{ $statusBadge['bg'] }} {{ $statusBadge['text'] }}">
									{{ \App\Helpers\SmartTitle::convert($statusBadge['label']) }}
								</span>
							@endif
						</td>

						<td class="whitespace-nowrap">
							<div class="flex flex-col gap-1 w-[115px]">
								<a
									href="{{ $isApprovalMode ? route('sppd.show', ['sppd' => $sppd, 'from' => 'approval']) : route('sppd.show', $sppd) }}"
									wire:navigate
									class="inline-flex items-center justify-center gap-1.5 rounded border border-slate-300 bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-200 w-full text-center">
									<i class="fa-solid fa-eye text-[10px] text-slate-500"></i>
									<span>Lihat</span>
								</a>

								@if ($sppd->status->value === 'in_progress' && $sppd->revision_note && auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
									<a
										href="{{ route('sppd.create.details', ['sppd_id' => $sppd->id]) }}"
										wire:navigate
										class="inline-flex items-center justify-center gap-1.5 rounded border border-amber-300 bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700 shadow-2xs transition hover:bg-amber-100 w-full text-center">
										<i class="fa-solid fa-pen-to-square text-[10px] text-amber-600"></i>
										<span>Edit Perbaikan</span>
									</a>
								@endif

								@if (in_array($sppd->status->value, ['approved', 'completed']))
									<a
										href="{{ route('sppd.next', $sppd) }}" wire:navigate
										class="inline-flex items-center justify-center gap-1.5 rounded bg-cyan-600 px-2 py-1 text-[10px] font-bold text-white shadow-2xs transition hover:bg-cyan-700 w-full text-center">
										<span>Selanjutnya</span>
										<i class="fa-solid fa-arrow-right text-[10px]"></i>
									</a>

									@if (auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
										@if ($allGreen)
											<button
												type="button"
												wire:click="startSppdLanjutan({{ $sppd->id }})"
												wire:loading.attr="disabled"
												class="inline-flex items-center justify-center gap-1.5 rounded bg-emerald-600 px-2 py-1 text-[10px] font-bold text-white shadow-2xs transition hover:bg-emerald-700 w-full text-center disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
												<i class="fa-solid fa-plus text-[10px]" wire:loading.remove
													wire:target="startSppdLanjutan({{ $sppd->id }})"></i>
												<i class="fa-solid fa-circle-notch fa-spin text-[10px]" wire:loading
													wire:target="startSppdLanjutan({{ $sppd->id }})"></i>
												<span>SPPD Lanjutan</span>
											</button>
										@endif
									@endif
								@endif

								@if (auth()->user()->hasRole('super_admin'))
									{{-- Super admin dapat menghapus SPPD apa pun secara permanen. --}}
									<button
										type="button"
										wire:click="confirmDelete({{ $sppd->id }})"
										title="Hapus SPPD"
										class="inline-flex items-center justify-center gap-1.5 rounded border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-600 transition hover:bg-red-100 w-full text-center">
										<i class="fa-solid fa-trash text-[10px]"></i>
										<span>Hapus</span>
									</button>
								@elseif ($sppd->status->value === 'in_progress' && auth()->user()->hasRole('admin_opd'))
									<button
										type="button"
										wire:click="confirmDelete({{ $sppd->id }})"
										title="Batalkan Pengajuan"
										class="inline-flex items-center justify-center gap-1.5 rounded border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-600 transition hover:bg-red-100 w-full text-center">
										<i class="fa-solid fa-trash text-[10px]"></i>
										<span>Batalkan</span>
									</button>
								@endif
							</div>
						</td>
					</tr>

				@empty
					<tr>
						<td colspan="7" class="py-12 text-center text-slate-400">
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

</div>
