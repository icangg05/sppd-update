<div class="mx-auto max-w-4xl space-y-4 p-1">

  @php
    // Admin OPD tidak membuka daftar struktur: kembali ke profil unit yang diedit.
    $backUrl = $isSuperAdmin ? route('master.departments.index') : route('master.departments.show', $department->id);
  @endphp

  {{-- Header Halaman (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid {{ $isEdit ? 'fa-building-user' : 'fa-folder-plus' }} pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid {{ $isEdit ? 'fa-building-user' : 'fa-folder-plus' }} text-[9px]"></i>
          {{ $isEdit ? 'Perbarui Instansi' : 'Instansi Baru' }}
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">
          {{ $isEdit ? 'Edit Profil Instansi' : 'Tambah Instansi' }}
        </h1>
        <p class="mt-1 text-xs text-slate-500">
          {{ $isEdit
            ? 'Perbarui informasi profil instansi atau sub-unit kerja terkait'
            : 'Tambahkan entitas OPD baru atau sub-struktur unit kerja pendukung' }}
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        @if ($isEdit && $isSuperAdmin)
          <x-ui.button href="{{ route('master.departments.show', $department->id) }}" variant="primary" class="shrink-0">
            <x-slot name="icon"><i class="fa-solid fa-eye text-xs"></i></x-slot>
            Lihat Detail
          </x-ui.button>
        @endif
        <x-ui.button href="{{ $backUrl }}" variant="secondary" class="shrink-0">
          <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
          Kembali
        </x-ui.button>
      </div>
    </div>
  </div>

  {{-- Panduan Menyusun Struktur — susunan unit hanya diatur Super Admin. --}}
  @if ($isSuperAdmin)
  <div class="dash-enter flex items-start gap-3 rounded border border-primary-200 bg-primary-100/50 p-4 text-xs leading-relaxed text-slate-600">
    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600 ring-1 ring-primary-200">
      <i class="fa-solid fa-sitemap text-xs"></i>
    </div>
    <div>
      <p class="mb-1 font-bold text-primary-700">Panduan menyusun struktur</p>
      <ul class="list-disc space-y-0.5 pl-4">
        <li><strong>OPD baru (induk):</strong> kosongkan "Instansi Induk Pengampu". Kode, tipe, & kop surat diatur di tingkat ini.</li>
        <li><strong>Sub-unit</strong> hanya untuk unit yang punya pejabat, kop surat, anggaran, atau admin sendiri: Kelurahan di bawah Kecamatan, Puskesmas di bawah Dinas Kesehatan, serta Asisten → Bagian → Subbagian di Sekretariat Daerah.</li>
        <li>Bidang/Seksi di Dinas tidak perlu dibuat: pegawainya cukup di instansi induk, dan Kabid sebagai penyetuju dipilih lewat atasan langsung.</li>
      </ul>
    </div>
  </div>
  @endif

  <form wire:submit="save" class="space-y-4">

    {{-- ── Kelompok 1: Identitas & Struktur ── --}}
    <section class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
      <header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
        <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
          <i class="fa-solid fa-sitemap"></i>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-800">Identitas & Struktur</h3>
          <p class="text-xs text-slate-500">Nama unit, posisi dalam hierarki, dan penanggung jawab.</p>
        </div>
      </header>

      {{-- Grid Utama Form --}}
      <div class="grid grid-cols-1 gap-x-4 gap-y-4 p-5 sm:p-6 md:grid-cols-2">

        {{-- Input Nama Unit Kerja --}}
        <div class="space-y-0.5">
          <x-form.input wire:model="name" name="name" label="Nama Unit Kerja / Struktur"
            placeholder="Misal: Bidang Tata Usaha" required :disabled="$parentLocked" />
          @if ($parentLocked)
            <p class="text-[10px] text-slate-500 font-medium mt-0.5">
              <i class="fa-solid fa-lock text-slate-500 mr-1"></i>Nama unit kerja hanya dapat diubah oleh Super Admin.
            </p>
          @endif
        </div>

        {{-- Instansi Induk --}}
        <div class="space-y-0.5">
          @if ($parentLocked)
            <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Instansi Induk Pengampu</label>
            <div class="rounded border border-slate-300 bg-slate-100 px-3 py-2 text-xs text-slate-500">
              {{ $department->parent?->name ?? 'Tidak ada (Top-level)' }}
            </div>
            <p class="text-[10px] text-slate-500 font-medium mt-0.5">
              <i class="fa-solid fa-lock text-slate-500 mr-1"></i>Hanya dapat diubah oleh Super Admin.
            </p>
          @else
            @php
              $parentOptions = collect($parents)
                ->map(fn($p) => ['value' => (string) $p->id, 'label' => $p->display_name])
                ->when($isSuperAdmin, fn($c) => $c->prepend(['value' => '', 'label' => '— Tidak ada (OPD induk baru) —']))
                ->values()
                ->all();
            @endphp
            <x-form.searchable-select wire:model.live="parent_id" name="parent_id"
              label="Instansi Induk Pengampu" :options="$parentOptions"
              placeholder="— Pilih Instansi Induk —" searchPlaceholder="Cari instansi induk..."
              :required="! $isSuperAdmin" />
            <p class="text-[10px] text-slate-500 font-medium mt-0.5">
              <i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Kosongkan untuk membuat OPD induk baru; pilih induk untuk membuat sub-unit di bawahnya.
            </p>
          @endif
        </div>

        {{-- Kode — selalu tampil --}}
        <div class="space-y-0.5">
          <x-form.input wire:model="code" name="code" label="Kode Unit Kerja"
            placeholder="Misal: 15.42.5" class="font-mono" />
        </div>

        {{-- Tipe — hanya untuk OPD induk --}}
        @if ($isRoot)
          <div class="space-y-0.5">
            @if ($isSuperAdmin)
              @php
                $typeOptions = collect($types)
                  ->map(fn($t) => ['value' => $t->value, 'label' => $t->label()])
                  ->all();
              @endphp
              <x-form.searchable-select wire:model.live="type" name="type" label="Tipe Entitas Wilayah" required
                :options="$typeOptions" placeholder="— Pilih Tipe —" searchPlaceholder="Cari tipe..." />
            @else
              <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Tipe Entitas Wilayah</label>
              <div class="rounded border border-slate-300 bg-slate-100 px-3 py-2 text-xs text-slate-500">
                {{ collect($types)->firstWhere('value', $type)?->label() ?? '—' }}
              </div>
              <p class="text-[10px] text-slate-500 font-medium mt-0.5">
                <i class="fa-solid fa-lock text-slate-500 mr-1"></i>Hanya dapat diubah oleh Super Admin.
              </p>
            @endif
          </div>
        @else
          <div class="space-y-0.5">
            @if ($isSuperAdmin)
              @php
                $typeOptions = collect($types)
                  ->map(fn($t) => ['value' => $t->value, 'label' => $t->label()])
                  ->all();
              @endphp
              <x-form.searchable-select wire:model.live="type" name="type" label="Tipe Entitas" required
                :options="$typeOptions" placeholder="— Pilih Tipe —" searchPlaceholder="Cari tipe..." />
              <p class="text-[10px] text-slate-500 font-medium mt-0.5">
                <i class="fa-solid fa-circle-info text-primary-500 mr-1"></i>Bawaan mengikuti induk; ubah bila unit ini entitas mandiri (mis. Kelurahan di bawah Kecamatan).
              </p>
            @else
              <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Tipe Entitas (mengikuti induk)</label>
              <div class="rounded border border-slate-300 bg-slate-100 px-3 py-2 text-xs text-slate-500">
                {{ collect($types)->firstWhere('value', $type)?->label() ?? '—' }}
              </div>
            @endif
          </div>
        @endif

        {{-- Akses Data Unit di Bawahnya (zona data) — hanya Super Admin --}}
        @if ($isSuperAdmin)
          <div class="md:col-span-2 space-y-0.5">
            <x-form.checkbox wire:model="can_view_children_data"
              label="Dapat Melihat Data Unit di Bawahnya"
              hint="Bila dinonaktifkan, instansi ini tidak melihat data (SPPD, anggaran, pegawai) milik unit-unit di bawahnya. Tiap unit tersebut mengelola datanya sendiri secara independen." />
          </div>
        @endif

      </div>{{-- /grid Identitas & Struktur --}}
    </section>

    {{-- ── Kelompok 2: Kop Surat ── --}}
    <section class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
      <header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
        <div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
          <i class="fa-regular fa-image"></i>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-800">Kop Surat</h3>
          <p class="text-xs text-slate-500">Dipakai pada dokumen cetak. Sub-unit dapat mewarisi kop dari induk.</p>
        </div>
      </header>
      <div class="grid grid-cols-1 gap-x-4 gap-y-4 p-5 sm:p-6 md:grid-cols-2">

        {{-- Unggah Kop Surat Utama — tersedia untuk semua unit. Sub-unit yang
             dikosongkan akan otomatis mewarisi kop instansi induknya. --}}
        <div class="space-y-1.5 {{ $type === 'dprd' ? 'md:col-span-1' : 'md:col-span-2' }}">
          <x-form.file wire:model="letterhead" label="Kop Surat Utama / SPPD (PNG/JPG/WEBP)" accept="image/*"
            hint="{{ $isRoot
              ? 'Rekomendasi rasio cetak 1000x200 pixel. Kop surat ini digunakan pada dokumen SPPD.'
              : 'Rekomendasi rasio cetak 1000x200 pixel. Kosongkan untuk mengikuti kop instansi induk.' }}" />
          <div wire:loading wire:target="letterhead" class="text-[10px] text-primary-600">
            <i class="fa-solid fa-spinner fa-spin mr-1"></i>Mengunggah...
          </div>
          @if ($isEdit && $department->letterhead && \Illuminate\Support\Str::contains($department->letterhead, '/'))
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded w-full">
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kop Aktif (SPPD):</p>
                <x-ui.button type="button" variant="danger" wire:click="confirmDeleteKop('primary')"
                  class="gap-1 px-2 py-0.5 text-[10px] font-bold">
                  <i class="fa-solid fa-trash-can text-[9px]"></i> Hapus Kop
                </x-ui.button>
              </div>
              <img src="{{ asset('storage/' . $department->letterhead) }}"
                class="max-h-16 rounded border border-slate-300/70 p-1 bg-white shadow-sm w-full object-contain">
            </div>
          @elseif ($inheritedLetterhead && \Illuminate\Support\Str::contains($inheritedLetterhead, '/'))
            <div class="p-2.5 bg-amber-50 border border-amber-200 rounded w-full">
              <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1.5">
                <i class="fa-solid fa-link mr-1"></i>Kop Saat Ini (diwarisi dari induk):
              </p>
              <img src="{{ asset('storage/' . $inheritedLetterhead) }}"
                class="max-h-16 rounded border border-amber-300/70 p-1 bg-white shadow-sm w-full object-contain">
              <p class="text-[10px] text-amber-600/80 mt-1.5">Unggah berkas di atas untuk memberi unit ini kop sendiri.</p>
            </div>
          @endif
        </div>

        {{-- Kop Surat Kedua — Khusus DPRD --}}
        @if ($type === 'dprd')
          <div class="md:col-span-1 space-y-1.5">
            <x-form.file wire:model="letterhead_second" label="Kop Surat Kedua / SPT (PNG/JPG)" accept="image/*"
              hint="Kop surat ini digunakan khusus pada dokumen SPT anggota DPRD. Rekomendasi rasio cetak 1000x200 pixel." />
            <div wire:loading wire:target="letterhead_second" class="text-[10px] text-primary-600">
              <i class="fa-solid fa-spinner fa-spin mr-1"></i>Mengunggah...
            </div>
            @if ($isEdit && $department->letterhead_second && \Illuminate\Support\Str::contains($department->letterhead_second, '/'))
              <div class="p-2.5 bg-slate-50 border border-slate-200 rounded w-full">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                  <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kop Aktif (SPT):</p>
                  <x-ui.button type="button" variant="danger" wire:click="confirmDeleteKop('secondary')"
                    class="gap-1 px-2 py-0.5 text-[10px] font-bold">
                    <i class="fa-solid fa-trash-can text-[9px]"></i> Hapus Kop
                  </x-ui.button>
                </div>
                <img src="{{ asset('storage/' . $department->letterhead_second) }}"
                  class="max-h-16 rounded border border-slate-300/70 p-1 bg-white shadow-sm w-full object-contain">
              </div>
            @endif
          </div>
        @endif
      </div>{{-- /grid Kop Surat --}}
    </section>

    {{-- Form Actions --}}
    <div class="sticky bottom-4 z-10 flex items-center justify-end gap-3 rounded border border-slate-200 bg-white/85 p-3 shadow-lg shadow-slate-900/5 backdrop-blur-sm">
      <x-ui.button href="{{ $backUrl }}" variant="secondary">
        <x-slot name="icon"><i class="fa-solid fa-xmark text-xs"></i></x-slot>
        Batal
      </x-ui.button>

      <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
        <span wire:loading.remove wire:target="save"><i class="fa-solid fa-floppy-disk text-xs"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Instansi' }}</span>
        <span wire:loading wire:target="save"><i class="fa-solid fa-spinner fa-spin text-xs"></i> Menyimpan...</span>
      </x-ui.button>
    </div>
  </form>

  {{-- Modal Konfirmasi Hapus Kop Surat — hanya bisa ditutup lewat tombol (tidak closeable) --}}
  <x-ui.modal show="$wire.showDeleteKopModal" title="Konfirmasi Hapus Kop Surat"
    description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash-can text-rose-600"
    :closeable="false">
    <div class="space-y-4">
      <p class="text-sm text-slate-600">
        Yakin ingin menghapus
        <span class="font-bold text-slate-800">
          {{ $deleteKopTarget === 'secondary' ? 'kop surat kedua (SPT)' : 'kop surat utama' }}
        </span>
        instansi ini? Berkas kop akan dihapus permanen dan unit akan kembali mengikuti kop instansi induk (bila ada).
      </p>

      <div class="flex items-center justify-end gap-2 pt-1">
        <x-ui.button type="button" variant="secondary" wire:click="closeDeleteKopModal">
          Tutup
        </x-ui.button>
        <x-ui.button type="button" variant="danger" wire:click="deleteKop">
          <span wire:loading.remove wire:target="deleteKop"><i class="fa-solid fa-trash-can"></i> Hapus</span>
          <span wire:loading wire:target="deleteKop"><i class="fa-solid fa-spinner fa-spin"></i> Menghapus...</span>
        </x-ui.button>
      </div>
    </div>
  </x-ui.modal>
</div>
