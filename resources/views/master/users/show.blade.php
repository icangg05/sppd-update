@extends('layouts.app')
@section('title', 'Detail Pegawai')
@section('page-title', 'Detail Pegawai')

@section('content')
	<div class="p-1 space-y-6">

		{{-- Header Halaman --}}
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
					<i class="fa-solid fa-user-check mr-2 text-cyan-600"></i>Profil Pegawai
				</h1>
				<p class="mt-1 text-xs text-slate-500 font-medium">Detail informasi lengkap pegawai dan hak akses pengguna sistem</p>
			</div>
			<div class="flex items-center gap-2">
				<a wire:navigate href="{{ route('master.users.index', array_filter(['type' => request('type')])) }}"
					class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
					<i class="fa-solid fa-arrow-left"></i> Kembali
				</a>
				<a wire:navigate href="{{ route('master.users.edit', array_filter(['user' => $user, 'type' => request('type')])) }}"
					class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
					<i class="fa-solid fa-pen-to-square"></i> Edit Data
				</a>
			</div>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

			{{-- Kolom Kiri: Ringkasan Profil --}}
			<div class="md:col-span-1 space-y-6">
				<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center text-center">

					{{-- Avatar Ring --}}
					<div
						class="w-24 h-24 bg-cyan-100 text-cyan-700 rounded-full flex items-center justify-center font-black text-3xl mb-4 ring-4 ring-cyan-50 shadow-inner">
						{{ strtoupper(substr($user->name, 0, 1)) }}
					</div>

					<h3 class="text-base font-bold text-slate-900 mb-0.5 px-2">{{ $user->name }}</h3>
					<p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">
						{{ $user->position?->name ?? 'Pegawai' }}</p>

					<div class="w-full pt-4 border-t border-slate-100 flex flex-col gap-3">

						{{-- Alert NIK --}}
						@if (!$user->nik)
							<div class="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-left">
								<i class="fa-solid fa-circle-exclamation text-amber-600 mt-0.5 text-sm shrink-0"></i>
								<p class="text-[11px] font-medium leading-relaxed text-amber-800">
									<strong>NIK belum terisi.</strong> Lengkapi NIK di menu Edit Profil Pegawai sebelum melakukan TTE.
								</p>
							</div>
						@endif

						{{-- Status Badge --}}
						<div class="flex justify-center">
							@if ($user->is_active)
								<span
									class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
									<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
								</span>
							@else
								<span
									class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-700 ring-1 ring-inset ring-rose-600/10">
									<span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Nonaktif
								</span>
							@endif
						</div>

						{{-- Roles Badge --}}
						<div class="mt-1 flex flex-wrap justify-center gap-1">
							@foreach ($user->roles as $role)
								<span
									class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-[11px] font-bold text-slate-600 ring-1 ring-inset ring-slate-500/10">
									<i class="fa-solid fa-key text-[9px] mr-1 text-slate-400"></i>{{ $role->label }}
								</span>
							@endforeach
						</div>
					</div>
				</div>
			</div>

			{{-- Kolom Kanan: Detail Informasi --}}
			<div class="md:col-span-2">
				<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
					<h3
						class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-5 pb-2 border-b border-slate-100 flex items-center gap-2">
						<i class="fa-solid fa-id-card-clip text-cyan-500 text-base"></i>Informasi Detail Pegawai
					</h3>

					<div class="grid grid-cols-1 sm:grid-cols-2 gap-y-5 gap-x-6">

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIP / ID Sistem</label>
							<p
								class="text-sm font-mono font-semibold text-slate-800 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100 inline-block">
								{{ $user->nip ?? '-' }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nomor Induk Kependudukan
								(NIK)</label>
							<p
								class="text-sm font-mono font-semibold text-slate-800 {{ $user->nik ? 'bg-slate-50 border-slate-100' : 'bg-amber-50/50 border-amber-100 text-amber-700' }} px-2.5 py-1.5 rounded-lg border inline-block">
								{{ $user->nik ?? 'Belum Diatur' }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Username</label>
							<p class="text-sm font-medium text-slate-800"><i
									class="fa-solid fa-at mr-1.5 text-slate-400"></i>{{ $user->username }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Alamat Email</label>
							<p class="text-sm font-medium text-slate-800"><i
									class="fa-regular fa-envelope mr-1.5 text-slate-400"></i>{{ $user->email ?? '-' }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">No. Telepon / WhatsApp</label>
							<p class="text-sm font-medium text-slate-800"><i
									class="fa-solid fa-phone mr-1.5 text-slate-400"></i>{{ $user->phone ?? '-' }}</p>
						</div>

						<div class="sm:col-span-2 my-2">
							<hr class="border-slate-100">
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tipe Kepegawaian</label>
							<p class="text-sm font-semibold text-slate-800"><i
									class="fa-solid fa-user-tier mr-1.5 text-slate-400"></i>{{ $user->employee_type->label() }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Golongan / Pangkat</label>
							<p class="text-sm font-semibold text-slate-800">
								<i
									class="fa-solid fa-award mr-1.5 text-slate-400"></i>{{ $user->rank ? $user->rank->group . ' — ' . $user->rank->name : '-' }}
							</p>
						</div>

						<div class="sm:col-span-2 space-y-1">
							<label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Instansi / Unit Kerja
								(OPD)</label>
							<div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center gap-3">
								<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-50 text-cyan-600">
									<i class="fa-solid fa-building text-sm"></i>
								</div>
								<p class="text-sm font-bold text-slate-800">{{ $user->department?->name ?? 'Belum ada instansi' }}</p>
							</div>
						</div>

					</div>
				</div>

				{{-- Card: Riwayat Perjalanan Dinas --}}
				<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm mt-6">
					<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 pb-2 border-b border-slate-100">
						<h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
							<i class="fa-solid fa-route text-cyan-500 text-base"></i>Riwayat Perjalanan Dinas
						</h3>

						{{-- Search Trip Form --}}
						<form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2 w-full sm:w-72">
							@foreach (request()->except(['search_trip', 'page_pelaksana', 'page_pengikut']) as $key => $value)
								@if (is_array($value))
									@foreach ($value as $val)
										<input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
									@endforeach
								@else
									<input type="hidden" name="{{ $key }}" value="{{ $value }}">
								@endif
							@endforeach
							<div class="relative flex-1">
								<span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
									<i class="fa-solid fa-magnifying-glass text-xs"></i>
								</span>
								<input type="text" name="search_trip" value="{{ request('search_trip') }}"
									class="w-full rounded-lg border border-slate-300 bg-white py-1.5 pl-8 pr-2.5 text-xs text-slate-700 placeholder-slate-400 focus:border-cyan-500 focus:outline-hidden"
									placeholder="Cari maksud, nomor, atau tujuan...">
							</div>
							<button type="submit" class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-900">
								Cari
							</button>
							@if(request('search_trip'))
								<a href="{{ url()->current() }}" class="text-slate-400 hover:text-slate-600">
									<i class="fa-solid fa-circle-xmark text-sm"></i>
								</a>
							@endif
						</form>
					</div>

					<div x-data="{ activeTab: '{{ request()->has('page_pengikut') ? 'pengikut' : 'pelaksana' }}' }" class="space-y-4">
						{{-- Tab Buttons --}}
						<div class="flex border-b border-slate-200">
							<button @click="activeTab = 'pelaksana'"
								:class="activeTab === 'pelaksana' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
								class="flex-1 py-2.5 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-colors cursor-pointer">
								Sebagai Pelaksana Utama ({{ $tripsAsPelaksana->total() }})
							</button>
							<button @click="activeTab = 'pengikut'"
								:class="activeTab === 'pengikut' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
								class="flex-1 py-2.5 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-colors cursor-pointer">
								Sebagai Pengikut ({{ $tripsAsFollower->total() }})
							</button>
						</div>

						{{-- Tab: Pelaksana --}}
						<div x-show="activeTab === 'pelaksana'" class="space-y-3">
							@forelse ($tripsAsPelaksana as $trip)
								<div class="p-4 bg-slate-50 border border-slate-200 rounded-xl leading-relaxed flex flex-col sm:flex-row sm:items-center justify-between gap-4">
									<div class="space-y-1">
										<div class="flex items-center gap-2 flex-wrap">
											<span class="text-xs font-mono font-bold text-slate-600">{{ $trip->document_number ?? 'Belum memiliki nomor seri' }}</span>
											<x-ui.badge :status="$trip->status->value" class="px-2 py-0.5 rounded-sm text-[9px] font-bold uppercase tracking-wider">
												{{ $trip->status->label() }}
											</x-ui.badge>
										</div>
										<p class="text-sm font-semibold text-slate-800">{{ $trip->purpose }}</p>
										<p class="text-xs text-slate-500 flex items-center gap-1">
											<i class="fa-solid fa-location-dot text-slate-400"></i>
											@foreach ($trip->destinations as $dest)
												{{ $dest->province->name }}{{ $dest->regency ? ', ' . $dest->regency->name : '' }}@if(!$loop->last) ; @endif
											@endforeach
										</p>
										<p class="text-xs text-slate-500">
											<i class="fa-regular fa-calendar mr-1"></i>{{ $trip->start_date->translatedFormat('d M Y') }} s/d {{ $trip->end_date->translatedFormat('d M Y') }} ({{ $trip->duration_days }} hari)
										</p>
									</div>
									<div class="shrink-0">
										<a wire:navigate href="{{ route('sppd.show', $trip) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-100">
											Detail <i class="fa-solid fa-arrow-right text-[10px]"></i>
										</a>
									</div>
								</div>
							@empty
								<p class="text-sm text-slate-400 italic text-center py-6">Belum ada riwayat perjalanan sebagai pelaksana utama.</p>
							@endforelse

							@if ($tripsAsPelaksana->hasPages())
								<div class="mt-4 pt-3 border-t border-slate-100">
									{{ $tripsAsPelaksana->links() }}
								</div>
							@endif
						</div>

						{{-- Tab: Pengikut --}}
						<div x-show="activeTab === 'pengikut'" class="space-y-3" style="display: none;">
							@forelse ($tripsAsFollower as $trip)
								<div class="p-4 bg-slate-50 border border-slate-200 rounded-xl leading-relaxed flex flex-col sm:flex-row sm:items-center justify-between gap-4">
									<div class="space-y-1">
										<div class="flex items-center gap-2 flex-wrap">
											<span class="text-xs font-mono font-bold text-slate-600">{{ $trip->document_number ?? 'Belum memiliki nomor seri' }}</span>
											<x-ui.badge :status="$trip->status->value" class="px-2 py-0.5 rounded-sm text-[9px] font-bold uppercase tracking-wider">
												{{ $trip->status->label() }}
											</x-ui.badge>
											<span class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-2 py-0.5 rounded-sm text-[9px] font-bold uppercase tracking-wider">
												Pelaksana: {{ $trip->user->name }}
											</span>
										</div>
										<p class="text-sm font-semibold text-slate-800">{{ $trip->purpose }}</p>
										<p class="text-xs text-slate-500 flex items-center gap-1">
											<i class="fa-solid fa-location-dot text-slate-400"></i>
											@foreach ($trip->destinations as $dest)
												{{ $dest->province->name }}{{ $dest->regency ? ', ' . $dest->regency->name : '' }}@if(!$loop->last) ; @endif
											@endforeach
										</p>
										<p class="text-xs text-slate-500">
											<i class="fa-regular fa-calendar mr-1"></i>{{ $trip->start_date->translatedFormat('d M Y') }} s/d {{ $trip->end_date->translatedFormat('d M Y') }} ({{ $trip->duration_days }} hari)
										</p>
									</div>
									<div class="shrink-0">
										<a wire:navigate href="{{ route('sppd.show', $trip) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-100">
											Detail <i class="fa-solid fa-arrow-right text-[10px]"></i>
										</a>
									</div>
								</div>
							@empty
								<p class="text-sm text-slate-400 italic text-center py-6">Belum ada riwayat perjalanan sebagai pengikut.</p>
							@endforelse

							@if ($tripsAsFollower->hasPages())
								<div class="mt-4 pt-3 border-t border-slate-100">
									{{ $tripsAsFollower->links() }}
								</div>
							@endif
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
@endsection
