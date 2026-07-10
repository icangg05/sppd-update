@props([
    'type' => 'button',
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'class' => '',
])

@php
$variantClasses = [
    'primary' => 'bg-primary-700 text-white hover:bg-primary-700 focus-visible:ring-primary-500',
    'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-400',
    'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:ring-emerald-500',
    'warning' => 'bg-amber-500 text-white hover:bg-amber-600 focus-visible:ring-amber-400',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500',
    'ghost' => 'text-slate-500 hover:text-slate-800 hover:bg-slate-100 focus-visible:ring-slate-300',
    'dark' => 'bg-slate-800 text-white hover:bg-slate-900 focus-visible:ring-slate-600',
];

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-sm',
];

$variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

// Spinner otomatis saat aksi Livewire berjalan. Jika atribut wire:target ada,
// spinner hanya muncul untuk aksi itu; jika tidak, untuk aksi apa pun di komponen.
$wireTarget = $attributes->whereStartsWith('wire:target')->first();

$resolvedClass = trim(
    'group relative inline-flex items-center justify-center gap-2 rounded font-medium shadow-2xs transition-colors '
    . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 '
    . 'disabled:cursor-not-allowed disabled:opacity-60 '
    . $sizeClass . ' ' . $variantClass . ' ' . $class
);
@endphp

@if ($href)
    <a wire:navigate href="{{ $href }}" {{ $attributes->merge(['class' => $resolvedClass]) }}>
        @isset($icon) {{ $icon }} @endisset
        <span>{{ $slot }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        wire:loading.attr="disabled"
        {{ $attributes->merge(['class' => $resolvedClass]) }}
    >
        {{-- Spinner saat loading (Livewire) --}}
        @if ($loading || $wireTarget)
            <span
                wire:loading.flex
                @if ($wireTarget) wire:target="{{ $wireTarget }}" @endif
                class="hidden items-center"
            >
                <i class="fa-solid fa-circle-notch fa-spin"></i>
            </span>
        @endif

        <span
            @if ($loading || $wireTarget)
                wire:loading.remove
                @if ($wireTarget) wire:target="{{ $wireTarget }}" @endif
            @endif
            class="inline-flex items-center gap-2"
        >
            @isset($icon) {{ $icon }} @endisset
            <span>{{ $slot }}</span>
        </span>
    </button>
@endif
