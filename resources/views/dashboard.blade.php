@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
{{-- Welcome Hero Banner --}}
<div class="dashboard-hero">
  <div class="dashboard-hero-content">
    <p class="dashboard-hero-label">SELAMAT DATANG KEMBALI</p>
    <h1 class="dashboard-hero-title">Dashboard SPPD {{ auth()->user()->department?->name ?? 'Sistem' }}</h1>
    <p class="dashboard-hero-desc">
      Pantau seluruh proses telaah, realisasi, dan laporan perjalanan dinas secara real-time.
      Total anggaran tahun berjalan: <strong>Rp {{ number_format($totalBudget, 0, ',', '.') }}</strong>.
    </p>
  </div>
  <div class="dashboard-hero-actions">
    @can('sppd.create')
    <a href="{{ route('sppd.create') }}" class="btn-hero-primary">
      <i class="fa-solid fa-plus"></i>
      Pengajuan Baru
    </a>
    @endcan
    <a href="{{ route('sppd.index') }}" class="btn-hero-secondary">
      Lihat Laporan
      <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>
</div>

{{-- Stat Cards --}}
<div class="dashboard-stats">
  {{-- Total SPPD --}}
  <div class="stat-card-new">
    <div class="stat-card-icon stat-icon-blue">
      <i class="fa-solid fa-file-lines fa-lg"></i>
    </div>
    <div class="stat-card-body">
      <div class="stat-card-trend stat-trend-up">
        <i class="fa-solid fa-arrow-trend-up"></i>
        +12.5%
      </div>
      <p class="stat-card-label">TOTAL SPPD</p>
      <p class="stat-card-value">{{ $stats['total'] }}</p>
    </div>
  </div>

  {{-- Telaah Masuk --}}
  <div class="stat-card-new">
    <div class="stat-card-icon stat-icon-green">
      <i class="fa-solid fa-envelope-open-text fa-lg"></i>
    </div>
    <div class="stat-card-body">
      <div class="stat-card-trend stat-trend-up">
        <i class="fa-solid fa-arrow-trend-up"></i>
        +4 baru
      </div>
      <p class="stat-card-label">TELAAH MASUK</p>
      <p class="stat-card-value">{{ $stats['draft'] }}</p>
    </div>
  </div>

  {{-- Di Proses --}}
  <div class="stat-card-new">
    <div class="stat-card-icon stat-icon-orange">
      <i class="fa-solid fa-hourglass-half fa-lg"></i>
    </div>
    <div class="stat-card-body">
      <div class="stat-card-trend stat-trend-neutral">
        <i class="fa-solid fa-clock"></i>
        menunggu review
      </div>
      <p class="stat-card-label">DI PROSES</p>
      <p class="stat-card-value">{{ $stats['in_progress'] }}</p>
    </div>
  </div>

  {{-- Selesai --}}
  <div class="stat-card-new">
    <div class="stat-card-icon stat-icon-teal">
      <i class="fa-solid fa-circle-check fa-lg"></i>
    </div>
    <div class="stat-card-body">
      <div class="stat-card-trend stat-trend-up">
        <i class="fa-solid fa-arrow-trend-up"></i>
        +8 minggu ini
      </div>
      <p class="stat-card-label">SELESAI</p>
      <p class="stat-card-value">{{ $stats['completed'] }}</p>
    </div>
  </div>
</div>

{{-- Charts Row: Trend + Status Distribution --}}
<div class="dashboard-charts">
  {{-- Trend Chart --}}
  <div class="dashboard-chart-main">
    <div class="chart-header">
      <div>
        <h3 class="chart-title">Tren Pengajuan SPPD</h3>
        <p class="chart-subtitle">Perbandingan telaah masuk vs selesai 12 bulan terakhir</p>
      </div>
      <div class="chart-legend">
        <div class="chart-legend-item">
          <span class="chart-legend-dot" style="background: #3b82f6;"></span>
          Masuk
        </div>
        <div class="chart-legend-item">
          <span class="chart-legend-dot" style="background: #10b981;"></span>
          Selesai
        </div>
      </div>
    </div>
    <div class="chart-body">
      <canvas id="trendChart"></canvas>
    </div>
  </div>

  {{-- Donut Chart --}}
  <div class="dashboard-chart-side">
    <div class="chart-header">
      <div>
        <h3 class="chart-title">Distribusi Status</h3>
        <p class="chart-subtitle">Sebaran status seluruh SPPD</p>
      </div>
    </div>
    <div class="chart-body-donut">
      <canvas id="statusDonutChart"></canvas>
    </div>
    <div class="donut-legend">
      @foreach($statusDistribution as $item)
        @if($item['count'] > 0)
        <div class="donut-legend-item">
          <div class="donut-legend-left">
            <span class="donut-legend-dot" style="background: {{ $item['color'] }};"></span>
            <span>{{ $item['label'] }}</span>
          </div>
          <span class="donut-legend-value">{{ $item['count'] }}</span>
        </div>
        @endif
      @endforeach
    </div>
  </div>
</div>

