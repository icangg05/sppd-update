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
			<a href="{{ route('sppd.create') }}" class="btn-primary">
				<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
				</svg>
				Buat SPPD
			</a>
		@endcan
	</div>

	{{-- Filters --}}
	<div class="card p-4 mb-4">
		<form method="GET" action="{{ route('sppd.index') }}" class="flex flex-col sm:flex-row gap-3">
			<div class="flex-1">
				<input type="text" name="search" value="{{ request('search') }}" class="form-input"
					placeholder="Cari pelaksana, maksud, atau nomor surat...">
			</div>
			<select name="status" class="form-select w-full sm:w-40">
				<option value="">Semua Status</option>
				@foreach ($statuses as $status)
					<option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
						{{ $status->label() }}</option>
				@endforeach
			</select>
			<select name="domain" class="form-select w-full sm:w-40">
				<option value="">Semua Domain</option>
				@foreach ($domains as $domain)
					<option value="{{ $domain->value }}" {{ request('domain') === $domain->value ? 'selected' : '' }}>
						{{ $domain->label() }}</option>
				@endforeach
			</select>
			<button type="submit" class="btn-secondary">
				<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round"
						d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
				</svg>
				Filter
			</button>
			@if (request()->hasAny(['search', 'status', 'domain']))
				<a href="{{ route('sppd.index') }}" class="btn-ghost">Reset</a>
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
					<tr>
						<td class="text-slate-400">{{ $sppds->firstItem() + $i }}</td>
						<td>
							<p class="font-medium text-slate-900">{{ $sppd->user->name }}</p>
							<p class="text-xs text-slate-400">{{ $sppd->budget?->department?->name ?? '-' }}</p>
						</td>
						<td class="max-w-[250px]">
							<p class="truncate font-medium">{{ $sppd->purpose }}</p>
							<p class="text-xs text-slate-400">{{ $sppd->category?->name }} ·
								{{ $sppd->document_number ?? 'Belum bernomor' }}</p>
						</td>
						<td class="whitespace-nowrap text-xs">
							<p>{{ $sppd->start_date->format('d M Y') }}</p>
							<p class="text-slate-400">s/d {{ $sppd->end_date->format('d M Y') }}</p>
						</td>
						<td>
							<span class="badge bg-slate-100 text-slate-700">{{ $sppd->domain->label() }}</span>
						</td>
						<td>
							<span class="badge-{{ $sppd->status->value }}">{{ $sppd->status->label() }}</span>
						</td>
						<td class="flex gap-2">
							<a href="{{ route('sppd.show', $sppd) }}" class="btn-ghost text-xs py-1 px-2">Detail →</a>
							@if($sppd->status->value === 'in_progress' && (auth()->id() === $sppd->creator_id || auth()->id() === $sppd->user_id))
								<form action="{{ route('sppd.destroy', $sppd) }}" method="POST" onsubmit="return confirm('Hapus/Batalkan pengajuan SPPD ini?')">
									@csrf
									@method('DELETE')
									<button type="submit" class="text-red-500 hover:text-red-700" title="Batalkan Pengajuan">
										<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
									</button>
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
