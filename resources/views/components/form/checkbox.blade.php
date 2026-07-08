@props([
    'name' => null,
    'label' => null,
    'value' => '1',
    'checked' => false,
    'id' => null,
    'required' => false,
    'hint' => null,
    'class' => '',
    'wrapperClass' => '',
])

@php
// Saat dipakai di Livewire (wire:model), property binding dipakai untuk id & @error.
$wireModel = $attributes->whereStartsWith('wire:model')->first();
$key = $name ?? $wireModel;
$id = $id ?? ($name && str_contains($name, '[')
    ? str_replace(['[', ']'], '', $name) . '_' . str_replace([' ', '/'], '_', $value)
    : ($key . ($wireModel ? '_' . str_replace([' ', '/'], '_', $value) : '')));
@endphp

<div class="{{ $wrapperClass }}">
    <label for="{{ $id }}" class="inline-flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm">
        <input
            type="checkbox"
            @if ($name) name="{{ $name }}" @endif
            id="{{ $id }}"
            value="{{ $value }}"
            @if ($checked) checked @endif
            @if ($required) required @endif
            {{ $attributes->merge(['class' => trim('mt-0.5 h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 ' . $class)]) }}
        >
        <span class="font-medium">{{ $label }}</span>
    </label>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($key)
        @error($key)
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
