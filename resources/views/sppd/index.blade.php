@extends('layouts.app')

@section('title', 'Daftar SPPD')
@section('page-title', 'Daftar SPPD')

@section('content')
	<div class="flex flex-col gap-4 p-1">

		{{-- Header Halaman --}}
		<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
			<div class="leading-tight">
				<h1 class="text-lg font-bold text-slate-800">Daftar SPPD</h1>
				<p class="text-xs text-slate-500 mt-0.5">Kelola semua pengajuan perjalanan dinas secara real-time</p>
			</div>
			<div class="flex flex-col gap-2 sm:flex-row sm:items-center">
				<div class="relative shrink-0">
					<select id="filter-jabatan" onchange="filterByJabatan(this.value)" class="w-full sm:w-48 appearance-none rounded border border-slate-300 bg-white px-3 py-2 pr-8 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500">
						<option value="">Semua Jabatan</option>
						@if (auth()->user()->department?->type === \App\Enums\DepartmentType::DPRD || auth()->user()->department?->parent?->type === \App\Enums\DepartmentType::DPRD)
							<option value="anggota_dprd" {{ request('jabatan') === 'anggota_dprd' ? 'selected' : '' }}>Anggota DPRD</option>
							<option value="staff_dprd" {{ request('jabatan') === 'staff_dprd' ? 'selected' : '' }}>Staff DPRD</option>
							<option value="sekwan" {{ request('jabatan') === 'sekwan' ? 'selected' : '' }}>Sekwan</option>
						@else
							<option value="kepala_opd" {{ request('jabatan') === 'kepala_opd' ? 'selected' : '' }}>Kepala OPD</option>
							<option value="eselon_ii" {{ request('jabatan') === 'eselon_ii' ? 'selected' : '' }}>Eselon II</option>
							<option value="eselon_iii" {{ request('jabatan') === 'eselon_iii' ? 'selected' : '' }}>Eselon III</option>
							<option value="eselon_iv" {{ request('jabatan') === 'eselon_iv' ? 'selected' : '' }}>Eselon IV</option>
							<option value="staf" {{ request('jabatan') === 'staf' ? 'selected' : '' }}>Staf</option>
						@endif
					</select>
					<div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
						<i class="fa-solid fa-chevron-down text-xs"></i>
					</div>
				</div>

				@can('sppd.create')
					<x-ui.button href="{{ route('sppd.create') }}"
						class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-700 justify-center">
						<x-slot name="icon">
							<i class="fa-solid fa-plus text-xs"></i>
						</x-slot>
						Buat SPPD
					</x-ui.button>
				@endcan
			</div>
		</div>

		{{-- Bar Filter --}}
		<div class="rounded border border-slate-200 bg-white p-4 shadow-md">
			<form method="GET" action="{{ route('sppd.index') }}" class="flex flex-col gap-3 sm:flex-row">
				<div class="flex-1">
					<x-form.input
						name="search"
						:value="request('search')"
						placeholder="Cari pelaksana, maksud, atau nomor surat..."
						wrapperClass="w-full" />
				</div>
				<x-form.select name="status" wrapperClass="w-full sm:w-44">
					<option value="">Semua Status</option>
					@foreach ($statuses as $status)
						<option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
							{{ $status->label() }}
						</option>
					@endforeach
				</x-form.select>
				<x-form.select name="domain" wrapperClass="w-full sm:w-44">
					<option value="">Semua Domain</option>
					@foreach ($domains as $domain)
						<option value="{{ $domain->value }}" {{ request('domain') === $domain->value ? 'selected' : '' }}>
							{{ $domain->label() }}
						</option>
					@endforeach
				</x-form.select>

				<div class="flex items-center gap-2 shrink-0">
					<x-ui.button type="submit" variant="secondary"
						class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
						<x-slot name="icon">
							<i class="fa-solid fa-magnifying-glass text-xs text-slate-400"></i>
						</x-slot>
						Filter
					</x-ui.button>
					@if (request()->hasAny(['search', 'status', 'domain', 'jabatan']))
						<x-ui.button href="{{ route('sppd.index') }}" variant="ghost"
							class="text-sm font-medium text-slate-500 hover:text-slate-800 px-2 py-2">Reset</x-ui.button>
					@endif
				</div>
			</form>
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
							    'in_progress' => ['bg' => 'bg-blue-50 border-blue-200', 'text' => 'text-blue-700', 'label' => 'Proses'],
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
						@endphp

						<tr>
							<td class="text-center text-xs font-semibold text-slate-400">
								{{ $sppds->firstItem() + $i }}.
							</td>

							<td>
								<p class="font-semibold text-slate-800">
									{{ $sppd->user->name }}
								</p>

								<p class="mt-0.5 text-xs text-slate-400">
									{{ $sppd->budget?->department?->name ?? '-' }}
								</p>
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
									class="inline-block rounded-sm border border-slate-200/60 bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600">
									{{ $sppd->domain->shortLabel() }}
								</span>
							</td>

							<td class="whitespace-nowrap">
								<span
									class="inline-block rounded-sm border px-1.5 py-0.5 text-xs font-bold tracking-wide uppercase {{ $statusBadge['bg'] }} {{ $statusBadge['text'] }}">
									{{ $statusBadge['label'] }}
								</span>
							</td>

							<td class="whitespace-nowrap">
								<div class="flex items-center justify-end gap-1.5">

									<a
										href="{{ route('sppd.show', $sppd) }}"
										class="inline-flex items-center rounded border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50">
										Detail
									</a>

									@if (in_array($sppd->status->value, ['approved', 'completed']))
										<a
											href="{{ route('sppd.next', $sppd) }}"
											class="inline-flex items-center rounded bg-cyan-600 px-2.5 py-1.5 text-xs font-bold text-white shadow-2xs transition hover:bg-cyan-700">
											Selanjutnya
										</a>
									@endif

									@if ($sppd->status->value === 'in_progress' && (auth()->id() === $sppd->creator_id || auth()->id() === $sppd->user_id))
										<form
											action="{{ route('sppd.destroy', $sppd) }}"
											method="POST"
											onsubmit="return confirm('Hapus/Batalkan pengajuan SPPD ini?')"
											class="inline m-0">

											@csrf
											@method('DELETE')

											<button
												type="submit"
												title="Batalkan Pengajuan"
												class="inline-flex items-center justify-center rounded border border-red-200 bg-red-50 p-1.5 text-xs font-medium text-red-600 transition hover:bg-red-100">
												<i class="fa-solid fa-trash text-xs"></i>
											</button>
										</form>
									@endif

								</div>
							</td>
						</tr>

					@empty
						<tr>
							<td colspan="7" class="py-12 text-center text-slate-400">
								<div class="flex flex-col items-center justify-center gap-2">
									<i class="fa-solid fa-file-lines text-3xl text-slate-200"></i>
									<p class="text-sm">Belum ada data SPPD.</p>
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

	<script>
		function filterByJabatan(value) {
			const url = new URL(window.location.href);
			if (value) {
				url.searchParams.set('jabatan', value);
			} else {
				url.searchParams.delete('jabatan');
			}
			// Reset to page 1 on filter change to avoid empty pages
			url.searchParams.delete('page');
			window.location.href = url.toString();
		}
	</script>
@endsection
