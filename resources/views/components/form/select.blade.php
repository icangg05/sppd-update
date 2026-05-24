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
$resolvedClass = trim('w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-2xs transition focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 ' . $class);
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase {{ $labelClass }}">
            {{ $label }}
            @if ($required) <span class="text-rose-500">*</span> @endif
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
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
