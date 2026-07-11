{{-- Dashboard: Pemohon (staf, anggota_dprd) --}}

{{-- KPI SPPD saya --}}
<div class="dash-enter grid grid-cols-2 gap-4 lg:grid-cols-4">
  @php
    $kpis = [
      ['label' => 'Dalam Proses', 'value' => $stats['in_progress'], 'icon' => 'fa-hourglass-half', 'tone' => 'amber'],
      ['label' => 'Disetujui', 'value' => $stats['approved'], 'icon' => 'fa-circle-check', 'tone' => 'green'],
      ['label' => 'Selesai', 'value' => $stats['completed'], 'icon' => 'fa-flag-checkered', 'tone' => 'emerald'],
      ['label' => 'Perlu Perbaikan', 'value' => $stats['rejected'], 'icon' => 'fa-pen-to-square', 'tone' => 'rose'],
    ];
  @endphp
  @foreach ($kpis as $kpi)
    <x-dashboard.stat-card :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :tone="$kpi['tone']" />
  @endforeach
</div>

<div class="dash-enter grid grid-cols-1 gap-5 lg:grid-cols-2">
  {{-- SPPD saya terbaru --}}
  @include('dashboard.partials._recent-sppd', ['items' => $mySppd, 'title' => 'SPPD Saya'])

  {{-- Laporan perjalanan yang perlu dilengkapi --}}
  <div class="flex flex-col rounded border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 p-4">
      <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-clipboard-list text-primary-500"></i> Laporan Perlu Dilengkapi
        @if ($needReport->isNotEmpty())
          <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-bold text-primary-700">{{ $needReport->count() }}</span>
        @endif
      </h3>
      <p class="mt-0.5 text-xs text-slate-500">SPPD selesai yang laporannya belum dibuat</p>
    </div>
    <div class="flex flex-col divide-y divide-slate-100">
      @forelse ($needReport as $item)
        <a wire:navigate href="{{ route('sppd.report-input', $item) }}"
          class="flex items-center justify-between gap-3 p-3 text-sm transition hover:bg-slate-50 focus:outline-none focus-visible:bg-primary-50/60 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
          <div class="min-w-0">
            <p class="line-clamp-1 font-medium text-slate-700">{{ $item->purpose }}</p>
            <p class="text-xs text-slate-500">{{ $item->destinations->first()?->regency?->name ?? '-' }}</p>
          </div>
          <span class="shrink-0 rounded bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700">Buat Laporan</span>
        </a>
      @empty
        <div class="flex flex-col items-center justify-center p-8 text-slate-500">
          <i class="fa-solid fa-circle-check mb-3 text-3xl text-slate-300"></i>
          <p class="text-sm">Semua laporan sudah lengkap</p>
        </div>
      @endforelse
    </div>
  </div>
</div>
