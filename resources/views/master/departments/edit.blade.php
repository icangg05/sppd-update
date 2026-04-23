@extends('layouts.app')
@section('title', 'Edit Profil OPD')
@section('page-title', 'Edit Profil OPD')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Edit Profil OPD</h1>
    <p class="page-subtitle">Ubah informasi OPD atau unit kerja</p>
  </div>
  <a href="{{ auth()->user()->hasRole('super_admin') ? route('master.departments.index') : route('master.departments.show', $department->id) }}" class="btn-secondary">
    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
    Kembali
  </a>
</div>

<form method="POST" action="{{ route('master.departments.update', $department->id) }}">
  @csrf
  @method('PUT')
  <div class="card p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="form-label">Nama Instansi / OPD <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $department->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="form-label">Instansi Induk</label>
        <select name="parent_id" id="parent_id" class="form-select" onchange="toggleFields()" {{ !auth()->user()->hasRole('super_admin') ? 'disabled' : '' }}>
          <option value="" data-type="">— Tidak ada (Top-level) —</option>
          @foreach($parents as $p)
            <option value="{{ $p->id }}" data-type="{{ $p->type->value }}" {{ old('parent_id', $department->parent_id) == $p->id ? 'selected' : '' }}>{{ $p->display_name }}</option>
          @endforeach
        </select>
        @if(!auth()->user()->hasRole('super_admin'))
          <input type="hidden" name="parent_id" value="{{ $department->parent_id }}">
        @endif
      </div>

      <div id="code_field">
        <label class="form-label">Kode OPD</label>
        <input type="text" name="code" value="{{ old('code', $department->code) }}" class="form-input" placeholder="Misal: DISDIK">
        @error('code') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div id="type_field">
        <label class="form-label">Tipe <span class="text-red-500">*</span></label>
        <select name="type" id="type_select" class="form-select" required {{ !auth()->user()->hasRole('super_admin') ? 'disabled' : '' }}>
          @foreach($types as $t)
            <option value="{{ $t->value }}" {{ old('type', $department->type->value) === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
          @endforeach
        </select>
        @if(!auth()->user()->hasRole('super_admin'))
          <input type="hidden" name="type" value="{{ $department->type->value }}">
        @endif
      </div>

      <div class="md:col-span-2">
        <label class="form-label">Kepala / Pimpinan Instansi</label>
        <select name="head_id" class="form-select">
          <option value="">— Pilih Pimpinan —</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('head_id', $department->head_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} {{ $user->nip ? '('.$user->nip.')' : '' }}</option>
          @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Hanya menampilkan pegawai yang berada pada OPD ini.</p>
        @error('head_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div class="md:col-span-2" id="letterhead_field">
        <label class="form-label">Kop Surat (Teks/Baris Kop)</label>
        <textarea name="letterhead" class="form-input" rows="4" placeholder="PEMERINTAH KOTA KENDARI&#10;DINAS KOMUNIKASI DAN INFORMATIKA&#10;Jalan ...">{{ old('letterhead', $department->letterhead) }}</textarea>
        @error('letterhead') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <a href="{{ auth()->user()->hasRole('super_admin') ? route('master.departments.index') : route('master.departments.show', $department->id) }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
  </div>
</form>

<script>
    function toggleFields() {
        const parentSelect = document.getElementById('parent_id');
        const parentId = parentSelect.value;
        const selectedOption = parentSelect.options[parentSelect.selectedIndex];
        const parentType = selectedOption.getAttribute('data-type');
        
        const codeField = document.getElementById('code_field');
        const typeField = document.getElementById('type_field');
        const typeSelect = document.getElementById('type_select');
        const letterheadField = document.getElementById('letterhead_field');
        
        if (parentId) {
            codeField.style.display = 'none';
            letterheadField.style.display = 'none';
            
            // Auto set type to parent's type and hide the selection
            if (parentType) {
                typeSelect.value = parentType;
                typeField.style.display = 'none';
            }
        } else {
            codeField.style.display = 'block';
            letterheadField.style.display = 'block';
            typeField.style.display = 'block';
        }
    }
    
    // Run on load
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>
@endsection
