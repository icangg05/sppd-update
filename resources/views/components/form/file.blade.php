@props([
    'name',
    'label' => null,
    'id' => null,
    'required' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
    'accept' => '.pdf,.docx,.jpg,.jpeg,.png',
])

@php
$id = $id ?? $name;
$resolvedClass = trim('w-full rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-700 file:mr-3 file:rounded-sm file:border-0 file:bg-cyan-50 file:px-3 file:py-1 file:text-xs file:font-bold file:uppercase file:tracking-wide file:text-cyan-700 file:transition file:hover:bg-cyan-100 ' . $class);
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase {{ $labelClass }}">
            {{ $label }}
            @if ($required) <span class="text-rose-500">*</span> @endif
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
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
