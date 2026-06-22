@props([
    // Mode status: petakan ke kelas CSS badge-{status} (draft, approved, rejected, dst).
    'status' => null,
    // Mode warna manual (dipakai jika status tidak diisi).
    'bg' => 'bg-slate-100',
    'text' => 'text-slate-700',
])

@php
    if ($status) {
        // Warna mengikuti .badge-{status} di app.css; bentuk/teks lewat class tambahan.
        $base = 'badge-' . $status;
    } else {
        $base = 'badge ' . $bg . ' ' . $text;
    }
    $classes = trim($base . ' ' . ($attributes->get('class') ?? ''));
@endphp

<span {{ $attributes->except(['class'])->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
