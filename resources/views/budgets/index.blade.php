@extends('layouts.app')

@section('title', 'DPA - Data Anggaran')
@section('page-title', 'Dokumen Pelaksanaan Anggaran (DPA)')

@section('content')
	<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
		<div class="flex items-center gap-3">
			<div class="p-2 bg-emerald-100 rounded-lg">
				<svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
						d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
				</svg>
			</div>
			<div>
				<h2 class="text-xl font-bold text-slate-900">Daftar Anggaran</h2>
				<p class="text-sm text-slate-500">Tahun Anggaran {{ $year }}</p>
			</div>
		</div>

		<div class="flex items-center gap-2">
			<button onclick="window.location.reload()" class="btn-secondary flex items-center gap-2">
				<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
						d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
				</svg>
				Refresh
			</button>
			@can('budget.create')
				<a href="{{ route('master.budgets.create') }}" class="btn-primary flex items-center gap-2">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
					</svg>
					Tambah Data
				</a>
			@endcan
		</div>
	</div>

	<div class="table-container bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
		{{-- Filter Header --}}
		<div class="p-4 border-b border-slate-100 bg-slate-50/50">
			<form action="{{ route('master.budgets.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
				<div class="flex-1 min-w-[200px]">
					<div class="relative">
						<input type="text" name="search" value="{{ request('search') }}"
							placeholder="Cari program, kegiatan, atau uraian..."
							class="w-full pl-10 pr-4 py-2 rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
						<div class="absolute left-3 top-2.5 text-slate-400">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
							</svg>
						</div>
					</div>
				</div>

				<div class="w-full md:w-auto">
					<select name="year" onchange="this.form.submit()"
						class="w-full py-2 rounded-lg border-slate-200 text-sm focus:border-emerald-500">
						@for ($y = date('Y'); $y >= 2019; $y--)
							<option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
						@endfor
					</select>
				</div>

				<button type="submit"
					class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
					Go
				</button>
			</form>
		</div>

		{{-- Table --}}
		<div class="overflow-x-auto">
			<table class="w-full text-left border-collapse">
				<thead>
					<tr class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold tracking-wider">
						<th class="px-3 py-4 border-b">No.</th>
						<th class="px-6 py-4 border-b">SKPD</th>
						<th class="px-6 py-4 border-b">Tahun</th>
						<th class="px-6 py-4 border-b">Program / Kegiatan</th>
						<th class="px-6 py-4 border-b">Kode Rekening</th>
						<th class="px-6 py-4 border-b">Uraian</th>
						<th class="px-6 py-4 border-b text-right">Total Anggaran</th>
						<th class="px-6 py-4 border-b text-right">Sisa</th>
						<th class="px-6 py-4 border-b text-center">Aksi</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					@forelse($budgets as $budget)
						<tr class="hover:bg-slate-50/80 transition-colors">
							<td class="px-3 py-4 align-top">
								<span class="text-xs font-bold text-slate-700">{{ $loop->iteration }}.</span>
							</td>
							<td class="px-6 py-4 align-top">
								<span class="text-xs font-bold text-slate-700">{{ $budget->department->name }}</span>
							</td>
							<td class="px-6 py-4 align-top text-sm">{{ $budget->year }}</td>
							<td class="px-6 py-4 align-top max-w-xs">
								<div class="text-xs font-bold text-emerald-700 mb-1">{{ $budget->program }}</div>
								<div class="text-[11px] text-slate-500 leading-relaxed">{{ $budget->activity }}</div>
							</td>
							<td class="px-6 py-4 align-top">
								<code
									class="bg-slate-100 px-2 py-1 rounded text-[11px] font-mono text-slate-600">{{ $budget->account_code }}</code>
							</td>
							<td class="px-6 py-4 align-top">
								<div class="text-sm text-slate-700 font-medium">{{ $budget->description }}</div>
							</td>
							<td class="px-6 py-4 align-top text-right font-semibold text-slate-900 whitespace-nowrap">
								{{ number_format($budget->total_amount, 0, ',', '.') }}
							</td>
							<td
								class="px-6 py-4 align-top text-right font-medium {{ $budget->balance < 0 ? 'text-red-600' : 'text-emerald-600' }} whitespace-nowrap">
								{{ number_format($budget->balance, 0, ',', '.') }}
							</td>
							<td class="px-6 py-4 align-top text-center whitespace-nowrap">
								<div class="flex flex-col gap-1">
									<a href="{{ route('master.budgets.show', $budget->id) }}"
										class="px-3 py-1 bg-blue-600 text-white text-[10px] font-bold rounded uppercase hover:bg-blue-700 text-center block">Detail</a>
									@can('budget.edit')
										<a href="{{ route('master.budgets.edit', $budget->id) }}"
											class="px-3 py-1 bg-amber-500 text-white text-[10px] font-bold rounded uppercase hover:bg-amber-600 text-center block">Edit</a>
									@endcan
									@can('budget.delete')
										<form action="{{ route('master.budgets.destroy', $budget->id) }}" method="POST" class="inline-block w-full"
											onsubmit="return confirm('Apakah Anda yakin ingin menghapus data anggaran ini?');">
											@csrf
											@method('DELETE')
											<button type="submit"
												class="w-full px-3 py-1 bg-red-600 text-white text-[10px] font-bold rounded uppercase hover:bg-red-700 text-center">Hapus</button>
										</form>
									@endcan
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="9" class="px-6 py-12 text-center text-slate-400">
								<div class="flex flex-col items-center gap-2">
									<svg class="w-12 h-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
											d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
									</svg>
									<span>Belum ada data anggaran ditemukan.</span>
								</div>
							</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		@if ($budgets->hasPages())
			<div class="p-4 border-t border-slate-100">
				{{ $budgets->links() }}
			</div>
		@endif
	</div>
@endsection
