@props([
    'bg' => 'bg-slate-100',
    'text' => 'text-slate-700',
])

@php
    $classes = trim('badge ' . $bg . ' ' . $text . ' ' . ($attributes->get('class') ?? ''));
@endphp

<span {{ $attributes->except(['class'])->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
