@extends('layouts.app')
@section('title', 'Detail Pegawai')
@section('page-title', 'Detail Pegawai')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Profil Pegawai</h1>
    <p class="page-subtitle">Detail informasi pegawai atau pengguna sistem</p>
  </div>
  <div class="flex gap-2">
    <a href="{{ route('master.users.index') }}" class="btn-secondary">
      <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
      Kembali
    </a>
    <a href="{{ route('master.users.edit', $user->id) }}" class="btn-primary">
      <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
      Edit Data
    </a>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="md:col-span-1 space-y-6">
    <div class="card p-6 flex flex-col items-center text-center">
      <div class="w-24 h-24 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-bold text-3xl mb-4">
        {{ strtoupper(substr($user->name, 0, 1)) }}
      </div>
      <h3 class="text-xl font-bold text-slate-900 mb-1">{{ $user->name }}</h3>
      <p class="text-slate-500 mb-3">{{ $user->position?->name ?? 'Pegawai' }}</p>
      
      <div class="w-full pt-4 border-t border-slate-100 flex flex-col gap-2">
        @if ($user->is_active)
          <span class="badge bg-emerald-100 text-emerald-800 self-center">Status: Aktif</span>
        @else
          <span class="badge bg-red-100 text-red-800 self-center">Status: Nonaktif</span>
        @endif
        
        <div class="mt-2 text-sm text-slate-600">
          @foreach ($user->roles as $role)
            <span class="inline-block px-2 py-1 bg-slate-100 rounded text-slate-700 border border-slate-200 m-0.5">{{ $role->name }}</span>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="md:col-span-2">
    <div class="card p-6">
      <h3 class="text-lg font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Informasi Detail</h3>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6">
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">NIP / ID</label>
          <p class="text-base text-slate-900 font-mono">{{ $user->nip ?? '-' }}</p>
        </div>
        
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Username</label>
          <p class="text-base text-slate-900">{{ $user->username }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Email</label>
          <p class="text-base text-slate-900">{{ $user->email }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">No. Telepon</label>
          <p class="text-base text-slate-900">{{ $user->phone ?? '-' }}</p>
        </div>
        
        <div class="sm:col-span-2 pt-2 pb-2">
          <hr class="border-slate-100">
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Tipe Pegawai</label>
          <p class="text-base text-slate-900">{{ $user->employee_type->label() }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Golongan/Pangkat</label>
          <p class="text-base text-slate-900">{{ $user->rank ? $user->rank->group . ' — ' . $user->rank->name : '-' }}</p>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Instansi / Unit Kerja</label>
          <p class="text-base text-slate-900">{{ $user->department?->name ?? 'Belum ada instansi' }}</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
