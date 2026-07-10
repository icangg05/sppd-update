@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'class' => '',
    'labelClass' => '',
    'wrapperClass' => '',
])

@php
	// Saat dipakai di Livewire (wire:model), property binding dipakai untuk id & @error.
	$wireModel = $attributes->whereStartsWith('wire:model')->first();
	$key = $name ?? $wireModel;
	$id = $id ?? $key;
	$resolvedClass = trim(
	    'w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 ' .
	        $class,
	);
@endphp

<div class="{{ $wrapperClass }}">
	@if ($label)
		<label for="{{ $id }}"
			class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase {{ $labelClass }}">
			{{ $label }}
			@if ($required)
				<span class="text-rose-500">*</span>
			@endif
		</label>
	@endif

	<select
		@if ($name) name="{{ $name }}" @endif
		id="{{ $id }}"
		@if ($required) required @endif
		@if ($disabled) disabled @endif
		{{ $attributes->merge(['class' => $resolvedClass]) }}>
		{{ $slot }}
	</select>

	@if ($hint)
		<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
	@endif

	@if ($key)
		@error($key)
			<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
		@enderror
	@endif
</div>
