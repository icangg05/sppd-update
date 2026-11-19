@props([
    // URL tujuan (sudah di-resolve dari route).
    'href' => '#',
    'title' => '',
    'desc' => '',
    'icon' => 'fa-file-lines',
    // Fase alur: 'orange' (sebelum perjalanan) atau 'primary' (sesudah).
    'tone' => 'primary',
])

@php
    // Kelas ditulis literal per tone agar terpindai Tailwind v4 (bukan interpolasi runtime).
    $tones = [
        'orange' => [
            'chip'  => 'bg-orange-100 text-orange-600',
            'hover' => 'hover:border-orange-300 hover:bg-orange-50/60',
            'title' => 'group-hover:text-orange-700',
            'arrow' => 'group-hover:border-orange-500 group-hover:bg-orange-500',
            'ring'  => 'focus-visible:ring-orange-400',
        ],
        'primary' => [
            'chip'  => 'bg-primary-100 text-primary-600',
            'hover' => 'hover:border-primary-300 hover:bg-primary-50/60',
            'title' => 'group-hover:text-primary-700',
            'arrow' => 'group-hover:border-primary-600 group-hover:bg-primary-600',
            'ring'  => 'focus-visible:ring-primary-500',
        ],
    ];
    $t = $tones[$tone] ?? $tones['primary'];
@endphp

{{-- Fase (sebelum/sesudah perjalanan) ditandai warna ikon; saat hover bingkai, latar, dan panah ikut warnanya. --}}
<a wire:navigate href="{{ $href }}"
    class="group flex items-center gap-3 rounded border border-slate-200 bg-white px-4 py-3 shadow-sm transition-all duration-200 {{ $t['hover'] }} hover:shadow-md active:scale-[0.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset {{ $t['ring'] }}">
    <div class="flex size-10 shrink-0 items-center justify-center rounded {{ $t['chip'] }}">
        <i class="fa-solid {{ $icon }} text-base"></i>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-bold leading-snug text-slate-800 transition-colors {{ $t['title'] }}">{{ $title }}</p>
        <p class="mt-0.5 text-xs text-slate-500">{{ $desc }}</p>
    </div>
    <span aria-hidden="true"
        class="flex size-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-white {{ $t['arrow'] }}">
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
    </span>
</a>
