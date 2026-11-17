@props([
    // SppdRequest yang ditampilkan (relasi user, destinations, costDetails sebaiknya sudah dimuat).
    'sppd',
    // Tujuan tautan baris; bawaan = halaman detail SPPD.
    'href' => null,
])

{{--
  Satu baris SPPD di daftar beranda (SPPD Terbaru, SPPD Saya, Menunggu
  Persetujuan). Batas antar-item dibentuk oleh tiga hal sekaligus: ubin tanggal
  berangkat di kiri sebagai jangkar visual tiap baris (judul SPPD sering mirip
  sehingga tidak bisa diandalkan), latar zebra zinc-100, dan garis pembatas
  slate-200 dari wadah `divide-y`. Bukan kartu bersarang (dilarang DESIGN.md).
  Zebra sengaja abu netral (zinc), bukan slate/primary: body memakai blue-50,
  dan tint kebiruan selevel itu membuat baris genap tampak tembus ke latar.
  Slot "aside" = penanda di kanan judul (mis. badge status).
--}}

@php
    $isSegera = strtolower($sppd->urgency ?? '') === 'segera';
    $date = $sppd->start_date ?? $sppd->created_at;
@endphp

<a wire:navigate href="{{ $href ?? route('sppd.show', $sppd) }}"
  {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-3 transition-colors even:bg-zinc-100 hover:bg-primary-50 focus:outline-none focus-visible:bg-primary-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500']) }}>
  {{-- Ubin tanggal berangkat; merah lembut bila Segera agar menonjol di antrean. --}}
  <span @class([
      'flex w-11 shrink-0 flex-col items-center justify-center rounded border bg-white py-1.5 leading-none',
      'border-rose-200 text-rose-700' => $isSegera,
      'border-slate-200 text-slate-700' => ! $isSegera,
  ]) title="Tanggal berangkat {{ $date?->locale('id')->translatedFormat('d F Y') }}">
    <span class="font-mono text-base font-bold">{{ $date?->format('d') ?? '-' }}</span>
    <span @class(['mt-1 text-[10px] font-semibold uppercase tracking-wide', 'text-rose-600' => $isSegera, 'text-slate-500' => ! $isSegera])>
      {{ $date?->locale('id')->translatedFormat('M') }}
    </span>
  </span>

  <span class="flex min-w-0 flex-1 flex-col gap-1.5">
    <span class="flex items-start justify-between gap-3">
      <span class="line-clamp-2 text-sm font-semibold leading-snug text-slate-800 lg:line-clamp-1">{{ $sppd->purpose }}</span>
      {{ $aside ?? '' }}
    </span>
    <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
      <x-ui.badge :color="$isSegera ? 'rose' : 'slate'" size="xs" class="shrink-0 uppercase">{{ $isSegera ? 'Segera' : 'Biasa' }}</x-ui.badge>
      <span class="flex min-w-0 items-center gap-1"><i class="fa-regular fa-user"></i> <span class="truncate">{{ $sppd->user->name ?? '-' }}</span></span>
      <span class="text-slate-300" aria-hidden="true">&bull;</span>
      <span class="flex min-w-0 items-center gap-1"><i class="fa-solid fa-location-dot"></i>
        <span class="truncate">{{ $sppd->destinations->first()?->place_name ?? '-' }}</span></span>
      <span class="text-slate-300" aria-hidden="true">&bull;</span>
      <span class="font-mono font-semibold text-slate-700">Rp {{ number_format($sppd->costDetails->sum('total'), 0, ',', '.') }}</span>
    </span>
  </span>
</a>
