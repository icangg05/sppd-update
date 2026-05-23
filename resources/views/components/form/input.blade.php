@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
])

@php
$id = $id ?? $name;
$resolvedValue = $value ?? old($name);
$resolvedClass = trim('form-input ' . $class);
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="form-label {{ $labelClass }}">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $resolvedValue }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        {{ $attributes->merge(['class' => $resolvedClass]) }}
    >

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
