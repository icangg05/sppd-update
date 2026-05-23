@extends('layouts.app')

@section('title', 'Daftar SPPD')
@section('page-title', 'Daftar SPPD')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Daftar SPPD</h1>
			<p class="page-subtitle">Kelola semua pengajuan perjalanan dinas</p>
		</div>
		@can('sppd.create')
			<x-ui.button href="{{ route('sppd.create') }}" class="flex items-center gap-2">
				<x-slot name="icon">
					<i class="fa-solid fa-plus"></i>
				</x-slot>
				Buat SPPD
			</x-ui.button>
		@endcan
	</div>

	{{-- Filters --}}
	<div class="card p-4 mb-4">
		<form method="GET" action="{{ route('sppd.index') }}" class="flex flex-col sm:flex-row gap-3">
			<div class="flex-1">
				<x-form.input
					name="search"
					:value="request('search')"
					placeholder="Cari pelaksana, maksud, atau nomor surat..."
					wrapperClass="w-full"
				/>
			</div>
			<x-form.select name="status" wrapperClass="w-full sm:w-42" class="w-full">
				<option value="">Semua Status</option>
				@foreach ($statuses as $status)
					<option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
						{{ $status->label() }}</option>
				@endforeach
			</x-form.select>
			<x-form.select name="domain" wrapperClass="w-full sm:w-45" class="w-full">
				<option value="">Semua Domain</option>
				@foreach ($domains as $domain)
					<option value="{{ $domain->value }}" {{ request('domain') === $domain->value ? 'selected' : '' }}>
						{{ $domain->label() }}</option>
				@endforeach
			</x-form.select>
			<x-ui.button type="submit" variant="secondary" class="flex items-center gap-2">
				<x-slot name="icon">
					<i class="fa-solid fa-magnifying-glass"></i>
				</x-slot>
				Filter
			</x-ui.button>
			@if (request()->hasAny(['search', 'status', 'domain']))
				<x-ui.button href="{{ route('sppd.index') }}" variant="ghost">Reset</x-ui.button>
			@endif
		</form>
	</div>

	{{-- Table --}}
	<div class="table-container">
		<table class="data-table">
			<thead>
				<tr>
					<th>No</th>
					<th>Pelaksana / Instansi</th>
					<th>Maksud Perjalanan</th>
					<th>Tanggal</th>
					<th>Domain</th>
					<th>Status</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				@forelse($sppds as $i => $sppd)
					@php
						$statusBadge = match ($sppd->status->value) {
							'draft' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700'],
							'in_progress' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-700'],
							'approved' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
							'rejected' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700'],
							'completed' => ['bg' => 'bg-violet-100', 'text' => 'text-violet-700'],
							'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
							'revision' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
							'signed' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-700'],
							'verified' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
							'returned' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-700'],
							default => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700'],
						};
					@endphp
					<tr>
						<td class="text-slate-400">{{ $sppds->firstItem() + $i }}</td>
						<td>
							<p class="font-medium text-slate-900">{{ $sppd->user->name }}</p>
							<p class="text-xs text-slate-400">{{ $sppd->budget?->department?->name ?? '-' }}</p>
						</td>
						<td class="max-w-62.5">
							<p class="truncate font-medium">{{ $sppd->purpose }}</p>
							<p class="text-xs text-slate-400">{{ $sppd->category?->name }} ·
								{{ $sppd->document_number ?? 'Belum bernomor' }}</p>
						</td>
						<td class="whitespace-nowrap text-xs">
							<p>{{ $sppd->start_date->format('d M Y') }}</p>
							<p class="text-slate-400">s/d {{ $sppd->end_date->format('d M Y') }}</p>
						</td>
						<td>
							<x-ui.badge bg="bg-slate-100" text="text-slate-700">
								{{ $sppd->domain->label() }}
							</x-ui.badge>
						</td>
						<td>
							<x-ui.badge :bg="$statusBadge['bg']" :text="$statusBadge['text']">
								{{ $sppd->status->label() }}
							</x-ui.badge>
						</td>
						<td class="flex gap-2">
							<x-ui.button href="{{ route('sppd.show', $sppd) }}" variant="secondary" class="text-xs py-1 px-2 border border-slate-200">
								Detail
							</x-ui.button>
							@if(in_array($sppd->status->value, ['approved', 'completed']))
								<x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="primary" class="text-[10px] font-bold px-3 py-1">
									Selanjutnya
								</x-ui.button>
							@endif

							@if ($sppd->status->value === 'in_progress' && (auth()->id() === $sppd->creator_id || auth()->id() === $sppd->user_id))
								<form action="{{ route('sppd.destroy', $sppd) }}" method="POST"
									onsubmit="return confirm('Hapus/Batalkan pengajuan SPPD ini?')">
									@csrf
									@method('DELETE')
									<x-ui.button type="submit" variant="danger" class="px-2 py-1.5" title="Batalkan Pengajuan">
										<x-slot name="icon">
											<i class="fa-solid fa-trash"></i>
										</x-slot>
									</x-ui.button>
								</form>
							@endif
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="7" class="text-center py-12 text-slate-400">Belum ada data SPPD</td>
					</tr>
				@endforelse
			</tbody>
		</table>

		@if ($sppds->hasPages())
			<div class="px-4 py-3 border-t border-slate-200">
				{{ $sppds->links() }}
			</div>
		@endif
	</div>
@endsection
