@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('page-title', 'Data Pegawai')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Data Pegawai</h1>
			<p class="page-subtitle">Kelola data pegawai dan pengguna sistem</p>
		</div>
		<a href="{{ route('master.users.create') }}" class="btn-primary">
			<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			Tambah Pegawai
		</a>
	</div>

	{{-- Filters --}}
	<div class="card p-4 mb-4">
		<form method="GET" action="{{ route('master.users.index') }}" class="flex flex-col sm:flex-row gap-3">
			<div class="flex-1">
				<input type="text" name="search" value="{{ request('search') }}" class="form-input"
					placeholder="Cari nama, username, NIP, atau email...">
			</div>
			@if (auth()->user()->hasRole('super_admin'))
				<select name="department_id" class="form-select w-full sm:w-56">
					<option value="">Semua Instansi</option>
					@foreach ($departments as $d)
						<option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>
							{{ $d->display_name }}
						</option>
					@endforeach
				</select>
			@endif
			<button type="submit" class="btn-secondary">
				<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round"
						d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
				</svg>
				Cari
			</button>
			@if (request()->hasAny(['search', 'department_id']))
				<a href="{{ route('master.users.index') }}" class="btn-ghost">Reset</a>
			@endif
		</form>
	</div>

	{{-- Table --}}
	<div class="table-container">
		<table class="data-tables">
			<thead>
				<tr>
					<th>No</th>
					<th>Pegawai</th>
					<th>NIP</th>
					<th>Instansi</th>
					<th>Jabatan</th>
					<th>Pangkat / Gol.</th>
					<th>Role</th>
					<th>Status</th>
					<th class="text-right">Aksi</th>
				</tr>
			</thead>
			<tbody>
				@forelse($users as $i => $user)
					<tr>
						<td class="text-slate-400">{{ $users->firstItem() + $i }}</td>
						<td>
							<div class="flex items-center gap-3">
								<div
									class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
									{{ strtoupper(substr($user->name, 0, 1)) }}
								</div>
								<div>
									<p class="font-medium text-slate-900">{{ $user->name }}</p>
									<p class="text-xs text-slate-500 font-medium">@ {{ $user->username }}</p>
									<p class="text-xs text-slate-400">{{ $user->email }}</p>
								</div>
							</div>
						</td>
						<td class="text-xs font-mono text-slate-500">{{ $user->nip ?? '-' }}</td>
						<td class="text-sm">{{ $user->department?->name ?? '-' }}</td>
						<td class="text-sm">{{ $user->position_name ?? ($user->position?->name ?? '-') }}</td>
						<td class="text-sm">
							@if ($user->rank)
								<p class="text-slate-900 font-medium">{{ $user->rank->name }}</p>
								<p class="text-xs text-slate-500">{{ $user->rank->group }}</p>
							@else
								-
							@endif
						</td>
						<td>
							@foreach ($user->roles as $role)
								<span class="badge bg-primary-100 text-primary-800">{{ $role->name }}</span>
							@endforeach
						</td>
						<td>
							@if ($user->is_active)
								<span class="badge bg-emerald-100 text-emerald-800">Aktif</span>
							@else
								<span class="badge bg-red-100 text-red-800">Nonaktif</span>
							@endif
						</td>
						<td class="text-right">
							<div class="flex justify-end gap-2 items-center">
								<form action="{{ route('master.users.toggle', $user) }}" method="POST" class="inline">
									@csrf @method('PATCH')
									<button type="submit"
										class="btn-ghost text-xs py-1 px-2 {{ $user->is_active ? 'text-red-600' : 'text-emerald-600' }}">
										{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
									</button>
								</form>
								<a href="{{ route('master.users.show', $user->id) }}"
									class="btn-ghost p-1.5 text-primary-600 hover:bg-primary-50" title="Detail">
									<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
										<path stroke-linecap="round" stroke-linejoin="round"
											d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
									</svg>
								</a>
								<a href="{{ route('master.users.edit', $user->id) }}" class="btn-ghost p-1.5 text-amber-600 hover:bg-amber-50"
									title="Edit">
									<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round"
											d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
									</svg>
								</a>
								<form action="{{ route('master.users.destroy', $user->id) }}" method="POST" class="inline"
									onsubmit="return confirm('Yakin ingin menghapus pegawai ini?')">
									@csrf @method('DELETE')
									<button type="submit" class="btn-ghost p-1.5 text-rose-600 hover:bg-rose-50" title="Hapus">
										<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round"
												d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
										</svg>
									</button>
								</form>
							</div>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="9" class="text-center py-12 text-slate-400">Belum ada data pegawai</td>
					</tr>
				@endforelse
			</tbody>
		</table>
		@if ($users->hasPages())
			<div class="px-4 py-3 border-t border-slate-200">{{ $users->links() }}</div>
		@endif
	</div>
@endsection
