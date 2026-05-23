@props([
    'name',
    'label' => null,
    'id' => null,
    'required' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
])

@php
$id = $id ?? $name;
$resolvedClass = trim('form-select ' . $class);
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

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => $resolvedClass]) }}
    >
        {{ $slot }}
    </select>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
