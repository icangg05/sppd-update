@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="flex flex-col gap-5 p-1 max-w-7xl mx-auto">

  {{-- Welcome Hero Banner --}}
  <div class="flex flex-col justify-between gap-4 rounded border border-cyan-200 bg-linear-to-r from-cyan-50 to-white p-5 shadow-md md:flex-row md:items-center">
    <div class="flex flex-col gap-1.5">
      <p class="text-xs font-bold tracking-wider text-cyan-700 uppercase">Selamat Datang Kembali</p>
      <h1 class="text-lg font-bold text-slate-800">Dashboard SPPD {{ auth()->user()->department?->name ?? 'Sistem' }}</h1>
      <p class="text-sm text-slate-600">
        Pantau seluruh proses telaah, realisasi, dan laporan perjalanan dinas secara real-time.
        Total anggaran: <strong class="text-slate-800 font-semibold">Rp {{ number_format($totalBudget, 0, ',', '.') }}</strong>.
      </p>
    </div>
    <div class="flex shrink-0 items-center gap-3">
      @can('sppd.create')
      <a href="{{ route('sppd.create') }}" class="inline-flex items-center gap-2 rounded bg-cyan-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-700 focus:ring-1 focus:ring-cyan-500">
        <i class="fa-solid fa-plus"></i>
        Pengajuan Baru
      </a>
      @endcan
      <a href="{{ route('sppd.index') }}" class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:ring-1 focus:ring-slate-300">
        Lihat Laporan
        <i class="fa-solid fa-arrow-right text-xs"></i>
      </a>
    </div>
  </div>

  {{-- Stat Cards --}}
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Total SPPD --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white p-4 shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-10 items-center justify-center rounded bg-blue-50 text-blue-600">
            <i class="fa-solid fa-file-lines text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total SPPD</p>
            <p class="text-lg font-bold text-slate-800">{{ $stats['total'] }}</p>
          </div>
        </div>
        <div class="flex items-center gap-1 rounded bg-green-50 px-2 py-1 text-xs font-medium text-green-700">
          <i class="fa-solid fa-arrow-trend-up"></i> +12%
        </div>
      </div>
    </div>

    {{-- Telaah Masuk --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white p-4 shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-10 items-center justify-center rounded bg-emerald-50 text-emerald-600">
            <i class="fa-solid fa-envelope-open-text text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Telaah Masuk</p>
            <p class="text-lg font-bold text-slate-800">{{ $stats['in_progress'] }}</p>
          </div>
        </div>
        <div class="flex items-center gap-1 rounded bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
          <i class="fa-solid fa-arrow-trend-up"></i> +4
        </div>
      </div>
    </div>

    {{-- Di Proses --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white p-4 shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-10 items-center justify-center rounded bg-amber-50 text-amber-600">
            <i class="fa-solid fa-hourglass-half text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Di Proses</p>
            <p class="text-lg font-bold text-slate-800">{{ $stats['in_progress'] }}</p>
          </div>
        </div>
        <div class="flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
          <i class="fa-regular fa-clock"></i> wait
        </div>
      </div>
    </div>

    {{-- Selesai --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white p-4 shadow-md">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-10 items-center justify-center rounded bg-teal-50 text-teal-600">
            <i class="fa-solid fa-circle-check text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Selesai</p>
            <p class="text-lg font-bold text-slate-800">{{ $stats['completed'] }}</p>
          </div>
        </div>
        <div class="flex items-center gap-1 rounded bg-green-50 px-2 py-1 text-xs font-medium text-green-700">
          <i class="fa-solid fa-arrow-trend-up"></i> +8
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row: Trend + Status Distribution --}}
  <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    {{-- Trend Chart --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white shadow-md lg:col-span-2">
      <div class="flex items-center justify-between border-b border-slate-100 p-4">
        <div>
          <h3 class="text-sm font-bold text-slate-800">Tren Pengajuan SPPD</h3>
          <p class="text-xs text-slate-500 mt-0.5">Telaah masuk vs selesai 12 bulan terakhir</p>
        </div>
        <div class="flex gap-4 text-xs font-medium text-slate-600">
          <div class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-blue-500"></span> Masuk</div>
          <div class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-emerald-500"></span> Selesai</div>
        </div>
      </div>
      <div class="relative h-60 w-full p-4">
        <canvas id="trendChart"></canvas>
      </div>
    </div>

    {{-- Donut Chart --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white shadow-md">
      <div class="border-b border-slate-100 p-4">
        <h3 class="text-sm font-bold text-slate-800">Distribusi Status</h3>
        <p class="text-xs text-slate-500 mt-0.5">Sebaran status seluruh SPPD</p>
      </div>
      <div class="relative h-40 w-full p-4">
        <canvas id="statusDonutChart"></canvas>
      </div>
      <div class="grid grid-cols-1 gap-2 px-5 pb-4 mt-2">
        @foreach($statusDistribution as $item)
          @if($item['count'] > 0)
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

  {{-- Bottom Row: Top OPD + Telaah Terbaru --}}
  <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    {{-- Top OPD Chart --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white shadow-md">
      <div class="border-b border-slate-100 p-4">
        <h3 class="text-sm font-bold text-slate-800">Top 6 OPD Pengaju SPPD</h3>
        <p class="text-xs text-slate-500 mt-0.5">Berdasarkan jumlah pengajuan</p>
      </div>
      <div class="relative h-72 w-full p-4">
        <canvas id="topOpdChart"></canvas>
      </div>
    </div>

    {{-- Telaah Terbaru --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white shadow-md">
      <div class="flex items-center justify-between border-b border-slate-100 p-4">
        <div class="flex items-center gap-2.5">
          <div class="flex size-7 items-center justify-center rounded bg-blue-50 text-blue-500">
            <i class="fa-solid fa-star text-xs"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-800">Telaah Terbaru</h3>
            <p class="text-xs text-slate-500">{{ $recentSppd->count() }} pengajuan SPPD terakhir</p>
          </div>
        </div>
        <a href="{{ route('sppd.index') }}" class="text-xs font-medium text-cyan-600 transition hover:text-cyan-800 hover:underline">
          Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
      </div>

      <div class="flex max-h-72 flex-col gap-2 overflow-y-auto p-3">
        @forelse($recentSppd as $item)
          <a href="{{ route('sppd.show', $item) }}" class="flex flex-col gap-2 rounded border border-transparent p-2.5 transition hover:bg-slate-50 hover:border-slate-100">
            <div class="flex items-start justify-between gap-3">
              <p class="line-clamp-2 lg:line-clamp-1 text-sm font-medium leading-snug text-slate-800">{{ $item->purpose }}</p>
              @php
                $statusClass = match($item->status->value) {
                  'draft' => 'bg-amber-50 text-amber-700 border-amber-200',
                  'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                  'approved', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                  'rejected' => 'bg-red-50 text-red-700 border-red-200',
                  default => 'bg-slate-50 text-slate-700 border-slate-200',
                };
                $statusLabels = [
                  'draft' => 'Masuk',
                  'in_progress' => 'Proses',
                  'approved' => 'Selesai',
                  'completed' => 'Selesai',
                  'rejected' => 'Ditolak',
                ];
              @endphp
              <span class="shrink-0 rounded-[3px] border px-1.5 py-0.5 text-[10px] font-bold tracking-wider uppercase {{ $statusClass }}">
                {{ $statusLabels[$item->status->value] ?? $item->status->label() }}
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
              <span class="flex items-center gap-1"><i class="fa-regular fa-user"></i> {{ $item->user->name }}</span>
              <span class="text-slate-300">&bull;</span>
              <span class="flex items-center gap-1"><i class="fa-solid fa-location-dot"></i> {{ $item->destinations->first()?->regency?->name ?? '-' }}</span>
              <span class="text-slate-300">&bull;</span>
              <span class="font-semibold text-slate-700">Rp {{ number_format($item->costDetails->sum('total'), 0, ',', '.') }}</span>
            </div>
          </a>
        @empty
          <div class="flex flex-col items-center justify-center p-8 text-slate-400">
            <i class="fa-solid fa-file-lines text-3xl mb-3 text-slate-200"></i>
            <p class="text-sm">Belum ada data SPPD</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {

    // Set default font to normal/medium sizes
    Chart.defaults.font.size = 12;
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#64748b';

    // ── Trend Chart ──
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
      const trendData = @json($monthlyTrend);
      const trendGradient = trendCtx.getContext('2d');

      const gradient = trendGradient.createLinearGradient(0, 0, 0, 200);
      gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
      gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

      const gradientGreen = trendGradient.createLinearGradient(0, 0, 0, 200);
      gradientGreen.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
      gradientGreen.addColorStop(1, 'rgba(16, 185, 129, 0)');

      new Chart(trendCtx, {
        type: 'line',
        data: {
          labels: trendData.map(d => d.month),
          datasets: [
            {
              label: 'Masuk',
              data: trendData.map(d => d.masuk),
              borderColor: '#3b82f6',
              backgroundColor: gradient,
              fill: true,
              tension: 0.3,
              borderWidth: 2.5,
              pointRadius: 0,
              pointHoverRadius: 5,
            },
            {
              label: 'Selesai',
              data: trendData.map(d => d.selesai),
              borderColor: '#10b981',
              backgroundColor: gradientGreen,
              fill: true,
              tension: 0.3,
              borderWidth: 2.5,
              pointRadius: 0,
              pointHoverRadius: 5,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: '#1e293b',
              titleFont: { size: 13 },
              bodyFont: { size: 12 },
              cornerRadius: 4,
              padding: 10,
            }
          },
          interaction: { mode: 'index', intersect: false },
          scales: {
            x: { grid: { display: false } },
            y: {
              beginAtZero: true,
              grid: { color: '#f1f5f9' },
              border: { display: false }
            }
          }
        }
      });
    }

    // ── Donut Chart ──
    const donutCtx = document.getElementById('statusDonutChart');
    if (donutCtx) {
      const statusData = @json($statusDistribution);
      const filtered = statusData.filter(s => s.count > 0);

      new Chart(donutCtx, {
        type: 'doughnut',
        data: {
          labels: filtered.map(s => s.label),
          datasets: [{
            data: filtered.map(s => s.count),
            backgroundColor: filtered.map(s => s.color),
            borderWidth: 0,
            hoverOffset: 4,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1e293b',
              bodyFont: { size: 12 },
              cornerRadius: 4,
              padding: 10,
            }
          }
        }
      });
    }

    // ── Top OPD Chart ──
    const opdCtx = document.getElementById('topOpdChart');
    if (opdCtx) {
      const opdData = @json($topDepartments);

      new Chart(opdCtx, {
        type: 'bar',
        data: {
          labels: opdData.map(d => d.name.length > 20 ? d.name.substring(0, 20) + '...' : d.name),
          datasets: [{
            data: opdData.map(d => d.total),
            backgroundColor: ['#0ea5e9', '#14b8a6', '#8b5cf6', '#f59e0b', '#f43f5e', '#64748b'],
            borderRadius: 2,
            barPercentage: 0.6,
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1e293b',
              titleFont: { size: 13 },
              bodyFont: { size: 12 },
              cornerRadius: 4,
              padding: 10
            }
          },
          scales: {
            x: {
              beginAtZero: true,
              grid: { color: '#f1f5f9' },
              border: { display: false }
            },
            y: {
              grid: { display: false },
              border: { display: false },
              ticks: { font: { size: 11, weight: '500' }, color: '#475569' }
            }
          }
        }
      });
    }
  });
</script>
@endpush
@endsection
