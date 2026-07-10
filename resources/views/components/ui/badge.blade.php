@props([
    // Mode status: petakan ke kelas CSS badge-{status} (draft, approved, rejected, dst).
    'status' => null,
    // Mode token warna: pill lembut sesuai warna Tailwind (lihat App\Support\BadgeColor).
    'color' => null,
    // Mode warna manual (dipakai jika status & color tidak diisi).
    'bg' => 'bg-slate-100',
    'text' => 'text-slate-700',
])

@php
    // Kelas pill lembut per token warna. Ditulis literal agar terpindai Tailwind v4.
    $colorMap = [
        'slate' => 'bg-slate-50 text-slate-700 ring-slate-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'yellow' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
        'lime' => 'bg-lime-50 text-lime-700 ring-lime-600/20',
        'green' => 'bg-green-50 text-green-700 ring-green-600/20',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'cyan' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'fuchsia' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-600/20',
        'pink' => 'bg-pink-50 text-pink-700 ring-pink-600/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    ];

    if ($color) {
        $pill = $colorMap[$color] ?? $colorMap['slate'];
        $base = 'inline-flex items-center rounded-md px-2 py-1 text-[11px] font-medium ring-1 ring-inset ' . $pill;
    } elseif ($status) {
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
