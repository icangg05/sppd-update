@props([
    'type' => 'button',
    'href' => null,
    'variant' => 'primary',
    'class' => '',
])

@php
$variantClasses = [
    'primary' => 'bg-cyan-600 text-white hover:bg-cyan-700',
    'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
    'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
    'warning' => 'bg-amber-500 text-white hover:bg-amber-600',
    'danger' => 'bg-red-600 text-white hover:bg-red-700',
    'ghost' => 'text-slate-500 hover:text-slate-800 hover:bg-slate-50',
];

$variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
$resolvedClass = trim('inline-flex items-center justify-center gap-2 rounded px-4 py-2 text-sm font-medium transition-colors shadow-2xs ' . $variantClass . ' ' . $class);
@endphp

@if ($href)
    <a wire:navigate href="{{ $href }}" {{ $attributes->merge(['class' => $resolvedClass]) }}>
        @isset($icon) {{ $icon }} @endisset
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $resolvedClass]) }}>
        @isset($icon) {{ $icon }} @endisset
        <span>{{ $slot }}</span>
    </button>
@endif
