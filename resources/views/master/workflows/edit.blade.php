@extends('layouts.app')
@section('title', 'Edit Workflow SPPD')
@section('page-title', 'Edit Workflow SPPD')

@section('content')
<div class="p-1 space-y-4">

  {{-- Header Halaman Compact --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
        <i class="fa-solid fa-route text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Edit Workflow SPPD</h1>
        <p class="text-[11px] text-slate-500 font-medium">Ubah parameter aturan urutan dan batas alur persetujuan skema SPPD terkait</p>
      </div>
    </div>

    <x-ui.button href="{{ route('master.workflows.index') }}" variant="secondary"
      class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
    </x-ui.button>
  </div>

  <form method="POST" action="{{ route('master.workflows.update', $workflow->id) }}" class="space-y-4">
    @csrf
    @method('PUT')

    {{-- Blok 1: Parameter Informasi Aturan --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
          <i class="fa-solid fa-sliders text-cyan-500"></i>Konfigurasi Parameter Aturan
        </h3>
      </div>

      <div class="p-4 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">

          {{-- Nama Workflow --}}
          <div class="md:col-span-2 space-y-0.5">
            <x-form.input name="name" label="Nama Skema Workflow" :value="old('name', $workflow->name)" placeholder="Misal: Alur Staf Reguler Luar Daerah" required class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          {{-- Filter Tipe Instansi --}}
          <div class="space-y-0.5">
            <x-form.select name="department_type" label="Berlaku Untuk Tipe Instansi (Opsional)" class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
              <option value="">-- Semua Tipe Instansi --</option>
              @foreach($departmentTypes as $type)
                <option value="{{ $type->value }}" {{ old('department_type', $workflow->department_type?->value) === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
              @endforeach
            </x-form.select>
            <p class="text-[10px] text-slate-400 font-medium">Kosongkan jika aturan ini bersifat universal bagi semua OPD.</p>
          </div>

          {{-- Filter Peran Pemohon --}}
          <div class="space-y-0.5">
            <x-form.select name="applicant_role" label="Berlaku Untuk Peran Pemohon (Opsional)" class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500">
              <option value="">-- Semua Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ old('applicant_role', $workflow->applicant_role) === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
              @endforeach
            </x-form.select>
            <p class="text-[10px] text-slate-400 font-medium">Kosongkan jika berlaku untuk semua tingkat golongan pemohon.</p>
          </div>

          {{-- Pilihan Ruang Lingkup Wilayah Tujuan --}}
          <div class="md:col-span-2 space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Cakupan Wilayah Tujuan (Opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded text-xs">
              @foreach($domains as $domain)
                <x-form.checkbox name="destination[]" :value="$domain->value" :checked="is_array(old('destination', $workflow->destination)) && in_array($domain->value, old('destination', $workflow->destination))" label="{{ $domain->label() }}" wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            <p class="text-[10px] text-slate-400 font-medium"><i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i>Dapat memilih lebih dari satu cakupan wilayah. Jika dikosongkan, sistem menganggap sah untuk semua destinasi.</p>
          </div>

          {{-- Status Keaktifan Aturan --}}
          <div class="md:col-span-2 pt-1">
            <x-form.checkbox name="is_active" label="Aktifkan Aturan Perubahan Workflow Langsung" :checked="old('is_active', $workflow->is_active)" wrapper-class="flex items-center gap-2 font-bold text-xs text-slate-800 cursor-pointer" />
          </div>
        </div>
      </div>
    </div>

    {{-- Blok 2: Manajemen Alur Urutan Persetujuan --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
        <div class="space-y-0.5">
          <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
            <i class="fa-solid fa-diagram-next text-cyan-500"></i>Alur Urutan Persetujuan (Steps) <span class="text-rose-500">*</span>
          </h3>
          <p class="text-[10px] text-slate-400 font-medium">Tentukan skema tingkatan peran aparatur penandatangan dari urutan awal hingga akhir.</p>
        </div>

        <button type="button" id="add-step-btn"
          class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
          <i class="fa-solid fa-plus text-cyan-600 text-[10px]"></i> Sisipkan Tahap
        </button>
      </div>

      <div class="p-4">
        @error('steps')
          <div class="p-2.5 mb-3 bg-rose-50 border border-rose-200 rounded text-rose-700 text-[11px] font-medium flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}
          </div>
        @enderror

        {{-- Container Step Items Ditempel Disini --}}
        <div id="steps-container" class="space-y-2 max-w-2xl"></div>
      </div>
    </div>

    {{-- Form Actions Footer Compact --}}
    <div class="pt-1 flex items-center justify-end gap-2">
      <a href="{{ route('master.workflows.index') }}"
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

{{-- Template Item Alur Dinamis --}}
<template id="step-template">
  <div class="step-item flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded transition-all duration-150 animate-fade-in">
    <div class="step-number w-6 h-6 flex items-center justify-center bg-slate-200 border border-slate-300 text-slate-700 font-black rounded text-[11px] shrink-0 shadow-inner">1</div>

    <div class="flex-1">
      <select name="steps[]" class="block w-full rounded border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-800 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>
        <option value="">-- Pilih Role Approver --</option>
        @foreach($roles as $role)
          <option value="{{ $role->name }}">{{ $role->name }}</option>
        @endforeach
      </select>
    </div>

    <button type="button" class="remove-step-btn p-1.5 text-slate-400 border border-transparent rounded hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 transition-colors shrink-0" title="Hapus Tahap">
      <i class="fa-solid fa-trash-can text-xs"></i>
    </button>
  </div>
</template>

{{-- Script Pengendali Operasi Baris --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('steps-container');
  const addBtn = document.getElementById('add-step-btn');
  const template = document.getElementById('step-template').innerHTML;

  const oldSteps = @json(old('steps', $workflow->steps ?? []));

  if (oldSteps.length > 0) {
    oldSteps.forEach(role => addStep(role));
  } else {
    addStep();
  }

  addBtn.addEventListener('click', () => addStep());

  function addStep(selectedRole = '') {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = template;
    const stepEl = tempDiv.firstElementChild;

    if (selectedRole) {
      const select = stepEl.querySelector('select');
      select.value = selectedRole;
    }

    stepEl.querySelector('.remove-step-btn').addEventListener('click', function() {
      if (container.children.length > 1) {
        stepEl.remove();
        updateNumbers();
      } else {
        alert('Skema workflow wajib memiliki sekurang-kurangnya 1 tahapan otorisasi pejabat approval.');
      }
    });

    container.appendChild(stepEl);
    updateNumbers();
  }

  function updateNumbers() {
    const items = container.querySelectorAll('.step-item');
    items.forEach((item, index) => {
      item.querySelector('.step-number').textContent = index + 1;
    });
  }
});
</script>
@endsection
