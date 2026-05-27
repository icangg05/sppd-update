@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('page-title', 'Data Pegawai')

@section('content')
	<div class="p-1 space-y-6">

		{{-- Header --}}
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
					<i class="fa-solid fa-users-gear mr-2 text-cyan-600"></i>Data Pegawai
				</h1>
				<p class="mt-1 text-xs text-slate-500 font-medium">Kelola data pegawai dan hak akses pengguna sistem</p>
			</div>
			<a href="{{ route('master.users.create') }}"
				class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
				<i class="fa-solid fa-plus"></i> Tambah Pegawai
			</a>
		</div>

		{{-- Filters --}}
		<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
			<form method="GET" action="{{ route('master.users.index') }}" class="flex flex-col sm:flex-row gap-3">

				{{-- Search Input with Icon --}}
				<div class="relative flex-1">
					<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
						<i class="fa-solid fa-magnifying-glass text-xs"></i>
					</div>
					<input type="text" name="search" value="{{ request('search') }}"
						class="block w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition"
						placeholder="Cari nama, username, NIP, atau email...">
				</div>

				{{-- Department Dropdown (Super Admin) --}}
				@if (auth()->user()->hasRole('super_admin'))
					<div class="relative w-full sm:w-64">
						<select name="department_id"
							class="block w-full appearance-none rounded-lg border border-slate-300 bg-slate-50 py-2 pl-3 pr-10 text-sm focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500 outline-none transition">
							<option value="">Semua Instansi</option>
							@foreach ($departments as $d)
								<option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>
									{{ $d->display_name }}
								</option>
							@endforeach
						</select>
						<div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
							<i class="fa-solid fa-chevron-down text-xs"></i>
						</div>
					</div>
				@endif

				{{-- Buttons --}}
				<div class="flex items-center gap-2">
					<button type="submit"
						class="inline-flex items-center gap-2 rounded bg-slate-800 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900">
						Cari
					</button>
					@if (request()->hasAny(['search', 'department_id']))
						<a href="{{ route('master.users.index') }}"
							class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
							<i class="fa-solid fa-rotate-right"></i> Reset
						</a>
					@endif
				</div>
			</form>
		</div>

		{{-- Table --}}
		<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full text-left whitespace-nowrap">
					<thead
						class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
						<tr>
							<th class="py-3 px-4 w-12 text-center">No</th>
							<th class="py-3 px-4">Pegawai</th>
							@if (auth()->user()->hasRole('super_admin'))
								<th class="py-3 px-4">Instansi</th>
							@endif
							<th class="py-3 px-4">Jabatan</th>
							<th class="py-3 px-4">Pangkat / Gol.</th>
							<th class="py-3 px-4">Role</th>
							<th class="py-3 px-4 text-center">Status</th>
							<th class="py-3 px-4 text-right">Aksi</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100 text-slate-700">
						@forelse($users as $i => $user)
							<tr class="hover:bg-slate-50/50 transition-colors">
								<td class="py-3 px-4 text-center text-xs text-slate-400">{{ $users->firstItem() + $i }}</td>

								<td class="py-3 px-4">
									<div class="flex items-center gap-3">
										{{-- Avatar Cyan --}}
										<div
											class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-bold text-cyan-700 ring-2 ring-white shadow-sm">
											{{ strtoupper(substr($user->name, 0, 1)) }}
										</div>
										<div>
											<p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
											<div class="flex items-center gap-2 mt-0.5">
												<span class="text-xs text-slate-500">NIP: {{ $user->nip ?? '-' }}</span>
											</div>
										</div>
									</div>
								</td>

								@if (auth()->user()->hasRole('super_admin'))
									<td class="py-3 px-4 text-sm text-slate-600">{{ $user->department?->name ?? '-' }}</td>
								@endif

								<td class="py-3 px-4 text-sm text-slate-600">{{ $user->position?->name ?? '-' }}</td>

								<td class="py-3 px-4">
									@if ($user->rank)
										<p class="text-sm text-slate-800">{{ $user->rank->name }}</p>
										<p class="text-xs text-slate-500">{{ $user->rank->group }}</p>
									@else
										<span class="text-sm text-slate-400">-</span>
									@endif
								</td>

								<td class="py-3 px-4">
									<div class="flex flex-wrap gap-1">
										@foreach ($user->roles as $role)
											{{-- Badge Role disesuaikan ke rumpun warna Cyan / Sky agar selaras --}}
											<span
												class="inline-flex items-center rounded-md bg-cyan-50 px-2 py-1 text-xs font-medium text-cyan-700 ring-1 ring-inset ring-cyan-600/20">
												{{ $role->name }}
											</span>
										@endforeach
									</div>
								</td>

								<td class="py-3 px-4 text-center">
									@if ($user->is_active)
										<span
											class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
											<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
										</span>
									@else
										<span
											class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10">
											<span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Nonaktif
										</span>
									@endif
								</td>

								<td class="py-3 px-4 text-right">
									<div class="flex items-center justify-end gap-1">
										{{-- Toggle Status --}}
										<form action="{{ route('master.users.toggle', $user) }}" method="POST" class="inline">
											@csrf @method('PATCH')
											<button type="submit" title="{{ $user->is_active ? 'Nonaktifkan Pegawai' : 'Aktifkan Pegawai' }}"
												class="rounded p-1.5 text-xs font-medium transition-colors {{ $user->is_active ? 'text-slate-400 hover:bg-slate-100 hover:text-rose-600' : 'text-slate-400 hover:bg-slate-100 hover:text-emerald-600' }}">
												<i class="fa-solid fa-power-off"></i>
											</button>
										</form>

										{{-- View --}}
										<a href="{{ route('master.users.show', $user->id) }}"
											class="rounded p-1.5 text-slate-400 hover:bg-cyan-50 hover:text-cyan-600 transition-colors"
											title="Detail Pegawai">
											<i class="fa-solid fa-eye"></i>
										</a>

										{{-- Edit --}}
										<a href="{{ route('master.users.edit', $user->id) }}"
											class="rounded p-1.5 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-colors"
											title="Edit Data">
											<i class="fa-solid fa-pen-to-square"></i>
										</a>

										{{-- Delete --}}
										<form action="{{ route('master.users.destroy', $user->id) }}" method="POST" class="inline"
											onsubmit="return confirm('Yakin ingin menghapus pegawai ini secara permanen?')">
											@csrf @method('DELETE')
											<button type="submit"
												class="rounded p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
												title="Hapus Data">
												<i class="fa-solid fa-trash-can"></i>
											</button>
										</form>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="{{ auth()->user()->hasRole('super_admin') ? '9' : '8' }}" class="py-12 text-center">
									<div class="flex flex-col items-center justify-center text-slate-400">
										<i class="fa-solid fa-folder-open text-3xl mb-3 opacity-50"></i>
										<p class="text-sm font-medium">Belum ada data pegawai yang ditemukan</p>
									</div>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>

			{{-- Pagination --}}
			@if ($users->hasPages())
				<div class="border-t border-slate-200 bg-slate-50/50 px-4 py-3">
					{{ $users->links() }}
				</div>
			@endif
		</div>
	</div>
@endsection
