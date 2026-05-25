@extends('layouts.app')
@section('title', 'Tambah Instansi')
@section('page-title', 'Tambah Instansi')

@section('content')
<div class="p-1 space-y-4">

  {{-- Header Halaman Compact --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
        <i class="fa-solid fa-folder-plus text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Tambah Instansi</h1>
        <p class="text-[11px] text-slate-500 font-medium">Tambahkan entitas OPD baru atau sub-struktur unit kerja pendukung</p>
      </div>
    </div>

    <x-ui.button href="{{ route('master.departments.index') }}" variant="secondary"
      class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
    </x-ui.button>
  </div>

  {{-- Form Container Compact --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-3 border-b border-slate-200 bg-slate-50/50">
      <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
        <i class="fa-solid fa-file-signature text-cyan-500"></i>Formulir Registrasi Unit Kerja
      </h3>
    </div>

    <form method="POST" action="{{ route('master.departments.store') }}" enctype="multipart/form-data" class="p-4 space-y-4">
      @csrf

      {{-- Grid Utama Form --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">

        {{-- Input Nama Unit Kerja --}}
        <div class="space-y-0.5">
          <x-form.input name="name" label="Nama Unit Kerja / Struktur" :value="old('name')" placeholder="Misal: Bidang Tata Usaha" required class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
        </div>

        {{-- Dropdown Instansi Induk --}}
        <div class="space-y-0.5">
          <x-form.select name="parent_id" id="parent_id" label="Instansi Induk Pengampu" onchange="toggleFields()" required class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
            @if(auth()->user()->hasRole('super_admin'))
              <option value="" data-type="">— Pilih Instansi Induk (Kosongkan jika OPD baru) —</option>
            @endif
            @foreach($parents as $p)
              <option value="{{ $p->id }}" data-type="{{ $p->type->value }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->display_name }}</option>
            @endforeach
          </x-form.select>
        </div>

        {{-- Logika Kondisional Super Admin --}}
        @if(auth()->user()->hasRole('super_admin'))
          <div id="code_field" class="space-y-0.5">
            <x-form.input name="code" label="Kode Singkatan Instansi" :value="old('code')" placeholder="Misal: DISDIK / KOMINFO" class="font-mono text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          <div id="type_field" class="space-y-0.5">
            <x-form.select name="type" id="type_select" label="Tipe Entitas Wilayah" required class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
              @foreach($types as $t)
                <option value="{{ $t->value }}" {{ old('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
              @endforeach
            </x-form.select>
          </div>
        @else
          <input type="hidden" name="type" id="type_select" value="{{ old('type', $parents[0]->type->value ?? 'opd') }}">
        @endif

        {{-- Pemilihan Kepala / Pimpinan (Penuh 2 Kolom) --}}
        <div class="md:col-span-2 space-y-0.5">
          <x-form.select name="head_id" label="Kepala / Pimpinan Penanggung Jawab" class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
            <option value="">— Pilih Pimpinan —</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}" {{ old('head_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
          </x-form.select>
        </div>

        {{-- Unggah Kop Surat Khusus Super Admin --}}
        @if(auth()->user()->hasRole('super_admin'))
          <div class="md:col-span-2 space-y-0.5" id="letterhead_field">
            <x-form.file name="letterhead" label="Unggah Berkas Kop Resmi Surat Kedinasan (PNG/JPG)" accept="image/*" class="text-xs focus:border-cyan-500 focus:ring-cyan-500" hint="Rekomendasi rasio cetak 1000x200 pixel. Hanya diwajibkan untuk level OPD utama." />
          </div>
        @endif
      </div>

      {{-- Form Actions Footer Compact --}}
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
        <a href="{{ route('master.departments.index') }}"
          class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
          Batal
        </a>

        <button type="submit"
          class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-4 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
          <i class="fa-solid fa-floppy-disk text-[11px]"></i> Simpan Instansi
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Script Sinkronisasi Kolom --}}
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
