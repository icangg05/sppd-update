@extends('layouts.app')

@section('title', 'DPA - Data Anggaran')
@section('page-title', 'Dokumen Pelaksanaan Anggaran (DPA)')

@section('content')
	<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
		<div class="flex items-center gap-3">
			<div class="p-2 bg-emerald-100 rounded">
				<i class="fa-solid fa-file-lines text-emerald-600 text-xl"></i>
			</div>
			<div>
				<h2 class="text-xl font-bold text-slate-900">Daftar Anggaran</h2>
				<p class="text-sm text-slate-500">Tahun Anggaran {{ $year }}</p>
			</div>
		</div>

		<div class="flex items-center gap-2">
			<x-ui.button type="button" variant="secondary" onclick="window.location.reload()" class="flex items-center gap-2">
				<x-slot name="icon">
					<i class="fa-solid fa-arrows-rotate"></i>
				</x-slot>
				Refresh
			</x-ui.button>
			@can('budget.create')
				<x-ui.button href="{{ route('master.budgets.create') }}" class="flex items-center gap-2">
					<x-slot name="icon">
						<i class="fa-solid fa-plus"></i>
					</x-slot>
					Tambah Data
				</x-ui.button>
			@endcan
		</div>
	</div>

	<div class="table-container bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
		{{-- Filter Header --}}
		<div class="p-4 border-b border-slate-100 bg-slate-50/50">
			<form action="{{ route('master.budgets.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
				<div class="flex-1 min-w-50">
					<div class="relative">
						<x-form.input
							name="search"
							:value="request('search')"
							placeholder="Cari program, kegiatan, atau uraian..."
							class="pl-10"
							wrapperClass="w-full"
						/>
						<div class="absolute left-3 top-2.5 text-slate-400">
							<i class="fa-solid fa-magnifying-glass"></i>
						</div>
					</div>
				</div>

				<x-form.select name="year" wrapperClass="w-full md:w-auto" class="w-full">
					@for ($y = date('Y'); $y >= 2019; $y--)
						<option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
					@endfor
				</x-form.select>

				<x-ui.button type="submit" class="px-6">
					Go
				</x-ui.button>
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
								<x-ui.button href="{{ route('master.budgets.show', $budget->id) }}" variant="secondary" class="text-[10px] font-bold uppercase px-3 py-1 block w-full">
									Detail
								</x-ui.button>
								@can('budget.edit')
									<x-ui.button href="{{ route('master.budgets.edit', $budget->id) }}" variant="warning" class="text-[10px] font-bold uppercase px-3 py-1 block w-full">
										Edit
									</x-ui.button>
								@endcan
								@can('budget.delete')
									<form action="{{ route('master.budgets.destroy', $budget->id) }}" method="POST" class="inline-block w-full"
										onsubmit="return confirm('Apakah Anda yakin ingin menghapus data anggaran ini?');">
										@csrf
										@method('DELETE')
										<x-ui.button type="submit" variant="danger" class="w-full text-[10px] font-bold uppercase px-3 py-1">
											Hapus
										</x-ui.button>
										</form>
									@endcan
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="9" class="px-6 py-12 text-center text-slate-400">
								<div class="flex flex-col items-center gap-2">
									<i class="fa-solid fa-file-circle-xmark fa-2x text-slate-200"></i>
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
