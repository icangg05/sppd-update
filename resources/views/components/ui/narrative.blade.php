@props([
    // Teks uraian bebas; poin-poin (per baris atau "-A; -B") tampil sebagai daftar.
    'text' => null,
])

@php
    $blocks = \App\Support\NarrativeText::blocks($text);
@endphp

{{-- Uraian telaah (Persoalan, Fakta, Analisis) di detail SPPD & halaman publik. Lihat App\Support\NarrativeText. --}}
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @foreach ($blocks as $block)
        @if ($block['type'] === 'p')
            <p>{{ $block['text'] }}</p>
        @else
            <ul role="list" class="space-y-1">
                @foreach ($block['items'] as $item)
                    <li class="flex gap-2">
                        <span class="min-w-4 shrink-0 tabular-nums text-slate-500" aria-hidden="true">{{ $item['marker'] }}</span>
                        <span class="min-w-0">{{ $item['text'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    @endforeach
</div>
