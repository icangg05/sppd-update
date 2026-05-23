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
			<i class="fa-solid fa-plus"></i>
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
				<i class="fa-solid fa-magnifying-glass"></i>
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
						<td class="text-sm">{{ $user->position?->name ?? '-' }}</td>
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
									<i class="fa-solid fa-eye"></i>
								</a>
								<a href="{{ route('master.users.edit', $user->id) }}" class="btn-ghost p-1.5 text-amber-600 hover:bg-amber-50"
									title="Edit">
									<i class="fa-solid fa-pen-to-square"></i>
								</a>
								<form action="{{ route('master.users.destroy', $user->id) }}" method="POST" class="inline"
									onsubmit="return confirm('Yakin ingin menghapus pegawai ini?')">
									@csrf @method('DELETE')
									<button type="submit" class="btn-ghost p-1.5 text-rose-600 hover:bg-rose-50" title="Hapus">
										<i class="fa-solid fa-trash"></i>
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
