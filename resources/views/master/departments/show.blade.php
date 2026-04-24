@extends('layouts.app')
@section('title', 'Detail Profil OPD')
@section('page-title', 'Detail Profil OPD')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Profil OPD</h1>
			<p class="page-subtitle">Detail informasi instansi atau unit kerja</p>
		</div>
		<div class="flex gap-2">
			@if (auth()->user()->hasRole('super_admin'))
				<a href="{{ route('master.departments.index') }}" class="btn-secondary">
					<svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
					</svg>
					Kembali
				</a>
			@endif
			<a href="{{ route('master.departments.edit', $department->id) }}" class="btn-primary">
				<svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round"
						d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
				</svg>
				Edit Profil
			</a>
		</div>
	</div>

	<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
		{{-- Info Utama --}}
		<div class="md:col-span-2 space-y-6">
			<div class="card p-6">
				<h3 class="text-lg font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Informasi Instansi</h3>

				<div class="space-y-4">
					@if (!$department->parent_id)
						<div>
							<label class="block text-sm font-medium text-slate-500 mb-1">Kode OPD</label>
							<p class="text-base font-mono text-slate-900 bg-slate-50 p-2 rounded border border-slate-100 inline-block">
								{{ $department->code }}</p>
						</div>
					@endif

					<div>
						<label class="block text-sm font-medium text-slate-500 mb-1">Nama Instansi / Unit Kerja</label>
						<p class="text-lg font-medium text-slate-900">{{ $department->name }}</p>
					</div>

					@if (!$department->parent_id)
						<div>
							<label class="block text-sm font-medium text-slate-500 mb-1">Tipe Instansi</label>
							<span
								class="badge bg-primary-50 text-primary-700 border border-primary-100">{{ $department->type->label() }}</span>
						</div>
					@endif

					@if ($department->parent)
						<div>
							<label class="block text-sm font-medium text-slate-500 mb-1">Induk Instansi</label>
							<p class="text-base text-slate-700">{{ $department->parent->name }}</p>
						</div>
					@endif

					{{-- Kop Surat --}}
					@php $inheritedKop = $department->getInheritedLetterhead(); @endphp
					@if ($inheritedKop)
						<div>
							<label class="block text-sm font-medium text-slate-500 mb-1 flex items-center gap-2">
								Kop Surat
								@if (empty($department->letterhead))
									<span class="badge bg-amber-50 text-amber-700 border-amber-200 text-[10px]">Warisan dari Induk</span>
								@endif
							</label>
							<div
								class="p-4 bg-slate-50 rounded-lg border border-slate-200 font-serif whitespace-pre-line text-center leading-tight">
								<img src="{{ asset('storage/' . $inheritedKop) }}" alt="kop-surat">
							</div>
						</div>
					@elseif(!$department->parent_id)
						<div>
							<label class="block text-sm font-medium text-slate-500 mb-1">Kop Surat</label>
							<p class="text-sm text-slate-400 italic">Belum ada kop surat yang diatur.</p>
						</div>
					@endif
				</div>
			</div>
		</div>

		{{-- Info Pimpinan & Statistik --}}
		<div class="space-y-6">
			<div class="card p-6 border-t-4 border-t-primary-500">
				<h3 class="text-lg font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Pimpinan Unit</h3>

				@if ($department->head)
					<div class="flex items-start gap-4">
						<div
							class="w-12 h-12 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-bold text-lg shrink-0">
							{{ strtoupper(substr($department->head->name, 0, 1)) }}
						</div>
						<div>
							<p class="font-medium text-slate-900">{{ $department->head->name }}</p>
							<p class="text-sm text-slate-500 mb-1">{{ $department->head->position_name ?? 'Pimpinan' }}</p>
							<div class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-mono">
								<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
									<path stroke-linecap="round" stroke-linejoin="round"
										d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
								</svg>
								NIP: {{ $department->head->nip ?? '-' }}
							</div>
						</div>
					</div>
				@else
					<div class="text-center py-6">
						<div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
							<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
							</svg>
						</div>
						<p class="text-sm text-slate-500 mb-3">Pimpinan belum ditentukan.</p>
						<a href="{{ route('master.departments.edit', $department->id) }}"
							class="text-sm font-medium text-primary-600 hover:text-primary-700">Atur Pimpinan &rarr;</a>
					</div>
				@endif
			</div>

			@if (!$department->parent_id)
				<div class="card p-6">
					<h3 class="text-sm font-semibold text-slate-800 mb-4 uppercase tracking-wider text-slate-500">Statistik OPD</h3>
					<div class="space-y-4">
						<div class="flex items-center justify-between p-3 bg-slate-50 rounded border border-slate-100">
							<div class="flex items-center gap-3 text-slate-600">
								<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
									<path stroke-linecap="round" stroke-linejoin="round"
										d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
								</svg>
								<span class="font-medium">Total Pegawai</span>
							</div>
							<span class="text-lg font-bold text-slate-900">{{ $department->users->count() }}</span>
						</div>
					</div>
				</div>
			@endif
		</div>
	</div>
@endsection
