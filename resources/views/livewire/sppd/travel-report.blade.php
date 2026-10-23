@php
  $isAdmin = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
  $hasFile = (bool) $sppd->report?->report_file;
  $hasPhoto = (bool) $sppd->report?->documentation_file;
  $doneCount = ($hasFile ? 1 : 0) + ($hasPhoto ? 1 : 0);
@endphp

<div class="p-1 space-y-6">

  {{-- Header (title card ala halaman index) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-emerald-50/50 px-5 py-4 shadow-sm">
    <i class="fa-solid fa-clipboard-check pointer-events-none absolute -right-4 -top-5 text-8xl text-emerald-500/6"
      aria-hidden="true"></i>
    <span class="pointer-events-none absolute inset-y-3 left-0 w-1 rounded-r bg-linear-to-b from-emerald-400/40 to-primary-400/40"
      aria-hidden="true"></span>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
          <i class="fa-solid fa-pen-to-square text-[9px]"></i> Langkah Laporan Akhir
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Laporan Perjalanan Dinas</h1>
        <p class="mt-1 text-sm text-slate-500">Unggah dokumen laporan dan foto dokumentasi hasil perjalanan.</p>
      </div>
      <x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="secondary" class="shrink-0">
        <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
        Kembali
      </x-ui.button>
    </div>
  </div>

  {{-- Ringkasan cepat (selaras laman Rincian Biaya). Maksud perjalanan memakai
       2 kolom karena teksnya bisa panjang. --}}
  <div class="dash-enter grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <div class="relative flex items-center overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm">
      <span class="absolute inset-y-0 left-0 w-1 bg-primary-500/70" aria-hidden="true"></span>
      <div class="flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600">
          <i class="fa-solid fa-user-tie"></i>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-slate-500">Pelaksana</p>
          <p class="font-bold leading-snug text-slate-800">{{ $sppd->user->name }}</p>
        </div>
      </div>
    </div>
    <div class="relative flex items-center overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm">
      <span class="absolute inset-y-0 left-0 w-1 bg-amber-500/70" aria-hidden="true"></span>
      <div class="flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
          <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-slate-500">Kelengkapan Berkas</p>
          <p class="text-lg font-bold text-slate-800">{{ $doneCount }} <span
              class="text-sm font-medium text-slate-400">/ 2</span></p>
          <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] font-semibold">
            <span class="{{ $hasFile ? 'text-emerald-700' : 'text-slate-400' }}">
              <i class="fa-solid {{ $hasFile ? 'fa-circle-check text-emerald-500' : 'fa-circle text-slate-300' }} mr-0.5"></i> Dokumen
            </span>
            <span class="{{ $hasPhoto ? 'text-emerald-700' : 'text-slate-400' }}">
              <i class="fa-solid {{ $hasPhoto ? 'fa-circle-check text-emerald-500' : 'fa-circle text-slate-300' }} mr-0.5"></i> Foto
            </span>
          </div>
        </div>
      </div>
    </div>
    <div class="relative flex items-center overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm sm:col-span-2">
      <span class="absolute inset-y-0 left-0 w-1 bg-emerald-500/70" aria-hidden="true"></span>
      <div class="flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
          <i class="fa-solid fa-bullseye"></i>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-slate-500">Maksud Perjalanan</p>
          <p class="text-sm font-medium leading-relaxed text-slate-700">{{ $sppd->purpose }}</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Form Laporan (Livewire, tanpa reload) --}}
  <form wire:submit="save" class="dash-enter space-y-4">
    <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
      <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3">
        <span class="flex size-6 items-center justify-center rounded bg-emerald-100 text-emerald-600">
          <i class="fa-solid fa-file-pen text-xs"></i>
        </span>
        <h3 class="text-sm font-bold tracking-wide text-slate-700">Detail Laporan</h3>
      </div>

      {{-- Kiri: kalender tanggal laporan; kanan: baris unggahan berkas. --}}
      <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-[18rem_minmax(0,1fr)]">
        <x-form.calendar wire:model="reportDate" label="Tanggal Laporan" accent="emerald" required :disabled="!$isAdmin"
          :min="$this->earliestReportDate()"
          :minMessage="'Tanggal laporan tidak boleh sebelum perjalanan selesai (' . $sppd->end_date->translatedFormat('d F Y') . ').'"
          :hint="'Paling awal ' . $sppd->end_date->translatedFormat('d F Y') . ' (tanggal kembali perjalanan).'" />

        {{-- Berkas wajib hanya bila belum ada yang tersimpan (sama dengan validasi backend). --}}
        <div class="divide-y divide-slate-100 md:border-l md:border-slate-100 md:pl-6">
          {{-- File Laporan --}}
          <div class="pb-5">
            <label for="reportFile" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
              <i class="fa-solid fa-file-lines mr-1 text-emerald-600"></i> File Laporan (Dokumen)
              <span class="text-rose-500">*</span></label>
            @if ($isAdmin)
              <input type="file" id="reportFile" wire:model="reportFile" accept=".pdf,.doc,.docx" @required(!$hasFile)
                class="w-full cursor-pointer rounded border border-slate-200 p-1.5 text-sm text-slate-500 transition file:mr-4 file:rounded file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700 hover:border-emerald-300 hover:file:bg-emerald-100">
              <p class="mt-1 text-xs text-slate-500">Format PDF, DOC, DOCX · maks. {{ app(\App\Services\AppSettings::class)->uploadMaxLabel(false) }} · {{ $hasFile ? 'kosongkan bila tidak diganti' : 'wajib diisi' }}.</p>
              <p wire:loading wire:target="reportFile" class="mt-1 text-xs text-slate-500">
                <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...</p>
              @error('reportFile') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            @endif
            @if ($hasFile)
              <div class="mt-2 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                  <i class="fa-solid fa-check text-emerald-500"></i> File Tersimpan
                </span>
                <a href="{{ asset('storage/' . $sppd->report->report_file) }}" target="_blank"
                  class="text-xs font-bold text-emerald-600 transition hover:text-emerald-800 hover:underline">Lihat File</a>
              </div>
            @elseif (!$isAdmin)
              <div class="mt-2">
                <span class="inline-flex items-center gap-1 rounded border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700">
                  <i class="fa-solid fa-circle-xmark text-rose-500"></i> Belum Diunggah
                </span>
              </div>
            @endif
          </div>

          {{-- Foto Dokumentasi --}}
          <div class="pt-5">
            <label for="documentationFile" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
              <i class="fa-solid fa-camera mr-1 text-emerald-600"></i> Foto Dokumentasi
              <span class="text-rose-500">*</span></label>
            @if ($isAdmin)
              <input type="file" id="documentationFile" wire:model="documentationFile" accept="image/*" @required(!$hasPhoto)
                class="w-full cursor-pointer rounded border border-slate-200 p-1.5 text-sm text-slate-500 transition file:mr-4 file:rounded file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700 hover:border-emerald-300 hover:file:bg-emerald-100">
              <p class="mt-1 text-xs text-slate-500">Format JPG, PNG · maks. {{ app(\App\Services\AppSettings::class)->uploadMaxLabel(true) }} · dikompres otomatis · {{ $hasPhoto ? 'kosongkan bila tidak diganti' : 'wajib diisi' }}.</p>
              <p wire:loading wire:target="documentationFile" class="mt-1 text-xs text-slate-500">
                <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...</p>
              @error('documentationFile') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            @endif
            @if ($hasPhoto)
              <div class="mt-2 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                  <i class="fa-solid fa-check text-emerald-500"></i> Foto Tersimpan
                </span>
                <x-ui.gallery-link :src="asset('storage/' . $sppd->report->documentation_file)" group="dokumentasi"
                  caption="Foto Dokumentasi Perjalanan"
                  class="text-xs font-bold text-emerald-600 transition hover:text-emerald-800 hover:underline">Lihat Foto</x-ui.gallery-link>
              </div>
            @elseif (!$isAdmin)
              <div class="mt-2">
                <span class="inline-flex items-center gap-1 rounded border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700">
                  <i class="fa-solid fa-circle-xmark text-rose-500"></i> Belum Diunggah
                </span>
              </div>
            @endif
          </div>
        </div>
      </div>

      @if ($isAdmin)
        <div class="flex items-center justify-between gap-3 rounded-b border-t border-slate-200 bg-slate-50 px-5 py-3">
          <p class="hidden text-xs text-slate-500 sm:block">
            <i class="fa-solid fa-circle-info mr-1 text-slate-400"></i> Pastikan berkas benar sebelum menyimpan.
          </p>
          <x-ui.button type="submit" variant="success" class="font-bold"
            wire:target="save,reportFile,documentationFile" wire:loading.attr="disabled">
            <x-slot name="icon"><i class="fa-solid fa-floppy-disk text-xs"></i></x-slot>
            {{ $sppd->report ? 'Perbarui Laporan' : 'Simpan Laporan' }}
          </x-ui.button>
        </div>
      @endif
    </div>
  </form>
</div>
