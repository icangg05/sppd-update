@props([
  'label' => null,
  'id' => null,
  'required' => false,
  'hint' => null,
  'class' => '',
  'labelClass' => '',
  'wrapperClass' => '',
  'accept' => 'image/png,image/jpeg,image/webp',
  // Pilihan rasio crop: label => rasio (null = bebas). Pilihan pertama aktif saat dibuka.
  'ratios' => ['Bebas' => null],
  // Sisi terpanjang hasil crop (px); default sama dengan batas kop surat di server.
  'maxDimension' => \App\Services\ImageCompressor::LETTERHEAD_DIMENSION,
  'maxKb' => null,
  'title' => 'Crop Gambar',
  // Saran cara crop yang tampil di atas area crop (opsional): judul + isi.
  'tipTitle' => null,
  'tip' => null,
])

@php
// Input berkas yang gambarnya di-crop dulu di browser, baru diunggah ke properti Livewire
// (lewat $wire.upload). wire:model hanya dibaca untuk nama properti — tidak dipasang ke
// <input>, karena unggahan otomatis wire:model akan mengirim gambar asli sebelum di-crop.
$model = $attributes->whereStartsWith('wire:model')->first();
$id = $id ?? $model;
$maxKb ??= app(\App\Services\AppSettings::class)->uploadMaxKb(true);
$ratioOptions = collect($ratios)->map(fn ($value, $text) => ['label' => $text, 'value' => $value])->values();
$resolvedClass = trim('w-full rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-700 file:mr-3 file:rounded file:border-0 file:bg-primary-50 file:px-3 file:py-1 file:text-xs file:font-bold file:uppercase file:tracking-wide file:text-primary-700 file:transition file:hover:bg-primary-100 ' . $class);
@endphp

