@props([
    // Nama properti Livewire yang diikat (boleh bertitik, mis. amounts.12).
    'model',
    'label' => null,
    'required' => false,
    'placeholder' => null,
    'disabled' => false,
    'id' => null,
    'wrapperClass' => '',
])

@php
    $id = $id ?? 'rupiah-' . str_replace('.', '-', $model);
@endphp

{{-- Input nominal rupiah: menampilkan pemisah ribuan saat diketik, menyimpan angka polos ke
     Livewire (deferred, terkirim bersama aksi simpan). Hanya menerima angka. --}}
<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
            {{ $label }}
            @if ($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif

    <div class="relative" x-data="{
        v: $wire.$entangle(@js($model)),
        format(x) { const n = String(x ?? '').replace(/\D/g, ''); return n === '' ? '' : n.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); },
    }">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500">Rp</span>
        <input type="text" inputmode="numeric" id="{{ $id }}" autocomplete="off" :value="format(v)"
            @input="v = $event.target.value.replace(/\D/g, ''); $event.target.value = format(v)"
            @keydown="if ($event.key.length === 1 && !$event.ctrlKey && !$event.metaKey && !/[0-9]/.test($event.key)) $event.preventDefault()"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @disabled($disabled)
            {{ $attributes->merge(['class' => 'w-full rounded border border-slate-300 bg-white py-2 pl-9 pr-3 font-mono text-sm tabular-nums text-slate-800 placeholder-slate-400 shadow-2xs outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-500']) }}>
    </div>

    @error($model)
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
