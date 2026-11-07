@props([
    'title',
    'icon' => null,
])

{{-- Kartu isi halaman dokumen SPPD, satu bahasa dengan kartu di halaman detail SPPD:
     header biru solid berteks putih, slot `aside` untuk ringkasan kecil di kanan header. --}}
<section {{ $attributes->merge(['class' => 'dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm']) }}>
    <h2 class="flex flex-wrap items-center gap-x-2 gap-y-1 bg-primary-700 px-5 py-3 text-sm font-bold text-white">
        @if ($icon)
            <i class="fa-solid {{ $icon }} text-xs text-primary-100"></i>
        @endif
        {{ $title }}
        @isset($aside)
            <span class="ml-auto text-xs font-medium text-primary-100">{{ $aside }}</span>
        @endisset
    </h2>
    {{ $slot }}
</section>
