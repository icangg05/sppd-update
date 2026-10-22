<div class="p-1 space-y-4">

  {{-- Header Halaman (title card) --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-sliders pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative min-w-0 leading-tight">
      <span
        class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
        <i class="fa-solid fa-gear text-[9px]"></i>
        Pengaturan
        <span class="ml-1 tabular-nums text-primary-600/70">· {{ count($items) }}</span>
      </span>
      <h1 class="text-xl font-bold tracking-tight text-slate-800">Pengaturan Aplikasi</h1>
      <p class="mt-1 text-xs text-slate-500">
        Nilai disimpan di <code class="font-mono text-[11px] text-slate-600">storage/app/private/{{ \App\Services\AppSettings::PATH }}</code>.
      </p>
    </div>
  </div>

  {{-- Daftar pengaturan --}}
  <form wire:submit="save" class="dash-enter bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
    <div class="divide-y divide-slate-100">
      @foreach ($items as $key => $item)
        @php
          $label = $definitions[$key]['label'] ?? $key;
          $editable = array_key_exists($key, $values);
          $isUrl = is_string($item['value']) && filter_var($item['value'], FILTER_VALIDATE_URL);
          $isNumber = in_array('integer', $definitions[$key]['rules'] ?? [], true);
        @endphp
        <div wire:key="setting-{{ $key }}" class="grid gap-3 px-4 py-4 md:grid-cols-[16rem_1fr] md:gap-6">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
            <x-ui.badge bg="bg-slate-100" text="text-slate-600" class="mt-1 font-mono">{{ $key }}</x-ui.badge>
            @if ($item['description'] !== '')
              <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ $item['description'] }}</p>
            @endif
          </div>

          <div class="min-w-0">
            @if ($editable && $isNumber)
              <x-form.input type="number" wire:model="values.{{ $key }}" min="1" max="100"
                wrapperClass="max-w-40" class="font-mono tabular-nums" />
            @elseif ($editable)
              <x-form.textarea wire:model="values.{{ $key }}" rows="3" class="font-mono text-xs break-all" />
              @if ($isUrl)
                <a href="{{ $item['value'] }}" target="_blank" rel="noopener noreferrer"
                  class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                  <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Buka link tersimpan
                </a>
              @endif
            @else
              <pre class="overflow-x-auto rounded border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-600">{{ json_encode($item['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
              <p class="mt-1 text-xs text-slate-500">Nilai berupa daftar/objek, ubah langsung di file JSON.</p>
            @endif
          </div>
        </div>
      @endforeach
    </div>

    <div class="flex justify-end border-t border-slate-200 bg-slate-50/50 px-4 py-3">
      <x-ui.button type="submit" variant="primary" class="font-bold">
        <x-slot:icon><i class="fa-solid fa-floppy-disk text-xs"></i></x-slot:icon>
        Simpan Pengaturan
      </x-ui.button>
    </div>
  </form>

</div>
