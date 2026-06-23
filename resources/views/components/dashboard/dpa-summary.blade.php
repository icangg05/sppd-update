@props([
    'pagu' => 0,
    'realisasi' => 0,
    'sisa' => 0,
    'percentage' => 0,
    'year' => null,
    'title' => 'DPA Tahun Berjalan',
    'subtitle' => null,
])

@php
    $pct = (float) $percentage;
    $pctLabel = $pagu > 0 ? number_format($pct, 1, ',', '.') : '0';

    $statusText = $pct > 90 ? 'Kritis' : ($pct > 75 ? 'Tinggi' : ($pct > 50 ? 'Waspada' : 'Aman'));
    $statusColor = $pct > 90
        ? 'bg-red-50 text-red-700 ring-red-600/20'
        : ($pct > 75 ? 'bg-orange-50 text-orange-700 ring-orange-600/20'
        : ($pct > 50 ? 'bg-yellow-50 text-yellow-800 ring-yellow-600/20'
        : 'bg-green-50 text-green-700 ring-green-600/20'));
@endphp

<div class="rounded border border-slate-200 bg-white p-5 shadow-md">
  <div class="mb-4 flex items-center justify-between">
    <div>
      <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-wallet text-cyan-500"></i> {{ $title }}
        @if ($year)
          <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-500">{{ $year }}</span>
        @endif
      </h3>
      @if ($subtitle)
        <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
      @endif
    </div>
    <span class="inline-flex items-center rounded-md px-2 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusColor }}">
      {{ $statusText }}
    </span>
  </div>

  <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
    {{-- Pagu --}}
    <div class="rounded border border-slate-100 bg-slate-50 p-3">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pagu DPA</p>
      <p class="mt-1 font-mono text-base font-bold text-slate-800">Rp {{ number_format($pagu, 0, ',', '.') }}</p>
    </div>
    {{-- Realisasi --}}
    <div class="rounded border border-cyan-100 bg-cyan-50/60 p-3">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-cyan-500">Realisasi</p>
      <p class="mt-1 font-mono text-base font-bold text-cyan-700">Rp {{ number_format($realisasi, 0, ',', '.') }}</p>
    </div>
    {{-- Sisa --}}
    <div class="rounded border p-3 {{ $sisa < 0 ? 'border-rose-100 bg-rose-50' : 'border-emerald-100 bg-emerald-50/60' }}">
      <p class="text-[11px] font-semibold uppercase tracking-wide {{ $sisa < 0 ? 'text-rose-500' : 'text-emerald-500' }}">
        Sisa Anggaran
      </p>
      <p class="mt-1 font-mono text-base font-bold {{ $sisa < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
        Rp {{ number_format($sisa, 0, ',', '.') }}
      </p>
    </div>
  </div>

  <div class="space-y-1.5">
    <div class="flex items-center justify-between text-xs">
      <span class="font-medium text-slate-500">Penggunaan anggaran</span>
      <span class="font-mono font-bold text-slate-700">{{ $pctLabel }}%</span>
    </div>
    <x-ui.budget-bar :percentage="$pct" height="h-3" />
  </div>
</div>
