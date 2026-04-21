@extends('layouts.app')
@section('title', 'Edit Workflow SPPD')
@section('page-title', 'Edit Workflow SPPD')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Edit Workflow SPPD</h1>
    <p class="page-subtitle">Ubah aturan persetujuan SPPD</p>
  </div>
  <a href="{{ route('master.workflows.index') }}" class="btn-secondary">← Kembali</a>
</div>

<form method="POST" action="{{ route('master.workflows.update', $workflow->id) }}">
  @csrf
  @method('PUT')
  <div class="card p-6 mb-6">
    <h3 class="font-semibold text-slate-900 mb-4">Informasi Aturan</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="md:col-span-2">
        <label class="form-label">Nama Workflow <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $workflow->name) }}" class="form-input" placeholder="Misal: Alur Staff Reguler Luar Daerah" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Berlaku Untuk Instansi (Opsional)</label>
        <select name="department_type" class="form-select">
          <option value="">-- Semua Tipe Instansi --</option>
          @foreach($departmentTypes as $type)
            <option value="{{ $type->value }}" {{ old('department_type', $workflow->department_type?->value) === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
          @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Kosongkan jika berlaku untuk semua instansi.</p>
      </div>
      <div>
        <label class="form-label">Berlaku Untuk Role Pemohon (Opsional)</label>
        <select name="applicant_role" class="form-select">
          <option value="">-- Semua Role --</option>
          @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ old('applicant_role', $workflow->applicant_role) === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
          @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Kosongkan jika berlaku untuk semua peran pemohon.</p>
      </div>
      <div>
        <label class="form-label">Berlaku Untuk Tujuan (Opsional)</label>
        <select name="destination" class="form-select">
          <option value="">-- Semua Tujuan --</option>
          @foreach($domains as $domain)
            <option value="{{ $domain->value }}" {{ old('destination', $workflow->destination?->value) === $domain->value ? 'selected' : '' }}>{{ $domain->label() }}</option>
          @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Dalam Daerah atau Luar Daerah.</p>
      </div>
      <div class="flex items-center mt-6">
        <label class="flex items-center cursor-pointer">
          <input type="checkbox" name="is_active" class="form-checkbox text-primary-600 rounded" {{ old('is_active', $workflow->is_active) ? 'checked' : '' }}>
          <span class="ml-2 text-sm text-slate-700 font-medium">Aktifkan Workflow Ini</span>
        </label>
      </div>
    </div>
  </div>

  <div class="card p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-slate-900">Alur Persetujuan (Steps) <span class="text-red-500">*</span></h3>
      <button type="button" id="add-step-btn" class="btn-secondary text-sm py-1.5">
        + Tambah Tahap
      </button>
    </div>
    <p class="text-sm text-slate-500 mb-4">Urutkan peran (role) yang harus menyetujui SPPD dari awal hingga akhir.</p>
    
    @error('steps') <p class="form-error mb-4">{{ $message }}</p> @enderror

    <div id="steps-container" class="space-y-3">
      <!-- Steps will be generated here by JS -->
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <a href="{{ route('master.workflows.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
  </div>
</form>

<template id="step-template">
  <div class="step-item flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
    <div class="step-number w-8 h-8 flex items-center justify-center bg-slate-200 text-slate-700 font-bold rounded-full text-sm">
      1
    </div>
    <div class="flex-1">
      <select name="steps[]" class="form-select" required>
        <option value="">-- Pilih Role Approver --</option>
        @foreach($roles as $role)
          <option value="{{ $role->name }}">{{ $role->name }}</option>
        @endforeach
      </select>
    </div>
    <button type="button" class="remove-step-btn p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
    </button>
  </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('steps-container');
  const addBtn = document.getElementById('add-step-btn');
  const template = document.getElementById('step-template').innerHTML;

  // Initialize from old input if any, or from db steps
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
        alert('Workflow harus memiliki minimal 1 tahap persetujuan.');
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
