@props([
    'name' => null,
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
// Saat dipakai di Livewire (wire:model), Livewire yang mengontrol value sehingga
// atribut value tidak perlu ditulis. Property binding dipakai untuk id & @error.
$wireModel = $attributes->whereStartsWith('wire:model')->first();
$key = $name ?? $wireModel;
$id = $id ?? $key;
$resolvedValue = $wireModel ? null : ($value ?? old($name));

// Field disabled otomatis berlatar abu-abu — ubah di satu tempat ini saja.
$disabledAttr = $attributes->get('disabled');
$isDisabled = $disabledAttr !== null && $disabledAttr !== false;
$stateClass = $isDisabled
    ? 'bg-slate-100 text-slate-500 cursor-not-allowed'
    : 'bg-white text-slate-800';

$resolvedClass = trim('w-full rounded border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 ' . $stateClass . ' ' . $class);
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase {{ $labelClass }}">
            {{ $label }}
            @if ($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        id="{{ $id }}"
        @unless ($wireModel) value="{{ $resolvedValue }}" @endunless
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        {{ $attributes->merge(['class' => $resolvedClass]) }}
    >

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($key)
        @error($key)
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
