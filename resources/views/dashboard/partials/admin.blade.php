{{-- Dashboard: Admin / Operasional (super_admin, admin_opd) --}}

{{-- KPI Cards --}}
<div class="dash-enter grid grid-cols-2 gap-4 lg:grid-cols-4">
  @php
    $kpis = [
      ['label' => 'Total SPPD', 'value' => $stats['total'], 'icon' => 'fa-file-lines', 'tone' => 'blue'],
      ['label' => 'Dalam Proses', 'value' => $stats['in_progress'], 'icon' => 'fa-hourglass-half', 'tone' => 'amber'],
      ['label' => 'Selesai', 'value' => $stats['approved'] + $stats['completed'], 'icon' => 'fa-circle-check', 'tone' => 'emerald'],
      ['label' => 'Ditolak', 'value' => $stats['rejected'], 'icon' => 'fa-circle-xmark', 'tone' => 'rose'],
    ];
  @endphp
  @foreach ($kpis as $kpi)
    <x-dashboard.stat-card :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :tone="$kpi['tone']" />
  @endforeach
</div>

{{-- Charts: Trend + Distribusi Status --}}
<div class="dash-enter grid grid-cols-1 gap-5 lg:grid-cols-3">
  <div class="flex flex-col rounded border border-l-2 border-slate-200 border-l-blue-400 bg-white shadow-sm lg:col-span-2">
    <div class="flex items-center justify-between border-b border-slate-100 p-4">
      <div>
        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
          <i class="fa-solid fa-chart-line text-emerald-500"></i> Tren Perjalanan Disetujui
        </h3>
        <p class="mt-0.5 text-xs text-slate-500">Per minggu, 12 minggu terakhir</p>
      </div>
      <div class="flex gap-4 text-xs font-medium text-slate-600">
        <div class="flex items-center gap-1.5"><span class="size-2.5 rounded bg-emerald-500"></span> Disetujui</div>
      </div>
    </div>
    <div class="relative h-60 w-full p-4"><canvas id="trendChart"></canvas></div>
  </div>

  <div class="flex flex-col rounded border border-l-2 border-slate-200 border-l-emerald-400 bg-white shadow-sm">
    <div class="border-b border-slate-100 p-4">
      <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-chart-pie text-emerald-500"></i> Distribusi Status
      </h3>
      <p class="mt-0.5 text-xs text-slate-500">Sebaran status SPPD (lingkup Anda)</p>
    </div>
    <div class="relative h-40 w-full p-4"><canvas id="statusDonutChart"></canvas></div>
    <div class="mt-2 grid grid-cols-1 gap-2 px-5 pb-4">
      @foreach ($statusDistribution as $item)
        @if ($item['count'] > 0)
          <div class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-2.5">
              <span class="size-3 rounded" style="background: {{ $item['color'] }};"></span>
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
<div class="dash-enter grid grid-cols-1 gap-5 @if ($topByUsage->count() > 1) lg:grid-cols-2 @endif">
  @if ($topByUsage->count() > 1)
    <div class="flex flex-col rounded border border-l-2 border-slate-200 border-l-primary-400 bg-white shadow-sm">
      <div class="border-b border-slate-100 p-4">
        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
          <i class="fa-solid fa-building-columns text-primary-500"></i> Pemakaian Anggaran per OPD
        </h3>
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
            <p class="font-mono text-xs text-slate-500">
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
  <div class="dash-enter rounded border border-l-2 border-slate-200 border-l-amber-400 bg-white shadow-sm">
    <div class="border-b border-slate-100 p-4">
      <h3 class="flex items-center gap-2 text-base font-bold text-slate-800">
        <i class="fa-solid fa-clipboard-check text-amber-500"></i> Menunggu Persetujuan Anda
        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">{{ $pendingApprovals->count() }}</span>
      </h3>
    </div>
    <div class="divide-y divide-slate-100">
      @foreach ($pendingApprovals as $appr)
        @php $urgency = $appr->sppdRequest->urgency ?? 'Biasa'; @endphp
        <a wire:navigate href="{{ route('sppd.show', $appr->sppdRequest) }}"
          class="flex items-center justify-between gap-3 p-3 text-sm transition hover:bg-slate-50">
          <span class="flex min-w-0 items-center gap-2">
            <x-ui.badge :color="$urgency === 'Segera' ? 'rose' : 'slate'" class="shrink-0">{{ $urgency }}</x-ui.badge>
            <span class="line-clamp-1 font-medium text-slate-700">{{ $appr->sppdRequest->purpose }}</span>
          </span>
          <span class="shrink-0 text-xs text-slate-500">{{ $appr->sppdRequest->user->name }}</span>
        </a>
      @endforeach
    </div>
  </div>
@endif

@push('scripts')
  <script>
    // Chart.js dimuat via loader dengan callback supaya tidak bergantung pada
    // urutan eksekusi script saat wire:navigate (Chart bisa belum ada saat
    // script inline jalan). Kalau sudah dimuat (navigasi berikutnya), langsung.
    (function() {
      function drawCharts() {
      Chart.defaults.font.size = 12;
      Chart.defaults.font.family = "'Geist', ui-sans-serif, system-ui, sans-serif";
      Chart.defaults.color = '#64748b';

      // Hormati preferensi kurangi-gerak: matikan animasi chart.
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        Chart.defaults.animation = false;
      }

      const trendCtx = document.getElementById('trendChart');
      if (trendCtx) {
        const trendData = @json($weeklyApproved);
        const c = trendCtx.getContext('2d');
        const gGreen = c.createLinearGradient(0, 0, 0, 200);
        gGreen.addColorStop(0, 'rgba(16,185,129,0.2)');
        gGreen.addColorStop(1, 'rgba(16,185,129,0)');
        new Chart(trendCtx, {
          type: 'line',
          data: {
            labels: trendData.map(d => d.week),
            datasets: [
              { label: 'Disetujui', data: trendData.map(d => d.count), borderColor: '#10b981', backgroundColor: gGreen, fill: true, tension: 0.3, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5 },
            ]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#1e293b', cornerRadius: 4, padding: 10 } },
            interaction: { mode: 'index', intersect: false },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' }, border: { display: false } } }
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
      }

      if (window.Chart) {
        drawCharts();
      } else {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        s.onload = drawCharts;
        document.head.appendChild(s);
      }
    })();
  </script>
@endpush
