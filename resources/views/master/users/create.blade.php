@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page-title', 'Tambah Pegawai')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Tambah Pegawai</h1>
			<p class="page-subtitle">Tambahkan pegawai baru ke sistem</p>
		</div>
		<a href="{{ route('master.users.index') }}" class="btn-secondary">← Kembali</a>
	</div>

	<form method="POST" action="{{ route('master.users.store') }}">
		@csrf
		<div class="card p-6 mb-6">
			<h3 class="font-semibold text-slate-900 mb-4">Informasi Pegawai</h3>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div>
					<label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
					<input type="text" name="name" value="{{ old('name') }}" class="form-input" required>
					@error('name')
						<p class="form-error">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="form-label">Username <span class="text-red-500">*</span></label>
					<input type="text" name="username" value="{{ old('username') }}" class="form-input" required>
					@error('username')
						<p class="form-error">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="form-label">Email <span class="text-red-500">*</span></label>
					<input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
					@error('email')
						<p class="form-error">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="form-label">Password <span class="text-red-500">*</span></label>
					<input type="password" name="password" class="form-input" required>
					@error('password')
						<p class="form-error">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="form-label">NIP</label>
					<input type="text" name="nip" value="{{ old('nip') }}" class="form-input" placeholder="18 digit">
					@error('nip')
						<p class="form-error">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="form-label">No. Telepon</label>
					<input type="text" name="phone" value="{{ old('phone') }}" class="form-input">
				</div>
				<div>
					<label class="form-label">Tipe Pegawai <span class="text-red-500">*</span></label>
					<select name="employee_type" class="form-select" required>
						@foreach (\App\Enums\EmployeeType::cases() as $type)
							<option value="{{ $type->value }}" {{ old('employee_type') === $type->value ? 'selected' : '' }}>
								{{ $type->label() }}</option>
						@endforeach
					</select>
				</div>
				<div>
					<label class="form-label">Instansi</label>
					<select name="department_id" class="form-select">
						<option value="">— Pilih —</option>
						@foreach ($departments as $d)
							<option value="{{ $d->id }}"
								{{ old('department_id', auth()->user()->hasRole('super_admin') ? '' : auth()->user()->department_id) == $d->id ? 'selected' : '' }}>
								{{ $d->display_name }}
							</option>
						@endforeach
					</select>
				</div>
				<div>
					<label class="form-label">Golongan/Pangkat</label>
					<select name="rank_id" class="form-select">
						<option value="">— Pilih —</option>
						@foreach ($ranks as $r)
							<option value="{{ $r->id }}" {{ old('rank_id') == $r->id ? 'selected' : '' }}>{{ $r->group }} —
								{{ $r->name }}</option>
						@endforeach
					</select>
				</div>
				<div>
					<label class="form-label">Jabatan</label>
					<select name="position_id" class="form-select">
						<option value="">— Pilih —</option>
						@foreach ($positions as $p)
							<option value="{{ $p->id }}" {{ old('position_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}
							</option>
						@endforeach
					</select>
				</div>
				<div>
					<label class="form-label">Role Sistem <span class="text-red-500">*</span></label>
					<select name="role" class="form-select" required>
						@foreach (\Spatie\Permission\Models\Role::all() as $role)
							<option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}
							</option>
						@endforeach
					</select>
				</div>
			</div>
		</div>

		<div class="flex justify-end gap-3">
			<a href="{{ route('master.users.index') }}" class="btn-secondary">Batal</a>
			<button type="submit" class="btn-primary">Simpan Pegawai</button>
		</div>
	</form>
@endsection