<div class="{{ $wrapperClass }}"
  x-data="imageCrop({ model: @js($model), maxDimension: @js($maxDimension), maxKb: @js($maxKb) })"
  x-init="setRatio(@js($ratioOptions->first()['value'] ?? null))">
  @if ($label)
    <label for="{{ $id }}" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase {{ $labelClass }}">
      {{ $label }}
      @if ($required) <span class="text-rose-500">*</span> @endif
    </label>
  @endif

  <input type="file" id="{{ $id }}" x-ref="input" accept="{{ $accept }}" @change="pick($event)"
    @if ($required) required @endif
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => $resolvedClass]) }}>

  {{-- Hint, progres unggah, atau galat sisi browser — bergantian di baris yang sama. --}}
  <p class="mt-1 text-xs">
    <span x-show="uploading" x-cloak class="text-primary-600">
      <i class="fa-solid fa-spinner fa-spin mr-1"></i>Mengunggah... <span x-text="progress + '%'"></span>
    </span>
    <span x-show="!uploading && error" x-cloak x-text="error" class="font-semibold text-red-600"></span>
    @if ($hint)
      <span x-show="!uploading && !error" class="text-slate-500">{{ $hint }}</span>
    @endif
  </p>

  @if ($model)
    @error($model)
      <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
  @endif

  {{-- Hasil crop yang sudah terunggah, menunggu disimpan. --}}
  <div x-show="preview" x-cloak class="mt-2 w-full rounded border border-primary-200 bg-primary-50/60 p-2.5">
    <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
      <p class="text-[10px] font-bold uppercase tracking-wider text-primary-700">
        <i class="fa-solid fa-crop-simple mr-1"></i>Hasil Crop (belum disimpan):
      </p>
      <div class="flex items-center gap-1.5">
        <x-ui.button type="button" variant="secondary" size="xs" @click="recrop()">
          <x-slot:icon><i class="fa-solid fa-crop-simple text-[9px]"></i></x-slot:icon>
          Crop Ulang
        </x-ui.button>
        <x-ui.button type="button" variant="danger-ghost" size="xs" @click="remove()">
          <x-slot:icon><i class="fa-solid fa-xmark text-[10px]"></i></x-slot:icon>
          Batalkan
        </x-ui.button>
      </div>
    </div>
    <img :src="preview" alt="Pratinjau hasil crop"
      class="max-h-16 w-full rounded border border-slate-300/70 bg-white object-contain p-1 shadow-sm">
    <p class="mt-1.5 text-[10px] text-primary-700/80">Gambar ini dipakai setelah formulir disimpan.</p>
  </div>

  <x-ui.modal show="open" :title="$title" icon="fa-solid fa-crop-simple text-primary-600" maxWidth="max-w-4xl"
    description="Geser atau ubah ukuran kotak untuk memilih bagian gambar yang dipakai.">
    <div class="space-y-3">
      @if ($tip)
        <x-ui.alert variant="info" icon="fa-crop-simple" :title="$tipTitle" class="py-2.5">{{ $tip }}</x-ui.alert>
      @endif

      {{-- Toolbar: rasio (kiri), zoom/putar/reset (kanan). --}}
      <div class="flex flex-wrap items-center justify-between gap-2">
        @if ($ratioOptions->count() > 1)
          <div class="inline-flex overflow-hidden rounded border border-slate-300" role="group" aria-label="Rasio crop">
            @foreach ($ratioOptions as $option)
              <button type="button" @click="setRatio(@js($option['value']))"
                :class="ratio === @js($option['value']) ? 'bg-primary-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'"
                :aria-pressed="ratio === @js($option['value'])"
                class="border-l border-slate-300 px-3 py-1.5 text-xs font-medium transition-colors first:border-l-0">
                {{ $option['label'] }}
              </button>
            @endforeach
          </div>
        @else
          <span></span>
        @endif

        <div class="flex items-center gap-1">
          @foreach ([
            ['zoom(0.1)', 'fa-magnifying-glass-plus', 'Perbesar'],
            ['zoom(-0.1)', 'fa-magnifying-glass-minus', 'Perkecil'],
            ['rotate(-90)', 'fa-rotate-left', 'Putar 90° ke kiri'],
            ['rotate(90)', 'fa-rotate-right', 'Putar 90° ke kanan'],
            ['resetCrop()', 'fa-arrows-rotate', 'Atur ulang'],
          ] as [$action, $icon, $tip])
            <button type="button" @click="{{ $action }}" title="{{ $tip }}" aria-label="{{ $tip }}"
              class="rounded border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
              <i class="fa-solid {{ $icon }} fa-fw text-xs"></i>
            </button>
          @endforeach
        </div>
      </div>

      {{-- Putar presisi untuk meluruskan hasil pindai yang miring: slider (tarik) atau tombol −/+,
           keduanya berjarak sesuai langkah terpilih (0,1° s.d. 1,5°), atau ketik sudutnya langsung. --}}
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-slate-600">
        <div class="flex min-w-64 flex-1 items-center gap-2">
          <span class="shrink-0 font-semibold">Putar presisi:</span>
          <button type="button" @click="rotateStep(-1)" :title="'Putar ke kiri ' + step + '°'" aria-label="Putar ke kiri sesuai langkah"
            class="shrink-0 rounded border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
            <i class="fa-solid fa-minus fa-fw text-xs"></i>
          </button>
          <input type="range" min="-45" max="45" :step="step" :value="fine"
            @input="setFine($event.target.value)" @dblclick="straighten()"
            aria-label="Kemiringan gambar" title="Tarik untuk memiringkan gambar; klik ganda untuk meluruskan"
            class="h-1.5 min-w-24 flex-1 cursor-pointer accent-primary-700">
          <button type="button" @click="rotateStep(1)" :title="'Putar ke kanan ' + step + '°'" aria-label="Putar ke kanan sesuai langkah"
            class="shrink-0 rounded border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
            <i class="fa-solid fa-plus fa-fw text-xs"></i>
          </button>
          {{-- Sudut diketik: diterapkan saat Enter / pindah fokus; nilai di luar ±45° dibatasi. --}}
          <label class="flex shrink-0 items-center gap-1">
            <input type="number" min="-45" max="45" :step="step" :value="fine" inputmode="decimal"
              @change="setFine($event.target.value); $event.target.value = fine"
              @keydown.enter.prevent="setFine($event.target.value); $event.target.value = fine"
              aria-label="Sudut kemiringan (derajat)"
              class="w-16 rounded border border-slate-300 bg-white px-1.5 py-1 text-right font-mono text-xs text-slate-800 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            <span class="font-semibold text-slate-700">°</span>
          </label>
        </div>

        <div class="flex items-center gap-2">
          <span class="font-semibold">Langkah:</span>
          <div class="inline-flex overflow-hidden rounded border border-slate-300" role="group" aria-label="Langkah putar">
            @foreach ([['0.1', '0,1°'], ['0.2', '0,2°'], ['0.5', '0,5°'], ['1', '1°'], ['1.5', '1,5°']] as [$value, $text])
              <button type="button" @click="step = {{ $value }}"
                :class="step === {{ $value }} ? 'bg-primary-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'"
                :aria-pressed="step === {{ $value }}"
                class="border-l border-slate-300 px-2.5 py-1 font-medium transition-colors first:border-l-0">
                {{ $text }}
              </button>
            @endforeach
          </div>
          <span x-show="base !== 0" x-cloak class="text-slate-500">
            Total: <span class="font-mono font-semibold text-slate-700" x-text="angleLabel()"></span>
          </span>
          <button type="button" x-show="fine !== 0" x-cloak @click="straighten()"
            class="text-primary-700 underline-offset-2 hover:underline">Luruskan</button>
        </div>
      </div>

      {{-- wire:ignore: DOM yang disisipkan Cropper.js jangan dirapikan ulang oleh morph Livewire. --}}
      <div wire:ignore class="image-crop-stage relative h-[min(60dvh,440px)] w-full overflow-hidden rounded border border-slate-200 bg-slate-100">
        <img x-init="bindImage($el)" alt="" class="block max-w-full opacity-0">
        <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-slate-100 text-xs text-slate-500">
          <i class="fa-solid fa-spinner fa-spin mr-2"></i>Memuat gambar...
        </div>
      </div>

      <p class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[11px] text-slate-500">
        <span>Gulir mouse atau cubit layar untuk zoom; seret gambar di luar kotak untuk membuat kotak baru.</span>
        <span x-show="size" class="font-mono text-slate-600" x-text="'Area: ' + size"></span>
      </p>

      <p x-show="error" x-cloak x-text="error" class="text-xs font-semibold text-red-600"></p>
    </div>

    <x-slot name="footer" class="flex items-center justify-end gap-2">
      <x-ui.button type="button" variant="secondary" @click="cancel()">Batal</x-ui.button>
      <x-ui.button type="button" variant="primary" @click="apply()" x-bind:disabled="loading">
        <x-slot:icon><i class="fa-solid fa-check text-xs"></i></x-slot:icon>
        Terapkan
      </x-ui.button>
    </x-slot>
  </x-ui.modal>
</div>
