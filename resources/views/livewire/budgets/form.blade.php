<div class="p-1 space-y-4">

  {{-- Header Halaman --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-cyan-100 rounded text-cyan-600">
        <i class="fa-solid {{ $isEdit ? 'fa-file-pen' : 'fa-file-circle-plus' }} text-base"></i>
      </div>
      <div>
        <h1 class="text-base font-bold text-slate-800 uppercase tracking-wide">
          {{ $isEdit ? 'Edit Data DPA' : 'Input Data DPA' }}
        </h1>
        <p class="text-[11px] text-slate-500 font-medium">Isi formulir anggaran secara ringkas di bawah ini</p>
      </div>
    </div>

    <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary"
      class="gap-1.5 px-3 py-1.5 text-xs font-bold">
      <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
    </x-ui.button>
  </div>

  {{-- Form Container --}}
  <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
    <form wire:submit="save" class="p-4 space-y-4">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">

        {{-- SKPD / Unit Kerja --}}
        @if ($isSuperAdmin)
          <div class="md:col-span-2">
            @php
              $departmentOptions = collect($departments)
                ->map(fn ($d) => ['value' => $d->id, 'label' => $d->display_name])
                ->all();
            @endphp
            <x-form.searchable-select wire:model="department_id" name="department_id"
              label="SKPD / Unit Kerja" required placeholder="-- Pilih Unit Kerja --"
              searchPlaceholder="Cari unit kerja..." :options="$departmentOptions" />
          </div>
        @else
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">SKPD / Unit Kerja Terikat</label>
            <div class="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded flex items-center gap-2 text-xs font-bold text-slate-700 h-[38px]">
              <i class="fa-solid fa-building text-slate-400 text-[11px]"></i>
              {{ collect($departments)->firstWhere('id', (int) $department_id)?->name ?? auth()->user()->department?->name ?? '—' }}
            </div>
          </div>
        @endif

        {{-- Tahun Anggaran --}}
        <div>
          <x-form.searchable-select wire:model="year" name="year" label="Tahun Anggaran" required
            placeholder="-- Pilih Tahun --" searchPlaceholder="Cari tahun..."
            :options="collect($years)->map(fn ($y) => ['value' => (string) $y, 'label' => (string) $y])->all()" />
        </div>

        {{-- Kode Rekening --}}
        <div>
          <x-form.input wire:model="account_code" type="text" label="Kode Rekening"
            placeholder="Contoh: 5.1.02.04..." class="font-mono text-xs py-1.5" required />
        </div>

        {{-- Jenis Anggaran --}}
        <div>
          <x-form.searchable-select wire:model="type" name="type" label="Jenis Anggaran" required
            placeholder="-- Pilih Jenis --" searchPlaceholder="Cari jenis..."
            :options="[
              'Perjalanan Dinas Dalam Daerah',
              'Perjalanan Dinas Luar Daerah',
              'Bimtek',
              'Perjalanan Lainnya',
            ]" />
        </div>

        {{-- Mata Anggaran --}}
        <div>
          <x-form.searchable-select wire:model="source" name="source" label="Mata Anggaran" required
            placeholder="-- Pilih Sumber --" searchPlaceholder="Cari sumber..."
            :options="['APBD', 'APBD-P', 'APBN']" />
        </div>

        {{-- Nama Program --}}
        <div class="md:col-span-3">
          <x-form.input wire:model="program" type="text" label="Nama Program Utama"
            placeholder="Masukkan nama program..." class="text-xs py-1.5" required />
        </div>

        {{-- Nama Kegiatan --}}
        <div class="md:col-span-3">
          <x-form.input wire:model="activity" type="text" label="Nama Kegiatan / Sub Kegiatan"
            placeholder="Masukkan nama kegiatan..." class="text-xs py-1.5" required />
        </div>

        {{-- Pagu Total --}}
        <div>
          <x-form.input wire:model="total_amount" type="number" label="Pagu Total (Rp)"
            placeholder="0" class="font-mono font-bold text-xs py-1.5 text-slate-800" required />
        </div>

        {{-- Uraian --}}
        <div class="md:col-span-2">
          <x-form.input wire:model="description" type="text" label="Uraian Singkat Penjelasan"
            placeholder="Deskripsi pelengkap anggaran..." class="text-xs py-1.5" required />
        </div>

      </div>

      {{-- Footer Actions --}}
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
        <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary"
          class="gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600">
          <i class="fa-solid fa-xmark text-[10px]"></i> Batal
        </x-ui.button>

        <x-ui.button type="submit" variant="success" wire:target="save" wire:loading.attr="disabled"
          class="gap-1.5 px-4 py-1.5 text-xs font-bold">
          <span wire:loading.remove wire:target="save">
            <i class="fa-solid fa-floppy-disk text-[11px]"></i> {{ $isEdit ? 'Perbarui Data DPA' : 'Simpan Data DPA' }}
          </span>
          <span wire:loading wire:target="save">
            <i class="fa-solid fa-spinner fa-spin text-[11px]"></i> Menyimpan...
          </span>
        </x-ui.button>
      </div>

    </form>
  </div>
</div>
