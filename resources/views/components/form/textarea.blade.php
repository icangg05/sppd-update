@props([
    'name',
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
$id = $id ?? $name;
$resolvedValue = $value ?? old($name);
$resolvedClass = trim('form-input min-h-32 ' . $class);
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

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        {{ $attributes->merge(['class' => $resolvedClass]) }}
    >{{ $resolvedValue }}</textarea>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
