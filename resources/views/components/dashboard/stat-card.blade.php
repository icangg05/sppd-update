@props([
    // Label kecil di atas angka (mis. "Total SPPD").
    'label' => '',
    // Angka/nilai utama.
    'value' => 0,
    // Ikon Font Awesome (mis. "fa-file-lines").
    'icon' => 'fa-chart-simple',
    // Tone semantik: primary, amber, violet, emerald, green, rose, blue, slate.
    'tone' => 'primary',
])

@php
    // Peta tone → ubin ikon, tint latar sudut, batas saat hover, dan watermark.
    // Identitas warna dibawa tint + ikon, bukan garis warna di sisi kiri
    // (dilarang DESIGN.md). Kelas ditulis literal agar terpindai Tailwind v4.
    $tones = [
        'primary' => ['icon' => 'bg-primary-50 text-primary-600 ring-primary-600/15', 'tint' => 'to-primary-50',  'hover' => 'hover:border-primary-200', 'wm' => 'text-primary-500'],
        'amber'   => ['icon' => 'bg-amber-50 text-amber-600 ring-amber-600/20',       'tint' => 'to-amber-50',    'hover' => 'hover:border-amber-200',   'wm' => 'text-amber-500'],
        'violet'  => ['icon' => 'bg-violet-50 text-violet-600 ring-violet-600/15',    'tint' => 'to-violet-50',   'hover' => 'hover:border-violet-200',  'wm' => 'text-violet-500'],
        'emerald' => ['icon' => 'bg-emerald-50 text-emerald-600 ring-emerald-600/15', 'tint' => 'to-emerald-50',  'hover' => 'hover:border-emerald-200', 'wm' => 'text-emerald-500'],
        'green'   => ['icon' => 'bg-green-50 text-green-600 ring-green-600/15',       'tint' => 'to-green-50',    'hover' => 'hover:border-green-200',   'wm' => 'text-green-500'],
        'rose'    => ['icon' => 'bg-rose-50 text-rose-600 ring-rose-600/15',          'tint' => 'to-rose-50',     'hover' => 'hover:border-rose-200',    'wm' => 'text-rose-500'],
        'blue'    => ['icon' => 'bg-blue-50 text-blue-600 ring-blue-600/15',          'tint' => 'to-blue-50',     'hover' => 'hover:border-blue-200',    'wm' => 'text-blue-500'],
        'slate'   => ['icon' => 'bg-slate-100 text-slate-600 ring-slate-600/15',      'tint' => 'to-slate-100',   'hover' => 'hover:border-slate-300',   'wm' => 'text-slate-500'],
    ];
    $t = $tones[$tone] ?? $tones['primary'];
@endphp

<div
    {{ $attributes->merge(['class' => 'group relative flex flex-col items-start gap-3 overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white from-45% p-4 shadow-sm transition duration-200 sm:flex-row sm:items-center sm:gap-3.5 hover:-translate-y-0.5 hover:shadow-md ' . $t['tint'] . ' ' . $t['hover']]) }}>
    {{-- Ornamen watermark: ikon besar sangat tipis di sudut, muncul saat diamati. --}}
    <i class="fa-solid {{ $icon }} pointer-events-none absolute -bottom-4 right-1 text-7xl opacity-[0.06] transition duration-200 group-hover:opacity-[0.1] {{ $t['wm'] }}"
        aria-hidden="true"></i>

    <div
        class="relative flex size-10 shrink-0 sm:size-11 items-center justify-center rounded ring-1 ring-inset {{ $t['icon'] }} transition duration-200 group-hover:scale-105">
        <i class="fa-solid {{ $icon }} text-lg"></i>
    </div>

    {{-- Layar kecil: ikon di atas angka agar label tidak terpotong; sm ke atas berdampingan. --}}
    <div class="relative w-full min-w-0">
        <p class="truncate text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-3xl font-bold leading-none tracking-tighter tabular-nums text-slate-800 sm:text-4xl">
            {{ $value }}</p>
    </div>
</div>
