@extends('layouts.app')
@section('title', 'Edit Profil OPD')
@section('page-title', 'Edit Profil OPD')

@section('content')
  @php
    $isSuperAdmin = auth()->user()->hasRole('super_admin');
  @endphp

  <div class="p-1 space-y-4">

    {{-- Header Halaman Compact --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
      <div class="flex items-center gap-2.5">
        <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
          <i class="fa-solid fa-building-gear text-base"></i>
        </div>
        <div>
          <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Edit Profil OPD</h1>
          <p class="text-[11px] text-slate-500 font-medium">Perbarui atribut informasi profil instansi atau sub-unit kerja
            terkait</p>
        </div>
      </div>

      <x-ui.button
        href="{{ $isSuperAdmin ? route('master.departments.index') : route('master.departments.show', $department->id) }}"
        variant="secondary"
        class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
      </x-ui.button>
    </div>

    {{-- Form Container Compact --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
          <i class="fa-solid fa-pen-to-square text-cyan-500"></i>Formulir Pembaruan Data Atribut OPD
        </h3>
      </div>

      <form method="POST" action="{{ route('master.departments.update', $department->id) }}" enctype="multipart/form-data"
        class="p-4 space-y-4">
        @csrf
        @method('PUT')

        {{-- Grid Utama Form --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">

          {{-- Input Nama Instansi --}}
          <div class="space-y-0.5">
            <x-form.input name="name" label="Nama Instansi / OPD" :value="old('name', $department->name)" required
              class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          {{-- Dropdown Instansi Induk (Disabled Kondisional) --}}
          <div class="space-y-0.5">
            <x-form.select
              name="parent_id"
              id="parent_id"
              label="Instansi Induk Pengampu"
              onchange="toggleFields()"
              @disabled(!$isSuperAdmin)
              class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
              <option value="" data-type="">— Tidak ada (Top-level) —</option>
              @foreach ($parents as $p)
                <option
                  value="{{ $p->id }}"
                  data-type="{{ $p->type->value }}"
                  {{ old('parent_id', $department->parent_id) == $p->id ? 'selected' : '' }}>
                  {{ $p->display_name }}
                </option>
              @endforeach
            </x-form.select>

            @if (!$isSuperAdmin)
              <input type="hidden" name="parent_id" value="{{ $department->parent_id }}">
            @endif
          </div>

          {{-- Input Kode OPD --}}
          <div id="code_field" class="space-y-0.5">
            <x-form.input name="code" label="Kode Singkatan OPD" :value="old('code', $department->code)"
              placeholder="Misal: DISDIK" class="font-mono text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          {{-- Dropdown Tipe (Disabled Kondisional) --}}
          <div id="type_field" class="space-y-0.5">
            <x-form.select
              name="type"
              id="type_select"
              label="Tipe Entitas Wilayah"
              required
              @disabled(!$isSuperAdmin)
              class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
              @foreach ($types as $t)
                <option
                  value="{{ $t->value }}"
                  {{ old('type', $department->type->value) === $t->value ? 'selected' : '' }}>
                  {{ $t->label() }}
                </option>
              @endforeach
            </x-form.select>

            @if (!$isSuperAdmin)
              <input type="hidden" name="type" value="{{ $department->type->value }}">
            @endif
          </div>

          {{-- Pemilihan Kepala / Pimpinan (Penuh 2 Kolom) --}}
          <div class="md:col-span-2 space-y-0.5">
            <x-form.select name="head_id" label="Kepala / Pimpinan Instansi"
              class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
              <option value="">— Pilih Pimpinan —</option>
              @foreach ($users as $user)
                <option value="{{ $user->id }}" {{ old('head_id', $department->head_id) == $user->id ? 'selected' : '' }}>
                  {{ $user->name }} {{ $user->nip ? '(' . $user->nip . ')' : '' }}
                </option>
              @endforeach
            </x-form.select>
            <p class="text-[10px] text-slate-400 font-medium mt-0.5">
              <i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i>Hanya memuat daftar aparatur pegawai yang terdaftar
              di lingkup OPD ini.
            </p>
          </div>

          {{-- Sektor Unggah & Pratinjau Kop Surat (Penuh 2 Kolom) --}}
          <div class="md:col-span-2 space-y-1.5" id="letterhead_field">
            <x-form.file name="letterhead" label="Unggah Berkas Kop Resmi Surat Kedinasan (PNG/JPG)" accept="image/*"
              class="text-xs focus:border-cyan-500 focus:ring-cyan-500"
              hint="Rekomendasi ukuran cetak 1000x200 pixel. Gunakan ekstensi berkas PNG latar transparan." />

            {{-- Blok Pratinjau Kop Eksisting --}}
            @if ($department->letterhead && \Illuminate\Support\Str::contains($department->letterhead, '/'))
              <div class="p-2.5 bg-slate-50 border border-slate-200 rounded max-w-xl">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Kop Lampiran Aktif Saat Ini:</p>
                <img src="{{ asset('storage/' . $department->letterhead) }}"
                  class="max-h-16 rounded border border-slate-300/70 p-1 bg-white shadow-sm">
              </div>
            @else
              <div class="p-2.5 bg-amber-50 border border-amber-200 rounded text-amber-700 text-[11px] font-medium flex items-start gap-2 max-w-xl">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 shrink-0"></i>
                <span>Peringatan: Struktur kop instansi saat ini masih berupa data teks usang. Sistem akan otomatis
                  menggantinya menjadi format gambar jika Anda mengunggah berkas baru.</span>
              </div>
            @endif
          </div>
        </div>

        {{-- Form Actions Footer Compact --}}
        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
          <a href="{{ $isSuperAdmin ? route('master.departments.index') : route('master.departments.show', $department->id) }}"
            class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
            Batal
          </a>

          <button type="submit"
            class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-4 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
            <i class="fa-solid fa-floppy-disk text-[11px]"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Script Sinkronisasi Sinkron --}}
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
