@extends('layouts.app')

@section('title', 'Edit Anggaran')
@section('page-title', 'Edit Data Anggaran')

@section('content')
<div class="p-1 space-y-4">

  {{-- Header Halaman Compact --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
        <i class="fa-solid fa-file-pen text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Ubah Data DPA</h1>
        <p class="text-[11px] text-slate-500 font-medium">Perbarui formulir rincian anggaran secara ringkas di bawah ini</p>
      </div>
    </div>

    <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary"
      class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
    </x-ui.button>
  </div>

  {{-- Form Container Compact --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">

    <form action="{{ route('master.budgets.update', $budget->id) }}" method="POST" class="p-4 space-y-4">
      @csrf
      @method('PUT')

      {{-- Grid Utama Form --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">

        {{-- Baris 1: SKPD (Mengambil 2 Kolom jika Super Admin) --}}
        @if(auth()->user()->hasRole('super_admin'))
          <div class="md:col-span-2 space-y-0.5">
            <x-form.select name="department_id" label="SKPD / Unit Kerja" required class="focus:border-cyan-500 focus:ring-cyan-500 text-xs py-1.5">
              @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ old('department_id', $budget->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
              @endforeach
            </x-form.select>
          </div>
        @else
          <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
          <div class="md:col-span-2 space-y-0.5">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">SKPD / Unit Kerja Terikat</label>
            <div class="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded flex items-center gap-2 text-xs font-bold text-slate-700 h-[34px]">
              <i class="fa-solid fa-building text-slate-400 text-[11px]"></i>
              {{ auth()->user()->department->name }}
            </div>
          </div>
        @endif

        {{-- Baris 1: Tahun Anggaran (1 Kolom) --}}
        <div class="space-y-0.5">
          <x-form.select name="year" label="Tahun Anggaran" required class="focus:border-cyan-500 focus:ring-cyan-500 text-xs py-1.5">
            @for($y = date('Y') + 1; $y >= 2019; $y--)
              <option value="{{ $y }}" {{ old('year', $budget->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
          </x-form.select>
        </div>

        {{-- Baris 2: Kode Rekening (1 Kolom) --}}
        <div class="space-y-0.5">
          <x-form.input type="text" name="account_code" label="Kode Rekening" :value="old('account_code', $budget->account_code)" placeholder="Contoh: 5.1.02.04..." class="font-mono text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
        </div>

        {{-- Baris 2: Jenis Anggaran (1 Kolom) --}}
        <div class="space-y-0.5">
          <x-form.select name="type" label="Jenis Anggaran" class="focus:border-cyan-500 focus:ring-cyan-500 text-xs py-1.5">
            <option value="">-- Pilih Jenis --</option>
            <option value="Perjalanan Dinas Dalam Daerah" {{ old('type', $budget->type) == 'Perjalanan Dinas Dalam Daerah' ? 'selected' : '' }}>Perjalanan Dinas Dalam Daerah</option>
            <option value="Perjalanan Dinas Luar Daerah" {{ old('type', $budget->type) == 'Perjalanan Dinas Luar Daerah' ? 'selected' : '' }}>Perjalanan Dinas Luar Daerah</option>
            <option value="Bimtek" {{ old('type', $budget->type) == 'Bimtek' ? 'selected' : '' }}>Bimtek</option>
            <option value="Perjalanan Lainnya" {{ old('type', $budget->type) == 'Perjalanan Lainnya' ? 'selected' : '' }}>Perjalanan Lainnya</option>
          </x-form.select>
        </div>

        {{-- Baris 2: Sumber Dana / Mata Anggaran (1 Kolom) --}}
        <div class="space-y-0.5">
          <x-form.select name="source" label="Mata Anggaran" class="focus:border-cyan-500 focus:ring-cyan-500 text-xs py-1.5">
            <option value="">-- Pilih Sumber --</option>
            <option value="APBD" {{ old('source', $budget->source) == 'APBD' ? 'selected' : '' }}>APBD</option>
            <option value="APBD-P" {{ old('source', $budget->source) == 'APBD-P' ? 'selected' : '' }}>APBD-P</option>
            <option value="APBN" {{ old('source', $budget->source) == 'APBN' ? 'selected' : '' }}>APBN</option>
          </x-form.select>
        </div>

        {{-- Baris 3: Program Utama (Penuh 3 Kolom) --}}
        <div class="md:col-span-3 space-y-0.5">
          <x-form.input type="text" name="program" label="Nama Program Utama" :value="old('program', $budget->program)" placeholder="Masukkan nama program..." class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
        </div>

        {{-- Baris 4: Kegiatan / Sub Kegiatan (Penuh 3 Kolom) --}}
        <div class="md:col-span-3 space-y-0.5">
          <x-form.input type="text" name="activity" label="Nama Kegiatan / Sub Kegiatan" :value="old('activity', $budget->activity)" placeholder="Masukkan nama kegiatan..." class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
        </div>

        {{-- Baris 5: Total Anggaran (1 Kolom) --}}
        <div class="space-y-0.5">
          <x-form.input type="number" name="total_amount" label="Pagu Total (Rp)" :value="old('total_amount', (int)$budget->total_amount)" placeholder="0" class="font-mono font-bold text-xs py-1.5 text-slate-800 focus:border-cyan-500 focus:ring-cyan-500" />
        </div>

        {{-- Baris 5: Uraian Penjelasan (Mengambil sisa 2 Kolom) --}}
        <div class="md:col-span-2 space-y-0.5">
          <x-form.input type="text" name="description" label="Uraian Singkat Penjelasan" :value="old('description', $budget->description)" placeholder="Deskripsi pelengkap anggaran..." class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
        </div>

      </div>

      {{-- Form Actions Footer Compact --}}
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
        <a wire:navigate href="{{ route('master.budgets.index') }}" class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
          Batal
        </a>

        <button type="submit"
          class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-4 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
          <i class="fa-solid fa-floppy-disk text-[11px]"></i> Perbarui Data
        </button>
      </div>

    </form>
  </div>
</div>
@endsection
