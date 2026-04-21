@extends('layouts.app')
@section('title', 'Tambah Instansi')
@section('page-title', 'Tambah Instansi')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah Instansi</h1>
    <p class="page-subtitle">Tambahkan OPD atau unit kerja baru</p>
  </div>
  <a href="{{ route('master.departments.index') }}" class="btn-secondary">← Kembali</a>
</div>

<form method="POST" action="{{ route('master.departments.store') }}">
  @csrf
  <div class="card p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Instansi <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Kode <span class="text-red-500">*</span></label>
        <input type="text" name="code" value="{{ old('code') }}" class="form-input" placeholder="Misal: DISDIK" required>
        @error('code') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Tipe <span class="text-red-500">*</span></label>
        <select name="type" class="form-select" required>
          @foreach($types as $t)
            <option value="{{ $t->value }}" {{ old('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Instansi Induk</label>
        <select name="parent_id" class="form-select">
          <option value="">— Tidak ada (Top-level) —</option>
          @foreach($parents as $p)
            <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Kepala / Pimpinan Instansi</label>
        <select name="head_id" class="form-select">
          <option value="">— Pilih Pimpinan —</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('head_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
          @endforeach
        </select>
        @error('head_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div class="md:col-span-2">
        <label class="form-label">Kop Surat (Teks/Baris Kop)</label>
        <textarea name="letterhead" class="form-input" rows="3" placeholder="PEMERINTAH KOTA KENDARI&#10;DINAS KOMUNIKASI DAN INFORMATIKA&#10;Jalan ...">{{ old('letterhead') }}</textarea>
        @error('letterhead') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <a href="{{ route('master.departments.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Instansi</button>
  </div>
</form>
@endsection
