@extends('layouts.app')
@section('title', 'Edit Pegawai')
@section('page-title', 'Edit Pegawai')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Edit Pegawai</h1>
			<p class="page-subtitle">Ubah data pegawai atau pengguna sistem</p>
		</div>
		<x-ui.button href="{{ route('master.users.index') }}" variant="secondary">← Kembali</x-ui.button>
	</div>

	<form method="POST" action="{{ route('master.users.update', $user->id) }}">
		@csrf
		@method('PUT')
		<div class="card p-6 mb-6">
			<h3 class="font-semibold text-slate-900 mb-4">Informasi Pegawai</h3>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<x-form.input
					name="name"
					label="Nama Lengkap"
					:value="old('name', $user->name)"
					required
				/>

				<x-form.input
					name="username"
					label="Username"
					:value="old('username', $user->username)"
					required
				/>

				<x-form.input
					type="email"
					name="email"
					label="Email"
					:value="old('email', $user->email)"
					required
				/>

				<x-form.input
					type="password"
					name="password"
					label="Password baru"
					placeholder="Kosongkan jika tidak ingin mengubah password"
				/>

				<x-form.input
					name="nip"
					label="NIP"
					:value="old('nip', $user->nip)"
					placeholder="18 digit"
				/>

				<x-form.input
					name="nik"
					label="NIK"
					:value="old('nik', $user->nik)"
					placeholder="16 digit NIK"
					required
				/>

				<x-form.input
					name="phone"
					label="No. Telepon"
					:value="old('phone', $user->phone)"
				/>

				<x-form.select
					name="employee_type"
					label="Tipe Pegawai"
					required
				>
					@foreach (\App\Enums\EmployeeType::cases() as $type)
						<option value="{{ $type->value }}"
							{{ old('employee_type', $user->employee_type->value) === $type->value ? 'selected' : '' }}>
							{{ $type->label() }}
						</option>
					@endforeach
				</x-form.select>

				<x-form.select
					name="department_id"
					label="Instansi"
				>
					<option value="">— Pilih —</option>
					@foreach ($departments as $d)
						<option value="{{ $d->id }}"
							{{ old('department_id', auth()->user()->hasRole('super_admin') ? $user->department_id : auth()->user()->department_id) == $d->id ? 'selected' : '' }}>
							{{ $d->display_name }}
						</option>
					@endforeach
				</x-form.select>

				<x-form.select
					name="rank_id"
					label="Golongan/Pangkat"
				>
					<option value="">— Pilih —</option>
					@foreach ($ranks as $r)
						<option value="{{ $r->id }}" {{ old('rank_id', $user->rank_id) == $r->id ? 'selected' : '' }}>
							{{ $r->group }} — {{ $r->name }}
						</option>
					@endforeach
				</x-form.select>

				<x-form.select
					name="position_id"
					label="Jabatan"
				>
					<option value="">— Pilih —</option>
					@foreach ($positions as $p)
						<option value="{{ $p->id }}" {{ old('position_id', $user->position_id) == $p->id ? 'selected' : '' }}>
							{{ $p->name }}
						</option>
					@endforeach
				</x-form.select>

				<x-form.select
					name="role"
					label="Role Sistem"
					required
				>
					@foreach (\Spatie\Permission\Models\Role::all() as $role)
						<option value="{{ $role->name }}"
							{{ old('role', $user->roles->first()?->name) === $role->name ? 'selected' : '' }}>
							{{ $role->name }}
						</option>
					@endforeach
				</x-form.select>
			</div>
		</div>

		<div class="flex justify-end gap-3">
			<x-ui.button href="{{ route('master.users.index') }}" variant="secondary">Batal</x-ui.button>
			<x-ui.button type="submit">Simpan Perubahan</x-ui.button>
		</div>
	</form>
@endsection
