@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page-title', 'Tambah Pegawai')

@section('content')
<div class="p-1 space-y-6">

  {{-- Header Halaman --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
        <i class="fa-solid fa-user-plus mr-2 text-cyan-600"></i>Tambah Pegawai
      </h1>
      <p class="mt-1 text-xs text-slate-500 font-medium">Tambahkan entitas pegawai baru beserta hak akses sistemnya</p>
    </div>
    <x-ui.button href="{{ route('master.users.index') }}" variant="secondary"
      class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <x-slot name="icon">
        <i class="fa-solid fa-arrow-left text-xs"></i>
      </x-slot>
      Kembali
    </x-ui.button>
  </div>

  {{-- Main Form --}}
  <form method="POST" action="{{ route('master.users.store') }}" class="space-y-6">
    @csrf

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      {{-- Sub-header Card --}}
      <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
          <i class="fa-solid fa-address-card text-cyan-500"></i>Formulir Data Kredensial & Kepegawaian
        </h3>
      </div>

      {{-- Form Fields --}}
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-6">

          <div class="space-y-1">
            <x-form.input name="name" label="Nama Lengkap" :value="old('name')" required
              class="focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div class="space-y-1">
            <x-form.input name="username" label="Username" :value="old('username')" required
              class="focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div class="space-y-1">
            <x-form.input type="email" name="email" label="Email Resmi" :value="old('email')" required
              class="focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div class="space-y-1">
            <x-form.input type="password" name="password" label="Password Akun" required
              class="focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div class="space-y-1">
            <x-form.input name="nip" label="NIP (Nomor Induk Pegawai)" :value="old('nip')" placeholder="18 digit angka"
              class="font-mono focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div class="space-y-1">
            <x-form.input name="nik" label="NIK (Nomor Induk Kependudukan)" :value="old('nik')" placeholder="16 digit angka" required
              class="font-mono focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div class="space-y-1">
            <x-form.input name="phone" label="No. Telepon / WhatsApp" :value="old('phone')" placeholder="Contoh: 08..."
              class="focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div class="space-y-1">
            <x-form.select name="employee_type" label="Tipe Status Pegawai" required
              class="focus:border-cyan-500 focus:ring-cyan-500">
              @foreach (\App\Enums\EmployeeType::cases() as $type)
                <option value="{{ $type->value }}" {{ old('employee_type') === $type->value ? 'selected' : '' }}>
                  {{ $type->label() }}
                </option>
              @endforeach
            </x-form.select>
          </div>

          <div class="sm:col-span-2 my-1">
            <hr class="border-slate-100">
          </div>

          <div class="space-y-1">
            <x-form.select name="department_id" label="Instansi / Unit Kerja (OPD)"
              class="focus:border-cyan-500 focus:ring-cyan-500">
              <option value="">— Pilih Instansi —</option>
              @foreach ($departments as $d)
                <option value="{{ $d->id }}" {{ old('department_id', auth()->user()->hasRole('super_admin') ? '' : auth()->user()->department_id) == $d->id ? 'selected' : '' }}>
                  {{ $d->display_name }}
                </option>
              @endforeach
            </x-form.select>
          </div>

          <div class="space-y-1">
            <x-form.select name="rank_id" label="Golongan / Pangkat"
              class="focus:border-cyan-500 focus:ring-cyan-500">
              <option value="">— Pilih Pangkat —</option>
              @foreach ($ranks as $r)
                <option value="{{ $r->id }}" {{ old('rank_id') == $r->id ? 'selected' : '' }}>
                  {{ $r->group }} — {{ $r->name }}
                </option>
              @endforeach
            </x-form.select>
          </div>

          <div class="space-y-1">
            <x-form.select name="position_id" label="Jabatan Struktural / Fungsional"
              class="focus:border-cyan-500 focus:ring-cyan-500">
              <option value="">— Pilih Jabatan —</option>
              @foreach ($positions as $p)
                <option value="{{ $p->id }}" {{ old('position_id') == $p->id ? 'selected' : '' }}>
                  {{ $p->name }}
                </option>
              @endforeach
            </x-form.select>
          </div>

          <div class="space-y-1">
            <x-form.select name="role" label="Role Otentikasi Sistem" required
              class="focus:border-cyan-500 focus:ring-cyan-500">
              @foreach (\Spatie\Permission\Models\Role::all() as $role)
                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                  {{ $role->name }}
                </option>
              @endforeach
            </x-form.select>
          </div>

        </div>
      </div>
    </div>

    {{-- Form Actions --}}
    <div class="flex justify-end gap-3">
      <x-ui.button href="{{ route('master.users.index') }}" variant="secondary"
        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
        Batal
      </x-ui.button>

      <x-ui.button type="submit"
        class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-6 py-2.5 text-sm font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
        <x-slot name="icon">
          <i class="fa-solid fa-floppy-disk text-xs"></i>
        </x-slot>
        Simpan Pegawai
      </x-ui.button>
    </div>
  </form>
</div>
@endsection
