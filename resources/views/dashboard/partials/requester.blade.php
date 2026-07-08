{{-- Dashboard: Pemohon (staf, anggota_dprd) --}}

{{-- KPI SPPD saya --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
  @php
    $kpis = [
      ['label' => 'Dalam Proses', 'value' => $stats['in_progress'], 'icon' => 'fa-hourglass-half', 'tone' => 'bg-amber-50 text-amber-600'],
      ['label' => 'Disetujui', 'value' => $stats['approved'], 'icon' => 'fa-circle-check', 'tone' => 'bg-green-50 text-green-600'],
      ['label' => 'Selesai', 'value' => $stats['completed'], 'icon' => 'fa-flag-checkered', 'tone' => 'bg-emerald-50 text-emerald-600'],
      ['label' => 'Perlu Perbaikan', 'value' => $stats['rejected'], 'icon' => 'fa-pen-to-square', 'tone' => 'bg-rose-50 text-rose-600'],
    ];
  @endphp
  @foreach ($kpis as $kpi)
    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-md">
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
  {{-- SPPD saya terbaru --}}
  @include('dashboard.partials._recent-sppd', ['items' => $mySppd, 'title' => 'SPPD Saya'])

  {{-- Laporan perjalanan yang perlu dilengkapi --}}
  <div class="flex flex-col rounded-2xl border border-slate-200 bg-white shadow-md">
    <div class="border-b border-slate-100 p-4">
      <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-clipboard-list text-primary-500"></i> Laporan Perlu Dilengkapi
      </h3>
      <p class="mt-0.5 text-xs text-slate-500">SPPD selesai yang laporannya belum dibuat</p>
    </div>
    <div class="flex flex-col divide-y divide-slate-100">
      @forelse ($needReport as $item)
        <a wire:navigate href="{{ route('sppd.report-input', $item) }}"
          class="flex items-center justify-between gap-3 p-3 text-sm transition hover:bg-slate-50">
          <div class="min-w-0">
            <p class="line-clamp-1 font-medium text-slate-700">{{ $item->purpose }}</p>
            <p class="text-xs text-slate-400">{{ $item->destinations->first()?->regency?->name ?? '-' }}</p>
          </div>
          <span class="shrink-0 rounded bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700">Buat Laporan</span>
        </a>
      @empty
        <div class="flex flex-col items-center justify-center p-8 text-slate-400">
          <i class="fa-solid fa-circle-check mb-3 text-3xl text-slate-200"></i>
          <p class="text-sm">Semua laporan sudah lengkap</p>
        </div>
      @endforelse
    </div>
  </div>
</div>
