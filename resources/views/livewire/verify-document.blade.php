<div class="p-1 space-y-4">

  {{-- Header --}}
  <div class="flex items-center gap-2.5 border-b border-slate-200 pb-3">
    <div class="rounded bg-emerald-100 p-1.5 text-emerald-600">
      <i class="fa-solid fa-file-shield text-base"></i>
    </div>
    <div>
      <h1 class="text-base font-bold uppercase tracking-wide text-slate-800">Cek Dokumen TTE</h1>
      <p class="text-[11px] font-medium text-slate-500">Verifikasi keaslian dokumen ber-tanda tangan elektronik ke BSrE</p>
    </div>
  </div>

  @if ($mode === 'upload')
    {{-- ══ Mode unggah: kirim PDF ke BSrE ══ --}}
    <form wire:submit="verifyUpload" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <label for="verify-file" class="mb-1.5 block text-sm font-semibold text-slate-700">
        Unggah dokumen PDF ber-TTE
      </label>
      <p class="mb-3 text-xs text-slate-500">
        Pilih berkas PDF yang sudah ditandatangani elektronik. Berkas akan diperiksa keasliannya ke server BSrE. Maks. 12&nbsp;MB.
      </p>

      <input id="verify-file" type="file" wire:model="file" accept="application/pdf"
        class="block w-full cursor-pointer rounded border border-slate-300 text-sm text-slate-600 file:mr-3 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 focus:outline-none" />

      <div wire:loading wire:target="file" class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
        <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah berkas…
      </div>

      @error('file')
        <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-rose-600">
          <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
        </p>
      @enderror

      <div class="mt-3 flex items-center gap-2">
        <x-ui.button type="submit" variant="primary" class="px-4 py-2 text-sm font-semibold"
          wire:loading.attr="disabled" wire:target="file,verifyUpload">
          <span wire:loading.remove wire:target="verifyUpload"><i class="fa-solid fa-shield-halved text-[11px]"></i> Verifikasi ke BSrE</span>
          <span wire:loading wire:target="verifyUpload"><i class="fa-solid fa-spinner fa-spin text-[11px]"></i> Memverifikasi…</span>
        </x-ui.button>

        @if ($checked)
          <button type="button" wire:click="resetUpload"
            class="rounded border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
            Cek dokumen lain
          </button>
        @endif
      </div>
    </form>

    {{-- 2 kolom: pratinjau dokumen (tampil saat dipilih) | hasil verifikasi (setelah diklik) --}}
    @if ($file)
      <div class="grid items-start gap-4 lg:grid-cols-2">
        {{-- Pratinjau dokumen — langsung tampil saat berkas dipilih --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-600">
            <i class="fa-regular fa-file-pdf text-rose-500"></i> Pratinjau Dokumen
          </div>
          <iframe src="{{ route('verify.preview', ['file' => $file->getFilename()]) }}" class="h-160 w-full bg-slate-100" title="Pratinjau dokumen"></iframe>
        </div>

        {{-- Hasil verifikasi — setelah tombol diklik --}}
        <div>
          @if ($checked && $bsreResult)
            @include('livewire.partials.bsre-result', ['result' => $bsreResult])
          @else
            <div class="flex h-full flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
              <i class="fa-solid fa-shield-halved mb-3 text-3xl text-slate-300"></i>
              <p class="text-sm font-medium text-slate-500">Berkas siap diverifikasi</p>
              <p class="mt-1 text-xs text-slate-400">Klik <span class="font-semibold">Verifikasi ke BSrE</span> untuk melihat hasil pemeriksaan keasliannya.</p>
            </div>
          @endif
        </div>
      </div>
    @endif

  @else
    {{-- ══ Mode target QR: cek catatan internal ══ --}}
    @if (! $valid)
      <div class="overflow-hidden rounded-2xl border border-rose-200 bg-white shadow-sm">
        <div class="flex flex-col items-center gap-3 bg-rose-50 px-6 py-8 text-center">
          <div class="flex size-14 items-center justify-center rounded-full bg-rose-100 text-rose-600">
            <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
          </div>
          <h2 class="text-lg font-bold text-rose-700">Dokumen Tidak Valid</h2>
          <p class="max-w-sm text-sm text-rose-600/80">
            Kode verifikasi tidak cocok dengan dokumen mana pun. Pastikan QR berasal dari dokumen asli.
          </p>
        </div>
      </div>
    @else
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- Header status keabsahan --}}
        <div class="flex flex-col items-center gap-3 px-6 py-8 text-center {{ $this->isSigned ? 'bg-emerald-50' : 'bg-amber-50' }}">
          <div class="flex size-14 items-center justify-center rounded-full {{ $this->isSigned ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
            <i class="fa-solid {{ $this->isSigned ? 'fa-shield-halved' : 'fa-clock' }} text-2xl"></i>
          </div>
          <div>
            <h2 class="text-lg font-bold {{ $this->isSigned ? 'text-emerald-700' : 'text-amber-700' }}">
              {{ $this->isSigned ? 'Dokumen Terverifikasi' : 'Dokumen Belum Ditandatangani' }}
            </h2>
            <p class="mt-1 text-sm {{ $this->isSigned ? 'text-emerald-600/80' : 'text-amber-600/80' }}">
              {{ $this->isSigned
                ? 'Dokumen ini terdaftar dan telah ditandatangani secara elektronik.'
                : 'Dokumen ini terdaftar dalam sistem, namun tanda tangan elektronik belum tersedia.' }}
            </p>
          </div>
        </div>

        {{-- Detail dokumen --}}
        <dl class="divide-y divide-slate-100 px-6 py-2 text-sm">
          <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <dt class="font-medium text-slate-500">Jenis Dokumen</dt>
            <dd class="font-semibold text-slate-800">{{ $this->typeLabel }}</dd>
          </div>
          <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <dt class="font-medium text-slate-500">Nomor Dokumen</dt>
            <dd class="font-mono font-semibold text-slate-800">{{ $sppd->document_number ?: '—' }}</dd>
          </div>
          @if ($traveler)
            <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-center sm:justify-between">
              <dt class="font-medium text-slate-500">Pelaksana</dt>
              <dd class="text-right font-semibold text-slate-800">
                {{ $traveler->name }}
                @if ($traveler->nip)
                  <span class="block font-mono text-[11px] font-normal text-slate-400">NIP {{ $traveler->nip }}</span>
                @endif
              </dd>
            </div>
          @endif
          <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-start sm:justify-between">
            <dt class="font-medium text-slate-500">Maksud</dt>
            <dd class="text-right text-slate-700 sm:max-w-xs">{{ $sppd->purpose ?: '—' }}</dd>
          </div>
          @if ($sppd->destinations->isNotEmpty())
            <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-start sm:justify-between">
              <dt class="font-medium text-slate-500">Tujuan</dt>
              <dd class="text-right text-slate-700 sm:max-w-xs">
                {{ $sppd->destinations->map(fn ($d) => $d->regency?->name)->filter()->join(', ') ?: '—' }}
              </dd>
            </div>
          @endif
          <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <dt class="font-medium text-slate-500">Tanggal</dt>
            <dd class="text-slate-700">
              {{ $sppd->start_date?->translatedFormat('d M Y') }} &ndash; {{ $sppd->end_date?->translatedFormat('d M Y') }}
            </dd>
          </div>
        </dl>

        {{-- Blok tanda tangan elektronik --}}
        @if ($this->isSigned && $signature)
          <div class="mx-6 mb-6 rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
            <p class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-emerald-600">
              <i class="fa-solid fa-pen-nib"></i> Tanda Tangan Elektronik
            </p>
            <dl class="space-y-2 text-sm">
              @if ($signature->signer)
                <div class="flex items-center justify-between gap-3">
                  <dt class="text-slate-500">Penandatangan</dt>
                  <dd class="text-right font-semibold text-slate-800">{{ $signature->signer->name }}</dd>
                </div>
              @endif
              <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Ditandatangani</dt>
                <dd class="font-medium text-slate-700">{{ $signature->signed_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
              </div>
              @if ($signature->certificate_serial)
                <div class="flex items-center justify-between gap-3">
                  <dt class="text-slate-500">Serial Sertifikat</dt>
                  <dd class="break-all text-right font-mono text-[11px] text-slate-600">{{ $signature->certificate_serial }}</dd>
                </div>
              @endif
            </dl>
          </div>
        @else
          <div class="mx-6 mb-6 rounded-xl border border-amber-100 bg-amber-50/60 p-4 text-sm text-amber-700">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Status tanda tangan saat ini:
            <span class="font-semibold">{{ $signature?->status?->label() ?? 'Belum diproses' }}</span>.
          </div>
        @endif

        {{-- Verifikasi kriptografis langsung ke BSrE atas berkas tersimpan --}}
        @if ($this->canVerifyBsre)
          <div class="mx-6 mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-600">
                  <i class="fa-solid fa-certificate text-cyan-500"></i> Verifikasi Resmi BSrE
                </p>
                <p class="mt-0.5 text-[11px] text-slate-500">Pemeriksaan kriptografis keaslian berkas langsung ke server BSrE.</p>
              </div>
              <x-ui.button type="button" wire:click="verifyWithBsre" variant="primary"
                class="shrink-0 px-3 py-2 text-xs font-semibold" wire:loading.attr="disabled" wire:target="verifyWithBsre">
                <span wire:loading.remove wire:target="verifyWithBsre"><i class="fa-solid fa-shield-halved text-[11px]"></i> Verifikasi ke BSrE</span>
                <span wire:loading wire:target="verifyWithBsre"><i class="fa-solid fa-spinner fa-spin text-[11px]"></i> Memverifikasi…</span>
              </x-ui.button>
            </div>

            @if ($bsreResult)
              <div class="mt-4">
                @include('livewire.partials.bsre-result', ['result' => $bsreResult])
              </div>
            @endif
          </div>
        @endif
      </div>
    @endif
  @endif
</div>
