@props([
    // Judul panel (mis. "SPPD Terbaru").
    'title' => '',
    // Keterangan kecil di bawah judul (opsional).
    'subtitle' => null,
    // Ikon Font Awesome (mis. "fa-chart-line").
    'icon' => 'fa-chart-simple',
    // Tone semantik ikon: primary, amber, violet, emerald, blue, rose, slate.
    'tone' => 'primary',
])

{{--
  Header panel beranda. Identitas warna panel dibawa oleh ubin ikon bertone,
  bukan garis warna di sisi kiri kartu (dilarang DESIGN.md). Slot default =
  aksi di kanan (mis. tautan "Lihat Semua"); slot "badge" = penghitung di
  samping judul.
--}}

@php
    // Kelas ditulis literal agar terpindai Tailwind v4.
    $tones = [
        'primary' => 'bg-primary-50 text-primary-600 ring-primary-600/15',
        'amber'   => 'bg-amber-50 text-amber-600 ring-amber-600/20',
        'violet'  => 'bg-violet-50 text-violet-600 ring-violet-600/15',
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-600/15',
        'blue'    => 'bg-blue-50 text-blue-600 ring-blue-600/15',
        'rose'    => 'bg-rose-50 text-rose-600 ring-rose-600/15',
        'slate'   => 'bg-slate-100 text-slate-500 ring-slate-500/15',
    ];
    $t = $tones[$tone] ?? $tones['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3']) }}>
  <div class="flex min-w-0 items-center gap-3">
    <span class="flex size-9 shrink-0 items-center justify-center rounded ring-1 ring-inset {{ $t }}" aria-hidden="true">
      <i class="fa-solid {{ $icon }} text-sm"></i>
    </span>
    <div class="min-w-0">
      <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
        <span class="truncate">{{ $title }}</span>
        {{ $badge ?? '' }}
      </h3>
      @if ($subtitle)
        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $subtitle }}</p>
      @endif
    </div>
  </div>

  @if ($slot->isNotEmpty())
    <div class="flex shrink-0 items-center gap-4">{{ $slot }}</div>
  @endif
</div>
