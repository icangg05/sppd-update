{{-- Dashboard: Admin / Operasional (super_admin, admin_opd) --}}

{{-- KPI Cards --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
  @php
    $kpis = [
      ['label' => 'Total SPPD', 'value' => $stats['total'], 'icon' => 'fa-file-lines', 'tone' => 'bg-blue-50 text-blue-600'],
      ['label' => 'Dalam Proses', 'value' => $stats['in_progress'], 'icon' => 'fa-hourglass-half', 'tone' => 'bg-amber-50 text-amber-600'],
      ['label' => 'Selesai', 'value' => $stats['completed'], 'icon' => 'fa-circle-check', 'tone' => 'bg-emerald-50 text-emerald-600'],
      ['label' => 'Ditolak', 'value' => $stats['rejected'], 'icon' => 'fa-circle-xmark', 'tone' => 'bg-rose-50 text-rose-600'],
    ];
  @endphp
  @foreach ($kpis as $kpi)
    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="flex size-10 items-center justify-center rounded-lg {{ $kpi['tone'] }}">
        <i class="fa-solid {{ $kpi['icon'] }} text-lg"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $kpi['label'] }}</p>
        <p class="text-4xl font-bold leading-none tracking-tight tabular-nums text-slate-800">{{ $kpi['value'] }}</p>
      </div>
    </div>
  @endforeach
</div>

{{-- Charts: Trend + Distribusi Status --}}
<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
  <div class="flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
    <div class="flex items-center justify-between border-b border-slate-100 p-4">
      <div>
        <h3 class="text-sm font-bold text-slate-800">Tren Pengajuan SPPD</h3>
        <p class="mt-0.5 text-xs text-slate-500">Masuk vs selesai 12 bulan terakhir</p>
      </div>
      <div class="flex gap-4 text-xs font-medium text-slate-600">
        <div class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-blue-500"></span> Masuk</div>
        <div class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-emerald-500"></span> Selesai</div>
      </div>
    </div>
    <div class="relative h-60 w-full p-4"><canvas id="trendChart"></canvas></div>
  </div>

  <div class="flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 p-4">
      <h3 class="text-sm font-bold text-slate-800">Distribusi Status</h3>
      <p class="mt-0.5 text-xs text-slate-500">Sebaran status SPPD (lingkup Anda)</p>
    </div>
    <div class="relative h-40 w-full p-4"><canvas id="statusDonutChart"></canvas></div>
    <div class="mt-2 grid grid-cols-1 gap-2 px-5 pb-4">
      @foreach ($statusDistribution as $item)
        @if ($item['count'] > 0)
          <div class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-2.5">
              <span class="size-3 rounded-sm" style="background: {{ $item['color'] }};"></span>
              <span class="text-slate-600">{{ $item['label'] }}</span>
            </div>
            <span class="font-bold text-slate-800">{{ $item['count'] }}</span>
          </div>
        @endif
      @endforeach
    </div>
  </div>
</div>

{{-- Pemakaian anggaran per OPD (super_admin) + SPPD terbaru --}}
<div class="grid grid-cols-1 gap-5 @if ($topByUsage->count() > 1) lg:grid-cols-2 @endif">
  @if ($topByUsage->count() > 1)
    <div class="flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 p-4">
        <h3 class="text-sm font-bold text-slate-800">Pemakaian Anggaran per OPD</h3>
        <p class="mt-0.5 text-xs text-slate-500">6 OPD dengan persentase realisasi tertinggi</p>
      </div>
      <div class="flex flex-col gap-4 p-4">
        @foreach ($topByUsage as $opd)
          <div class="space-y-1.5">
            <div class="flex items-center justify-between gap-2 text-xs">
              <span class="truncate font-medium text-slate-700" title="{{ $opd['name'] }}">{{ $opd['name'] }}</span>
              <span class="shrink-0 font-mono font-bold text-slate-600">{{ number_format($opd['percentage'], 1, ',', '.') }}%</span>
            </div>
            <x-ui.budget-bar :percentage="$opd['percentage']" height="h-2" />
            <p class="text-[11px] text-slate-500">
              Rp {{ number_format($opd['realisasi'], 0, ',', '.') }} / Rp {{ number_format($opd['pagu'], 0, ',', '.') }}
            </p>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  @include('dashboard.partials._recent-sppd', ['items' => $recentSppd])
</div>

{{-- Antrean persetujuan (bila admin juga approver) --}}
@if ($pendingApprovals->isNotEmpty())
  <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 p-4">
      <h3 class="flex items-center gap-2 text-base font-bold text-slate-800">
        <i class="fa-solid fa-clipboard-check text-amber-500"></i> Menunggu Persetujuan Anda
        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">{{ $pendingApprovals->count() }}</span>
      </h3>
    </div>
    <div class="divide-y divide-slate-100">
      @foreach ($pendingApprovals as $appr)
        <a wire:navigate href="{{ route('sppd.show', $appr->sppdRequest) }}"
          class="flex items-center justify-between gap-3 p-3 text-sm transition hover:bg-slate-50">
          <span class="line-clamp-1 font-medium text-slate-700">{{ $appr->sppdRequest->purpose }}</span>
          <span class="shrink-0 text-xs text-slate-500">{{ $appr->sppdRequest->user->name }}</span>
        </a>
      @endforeach
    </div>
  </div>
@endif

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Chart.defaults.font.size = 12;
      Chart.defaults.font.family = "'Poppins', ui-sans-serif, system-ui, sans-serif";
      Chart.defaults.color = '#64748b';

      // Hormati preferensi kurangi-gerak: matikan animasi chart.
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        Chart.defaults.animation = false;
      }

      const trendCtx = document.getElementById('trendChart');
      if (trendCtx) {
        const trendData = @json($monthlyTrend);
        const c = trendCtx.getContext('2d');
        const gBlue = c.createLinearGradient(0, 0, 0, 200);
        gBlue.addColorStop(0, 'rgba(59,130,246,0.2)');
        gBlue.addColorStop(1, 'rgba(59,130,246,0)');
        const gGreen = c.createLinearGradient(0, 0, 0, 200);
        gGreen.addColorStop(0, 'rgba(16,185,129,0.2)');
        gGreen.addColorStop(1, 'rgba(16,185,129,0)');
        new Chart(trendCtx, {
          type: 'line',
          data: {
            labels: trendData.map(d => d.month),
            datasets: [
              { label: 'Masuk', data: trendData.map(d => d.masuk), borderColor: '#3b82f6', backgroundColor: gBlue, fill: true, tension: 0.3, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5 },
              { label: 'Selesai', data: trendData.map(d => d.selesai), borderColor: '#10b981', backgroundColor: gGreen, fill: true, tension: 0.3, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5 },
            ]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#1e293b', cornerRadius: 4, padding: 10 } },
            interaction: { mode: 'index', intersect: false },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false } } }
          }
        });
      }

      const donutCtx = document.getElementById('statusDonutChart');
      if (donutCtx) {
        const statusData = @json($statusDistribution);
        const filtered = statusData.filter(s => s.count > 0);
        new Chart(donutCtx, {
          type: 'doughnut',
          data: { labels: filtered.map(s => s.label), datasets: [{ data: filtered.map(s => s.count), backgroundColor: filtered.map(s => s.color), borderWidth: 0, hoverOffset: 4 }] },
          options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', cornerRadius: 4, padding: 10 } } }
        });
      }
    });
  </script>
@endpush
