@props([
    // Persentase pemakaian (0..>100). >100 = over budget.
    'percentage' => 0,
    // Tinggi bar (kelas Tailwind), mis. h-2, h-2.5, h-3.
    'height' => 'h-2.5',
])

@php
    $pct = (float) $percentage;
    $width = max(0, min(100, $pct));

    // Ambang warna: ≤50 hijau, 51–75 kuning, 76–90 oranye, >90 merah.
    // Kelas ditulis literal agar terpindai Tailwind v4.
    $bar = $pct > 90
        ? 'bg-red-500'
        : ($pct > 75 ? 'bg-orange-500' : ($pct > 50 ? 'bg-yellow-400' : 'bg-green-500'));
@endphp

<div {{ $attributes->merge(['class' => 'w-full overflow-hidden rounded-full bg-slate-100 ' . $height]) }}>
    <div class="{{ $bar }} {{ $height }} rounded-full transition-all duration-500" style="width: {{ $width }}%"></div>
</div>
