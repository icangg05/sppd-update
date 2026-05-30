@extends('layouts.app')
@section('title', 'Workflow SPPD')
@section('page-title', 'Workflow SPPD')

@section('content')
	<div class="p-1 space-y-4">

		{{-- Header Halaman Compact --}}
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
			<div class="flex items-center gap-2.5">
				<div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
					<i class="fa-solid fa-route text-base"></i>
				</div>
				<div>
					<h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Workflow SPPD</h1>
					<p class="text-[11px] text-slate-500 font-medium">Atur alur dan tahapan persetujuan dokumen SPPD secara dinamis</p>
				</div>
			</div>

			<a href="{{ route('master.workflows.create') }}"
				class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
				<i class="fa-solid fa-plus text-[10px]"></i>
				Tambah Workflow
			</a>
		</div>

		{{-- Table Container --}}
		<div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full text-left whitespace-nowrap border-collapse">
					<thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
						<tr>
							<th class="py-2.5 px-3 w-12 text-center">No.</th>
							<th class="py-2.5 px-4">Nama Workflow</th>
							<th class="py-2.5 px-4">Instansi & Jabatan</th>
							<th class="py-2.5 px-4">Tujuan Wilayah</th>
							<th class="py-2.5 px-4">Alur Tahapan Persetujuan (Steps)</th>
							<th class="py-2.5 px-4 w-20 text-center">Status</th>
							<th class="py-2.5 px-4 w-24 text-center">Aksi</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
						@forelse($workflows as $i => $w)
							<tr class="hover:bg-slate-50/50 transition-colors">
								<td class="py-2.5 px-3 text-center text-slate-400 font-medium">
									{{ $i + 1 }}.
								</td>

								<td class="py-2.5 px-4">
									<span class="font-bold text-slate-900 tracking-wide">{{ $w->name }}</span>
								</td>

								<td class="py-2.5 px-4">
									<div class="space-y-1 text-[11px]">
										<span class="block text-slate-700 font-medium">
											<i class="fa-solid fa-building text-slate-400 mr-1 text-[10px]"></i>Tipe Instansi:
											@if (is_array($w->department_type) && count($w->department_type) > 0)
												<div class="flex flex-wrap gap-1 mt-0.5 ml-4">
													@foreach ($w->department_type as $dt)
														<span
															class="inline-flex items-center rounded bg-slate-100 px-1 py-0.5 text-[9px] font-bold text-slate-600 border border-slate-200">
															{{ \App\Enums\DepartmentType::tryFrom($dt)?->label() ?? $dt }}
														</span>
													@endforeach
												</div>
											@else
												Semua
											@endif
										</span>
										<span class="block text-slate-500">
											<i class="fa-solid fa-user-tag text-slate-400 mr-1 text-[10px]"></i>Pemohon:
											@if (is_array($w->applicant_role) && count($w->applicant_role) > 0)
												<div class="flex flex-wrap gap-1 mt-0.5 ml-4">
													@foreach ($w->applicant_role as $role)
														<span
															class="inline-flex items-center rounded bg-cyan-50 px-1 py-0.5 text-[9px] font-bold text-cyan-700 border border-cyan-100/70">
															{{ $roleLabels[$role] ?? $role }}
														</span>
													@endforeach
												</div>
											@else
												Semua
											@endif
										</span>
									</div>
								</td>

								<td class="py-2.5 px-4">
									@if (is_array($w->destination) && count($w->destination) > 0)
										<div class="flex flex-wrap gap-1">
											@foreach ($w->destination as $d)
												<span
													class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 border border-blue-100/70 uppercase">
													{{ \App\Enums\SppdDomain::tryFrom($d)?->label() ?? $d }}
												</span>
											@endforeach
										</div>
									@else
										<span
											class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 border border-slate-200">
											Semua
										</span>
									@endif
								</td>

								<td class="py-2.5 px-4">
									<div class="flex flex-wrap gap-1 items-center">
										@foreach ($w->steps as $idx => $role)
											<span
												class="inline-flex items-center rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[11px] text-slate-700 font-medium">
												<span class="text-cyan-600 font-bold mr-1">{{ $idx + 1 }}.</span>
												{{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role)) }}
											</span>
											@if (!$loop->last)
												<i class="fa-solid fa-chevron-right text-[10px] text-slate-300 mx-0.5"></i>
											@endif
										@endforeach
									</div>
								</td>

								<td class="py-2.5 px-4 text-center">
									@if ($w->is_active)
										<span
											class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200 uppercase">
											Aktif
										</span>
									@else
										<span
											class="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200 uppercase">
											Nonaktif
										</span>
									@endif
								</td>

								<td class="py-2.5 px-4 text-center">
									<div class="flex items-center justify-center gap-1">
										{{-- Tombol Edit --}}
										<a href="{{ route('master.workflows.edit', $w->id) }}"
											class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-colors"
											title="Edit">
											<i class="fa-solid fa-pen-to-square text-[10px]"></i>
										</a>

										{{-- Tombol Hapus Form --}}
										<form action="{{ route('master.workflows.destroy', $w->id) }}" method="POST" class="inline m-0"
											onsubmit="return confirm('Yakin ingin menghapus pengaturan workflow ini?')">
											@csrf @method('DELETE')
											<button type="submit"
												class="rounded border border-slate-200 bg-white p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
												title="Hapus">
												<i class="fa-solid fa-trash-can text-[10px]"></i>
											</button>
										</form>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="7" class="py-10 text-center text-slate-400">
									<div class="flex flex-col items-center justify-center gap-1.5">
										<i class="fa-solid fa-diagram-project text-2xl opacity-40"></i>
										<p class="font-medium">Belum ada pengaturan alur urutan workflow yang tersimpan</p>
									</div>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>

	</div>
@endsection
