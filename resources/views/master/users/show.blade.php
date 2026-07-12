@extends('layouts.app')
@section('title', 'Detail Pegawai')
@section('page-title', 'Detail Pegawai')

@section('content')
	<div class="mx-auto max-w-6xl space-y-6 p-1">

		{{-- Header Halaman --}}
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div class="flex items-center gap-3">
				<div class="flex size-11 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
					<i class="fa-solid fa-user-check text-lg"></i>
				</div>
				<div>
					<h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Detail Pegawai</h1>
					<p class="mt-0.5 text-sm text-slate-500">Informasi lengkap pegawai dan hak akses pengguna sistem.</p>
				</div>
			</div>
			<div class="flex flex-wrap items-center gap-2">
				<x-ui.button href="{{ route('master.users.index', array_filter(['type' => request('type')])) }}"
					variant="secondary" class="shrink-0">
					<x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
					Kembali
				</x-ui.button>

				{{-- Impersonasi: hanya super_admin, dan tidak untuk dirinya sendiri. --}}
				@if (auth()->user()->hasRole('super_admin') && $user->id !== auth()->id())
					@php
						$impersonateUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
							'users.impersonate',
							now()->addMinutes(5),
							[
								'user' => $user,
								'by' => auth()->id(),
								'nonce' => \Illuminate\Support\Str::uuid()->toString(),
							],
						);
					@endphp
					<button type="button" x-data="{ copied: false }"
						@click="navigator.clipboard.writeText(@js($impersonateUrl)); copied = true; setTimeout(() => copied = false, 2000)"
						title="Salin tautan, lalu buka di jendela incognito untuk masuk sebagai {{ $user->name }} tanpa mengganggu sesi Anda."
						class="inline-flex shrink-0 items-center gap-2 rounded bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-offset-1">
						<i class="fa-solid" :class="copied ? 'fa-check' : 'fa-user-secret'"></i>
						<span x-text="copied ? 'Tautan login tersalin' : 'Salin tautan login sebagai pengguna'"></span>
					</button>
				@endif

				<x-ui.button href="{{ route('master.users.edit', array_filter(['user' => $user, 'type' => request('type')])) }}"
					variant="primary" class="shrink-0">
					<x-slot name="icon"><i class="fa-solid fa-pen-to-square text-xs"></i></x-slot>
					Edit Data
				</x-ui.button>
			</div>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

			{{-- Kolom Kiri: Ringkasan Profil --}}
			<div class="md:col-span-1 space-y-6">
				@php
					$nameParts = preg_split('/\s+/', trim($user->name ?? ''), -1, PREG_SPLIT_NO_EMPTY);
					$initials = strtoupper(mb_substr($nameParts[0] ?? '?', 0, 1)
						. (count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));
				@endphp
				<div class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">

					{{-- Pita gradien institusional --}}
					<div class="relative h-10 bg-linear-to-br from-primary-800 via-primary-700 to-primary-600">
						<div class="absolute inset-0 opacity-20"
							style="background-image: radial-gradient(circle at 1px 1px, #ffffff 1px, transparent 0); background-size: 18px 18px;">
						</div>
					</div>

					<div class="mt-4 flex flex-col items-center px-6 pb-6 text-center">
						{{-- Avatar monogram --}}
						<div
							class="flex size-24 items-center justify-center rounded-full border-4 border-white bg-primary-100 text-3xl font-bold text-primary-700 shadow-md ring-1 ring-slate-900/5">
							{{ $initials }}
						</div>

						<h3 class="mt-3 px-2 text-lg font-bold tracking-tight text-slate-900 text-balance">{{ $user->name }}</h3>
						<div class="mt-2 flex flex-wrap items-center justify-center gap-2">
							<x-ui.badge color="blue">
								<i class="fa-solid fa-shield-halved mr-1.5 text-[10px]"></i>
								{{ $user->roles->first()?->label ?? 'Pegawai' }}
							</x-ui.badge>
							@if ($user->is_active)
								<span
									class="inline-flex items-center gap-1.5 rounded px-2 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 bg-emerald-50">
									<span class="size-1.5 rounded-full bg-emerald-500"></span> Aktif
								</span>
							@else
								<span
									class="inline-flex items-center gap-1.5 rounded px-2 py-1 text-[11px] font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 bg-rose-50">
									<span class="size-1.5 rounded-full bg-rose-500"></span> Nonaktif
								</span>
							@endif
						</div>

						{{-- Alert NIK --}}
						@if (!$user->nik)
							<div class="mt-4 flex w-full items-start gap-2.5 rounded border border-amber-200 bg-amber-50/70 p-3 text-left">
								<i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0 text-sm text-amber-600"></i>
								<p class="text-[11px] font-medium leading-relaxed text-amber-800">
									<strong>NIK belum terisi.</strong> Lengkapi NIK di menu Edit Data pegawai sebelum melakukan TTE.
								</p>
							</div>
						@endif
					</div>
				</div>
			</div>

			{{-- Kolom Kanan: Detail Informasi --}}
			<div class="md:col-span-2">
				<div class="dash-enter rounded border border-slate-200 bg-white p-6 shadow-sm">
					<div class="mb-5 flex items-center gap-3 border-b border-slate-100 pb-4">
						<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
							<i class="fa-regular fa-id-card"></i>
						</div>
						<div>
							<h3 class="text-sm font-bold text-slate-800">Informasi Detail</h3>
							<p class="text-xs text-slate-500">Identitas, kontak, dan penempatan pegawai.</p>
						</div>
					</div>

					<div class="grid grid-cols-1 sm:grid-cols-2 gap-y-5 gap-x-6">

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">NIP / ID Sistem</label>
							<p
								class="text-sm font-mono font-semibold text-slate-800 bg-slate-50 px-2.5 py-1.5 rounded border border-slate-100 inline-block">
								{{ $user->nip ?? '-' }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nomor Induk Kependudukan
								(NIK)</label>
							<p
								class="text-sm font-mono font-semibold text-slate-800 {{ $user->nik ? 'bg-slate-50 border-slate-100' : 'bg-amber-50/50 border-amber-100 text-amber-700' }} px-2.5 py-1.5 rounded border inline-block">
								{{ $user->nik ?? 'Belum Diatur' }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Username</label>
							<p class="text-sm font-medium text-slate-800"><i
									class="fa-solid fa-at mr-1.5 text-slate-500"></i>{{ $user->username }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Alamat Email</label>
							<p class="text-sm font-medium text-slate-800"><i
									class="fa-regular fa-envelope mr-1.5 text-slate-500"></i>{{ $user->email ?? '-' }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">No. Telepon / WhatsApp</label>
							<p class="text-sm font-medium text-slate-800"><i
									class="fa-solid fa-phone mr-1.5 text-slate-500"></i>{{ $user->phone ?? '-' }}</p>
						</div>

						<div class="sm:col-span-2 my-2">
							<hr class="border-slate-100">
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tipe Kepegawaian</label>
							<p class="text-sm font-semibold text-slate-800"><i
									class="fa-solid fa-user-tag mr-1.5 text-slate-500"></i>{{ $user->employee_type->label() }}</p>
						</div>

						<div class="space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Golongan / Pangkat</label>
							<p class="text-sm font-semibold text-slate-800">
								<i
									class="fa-solid fa-award mr-1.5 text-slate-500"></i>{{ $user->rank ? $user->rank->group . ' — ' . $user->rank->name : '-' }}
							</p>
						</div>

						<div class="sm:col-span-2 space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jabatan</label>
							<p class="text-sm font-semibold text-slate-800">
								<i class="fa-solid fa-briefcase mr-1.5 text-slate-500"></i>{{ $user->isDprdMember() ? (\App\Enums\DprdJabatan::tryFrom($user->dprd_jabatan ?? '')?->label(true) ?? ($user->dprd_jabatan ?? '-')) : ($user->position?->name ?? '-') }}
							</p>
						</div>

						<div class="sm:col-span-2 space-y-1">
							<label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Instansi / Unit Kerja
								(OPD)</label>
							<div class="p-3 bg-slate-50 border border-slate-100 rounded flex items-center gap-3">
								<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600">
									<i class="fa-solid fa-building text-sm"></i>
								</div>
								<p class="text-sm font-bold text-slate-800">{{ $user->department?->name ?? 'Belum ada instansi' }}</p>
							</div>
						</div>

					</div>
				</div>

				{{-- Card: Riwayat Perjalanan Dinas --}}
				<div class="dash-enter rounded border border-slate-200 bg-white p-6 shadow-sm mt-6">
					<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 pb-4 border-b border-slate-100">
						<div class="flex items-center gap-3">
							<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
								<i class="fa-solid fa-route"></i>
							</div>
							<div>
								<h3 class="text-sm font-bold text-slate-800">Riwayat Perjalanan Dinas</h3>
								<p class="text-xs text-slate-500">Sebagai pelaksana utama maupun pengikut.</p>
							</div>
						</div>

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
								<span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-500">
									<i class="fa-solid fa-magnifying-glass text-xs"></i>
								</span>
								<input type="text" name="search_trip" value="{{ request('search_trip') }}"
									class="w-full rounded border border-slate-300 bg-white py-1.5 pl-8 pr-2.5 text-xs text-slate-700 placeholder-slate-400 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30"
									placeholder="Cari maksud, nomor, atau tujuan...">
							</div>
							<button type="submit" class="inline-flex items-center rounded bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-slate-900 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500/40">
								Cari
							</button>
							@if(request('search_trip'))
								<a href="{{ url()->current() }}" class="text-slate-500 hover:text-slate-600">
									<i class="fa-solid fa-circle-xmark text-sm"></i>
								</a>
							@endif
						</form>
					</div>

					<div x-data="{ activeTab: '{{ request()->has('page_pengikut') ? 'pengikut' : 'pelaksana' }}' }" class="space-y-4">
						{{-- Tab Buttons --}}
						<div class="flex border-b border-slate-200">
							<button @click="activeTab = 'pelaksana'"
								:class="activeTab === 'pelaksana' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
								class="flex-1 py-2.5 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-colors cursor-pointer">
								Sebagai Pelaksana Utama ({{ $tripsAsPelaksana->total() }})
							</button>
							<button @click="activeTab = 'pengikut'"
								:class="activeTab === 'pengikut' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
								class="flex-1 py-2.5 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-colors cursor-pointer">
								Sebagai Pengikut ({{ $tripsAsFollower->total() }})
							</button>
						</div>

						{{-- Tab: Pelaksana --}}
						<div x-show="activeTab === 'pelaksana'" class="space-y-3">
							@forelse ($tripsAsPelaksana as $trip)
								<div class="group flex flex-col justify-between gap-4 rounded border border-slate-200 bg-slate-50 p-4 leading-relaxed transition-colors hover:border-primary-200 hover:bg-white sm:flex-row sm:items-center">
									<div class="space-y-1">
										<div class="flex items-center gap-2 flex-wrap">
											<span class="text-xs font-mono font-bold text-slate-600">{{ $trip->document_number ?? 'Belum memiliki nomor seri' }}</span>
											<x-ui.badge :status="$trip->status->value" class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">
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
											<i class="fa-regular fa-calendar mr-1 text-slate-400"></i>{{ $trip->start_date->translatedFormat('d M Y') }} s/d {{ $trip->end_date->translatedFormat('d M Y') }} ({{ $trip->duration_days }} hari)
										</p>
									</div>
									<div class="shrink-0">
										<a wire:navigate href="{{ route('sppd.show', $trip) }}" class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-primary-50 hover:text-primary-700 hover:border-primary-200 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40">
											Detail <i class="fa-solid fa-arrow-right text-[10px] transition-transform group-hover:translate-x-0.5"></i>
										</a>
									</div>
								</div>
							@empty
								<div class="flex flex-col items-center gap-2 py-10 text-center">
									<div class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
										<i class="fa-solid fa-route"></i>
									</div>
									<p class="text-sm font-medium text-slate-500">Belum ada perjalanan sebagai pelaksana utama.</p>
								</div>
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
								<div class="group flex flex-col justify-between gap-4 rounded border border-slate-200 bg-slate-50 p-4 leading-relaxed transition-colors hover:border-primary-200 hover:bg-white sm:flex-row sm:items-center">
									<div class="space-y-1">
										<div class="flex items-center gap-2 flex-wrap">
											<span class="text-xs font-mono font-bold text-slate-600">{{ $trip->document_number ?? 'Belum memiliki nomor seri' }}</span>
											<x-ui.badge :status="$trip->status->value" class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">
												{{ $trip->status->label() }}
											</x-ui.badge>
											<span class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">
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
											<i class="fa-regular fa-calendar mr-1 text-slate-400"></i>{{ $trip->start_date->translatedFormat('d M Y') }} s/d {{ $trip->end_date->translatedFormat('d M Y') }} ({{ $trip->duration_days }} hari)
										</p>
									</div>
									<div class="shrink-0">
										<a wire:navigate href="{{ route('sppd.show', $trip) }}" class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-primary-50 hover:text-primary-700 hover:border-primary-200 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40">
											Detail <i class="fa-solid fa-arrow-right text-[10px] transition-transform group-hover:translate-x-0.5"></i>
										</a>
									</div>
								</div>
							@empty
								<div class="flex flex-col items-center gap-2 py-10 text-center">
									<div class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
										<i class="fa-solid fa-user-group"></i>
									</div>
									<p class="text-sm font-medium text-slate-500">Belum ada perjalanan sebagai pengikut.</p>
								</div>
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
