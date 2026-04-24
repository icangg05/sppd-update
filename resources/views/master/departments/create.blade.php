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

<form method="POST" action="{{ route('master.departments.store') }}" enctype="multipart/form-data">
  @csrf
  <div class="card p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Unit Kerja <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="Misal: Bidang Tata Usaha" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="form-label">Instansi Induk <span class="text-red-500">*</span></label>
        <select name="parent_id" id="parent_id" class="form-select" onchange="toggleFields()" required>
          @if(auth()->user()->hasRole('super_admin'))
            <option value="" data-type="">— Pilih Instansi Induk (Kosongkan jika OPD baru) —</option>
          @endif
          @foreach($parents as $p)
            <option value="{{ $p->id }}" data-type="{{ $p->type->value }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->display_name }}</option>
          @endforeach
        </select>
        @error('parent_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      @if(auth()->user()->hasRole('super_admin'))
      <div id="code_field">
        <label class="form-label">Kode</label>
        <input type="text" name="code" value="{{ old('code') }}" class="form-input" placeholder="Misal: DISDIK">
        @error('code') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div id="type_field">
        <label class="form-label">Tipe <span class="text-red-500">*</span></label>
        <select name="type" id="type_select" class="form-select" required>
          @foreach($types as $t)
            <option value="{{ $t->value }}" {{ old('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
          @endforeach
        </select>
      </div>
      @else
        {{-- Untuk Admin OPD, tipe akan dikirim via hidden input yang diupdate lewat JS --}}
        <input type="hidden" name="type" id="type_select" value="{{ old('type', $parents[0]->type->value ?? 'opd') }}">
      @endif

      <div class="md:col-span-2">
        <label class="form-label">Kepala / Pimpinan Unit Kerja</label>
        <select name="head_id" class="form-select">
          <option value="">— Pilih Pimpinan —</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('head_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
          @endforeach
        </select>
        @error('head_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      @if(auth()->user()->hasRole('super_admin'))
      <div class="md:col-span-2" id="letterhead_field">
        <label class="form-label font-bold text-slate-700">Unggah Gambar Kop Surat (PNG/JPG)</label>
        <input type="file" name="letterhead" class="form-input" accept="image/*">
        <p class="mt-1 text-[10px] text-slate-500 italic">Disarankan ukuran 1000x200 pixel. Hanya untuk OPD/Instansi Utama.</p>
        @error('letterhead') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      @endif
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <a href="{{ route('master.departments.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Instansi</button>
  </div>
</form>

<script>
    function toggleFields() {
        const parentSelect = document.getElementById('parent_id');
        if (!parentSelect) return;

        const parentId = parentSelect.value;
        const selectedOption = parentSelect.options[parentSelect.selectedIndex];
        const parentType = selectedOption ? selectedOption.getAttribute('data-type') : null;
        
        const codeField = document.getElementById('code_field');
        const typeField = document.getElementById('type_field');
        const typeSelect = document.getElementById('type_select');
        const letterheadField = document.getElementById('letterhead_field');
        
        if (parentId) {
            if (codeField) codeField.style.display = 'none';
            if (letterheadField) letterheadField.style.display = 'none';
            
            // Auto set type to parent's type
            if (parentType && typeSelect) {
                typeSelect.value = parentType;
                if (typeField) typeField.style.display = 'none';
            }
        } else {
            if (codeField) codeField.style.display = 'block';
            if (letterheadField) letterheadField.style.display = 'block';
            if (typeField) typeField.style.display = 'block';
        }
    }
    
    // Run on load
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>
@endsection
