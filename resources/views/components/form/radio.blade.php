@props([
    'name' => null,
    'label' => null,
    // Keterangan kecil di bawah label (mode kartu pilihan).
    'description' => null,
    'value' => '',
    'checked' => false,
    'id' => null,
    'required' => false,
    'hint' => null,
    // false bila pesan error ditampilkan sekali oleh grup radio (bukan per opsi).
    'error' => true,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
])

@php
$wireModel = $attributes->whereStartsWith('wire:model')->first();
$key = $name ?? $wireModel;
$id = $id ?? $key;
@endphp

<div class="{{ $wrapperClass }}">
    <label for="{{ $id }}" class="inline-flex cursor-pointer items-start gap-3 rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition-colors hover:border-primary-300 has-checked:border-primary-500 has-checked:bg-primary-50/60 has-checked:ring-1 has-checked:ring-primary-500 {{ $labelClass }}">
        <input
            type="radio"
            @if ($name) name="{{ $name }}" @endif
            id="{{ $id }}"
            value="{{ $value }}"
            @if ($checked) checked @endif
            @if ($required) required @endif
            {{ $attributes->merge(['class' => trim('mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-primary-600 focus:ring-primary-500 ' . $class)]) }}
        >
        <span class="min-w-0">
            <span class="block font-medium">{{ $label }}</span>
            @if ($description)
                <span class="mt-0.5 block text-xs text-slate-500">{{ $description }}</span>
            @endif
        </span>
    </label>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($key && $error)
        @error($key)
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
