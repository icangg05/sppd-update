@extends('layouts.app')
@section('title', 'Edit Pegawai')
@section('page-title', 'Edit Pegawai')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Edit Pegawai</h1>
    <p class="page-subtitle">Ubah data pegawai atau pengguna sistem</p>
  </div>
  <a href="{{ route('master.users.index') }}" class="btn-secondary">← Kembali</a>
</div>

<form method="POST" action="{{ route('master.users.update', $user->id) }}">
  @csrf
  @method('PUT')
  <div class="card p-6 mb-6">
    <h3 class="font-semibold text-slate-900 mb-4">Informasi Pegawai</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Username <span class="text-red-500">*</span></label>
        <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-input" required>
        @error('username') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Email <span class="text-red-500">*</span></label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
        @error('email') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Password baru</label>
        <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah password">
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">NIP</label>
        <input type="text" name="nip" value="{{ old('nip', $user->nip) }}" class="form-input" placeholder="18 digit">
        @error('nip') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">No. Telepon</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Tipe Pegawai <span class="text-red-500">*</span></label>
        <select name="employee_type" class="form-select" required>
          @foreach(\App\Enums\EmployeeType::cases() as $type)
            <option value="{{ $type->value }}" {{ old('employee_type', $user->employee_type->value) === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
          @endforeach
        </select>
        @error('employee_type') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Instansi</label>
        <select name="department_id" class="form-select" {{ !auth()->user()->hasRole('super_admin') ? 'disabled' : '' }}>
          <option value="">— Pilih —</option>
          @foreach($departments as $d)
            <option value="{{ $d->id }}" {{ old('department_id', auth()->user()->hasRole('super_admin') ? $user->department_id : auth()->user()->department_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
          @endforeach
        </select>
        @if(!auth()->user()->hasRole('super_admin'))
          <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
        @endif
      </div>
      <div>
        <label class="form-label">Golongan/Pangkat</label>
        <select name="rank_id" class="form-select">
          <option value="">— Pilih —</option>
          @foreach($ranks as $r)
            <option value="{{ $r->id }}" {{ old('rank_id', $user->rank_id) == $r->id ? 'selected' : '' }}>{{ $r->group }} — {{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Jabatan Fungsional</label>
        <select name="position_id" class="form-select">
          <option value="">— Pilih —</option>
          @foreach($positions as $p)
            <option value="{{ $p->id }}" {{ old('position_id', $user->position_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Nama Jabatan</label>
        <input type="text" name="position_name" value="{{ old('position_name', $user->position_name) }}" class="form-input" placeholder="Misal: Staf Umum">
      </div>
      <div>
        <label class="form-label">Role Sistem <span class="text-red-500">*</span></label>
        <select name="role" class="form-select" required>
          @foreach(\Spatie\Permission\Models\Role::all() as $role)
            <option value="{{ $role->name }}" {{ old('role', $user->roles->first()?->name) === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
          @endforeach
        </select>
        @error('role') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <a href="{{ route('master.users.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
  </div>
</form>
@endsection
