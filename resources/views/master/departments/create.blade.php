@extends('layouts.app')
@section('title', 'Tambah Instansi')
@section('page-title', 'Tambah Instansi')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah Instansi</h1>
    <p class="page-subtitle">Tambahkan OPD atau unit kerja baru</p>
  </div>
  <x-ui.button href="{{ route('master.departments.index') }}" variant="secondary">← Kembali</x-ui.button>
</div>

<form method="POST" action="{{ route('master.departments.store') }}" enctype="multipart/form-data">
  @csrf
  <div class="card p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <x-form.input
        name="name"
        label="Nama Unit Kerja"
        :value="old('name')"
        placeholder="Misal: Bidang Tata Usaha"
        required
      />

      <x-form.select
        name="parent_id"
        id="parent_id"
        label="Instansi Induk"
        onchange="toggleFields()"
        required
      >
        @if(auth()->user()->hasRole('super_admin'))
          <option value="" data-type="">— Pilih Instansi Induk (Kosongkan jika OPD baru) —</option>
        @endif
        @foreach($parents as $p)
          <option value="{{ $p->id }}" data-type="{{ $p->type->value }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->display_name }}</option>
        @endforeach
      </x-form.select>

      @if(auth()->user()->hasRole('super_admin'))
      <div id="code_field">
        <x-form.input
          name="code"
          label="Kode"
          :value="old('code')"
          placeholder="Misal: DISDIK"
        />
      </div>

      <div id="type_field">
        <x-form.select
          name="type"
          id="type_select"
          label="Tipe"
          required
        >
          @foreach($types as $t)
            <option value="{{ $t->value }}" {{ old('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
          @endforeach
        </x-form.select>
      </div>
      @else
        <input type="hidden" name="type" id="type_select" value="{{ old('type', $parents[0]->type->value ?? 'opd') }}">
      @endif

      <div class="md:col-span-2">
        <x-form.select
          name="head_id"
          label="Kepala / Pimpinan Unit Kerja"
        >
          <option value="">— Pilih Pimpinan —</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('head_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
          @endforeach
        </x-form.select>
      </div>

      @if(auth()->user()->hasRole('super_admin'))
      <div class="md:col-span-2" id="letterhead_field">
        <x-form.file
          name="letterhead"
          label="Unggah Gambar Kop Surat (PNG/JPG)"
          accept="image/*"
          hint="Disarankan ukuran 1000x200 pixel. Hanya untuk OPD/Instansi Utama."
        />
      </div>
      @endif
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <x-ui.button href="{{ route('master.departments.index') }}" variant="secondary">Batal</x-ui.button>
    <x-ui.button type="submit">Simpan Instansi</x-ui.button>
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

    document.addEventListener('DOMContentLoaded', toggleFields);
</script>
@endsection
