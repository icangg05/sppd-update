@props([
    'name' => null,
    'label' => null,
    'value' => '1',
    'checked' => false,
    'id' => null,
    'required' => false,
    'hint' => null,
    // false untuk checkbox dalam grup: error ditampilkan sekali oleh grupnya.
    'showError' => true,
    'class' => '',
    'wrapperClass' => '',
    // 'sm' = versi ringkas untuk opsi tambahan di bawah field (mis. centang Sabtu/Minggu).
    'size' => 'md',
])

@php
// Saat dipakai di Livewire (wire:model), property binding dipakai untuk id & @error.
$wireModel = $attributes->whereStartsWith('wire:model')->first();
$key = $name ?? $wireModel;
$id = $id ?? ($name && str_contains($name, '[')
    ? str_replace(['[', ']'], '', $name) . '_' . str_replace([' ', '/'], '_', $value)
    : ($key . ($wireModel ? '_' . str_replace([' ', '/'], '_', $value) : '')));
$small = $size === 'sm';
@endphp

<div class="{{ $wrapperClass }}">
    <label for="{{ $id }}" @class([
        'inline-flex rounded border border-slate-200 bg-white text-slate-700 shadow-sm',
        'items-start gap-3 px-3 py-2 text-sm' => ! $small,
        'cursor-pointer items-center gap-1.5 px-2 py-1 text-xs' => $small,
    ])>
        <input
            type="checkbox"
            @if ($name) name="{{ $name }}" @endif
            id="{{ $id }}"
            value="{{ $value }}"
            @if ($checked) checked @endif
            @if ($required) required @endif
            {{ $attributes->merge(['class' => trim(($small ? 'h-3.5 w-3.5' : 'mt-0.5 h-4 w-4') . ' rounded border-slate-300 text-primary-600 focus:ring-primary-500 ' . $class)]) }}
        >
        <span class="font-medium">{{ $label }}</span>
    </label>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($key && $showError)
        @error($key)
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