{{-- Bottom Row: Top OPD + Telaah Terbaru --}}
<div class="dashboard-bottom">
  {{-- Top OPD Chart --}}
  <div class="dashboard-bottom-left">
    <div class="chart-header">
      <div>
        <h3 class="chart-title">Top 6 OPD Pengaju SPPD</h3>
        <p class="chart-subtitle">Berdasarkan jumlah pengajuan</p>
      </div>
    </div>
    <div class="chart-body">
      <canvas id="topOpdChart"></canvas>
    </div>
  </div>

  {{-- Telaah Terbaru --}}
  <div class="dashboard-bottom-right">
    <div class="chart-header">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-star text-blue-500"></i>
        <div>
          <h3 class="chart-title">Telaah Terbaru</h3>
          <p class="chart-subtitle">{{ $recentSppd->count() }} pengajuan SPPD terakhir</p>
        </div>
      </div>
      <a href="{{ route('sppd.index') }}" class="telaah-link">
        Lihat Semua
        <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
    <div class="telaah-list">
      @forelse($recentSppd as $item)
        <a href="{{ route('sppd.show', $item) }}" class="telaah-item">
          <div class="telaah-item-content">
            <p class="telaah-item-title">{{ Str::limit($item->purpose, 55) }}</p>
            <div class="telaah-item-meta">
              <span>{{ $item->user->name }}</span>
              <span class="telaah-dot">·</span>
              <span>{{ $item->destinations->first()?->regency?->name ?? '-' }}</span>
              <span class="telaah-dot">·</span>
              <span>Rp {{ number_format($item->costDetails->sum('total'), 0, ',', '.') }}</span>
            </div>
          </div>
          <div>
            @php
              $statusClass = match($item->status->value) {
                'draft' => 'telaah-badge-yellow',
                'in_progress' => 'telaah-badge-blue',
                'approved' => 'telaah-badge-green',
                'completed' => 'telaah-badge-green',
                'rejected' => 'telaah-badge-red',
                default => 'telaah-badge-gray',
              };
              $statusLabels = [
                'draft' => 'Telaah Masuk',
                'in_progress' => 'Sedang Di Proses',
                'approved' => 'Perjalanan Selesai & Masukkan Laporan',
                'completed' => 'Perjalanan Selesai & Masukkan Laporan',
                'rejected' => 'Ditolak',
              ];
            @endphp
            <span class="telaah-badge {{ $statusClass }}">{{ $statusLabels[$item->status->value] ?? $item->status->label() }}</span>
          </div>
        </a>
      @empty
        <div class="telaah-empty">
          <i class="fa-solid fa-file-lines fa-2x text-slate-300 mx-auto mb-2 block"></i>
          Belum ada data SPPD
        </div>
      @endforelse
    </div>
  </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // ── Trend Chart (Area/Line) ──
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
      const trendData = @json($monthlyTrend);
      const trendGradient = trendCtx.getContext('2d');
      const gradient = trendGradient.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
      gradient.addColorStop(1, 'rgba(59, 130, 246, 0.01)');

      const gradientGreen = trendGradient.createLinearGradient(0, 0, 0, 300);
      gradientGreen.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
      gradientGreen.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

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
              tension: 0.4,
              borderWidth: 2.5,
              pointRadius: 0,
              pointHoverRadius: 5,
              pointHoverBackgroundColor: '#3b82f6',
            },
            {
              label: 'Selesai',
              data: trendData.map(d => d.selesai),
              borderColor: '#10b981',
              backgroundColor: gradientGreen,
              fill: true,
              tension: 0.4,
              borderWidth: 2.5,
              pointRadius: 0,
              pointHoverRadius: 5,
              pointHoverBackgroundColor: '#10b981',
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
              titleColor: '#f8fafc',
              bodyColor: '#e2e8f0',
              borderColor: '#334155',
              borderWidth: 1,
              cornerRadius: 8,
              padding: 12,
            }
          },
          interaction: { mode: 'index', intersect: false },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: '#94a3b8', font: { size: 12 } }
            },
            y: {
              beginAtZero: true,
              grid: { color: '#f1f5f9' },
              ticks: { color: '#94a3b8', font: { size: 12 }, stepSize: 15 }
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
            hoverOffset: 8,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '65%',
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1e293b',
              titleColor: '#f8fafc',
              bodyColor: '#e2e8f0',
              cornerRadius: 8,
              padding: 12,
            }
          }
        }
      });
    }

    // ── Top OPD Horizontal Bar Chart ──
    const opdCtx = document.getElementById('topOpdChart');
    if (opdCtx) {
      const opdData = @json($topDepartments);

      new Chart(opdCtx, {
        type: 'bar',
        data: {
          labels: opdData.map(d => d.name.length > 20 ? d.name.substring(0, 20) + '...' : d.name),
          datasets: [{
            label: 'Pengajuan',
            data: opdData.map(d => d.total),
            backgroundColor: (ctx) => {
              const colors = ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];
              return colors[ctx.dataIndex % colors.length];
            },
            borderRadius: 6,
            barPercentage: 0.7,
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
              titleColor: '#f8fafc',
              bodyColor: '#e2e8f0',
              cornerRadius: 8,
              padding: 12,
            }
          },
          scales: {
            x: {
              beginAtZero: true,
              grid: { color: '#f1f5f9' },
              ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 15 }
            },
            y: {
              grid: { display: false },
              ticks: { color: '#334155', font: { size: 12, weight: '500' } }
            }
          }
        }
      });
    }
  });
</script>
@endpush
@endsection
