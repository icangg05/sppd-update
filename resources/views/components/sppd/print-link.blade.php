@props([
    // Terkunci: tampil sebagai tombol mati berikon gembok, alasan dibaca lewat title & aria.
    'disabled' => false,
    'reason' => null,
    'icon' => 'fa-print',
    // sm: aksi per baris tabel; md: aksi utama halaman (setara x-ui.button md).
    'size' => 'sm',
    // primary: dokumen akhir/utama; amber: dokumen sementara (mis. kuitansi panjar),
    // agar dua tombol cetak berdampingan mudah dibedakan. amber-700 menjaga kontras teks putih ≥ 4.5:1.
    'tone' => 'primary',
])

@php
    $toneClass = $tone === 'amber'
        ? 'bg-amber-700 text-white hover:bg-amber-800 focus-visible:ring-amber-500'
        : 'bg-primary-600 text-white hover:bg-primary-800 focus-visible:ring-primary-500';
    $sizeClass = $size === 'md' ? 'gap-2 px-4 py-2 text-sm' : 'gap-1.5 px-3 py-1.5 text-xs';
    $base = 'inline-flex items-center justify-center whitespace-nowrap rounded font-semibold shadow-2xs transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 ' . $sizeClass;
@endphp

{{-- Tombol cetak dokumen PDF (tab baru), satu gaya di semua halaman dokumen SPPD.
     Atribut tambahan (mis. x-bind:href berisi tanggal cetak) diteruskan ke <a>. --}}
@if ($disabled)
    <span role="link" aria-disabled="true" title="{{ $reason }}" @if ($reason) aria-label="{{ trim($slot) }}: {{ $reason }}" @endif
        class="{{ $base }} cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-500 shadow-none">
        <i class="fa-solid fa-lock text-[10px]"></i> {{ $slot }}
    </span>
@else
    <a target="_blank" rel="noopener"
        {{ $attributes->merge(['class' => $base . ' ' . $toneClass]) }}>
        <i class="fa-solid {{ $icon }} text-[10px]"></i> {{ $slot }}
    </a>
@endif
