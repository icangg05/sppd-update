@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'id' => null,
    'rows' => 3,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
])

@php
// Saat dipakai di Livewire (wire:model), Livewire yang mengontrol isi textarea.
$wireModel = $attributes->whereStartsWith('wire:model')->first();
$key = $name ?? $wireModel;
$id = $id ?? $key;
$resolvedValue = $wireModel ? null : ($value ?? old($name));
// min-h disesuaikan agar proporsional dan tidak terlalu memaksa jika rows yang dikirim kecil
$resolvedClass = trim('w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 min-h-[80px] ' . $class);
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase {{ $labelClass }}">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <textarea
        @if ($name) name="{{ $name }}" @endif
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        {{ $attributes->merge(['class' => $resolvedClass]) }}
    >{{ $resolvedValue }}</textarea>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($key)
        @error($key)
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
