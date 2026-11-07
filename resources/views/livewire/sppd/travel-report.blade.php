@php
  $isAdmin = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
  $hasFile = (bool) $sppd->report?->report_file;
  $hasPhoto = (bool) $sppd->report?->documentation_file;
  $doneCount = ($hasFile ? 1 : 0) + ($hasPhoto ? 1 : 0);
  $settings = app(\App\Services\AppSettings::class);
  $savedBadge = 'inline-flex items-center gap-1 rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700';
  $missingBadge = 'inline-flex items-center gap-1 rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-600';
  $viewLink = 'text-xs font-semibold text-primary-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded';
@endphp

<div class="space-y-4 p-1">

  <x-sppd.doc-header :sppd="$sppd" title="Laporan Perjalanan Dinas"
    description="Unggah dokumen laporan dan foto dokumentasi hasil perjalanan." />

  {{-- Form Laporan (Livewire, tanpa reload) --}}
  <form wire:submit="save">
    <x-sppd.panel title="Detail Laporan" icon="fa-file-pen">
      <x-slot:aside>{{ $doneCount }}/2 berkas tersimpan</x-slot:aside>

      {{-- Kiri: kalender tanggal laporan; kanan: unggahan berkas. --}}
      <div class="grid grid-cols-1 gap-6 px-5 py-4 md:grid-cols-[18rem_minmax(0,1fr)]">
        <x-form.calendar wire:model="reportDate" label="Tanggal Laporan" required :disabled="!$isAdmin"
          :min="$this->earliestReportDate()"
          :minMessage="'Tanggal laporan tidak boleh sebelum perjalanan selesai (' . $sppd->end_date->translatedFormat('d F Y') . ').'"
          :hint="'Paling awal ' . $sppd->end_date->translatedFormat('d F Y') . ' (tanggal kembali perjalanan).'" />

        {{-- Berkas wajib hanya bila belum ada yang tersimpan (sama dengan validasi backend). --}}
        <div class="divide-y divide-slate-200 md:border-l md:border-slate-200 md:pl-6">
          {{-- Konteks: maksud perjalanan yang dilaporkan. --}}
          <div class="pb-4">
            <p class="text-xs font-medium text-slate-500">Maksud perjalanan</p>
            <p class="mt-0.5 max-w-[75ch] text-sm font-medium text-slate-800">{{ $sppd->purpose }}</p>
          </div>

          <div class="py-4">
            @if ($isAdmin)
              <x-form.file wire:model="reportFile" id="reportFile" label="File laporan (dokumen)" accept=".pdf,.doc,.docx"
                :required="!$hasFile"
                :hint="'Format PDF, DOC, DOCX · maks. ' . $settings->uploadMaxLabel(false) . ' · ' . ($hasFile ? 'kosongkan bila tidak diganti' : 'wajib diisi') . '.'" />
            @else
              <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-600">File laporan (dokumen)</p>
            @endif
            <div class="mt-2 flex items-center gap-3">
              @if ($hasFile)
                <span class="{{ $savedBadge }}"><i class="fa-solid fa-check text-[10px]"></i> File tersimpan</span>
                <a href="{{ asset('storage/' . $sppd->report->report_file) }}" target="_blank" rel="noopener" class="{{ $viewLink }}">Lihat file</a>
              @else
                <span class="{{ $missingBadge }}">Belum diunggah</span>
              @endif
            </div>
          </div>

          <div class="pt-4">
            @if ($isAdmin)
              <x-form.file wire:model="documentationFile" id="documentationFile" label="Foto dokumentasi" accept="image/*"
                :required="!$hasPhoto"
                :hint="'Format JPG, PNG · maks. ' . $settings->uploadMaxLabel(true) . ' · dikompres otomatis · ' . ($hasPhoto ? 'kosongkan bila tidak diganti' : 'wajib diisi') . '.'" />
            @else
              <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-600">Foto dokumentasi</p>
            @endif
            <div class="mt-2 flex items-center gap-3">
              @if ($hasPhoto)
                <span class="{{ $savedBadge }}"><i class="fa-solid fa-check text-[10px]"></i> Foto tersimpan</span>
                <x-ui.gallery-link :src="asset('storage/' . $sppd->report->documentation_file)" group="dokumentasi"
                  caption="Foto Dokumentasi Perjalanan" class="{{ $viewLink }}">Lihat foto</x-ui.gallery-link>
              @else
                <span class="{{ $missingBadge }}">Belum diunggah</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      @if ($isAdmin)
        <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
          <p class="hidden text-xs text-slate-600 sm:block">
            <i class="fa-solid fa-circle-info mr-1 text-slate-500"></i> Pastikan berkas sudah benar sebelum menyimpan.
          </p>
          <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto"
            wire:target="save,reportFile,documentationFile">
            <x-slot name="icon"><i class="fa-solid fa-floppy-disk text-xs"></i></x-slot>
            {{ $sppd->report ? 'Perbarui Laporan' : 'Simpan Laporan' }}
          </x-ui.button>
        </div>
      @endif
    </x-sppd.panel>
  </form>
</div>
