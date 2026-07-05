<div class="flex flex-col gap-5 p-1">

  {{-- Header --}}
  <div class="leading-tight">
    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
      <i class="fa-solid fa-chart-pie text-cyan-600"></i> Rekap &amp; Statistik SPPD
    </h1>
    <p class="text-xs text-slate-500 mt-0.5">Ringkasan pengajuan perjalanan dinas dalam lingkup kewenangan Anda</p>
  </div>

  {{-- KPI ringkas --}}
  <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
    @php
      $kpis = [
        ['label' => 'Total SPPD',    'value' => $stats['total'],       'icon' => 'fa-layer-group',  'tone' => 'bg-slate-100 text-slate-600'],
        ['label' => 'Dalam Proses',  'value' => $stats['in_progress'], 'icon' => 'fa-spinner',      'tone' => 'bg-blue-50 text-blue-600'],
        ['label' => 'Disetujui',     'value' => $stats['approved'],    'icon' => 'fa-circle-check', 'tone' => 'bg-green-50 text-green-600'],
        ['label' => 'Selesai',       'value' => $stats['completed'],   'icon' => 'fa-flag-checkered','tone' => 'bg-emerald-50 text-emerald-600'],
        ['label' => 'Ditolak',       'value' => $stats['rejected'],    'icon' => 'fa-circle-xmark', 'tone' => 'bg-rose-50 text-rose-600'],
      ];
    @endphp
    @foreach ($kpis as $kpi)
      <div class="flex items-center gap-3 rounded border border-slate-200 bg-white p-4 shadow-md">
        <div class="flex size-10 items-center justify-center rounded {{ $kpi['tone'] }}">
          <i class="fa-solid {{ $kpi['icon'] }} text-lg"></i>
        </div>
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
          <p class="text-lg font-bold text-slate-800">{{ $kpi['value'] }}</p>
        </div>
      </div>
    @endforeach
  </div>

  <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    {{-- Tren 12 bulan --}}
    <div class="rounded border border-slate-200 bg-white p-4 shadow-md">
      <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-chart-column text-cyan-500"></i> Tren Pengajuan 12 Bulan
      </h3>
      @php $maxTrend = max(1, collect($monthlyTrend)->max('masuk')); @endphp
      <div class="flex items-end justify-between gap-1.5" style="height: 160px;">
        @foreach ($monthlyTrend as $m)
          <div class="flex flex-1 flex-col items-center justify-end gap-1" title="{{ $m['month'] }}: {{ $m['masuk'] }} masuk, {{ $m['selesai'] }} selesai">
            <div class="flex w-full items-end justify-center gap-0.5" style="height: 130px;">
              <div class="w-1/2 rounded-t bg-blue-400" style="height: {{ (int) round($m['masuk'] / $maxTrend * 100) }}%;"></div>
              <div class="w-1/2 rounded-t bg-emerald-400" style="height: {{ (int) round($m['selesai'] / $maxTrend * 100) }}%;"></div>
            </div>
            <span class="text-[9px] font-medium text-slate-400">{{ $m['month'] }}</span>
          </div>
        @endforeach
      </div>
      <div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-slate-500">
        <span class="flex items-center gap-1"><span class="inline-block size-2.5 rounded-sm bg-blue-400"></span> Masuk</span>
        <span class="flex items-center gap-1"><span class="inline-block size-2.5 rounded-sm bg-emerald-400"></span> Selesai</span>
      </div>
    </div>

    {{-- Distribusi status --}}
    <div class="rounded border border-slate-200 bg-white p-4 shadow-md">
      <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-chart-pie text-cyan-500"></i> Distribusi Status
      </h3>
      <div class="flex flex-col gap-3">
        @php $totalDist = max(1, $stats['total']); @endphp
        @foreach ($statusDistribution as $d)
          <div>
            <div class="mb-1 flex items-center justify-between text-xs">
              <span class="font-medium text-slate-600">{{ $d['label'] }}</span>
              <span class="font-semibold text-slate-800">{{ $d['count'] }}
                <span class="text-slate-400">({{ round($d['count'] / $totalDist * 100) }}%)</span>
              </span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
              <div class="{{ $d['color'] }} h-full rounded-full" style="width: {{ round($d['count'] / $totalDist * 100) }}%;"></div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Unit kerja tersibuk --}}
  <div class="rounded border border-slate-200 bg-white p-4 shadow-md">
    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800">
      <i class="fa-solid fa-building text-cyan-500"></i> Unit Kerja Tersibuk
    </h3>
    @php $maxUnit = max(1, (int) ($topUnits->max('jumlah') ?? 1)); @endphp
    <div class="flex flex-col gap-3">
      @forelse ($topUnits as $unit)
        <div>
          <div class="mb-1 flex items-center justify-between text-xs">
            <span class="line-clamp-1 font-medium text-slate-600">{{ $unit->unit ?? '-' }}</span>
            <span class="shrink-0 font-semibold text-slate-800">{{ $unit->jumlah }} SPPD</span>
          </div>
          <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-cyan-500" style="width: {{ round($unit->jumlah / $maxUnit * 100) }}%;"></div>
          </div>
        </div>
      @empty
        <p class="py-6 text-center text-sm text-slate-400">Belum ada data SPPD dalam lingkup Anda.</p>
      @endforelse
    </div>
  </div>

</div>
