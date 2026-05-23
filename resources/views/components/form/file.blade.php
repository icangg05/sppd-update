@props([
    'name',
    'label' => null,
    'id' => null,
    'required' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
    'accept' => null,
])

@php
$id = $id ?? $name;
$resolvedClass = trim('form-input file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 file:transition file:hover:bg-slate-200 ' . $class);
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
        type="file"
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required @endif
        @if ($accept) accept="{{ $accept }}" @endif
        {{ $attributes->merge(['class' => $resolvedClass]) }}
    >

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
