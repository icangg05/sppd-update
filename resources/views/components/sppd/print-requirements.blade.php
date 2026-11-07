@props([
    'title' => 'Cetak belum bisa dilakukan',
    // Daftar prasyarat: [['ok' => bool, 'label' => string, 'href' => ?string, 'link' => ?string]].
    // Hanya prasyarat yang belum terpenuhi yang ditampilkan; tanpa kekurangan, alert tidak muncul.
    'items' => [],
])

@php
    $missing = collect($items)->reject(fn($item) => $item['ok']);
@endphp

@if ($missing->isNotEmpty())
    <div role="status" {{ $attributes->merge(['class' => 'dash-enter flex items-start gap-3 rounded border border-amber-300 bg-amber-50 px-4 py-3']) }}>
        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-sm text-amber-600"></i>
        <div class="min-w-0 text-sm">
            <p class="font-bold text-amber-900">{{ $title }}</p>
            <ul class="mt-1 space-y-0.5 text-amber-900">
                @foreach ($missing as $item)
                    <li>
                        {{ $item['label'] }}
                        @if (!empty($item['href']))
                            <a href="{{ $item['href'] }}" wire:navigate
                                class="ml-1 font-semibold text-amber-950 underline underline-offset-2 hover:no-underline">{{ $item['link'] ?? 'Lengkapi' }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
