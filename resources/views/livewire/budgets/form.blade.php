<div class="mx-auto max-w-4xl space-y-6 p-1">

  {{-- Header Halaman --}}
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
      <div class="flex size-11 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
        <i class="fa-solid {{ $isEdit ? 'fa-file-pen' : 'fa-file-circle-plus' }} text-lg"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
          {{ $isEdit ? 'Edit Data DPA' : 'Input Data DPA' }}
        </h1>
        <p class="mt-0.5 text-sm text-slate-500">Lengkapi rincian dokumen pelaksanaan anggaran di bawah ini.</p>
      </div>
    </div>

    <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary" class="shrink-0">
      <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
      Kembali
    </x-ui.button>
  </div>

  {{-- Form --}}
  <form wire:submit="save" class="space-y-6">

    {{-- ── Kelompok 1: Sumber & Klasifikasi ── --}}
    <section class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
      <header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
        <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
          <i class="fa-solid fa-layer-group"></i>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-800">Sumber & Klasifikasi</h3>
          <p class="text-xs text-slate-500">Instansi, periode, dan kode anggaran.</p>
        </div>
      </header>

      <div class="grid grid-cols-1 gap-x-4 gap-y-5 p-5 sm:p-6 md:grid-cols-3">

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
            <div class="flex items-center gap-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
              <i class="fa-solid fa-building text-xs text-slate-500"></i>
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

        {{-- Mata Anggaran --}}
        <div>
          <x-form.searchable-select wire:model="source" name="source" label="Mata Anggaran" required
            placeholder="-- Pilih Sumber --" searchPlaceholder="Cari sumber..."
            :options="['APBD', 'APBD-P', 'APBN']" />
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

        {{-- Kode Rekening --}}
        <div>
          <x-form.input wire:model="account_code" type="text" label="Kode Rekening"
            placeholder="Contoh: 5.1.02.04..." class="font-mono" required />
        </div>
      </div>
    </section>

    {{-- ── Kelompok 2: Rincian & Pagu ── --}}
    <section class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
      <header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
        <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-800">Rincian & Pagu</h3>
          <p class="text-xs text-slate-500">Program, kegiatan, dan nilai pagu.</p>
        </div>
      </header>

      <div class="grid grid-cols-1 gap-x-4 gap-y-5 p-5 sm:p-6 md:grid-cols-3">

        {{-- Nama Program --}}
        <div class="md:col-span-3">
          <x-form.input wire:model="program" type="text" label="Nama Program Utama"
            placeholder="Masukkan nama program..." required />
        </div>

        {{-- Nama Kegiatan --}}
        <div class="md:col-span-3">
          <x-form.input wire:model="activity" type="text" label="Nama Kegiatan / Sub Kegiatan"
            placeholder="Masukkan nama kegiatan..." required />
        </div>

        {{-- Uraian --}}
        <div class="md:col-span-2">
          <x-form.input wire:model="description" type="text" label="Uraian Singkat Penjelasan"
            placeholder="Deskripsi pelengkap anggaran..." required />
        </div>

        {{-- Pagu Total --}}
        <div>
          <x-form.input wire:model="total_amount" type="number" label="Pagu Total (Rp)"
            placeholder="0" class="font-mono font-bold text-slate-800" required
            hint="Angka saja, tanpa titik atau koma." />
        </div>
      </div>
    </section>

    {{-- Footer Actions --}}
    <div class="sticky bottom-4 z-10 flex items-center justify-end gap-3 rounded border border-slate-200 bg-white/85 p-3 shadow-lg shadow-slate-900/5 backdrop-blur-sm">
      <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary">
        <x-slot name="icon"><i class="fa-solid fa-xmark text-xs"></i></x-slot>
        Batal
      </x-ui.button>

      <x-ui.button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="save">
          <i class="fa-solid fa-floppy-disk text-xs"></i> {{ $isEdit ? 'Perbarui Data DPA' : 'Simpan Data DPA' }}
        </span>
        <span wire:loading wire:target="save">
          <i class="fa-solid fa-spinner fa-spin text-xs"></i> Menyimpan...
        </span>
      </x-ui.button>
    </div>

  </form>
</div>
