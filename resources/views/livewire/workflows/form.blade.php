<div class="p-1 space-y-4">

  {{-- Header Halaman --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-primary-100 rounded text-primary-600">
        <i class="fa-solid fa-route text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">
          {{ $isEdit ? 'Edit Workflow SPPD' : 'Tambah Workflow SPPD' }}
        </h1>
        <p class="text-[11px] text-slate-500 font-medium">
          {{ $isEdit
            ? 'Ubah parameter aturan urutan dan batas alur persetujuan skema SPPD terkait'
            : 'Inisiasi konfigurasi aturan urutan dan batas alur persetujuan SPPD baru' }}
        </p>
      </div>
    </div>

    <a wire:navigate href="{{ route('master.workflows.index') }}"
      class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
    </a>
  </div>

  <form wire:submit="save" class="space-y-4">

    {{-- Blok 1: Parameter Informasi Aturan --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
          <i class="fa-solid fa-sliders text-primary-500"></i>Konfigurasi Parameter Aturan
        </h3>
      </div>

      <div class="p-4 space-y-3">
        <div class="grid grid-cols-1 gap-x-4 gap-y-3">

          {{-- Nama Workflow --}}
          <div class="space-y-0.5">
            <x-form.input name="name" label="Nama Skema Workflow" wire:model="name"
              placeholder="Misal: Alur Staf Reguler Luar Daerah" required
              class="text-xs py-1.5 focus:border-primary-500 focus:ring-primary-500" />
          </div>

          {{-- Filter Tipe Instansi (multi-checkbox) --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Berlaku Untuk Tipe Instansi (Opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded text-xs">
              @foreach ($departmentTypes as $type)
                <x-form.checkbox wire:model="department_type" :value="$type->value" :label="$type->label()"
                  wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Dapat memilih lebih dari satu tipe instansi. Kosongkan jika berlaku untuk semua OPD.</p>
          </div>

          {{-- Filter Peran Pemohon (multi-checkbox dengan label) --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Berlaku Untuk Peran Pemohon (Opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded text-xs">
              @foreach ($roles as $role)
                <x-form.checkbox wire:model="applicant_role" :value="$role->name" :label="$role->label ?? $role->name"
                  wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Dapat memilih lebih dari satu peran pemohon. Kosongkan jika berlaku untuk semua tingkat peran.</p>
          </div>

          {{-- Pilihan Ruang Lingkup Wilayah Tujuan --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide">Cakupan Wilayah Tujuan (Opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded text-xs">
              @foreach ($domains as $domain)
                <x-form.checkbox wire:model="destination" :value="$domain->value" :label="$domain->label()"
                  wrapper-class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer" />
              @endforeach
            </div>
            <p class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Dapat memilih lebih dari satu cakupan wilayah. Jika dikosongkan, sistem menganggap sah untuk semua destinasi.</p>
          </div>

          {{-- Status Keaktifan Aturan --}}
          <div class="pt-1">
            <x-form.checkbox wire:model="is_active" label="Aktifkan Aturan Perubahan Workflow Langsung"
              wrapper-class="flex items-center gap-2 font-bold text-xs text-slate-800 cursor-pointer" />
          </div>
        </div>
      </div>
    </div>

    {{-- Blok 2: Manajemen Alur Urutan Persetujuan --}}
    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-3 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
        <div class="space-y-0.5">
          <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
            <i class="fa-solid fa-diagram-next text-primary-500"></i>Alur Urutan Persetujuan (Steps) <span class="text-rose-500">*</span>
          </h3>
          <p class="text-[10px] text-slate-500 font-medium">Tentukan skema tingkatan peran aparatur penandatangan dari urutan awal hingga akhir. Gunakan tombol panah untuk mengubah urutan.</p>
        </div>

        <x-ui.button type="button" wire:click="addStep" variant="secondary"
          class="px-2.5 py-1 text-[11px] font-bold text-slate-700">
          <i class="fa-solid fa-plus text-primary-600 text-[10px]"></i> Sisipkan Tahap
        </x-ui.button>
      </div>

      <div class="p-4">
        @error('steps')
          <div class="p-2.5 mb-3 bg-rose-50 border border-rose-200 rounded text-rose-700 text-[11px] font-medium flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}
          </div>
        @enderror

        @php
          $roleOptions = collect($roles)
            ->map(fn ($r) => ['value' => $r->name, 'label' => $r->label ?? $r->name])
            ->all();
        @endphp
        <div class="space-y-2 max-w-2xl">
          @foreach ($steps as $i => $step)
            <div wire:key="step-{{ $i }}"
              class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded shadow-sm">
              <div class="flex items-center gap-2 flex-1 w-full">
                <div class="flex flex-col gap-0.5 shrink-0">
                  <button type="button" wire:click="moveStep({{ $i }}, -1)" @disabled($loop->first)
                    class="px-1 text-slate-300 hover:text-primary-600 disabled:opacity-30 disabled:hover:text-slate-300" title="Naik">
                    <i class="fa-solid fa-chevron-up text-[10px]"></i>
                  </button>
                  <button type="button" wire:click="moveStep({{ $i }}, 1)" @disabled($loop->last)
                    class="px-1 text-slate-300 hover:text-primary-600 disabled:opacity-30 disabled:hover:text-slate-300" title="Turun">
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                  </button>
                </div>
                <div class="step-number w-6 h-6 flex items-center justify-center bg-slate-200 border border-slate-300 text-slate-700 font-black rounded text-[11px] shrink-0 shadow-inner">
                  {{ $i + 1 }}
                </div>

                <div class="flex-1">
                  <x-form.searchable-select wire:model="steps.{{ $i }}.role" :options="$roleOptions"
                    placeholder="— Pilih Role Approver —" searchPlaceholder="Cari role..."
                    class="px-2.5 py-1.5 text-xs" />
                </div>
              </div>

              <div class="flex items-center justify-between sm:justify-start gap-4 px-2 sm:px-0 w-full sm:w-auto shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200">
                <div class="flex items-center gap-4">
                  <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer select-none hover:text-primary-600 transition-colors">
                    <input type="checkbox" wire:model.live="steps.{{ $i }}.signs_spt"
                      class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-4 h-4 transition-colors">
                    <span>TTD SPT</span>
                  </label>

                  <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer select-none hover:text-primary-600 transition-colors">
                    <input type="checkbox" wire:model.live="steps.{{ $i }}.signs_sppd"
                      class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-4 h-4 transition-colors">
                    <span>TTD SPPD</span>
                  </label>
                </div>

                <button type="button" wire:click="removeStep({{ $i }})"
                  class="p-1.5 text-slate-500 border border-transparent rounded hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 transition-colors shrink-0"
                  title="Hapus Tahap">
                  <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Form Actions Footer --}}
    <div class="pt-1 flex items-center justify-end gap-2">
      <a wire:navigate href="{{ route('master.workflows.index') }}"
        class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
        Batal
      </a>

      <x-ui.button type="submit"
        class="px-4 py-1.5 text-xs font-bold shadow-sm shadow-primary-200">
        <span wire:loading.remove wire:target="save">
          <i class="fa-solid fa-floppy-disk text-[11px]"></i>
          {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Aturan Workflow' }}
        </span>
        <span wire:loading wire:target="save"><i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...</span>
      </x-ui.button>
    </div>
  </form>
</div>
