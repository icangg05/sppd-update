@props([
    'title' => 'Cetak belum bisa dilakukan',
    // Daftar prasyarat: [['ok' => bool, 'label' => string, 'href' => ?string, 'link' => ?string]].
    // Hanya prasyarat yang belum terpenuhi yang ditampilkan; tanpa kekurangan, kotak tidak muncul.
    'items' => [],
])

@php
    $missing = collect($items)->reject(fn($item) => $item['ok']);
    $total = count($items);
@endphp

{{-- Daftar periksa syarat cetak: kepala bertint amber berisi judul & jumlah syarat yang kurang, lalu satu baris
     per syarat dengan tombol menuju tempat melengkapinya (di layar sempit tombol turun ke bawah teks). --}}
@if ($missing->isNotEmpty())
    <div role="status" {{ $attributes->merge(['class' => 'dash-enter overflow-hidden rounded border border-amber-300 bg-white shadow-sm']) }}>
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-b border-amber-200 bg-amber-50 px-4 py-2.5">
            <p class="flex items-center gap-2.5 text-sm font-bold text-amber-900">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700" aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                </span>
                {{ $title }}
            </p>
            <span class="text-xs font-semibold text-amber-800">{{ $missing->count() }} dari {{ $total }} syarat belum terpenuhi</span>
        </div>
        <ul class="divide-y divide-slate-100">
            @foreach ($missing as $item)
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-2.5">
                    <span class="flex min-w-0 items-center gap-2.5 text-sm text-slate-800">
                        <i class="fa-regular fa-circle text-[11px] text-amber-500" aria-hidden="true"></i>
                        {{ $item['label'] }}
                    </span>
                    @if (!empty($item['href']))
                        <x-ui.button variant="secondary" size="sm" href="{{ $item['href'] }}" class="shrink-0">
                            {{ $item['link'] ?? 'Lengkapi' }}
                            <i class="fa-solid fa-arrow-right text-[10px] text-slate-500" aria-hidden="true"></i>
                        </x-ui.button>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
