@props([
    'sppd',
    'title',
    // Satu kalimat langkah kerja halaman ini, bahasa petugas.
    'description' => null,
])

@php
    $dateRange = $sppd->start_date->isSameDay($sppd->end_date)
        ? $sppd->start_date->translatedFormat('d M Y')
        : $sppd->start_date->translatedFormat('d M Y') . ' s/d ' . $sppd->end_date->translatedFormat('d M Y');
@endphp

{{-- Kepala halaman dokumen SPPD: tautan kembali ke Portal Dokumen, judul, lalu identitas
     perjalanan (pelaksana, tanggal, nomor) agar petugas yakin sedang mengolah SPPD yang benar. --}}
<header {{ $attributes->merge(['class' => 'dash-enter rounded border border-slate-200 bg-white px-5 py-4 shadow-sm']) }}>
    <a href="{{ route('sppd.next', $sppd) }}" wire:navigate
        class="inline-flex items-center gap-1.5 rounded text-xs font-semibold text-slate-500 transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
        <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali ke Portal Dokumen
    </a>

    <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-balance text-slate-800">{{ $title }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
                <span class="inline-flex items-center gap-1.5"><i class="fa-regular fa-user text-xs text-slate-500"></i>
                    {{ $sppd->user->name }}</span>
                <span class="inline-flex items-center gap-1.5"><i class="fa-regular fa-calendar text-xs text-slate-500"></i>
                    {{ $dateRange }}</span>
                @if ($sppd->document_number)
                    <span class="inline-flex items-center gap-1.5 font-mono text-xs text-slate-600"><i
                            class="fa-regular fa-file-lines text-xs text-slate-500"></i> {{ $sppd->document_number }}</span>
                @endif
            </p>
            @if ($description)
                <p class="mt-1.5 text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</header>
