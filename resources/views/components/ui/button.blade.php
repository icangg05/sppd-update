@props([
    'type' => 'button',
    'href' => null,
    'variant' => 'primary',
    'class' => '',
])

@php
$variantClasses = [
    'primary' => 'btn-primary',
    'secondary' => 'btn-secondary',
    'success' => 'btn-success',
    'warning' => 'btn-warning',
    'danger' => 'btn-danger',
    'ghost' => 'btn-ghost',
];

$variantClass = $variantClasses[$variant] ?? 'btn-primary';
$resolvedClass = trim('btn ' . $variantClass . ' ' . $class);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $resolvedClass]) }}>
        @isset($icon)
            {{ $icon }}
        @endisset

        <span>{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $resolvedClass]) }}>
        @isset($icon)
            {{ $icon }}
        @endisset

        <span>{{ $slot }}</span>
    </button>
@endif
