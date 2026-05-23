@extends('layouts.app')
@section('title', 'Edit Workflow SPPD')
@section('page-title', 'Edit Workflow SPPD')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Edit Workflow SPPD</h1>
    <p class="page-subtitle">Ubah aturan persetujuan SPPD</p>
  </div>
  <x-ui.button href="{{ route('master.workflows.index') }}" variant="secondary">← Kembali</x-ui.button>
</div>

<form method="POST" action="{{ route('master.workflows.update', $workflow->id) }}">
  @csrf
  @method('PUT')
  <div class="card p-6 mb-6">
    <h3 class="font-semibold text-slate-900 mb-4">Informasi Aturan</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="md:col-span-2">
        <x-form.input
          name="name"
          label="Nama Workflow"
          :value="old('name', $workflow->name)"
          placeholder="Misal: Alur Staff Reguler Luar Daerah"
          required
        />
      </div>

      <x-form.select
        name="department_type"
        label="Berlaku Untuk Instansi (Opsional)"
        hint="Kosongkan jika berlaku untuk semua instansi."
      >
        <option value="">-- Semua Tipe Instansi --</option>
        @foreach($departmentTypes as $type)
          <option value="{{ $type->value }}" {{ old('department_type', $workflow->department_type?->value) === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
        @endforeach
      </x-form.select>

      <x-form.select
        name="applicant_role"
        label="Berlaku Untuk Role Pemohon (Opsional)"
        hint="Kosongkan jika berlaku untuk semua peran pemohon."
      >
        <option value="">-- Semua Role --</option>
        @foreach($roles as $role)
          <option value="{{ $role->name }}" {{ old('applicant_role', $workflow->applicant_role) === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
        @endforeach
      </x-form.select>

      <div class="md:col-span-2">
        <p class="form-label mb-2 block">Berlaku Untuk Tujuan (Opsional)</p>
        <div class="flex flex-wrap gap-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
          @foreach($domains as $domain)
            <x-form.checkbox
              name="destination[]"
              :value="$domain->value"
              :checked="is_array(old('destination', $workflow->destination)) && in_array($domain->value, old('destination', $workflow->destination))"
              label="{{ $domain->label() }}"
              wrapperClass="flex items-center"
            />
          @endforeach
        </div>
        <p class="text-xs text-slate-500 mt-1">Pilih satu atau lebih. Kosongkan jika berlaku untuk semua tujuan.</p>
      </div>

      <div class="flex items-center mt-6">
        <x-form.checkbox
          name="is_active"
          label="Aktifkan Workflow Ini"
          :checked="old('is_active', $workflow->is_active)"
          wrapperClass="flex items-center"
        />
      </div>
    </div>
  </div>

  <div class="card p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-slate-900">Alur Persetujuan (Steps) <span class="text-red-500">*</span></h3>
      <x-ui.button type="button" id="add-step-btn" variant="secondary" class="text-sm py-1.5">
        + Tambah Tahap
      </x-ui.button>
    </div>
    <p class="text-sm text-slate-500 mb-4">Urutkan peran (role) yang harus menyetujui SPPD dari awal hingga akhir.</p>

    @error('steps') <p class="form-error mb-4">{{ $message }}</p> @enderror

    <div id="steps-container" class="space-y-3"></div>
  </div>

  <div class="flex justify-end gap-3">
    <x-ui.button href="{{ route('master.workflows.index') }}" variant="secondary">Batal</x-ui.button>
    <x-ui.button type="submit">Simpan Perubahan</x-ui.button>
  </div>
</form>

<template id="step-template">
  <div class="step-item flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
    <div class="step-number w-8 h-8 flex items-center justify-center bg-slate-200 text-slate-700 font-bold rounded-full text-sm">1</div>
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
