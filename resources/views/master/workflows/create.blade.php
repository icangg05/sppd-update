@extends('layouts.app')
@section('title', 'Tambah Workflow SPPD')
@section('page-title', 'Tambah Workflow SPPD')

@section('content')
<div class="p-1 space-y-4">

  {{-- Header Halaman Compact --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
        <i class="fa-solid fa-route text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">Tambah Workflow SPPD</h1>
        <p class="text-[11px] text-slate-500 font-medium">Inisiasi konfigurasi aturan urutan dan batas alur persetujuan SPPD baru</p>
      </div>
    </div>

    <x-ui.button href="{{ route('master.workflows.index') }}" variant="secondary"
      class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
    </x-ui.button>
  </div>

  <form method="POST" action="{{ route('master.workflows.store') }}" class="space-y-4">
    @csrf

    {{-- Blok 1: Parameter Informasi Aturan --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
          <i class="fa-solid fa-sliders text-cyan-500"></i>Konfigurasi Parameter Aturan
        </h3>
      </div>

      <div class="p-4 space-y-3">
        <div class="grid grid-cols-1 gap-x-4 gap-y-3">

          {{-- Nama Workflow --}}
          <div class="space-y-0.5">
            <x-form.input name="name" label="Nama Skema Workflow" :value="old('name')" placeholder="Misal: Alur Staf Reguler Luar Daerah" required class="text-xs py-1.5 focus:border-cyan-500 focus:ring-cyan-500" />
          </div>

          {{-- Filter Tipe Instansi (multi-checkbox) --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Berlaku Untuk Tipe Instansi (Opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded text-xs">
              @foreach($departmentTypes as $type)
                <x-form.checkbox name="department_type[]" :id="'dept_' . $type->value" :value="$type->value" :checked="is_array(old('department_type')) && in_array($type->value, old('department_type'))" label="{{ $type->label() }}" wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            <p class="text-[10px] text-slate-400 font-medium"><i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i>Dapat memilih lebih dari satu tipe instansi. Kosongkan jika berlaku untuk semua OPD.</p>
          </div>

          {{-- Filter Peran Pemohon (multi-checkbox dengan label) --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Berlaku Untuk Peran Pemohon (Opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded text-xs">
              @foreach($roles as $role)
                <x-form.checkbox name="applicant_role[]" :id="'role_' . $role->name" :value="$role->name" :checked="is_array(old('applicant_role')) && in_array($role->name, old('applicant_role'))" label="{{ $role->label ?? $role->name }}" wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            <p class="text-[10px] text-slate-400 font-medium"><i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i>Dapat memilih lebih dari satu peran pemohon. Kosongkan jika berlaku untuk semua tingkat peran.</p>
          </div>

          {{-- Pilihan Ruang Lingkup Wilayah Tujuan --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Cakupan Wilayah Tujuan (Opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded text-xs">
              @foreach($domains as $domain)
                <x-form.checkbox name="destination[]" :id="'destination_' . $domain->value" :value="$domain->value" :checked="is_array(old('destination')) && in_array($domain->value, old('destination'))" label="{{ $domain->label() }}" wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            <p class="text-[10px] text-slate-400 font-medium"><i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i>Dapat memilih lebih dari satu cakupan wilayah. Jika dikosongkan, sistem menganggap sah untuk semua destinasi.</p>
          </div>

          {{-- Status Keaktifan Aturan --}}
          <div class="pt-1">
            <x-form.checkbox name="is_active" label="Aktifkan Aturan Perubahan Workflow Langsung" :checked="true" wrapper-class="flex items-center gap-2 font-bold text-xs text-slate-800 cursor-pointer" />
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
          <p class="text-[10px] text-slate-400 font-medium">Tentukan skema tingkatan peran aparatur penandatangan dari urutan awal hingga akhir. <span class="text-cyan-600 font-semibold"><i class="fa-solid fa-grip-vertical mr-0.5"></i>Drag baris untuk mengubah urutan.</span></p>
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
      <a wire:navigate href="{{ route('master.workflows.index') }}"
        class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
        Batal
      </a>

      <button type="submit"
        class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-4 py-1.5 text-xs font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
        <i class="fa-solid fa-floppy-disk text-[11px]"></i> Simpan Aturan Workflow
      </button>
    </div>
  </form>
</div>

{{-- Template Item Alur Dinamis --}}
<template id="step-template">
  <div class="step-item flex flex-col sm:flex-row sm:items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded transition-all duration-150 cursor-grab active:cursor-grabbing shadow-sm"
       draggable="true">
    <div class="flex items-center gap-2 flex-1 w-full">
      <div class="drag-handle flex flex-col gap-0.5 px-1 text-slate-300 hover:text-slate-500 shrink-0 cursor-grab active:cursor-grabbing" title="Drag untuk ubah urutan">
        <i class="fa-solid fa-grip-vertical text-sm"></i>
      </div>
      <div class="step-number w-6 h-6 flex items-center justify-center bg-slate-200 border border-slate-300 text-slate-700 font-black rounded text-[11px] shrink-0 shadow-inner">1</div>

      <div class="flex-1">
        <select class="role-select block w-full rounded border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-800 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>
          <option value="">-- Pilih Role Approver --</option>
          @foreach($roles as $role)
            <option value="{{ $role->name }}">{{ $role->label ?? $role->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="flex items-center justify-between sm:justify-start gap-4 px-2 sm:px-0 w-full sm:w-auto shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200">
      <div class="flex items-center gap-4">
        <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer select-none hover:text-cyan-600 transition-colors">
          <input type="checkbox" class="signs-spt-checkbox rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 w-4 h-4 transition-colors" value="1">
          <span>TTD SPT</span>
        </label>

        <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer select-none hover:text-cyan-600 transition-colors">
          <input type="checkbox" class="signs-sppd-checkbox rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 w-4 h-4 transition-colors" value="1">
          <span>TTD SPPD</span>
        </label>
      </div>

      <button type="button" class="remove-step-btn p-1.5 text-slate-400 border border-transparent rounded hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 transition-colors shrink-0" title="Hapus Tahap">
        <i class="fa-solid fa-trash-can text-xs"></i>
      </button>
    </div>
  </div>
</template>

{{-- Script Pengendali Operasi Baris dengan Drag & Drop --}}
<script>
(function() {
  const container = document.getElementById('steps-container');
  if (!container) return;
  const addBtn = document.getElementById('add-step-btn');
  const template = document.getElementById('step-template').innerHTML;

  const oldSteps = @json(old('steps', []));

  if (oldSteps.length > 0) {
    oldSteps.forEach(step => {
      if (typeof step === 'object' && step !== null) {
        addStep(step.role, step.signs_spt, step.signs_sppd);
      } else {
        addStep(step);
      }
    });
  } else {
    addStep();
  }

  addBtn.addEventListener('click', () => addStep());

  function addStep(selectedRole = '', signsSpt = false, signsSppd = false) {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = template;
    const stepEl = tempDiv.firstElementChild;

    if (selectedRole) {
      const select = stepEl.querySelector('.role-select');
      select.value = selectedRole;
    }

    if (signsSpt) {
      stepEl.querySelector('.signs-spt-checkbox').checked = true;
    }

    if (signsSppd) {
      stepEl.querySelector('.signs-sppd-checkbox').checked = true;
    }

    const sptCheckbox = stepEl.querySelector('.signs-spt-checkbox');
    const sppdCheckbox = stepEl.querySelector('.signs-sppd-checkbox');

    sptCheckbox.addEventListener('change', function() {
      if (this.checked) {
        document.querySelectorAll('.signs-spt-checkbox').forEach(cb => {
          if (cb !== this) cb.checked = false;
        });
      }
    });

    sppdCheckbox.addEventListener('change', function() {
      if (this.checked) {
        document.querySelectorAll('.signs-sppd-checkbox').forEach(cb => {
          if (cb !== this) cb.checked = false;
        });
      }
    });

    stepEl.querySelector('.remove-step-btn').addEventListener('click', function() {
      if (container.children.length > 1) {
        stepEl.remove();
        updateNumbers();
      } else {
        alert('Skema workflow wajib memiliki sekurang-kurangnya 1 tahapan otorisasi pejabat approval.');
      }
    });

    // Drag & drop events
    initDragDrop(stepEl);

    container.appendChild(stepEl);
    updateNumbers();
  }

  function initDragDrop(el) {
    el.addEventListener('dragstart', onDragStart);
    el.addEventListener('dragend', onDragEnd);
    el.addEventListener('dragover', onDragOver);
    el.addEventListener('drop', onDrop);
    el.addEventListener('dragleave', onDragLeave);
  }

  let dragSrc = null;

  function onDragStart(e) {
    dragSrc = this;
    this.classList.add('opacity-50', 'ring-2', 'ring-cyan-400');
    e.dataTransfer.effectAllowed = 'move';
  }

  function onDragEnd(e) {
    this.classList.remove('opacity-50', 'ring-2', 'ring-cyan-400');
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('border-cyan-400', 'border-dashed', 'bg-cyan-50'));
  }

  function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (this !== dragSrc) {
      this.classList.add('border-cyan-400', 'border-dashed', 'bg-cyan-50');
    }
    return false;
  }

  function onDragLeave(e) {
    this.classList.remove('border-cyan-400', 'border-dashed', 'bg-cyan-50');
  }

  function onDrop(e) {
    e.stopPropagation();
    e.preventDefault();
    if (dragSrc !== this) {
      const allItems = [...container.querySelectorAll('.step-item')];
      const srcIdx = allItems.indexOf(dragSrc);
      const tgtIdx = allItems.indexOf(this);
      if (srcIdx < tgtIdx) {
        container.insertBefore(dragSrc, this.nextSibling);
      } else {
        container.insertBefore(dragSrc, this);
      }
      updateNumbers();
    }
    this.classList.remove('border-cyan-400', 'border-dashed', 'bg-cyan-50');
    return false;
  }

  function updateNumbers() {
    const items = container.querySelectorAll('.step-item');
    items.forEach((item, index) => {
      item.querySelector('.step-number').textContent = index + 1;
      
      // Update input names with contiguous index
      item.querySelector('.role-select').name = `steps[${index}][role]`;
      item.querySelector('.signs-spt-checkbox').name = `steps[${index}][signs_spt]`;
      item.querySelector('.signs-sppd-checkbox').name = `steps[${index}][signs_sppd]`;
    });
  }
})();
</script>
@endsection
