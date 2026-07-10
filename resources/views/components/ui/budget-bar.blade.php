@props([
    // Persentase pemakaian (0..>100). >100 = over budget.
    'percentage' => 0,
    // Tinggi bar (kelas Tailwind), mis. h-2, h-2.5, h-3.
    'height' => 'h-2.5',
])

@php
    $pct = (float) $percentage;
    $width = max(0, min(100, $pct));

    // Ambang warna + label teks (agar makna tak lewat warna saja — a11y).
    // Kelas ditulis literal agar terpindai Tailwind v4.
    [$bar, $level] = match (true) {
        $pct > 90 => ['bg-red-500', 'Kritis'],
        $pct > 75 => ['bg-orange-500', 'Tinggi'],
        $pct > 50 => ['bg-yellow-400', 'Waspada'],
        default   => ['bg-green-500', 'Aman'],
    };
@endphp

<div {{ $attributes->merge(['class' => 'w-full overflow-hidden rounded-full bg-slate-100 ' . $height]) }}
    role="progressbar" aria-valuenow="{{ round($pct) }}" aria-valuemin="0" aria-valuemax="100"
    aria-label="Penggunaan anggaran {{ number_format($pct, 1, ',', '.') }}% — {{ $level }}">
    <div class="{{ $bar }} {{ $height }} rounded-full transition-all duration-500 motion-reduce:transition-none"
        style="width: {{ $width }}%"></div>
</div>
