@props([
    'variant' => 'danger',
    'title' => null,
    'icon' => null,
])

@php
// Tint solid + teks pekat sewarna (bukan abu di atas warna), tanpa gradien/stripe.
// Kontras teks isi ≥ 4.5:1 terhadap latar -50 di semua varian.
$styles = [
    'danger' => [
        'box' => 'border-red-200 bg-red-50',
        'icon' => 'bg-red-100 text-red-600',
        'title' => 'text-red-800',
        'body' => 'text-red-700',
        'default' => 'fa-triangle-exclamation',
    ],
    'warning' => [
        'box' => 'border-amber-200 bg-amber-50',
        'icon' => 'bg-amber-100 text-amber-700',
        'title' => 'text-amber-900',
        'body' => 'text-amber-800',
        'default' => 'fa-circle-exclamation',
    ],
    'info' => [
        'box' => 'border-primary-200 bg-primary-50',
        'icon' => 'bg-primary-100 text-primary-800',
        'title' => 'text-primary-900',
        'body' => 'text-primary-800',
        'default' => 'fa-circle-info',
    ],
    'success' => [
        'box' => 'border-emerald-200 bg-emerald-50',
        'icon' => 'bg-emerald-100 text-emerald-700',
        'title' => 'text-emerald-900',
        'body' => 'text-emerald-800',
        'default' => 'fa-circle-check',
    ],
];
$style = $styles[$variant] ?? $styles['danger'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded border px-4 py-3 ' . $style['box']]) }}>
    <span class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $style['icon'] }}" aria-hidden="true">
        <i class="fa-solid {{ $icon ?? $style['default'] }} text-sm"></i>
    </span>

    <div class="min-w-0 flex-1 pt-0.5">
        @if ($title)
            <p class="text-sm font-semibold {{ $style['title'] }}">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-0.5' : '' }} text-sm leading-relaxed {{ $style['body'] }}">
            {{ $slot }}
        </div>
    </div>

    @isset($actions)
        <div class="shrink-0">{{ $actions }}</div>
    @endisset
</div>
