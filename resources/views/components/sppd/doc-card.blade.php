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
            'hover' => 'hover:border-orange-300',
            'title' => 'group-hover:text-orange-700',
            'arrow' => 'text-orange-500',
            'ring'  => 'focus-visible:ring-orange-400',
            'edge'  => 'bg-orange-400/70',
        ],
        'primary' => [
            'chip'  => 'bg-primary-100 text-primary-600',
            'hover' => 'hover:border-primary-300',
            'title' => 'group-hover:text-primary-700',
            'arrow' => 'text-primary-600',
            'ring'  => 'focus-visible:ring-primary-500',
            'edge'  => 'bg-primary-500/70',
        ],
    ];
    $t = $tones[$tone] ?? $tones['primary'];
@endphp

<a wire:navigate href="{{ $href }}"
    class="group relative flex items-center gap-3 overflow-hidden rounded border border-slate-200 bg-white py-3 pl-4 pr-10 shadow-sm transition-all duration-200 {{ $t['hover'] }} hover:shadow-md active:scale-[0.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset {{ $t['ring'] }}">
    {{-- Garis aksen kiri menandai fase (sebelum/sesudah perjalanan). --}}
    <span class="absolute inset-y-0 left-0 w-1 {{ $t['edge'] }}" aria-hidden="true"></span>
    <div class="flex size-10 shrink-0 items-center justify-center rounded {{ $t['chip'] }}">
        <i class="fa-solid {{ $icon }} text-base"></i>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-bold leading-snug text-slate-800 transition-colors {{ $t['title'] }}">{{ $title }}</p>
        <p class="mt-0.5 text-xs text-slate-500">{{ $desc }}</p>
    </div>
    {{-- Panah muncul & bergeser saat hover; pr-10 di kartu menyisakan tempatnya. --}}
    <span
        class="absolute right-3 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-full bg-slate-50 opacity-0 transition-all duration-200 group-hover:translate-x-0.5 group-hover:opacity-100 {{ $t['arrow'] }}">
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
    </span>
</a>
