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
        'primary' => ['icon' => 'bg-primary-50 text-primary-600', 'bar' => 'bg-primary-400', 'wm' => 'text-primary-500'],
        'amber'   => ['icon' => 'bg-amber-50 text-amber-600',     'bar' => 'bg-amber-400',   'wm' => 'text-amber-500'],
        'violet'  => ['icon' => 'bg-violet-50 text-violet-600',   'bar' => 'bg-violet-400',  'wm' => 'text-violet-500'],
        'emerald' => ['icon' => 'bg-emerald-50 text-emerald-600', 'bar' => 'bg-emerald-400', 'wm' => 'text-emerald-500'],
        'green'   => ['icon' => 'bg-green-50 text-green-600',     'bar' => 'bg-green-400',   'wm' => 'text-green-500'],
        'rose'    => ['icon' => 'bg-rose-50 text-rose-600',       'bar' => 'bg-rose-400',    'wm' => 'text-rose-500'],
        'blue'    => ['icon' => 'bg-blue-50 text-blue-600',       'bar' => 'bg-blue-400',    'wm' => 'text-blue-500'],
        'slate'   => ['icon' => 'bg-slate-100 text-slate-600',    'bar' => 'bg-slate-400',   'wm' => 'text-slate-500'],
    ];
    $t = $tones[$tone] ?? $tones['primary'];
@endphp

<div
    {{ $attributes->merge(['class' => 'group relative flex items-center gap-3.5 overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md']) }}>
    {{-- Aksen tipis sesuai tone (satu sumber warna, bukan warna baru). --}}
    <span class="absolute inset-y-0 left-0 w-1 {{ $t['bar'] }}" aria-hidden="true"></span>
    {{-- Ornamen watermark: ikon besar sangat tipis di sudut, muncul saat diamati. --}}
    <i class="fa-solid {{ $icon }} pointer-events-none absolute -bottom-4 right-1 text-7xl opacity-[0.05] transition duration-200 group-hover:opacity-[0.08] {{ $t['wm'] }}"
        aria-hidden="true"></i>

    <div
        class="relative flex size-11 shrink-0 items-center justify-center rounded {{ $t['icon'] }} transition duration-200 group-hover:scale-105">
        <i class="fa-solid {{ $icon }} text-lg"></i>
    </div>

    <div class="relative min-w-0">
        <p class="truncate text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-3xl font-bold leading-none tracking-tighter tabular-nums text-slate-800 sm:text-4xl">
            {{ $value }}</p>
    </div>
</div>
