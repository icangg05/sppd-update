@props([
    'name',
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
$id = $id ?? $name;
@endphp

<div class="{{ $wrapperClass }}">
    <label for="{{ $id }}" class="inline-flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            @if ($checked) checked @endif
            @if ($required) required @endif
            {{ $attributes->merge(['class' => trim('mt-0.5 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 ' . $class)]) }}
        >
        <span class="font-medium">{{ $label }}</span>
    </label>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
