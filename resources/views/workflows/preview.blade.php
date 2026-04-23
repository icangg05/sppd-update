@extends('layouts.app')
@section('title', 'Alur Pengajuan SPPD')
@section('page-title', 'Alur Pengajuan SPPD')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Daftar Alur Pengajuan SPPD</h1>
			<p class="page-subtitle">Informasi tahapan persetujuan SPPD yang berlaku saat ini</p>
		</div>
		@if ($user->department)
			<div class="px-4 py-2 bg-slate-100 rounded-lg border border-slate-200">
				<span class="text-[10px] font-bold text-slate-500 block uppercase">Konteks Instansi Anda:</span>
				<span class="text-sm font-bold text-slate-700">{{ $user->department->name }}</span>
			</div>
		@endif
	</div>

	<div class="table-container">
		<table class="data-table">
			<thead>
				<tr>
					<th>No</th>
					<th>Nama Alur</th>
					<th>Berlaku Untuk</th>
					<th>Tujuan Perjalanan</th>
					<th>Tahapan Persetujuan</th>
				</tr>
			</thead>
			<tbody>
				@forelse($workflows as $i => $w)
					<tr>
						<td class="text-slate-400">{{ $i + 1 }}</td>
						<td>
							<p class="font-medium text-slate-900">{{ $w->name }}</p>
						</td>
						<td class="text-sm">
							<span class="block text-slate-700">Tipe Instansi: {{ $w->department_type?->label() ?? 'Semua' }}</span>
							<span class="block text-slate-500">Jabatan Pemohon: {{ $w->applicant_role ?? 'Semua' }}</span>
						</td>
						<td>
							@if(is_array($w->destination) && count($w->destination) > 0)
								<div class="flex flex-wrap gap-1">
									@foreach($w->destination as $d)
										<span class="badge bg-blue-50 text-blue-700 border border-blue-100 text-[10px]">
											{{ \App\Enums\SppdDomain::tryFrom($d)?->label() ?? $d }}
										</span>
									@endforeach
								</div>
							@else
								<span class="badge bg-slate-100 text-slate-500">Semua</span>
							@endif
						</td>
						<td>
							<div class="flex flex-wrap gap-4 items-center">
								@foreach ($w->steps as $idx => $role)
									<div class="flex items-center">
										<div class="flex flex-col items-start">
											<span class="text-[10px] font-bold text-slate-400 mb-0.5">STEP {{ $idx + 1 }}</span>
											<span
												class="uppercase badge bg-white border border-slate-200 text-slate-700 shadow-sm">{{ ucwords(str_replace('_', ' ', $role)) }}</span>

											@if (isset($roleMapping[$role]))
												<span class="text-[11px] text-emerald-600 font-medium mt-1">
													<svg class="w-3 h-3 inline mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
														stroke-width="3">
														<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
													</svg>
													{{ $roleMapping[$role] }}
												</span>
											@else
												<span class="text-[11px] text-rose-500 font-bold mt-1 uppercase flex items-center">
													<svg class="w-3.5 h-3.5 inline mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
														<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
													</svg>
													Data Pejabat Kosong!
												</span>
											@endif
										</div>
										@if (!$loop->last)
											<svg class="w-4 h-4 text-slate-300 mx-1 mt-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
												stroke-width="2">
												<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
											</svg>
										@endif
									</div>
								@endforeach
							</div>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="5" class="text-center py-12 text-slate-400">Belum ada alur pengajuan yang didefinisikan.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	<div class="mt-8 card p-6 bg-blue-50 border-blue-100">
		<h3 class="text-lg font-bold text-blue-900 mb-2 flex items-center">
			<svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
			</svg>
			Tentang Alur Pengajuan
		</h3>
		<p class="text-blue-800 text-sm leading-relaxed">
			Sistem akan memilih alur yang paling spesifik berdasarkan profil Anda (instansi dan jabatan) serta tujuan perjalanan
			dinas Anda.
			Jika tidak ditemukan alur yang spesifik, sistem akan menggunakan alur umum (jika tersedia).
			Pastikan data pejabat (approver) di instansi Anda sudah lengkap agar pengajuan tidak terhambat.
		</p>
	</div>
@endsection
