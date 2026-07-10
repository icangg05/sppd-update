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
    // Peta tone → warna ikon + aksen. Kelas ditulis literal agar terpindai Tailwind v4.
    $tones = [
        'primary' => ['icon' => 'bg-primary-50 text-primary-600', 'bar' => 'bg-primary-400'],
        'amber'   => ['icon' => 'bg-amber-50 text-amber-600',     'bar' => 'bg-amber-400'],
        'violet'  => ['icon' => 'bg-violet-50 text-violet-600',   'bar' => 'bg-violet-400'],
        'emerald' => ['icon' => 'bg-emerald-50 text-emerald-600', 'bar' => 'bg-emerald-400'],
        'green'   => ['icon' => 'bg-green-50 text-green-600',     'bar' => 'bg-green-400'],
        'rose'    => ['icon' => 'bg-rose-50 text-rose-600',       'bar' => 'bg-rose-400'],
        'blue'    => ['icon' => 'bg-blue-50 text-blue-600',       'bar' => 'bg-blue-400'],
        'slate'   => ['icon' => 'bg-slate-100 text-slate-600',    'bar' => 'bg-slate-400'],
    ];
    $t = $tones[$tone] ?? $tones['primary'];
@endphp

<div
    {{ $attributes->merge(['class' => 'group relative flex items-center gap-3.5 overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md']) }}>
    {{-- Aksen tipis sesuai tone (satu sumber warna, bukan warna baru). --}}
    <span class="absolute inset-y-0 left-0 w-1 {{ $t['bar'] }}" aria-hidden="true"></span>

    <div
        class="flex size-11 shrink-0 items-center justify-center rounded {{ $t['icon'] }} transition duration-200 group-hover:scale-105">
        <i class="fa-solid {{ $icon }} text-lg"></i>
    </div>

    <div class="min-w-0">
        <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-3xl font-bold leading-none tracking-tighter tabular-nums text-slate-800 sm:text-4xl">
            {{ $value }}</p>
    </div>
</div>
