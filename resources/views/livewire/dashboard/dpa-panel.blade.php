<div class="dash-enter flex flex-col gap-4">

  {{-- Ringkasan keseluruhan --}}
  <x-dashboard.dpa-summary :pagu="$summary['pagu']" :realisasi="$summary['realisasi']" :sisa="$summary['sisa']"
    :percentage="$summary['percentage']" :year="$summary['year']" :title="$title"
    subtitle="Pagu, realisasi, dan sisa anggaran tahun anggaran berjalan" />

  {{-- Per program / kegiatan --}}
  <div class="rounded border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 p-4">
      <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-list-ul text-primary-500"></i> Rincian per Program / Kegiatan
      </h3>
      <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ count($items) }} item</span>
    </div>

    {{-- Tinggi dibatasi (~5 baris terlihat), sisanya bisa di-scroll. --}}
    <div class="max-h-104 divide-y divide-slate-200 overflow-y-auto">
      @forelse ($items as $item)
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex min-w-0 flex-1 items-start gap-3">
            <span class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-100 font-mono text-[11px] font-bold text-slate-500">{{ $loop->iteration }}</span>
            <div class="min-w-0 flex-1">
              <p class="line-clamp-1 text-sm font-semibold text-slate-800" title="{{ $item['name'] }}">{{ $item['name'] }}</p>
              @if ($showDepartment && ! empty($item['department']))
                <p class="mt-0.5 flex items-center gap-1 text-[11px] font-medium text-primary-700">
                  <i class="fa-solid fa-building text-[10px] text-primary-500"></i>
                  <span class="line-clamp-1">{{ $item['department'] }}</span>
                </p>
              @endif
              <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                <span>Pagu <span class="font-mono font-semibold text-slate-700">Rp {{ number_format($item['pagu'], 0, ',', '.') }}</span></span>
                <span class="text-slate-300">&bull;</span>
                <span>Realisasi <span class="font-mono font-semibold text-primary-700">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</span></span>
                <span class="text-slate-300">&bull;</span>
                <span>Sisa <span class="font-mono font-semibold {{ $item['sisa'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">Rp {{ number_format($item['sisa'], 0, ',', '.') }}</span></span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3 sm:w-64">
            <div class="flex-1">
              <div class="mb-1 flex items-center justify-between text-[11px]">
                <span class="text-slate-500">Penggunaan</span>
                <span class="font-mono font-bold text-slate-600">{{ number_format($item['percentage'], 1, ',', '.') }}%</span>
              </div>
              <x-ui.budget-bar :percentage="$item['percentage']" height="h-2" />
            </div>
            <button type="button" wire:click="openDetail({{ $item['id'] }})" wire:loading.attr="disabled"
              wire:target="openDetail({{ $item['id'] }})"
              class="inline-flex shrink-0 items-center gap-1.5 rounded border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-primary-50 hover:text-primary-700 disabled:opacity-50">
              <i class="fa-solid fa-magnifying-glass-chart text-[11px]"></i>
              <span wire:loading.remove wire:target="openDetail({{ $item['id'] }})">Detail</span>
              <span wire:loading wire:target="openDetail({{ $item['id'] }})">Memuat…</span>
            </button>
          </div>
        </div>
      @empty
        <div class="flex flex-col items-center justify-center p-8 text-slate-500">
          <i class="fa-solid fa-folder-open mb-3 text-3xl text-slate-300"></i>
          <p class="text-sm">Belum ada DPA untuk tahun berjalan</p>
        </div>
      @endforelse
    </div>

    {{-- Hanya role yang berhak akses halaman anggaran yang melihat tautan ini. --}}
    @if (count($items) && auth()->user()->hasAnyRole(['super_admin', 'admin_opd']))
      <div class="border-t border-slate-100 p-3">
        <a href="{{ route('master.budgets.index') }}" wire:navigate
          class="flex items-center justify-center gap-2 rounded px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-50">
          <span>Lihat lainnya</span>
          <i class="fa-solid fa-arrow-right text-[11px]"></i>
        </a>
      </div>
    @endif
  </div>

  {{-- Modal rincian (konten di-query lazy saat tombol diklik) --}}
  <x-ui.modal show="$wire.showDetail" maxWidth="max-w-2xl" icon="fa-solid fa-magnifying-glass-chart"
    title="Rincian Realisasi" :description="$detail['name'] ?? null" :closeable="false">

    @if ($detail)
      {{-- Ringkasan item --}}
      <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded border border-slate-100 bg-slate-50 p-2.5">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Pagu</p>
          <p class="mt-0.5 font-mono text-sm font-bold text-slate-800">Rp {{ number_format($detail['pagu'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded border border-primary-100 bg-primary-50/60 p-2.5">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-primary-700">Realisasi</p>
          <p class="mt-0.5 font-mono text-sm font-bold text-primary-700">Rp {{ number_format($detail['realisasi'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded border p-2.5 {{ $detail['sisa'] < 0 ? 'border-rose-100 bg-rose-50' : 'border-emerald-100 bg-emerald-50/60' }}">
          <p class="text-[10px] font-semibold uppercase tracking-wide {{ $detail['sisa'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">Sisa</p>
          <p class="mt-0.5 font-mono text-sm font-bold {{ $detail['sisa'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">Rp {{ number_format($detail['sisa'], 0, ',', '.') }}</p>
        </div>
      </div>

      <div class="mb-3 space-y-1.5">
        <div class="flex items-center justify-between text-xs">
          <span class="font-medium text-slate-500">Penggunaan anggaran</span>
          <span class="font-mono font-bold text-slate-700">{{ number_format($detail['percentage'], 1, ',', '.') }}%</span>
        </div>
        <x-ui.budget-bar :percentage="$detail['percentage']" height="h-2.5" />
      </div>

      @if ($detail['department'] || $detail['account'])
        <p class="mb-3 text-[11px] text-slate-500">
          @if ($detail['department']) <i class="fa-solid fa-building"></i> {{ $detail['department'] }} @endif
          @if ($detail['account']) <span class="ml-2 rounded bg-slate-100 px-1.5 py-0.5 font-mono">{{ $detail['account'] }}</span> @endif
        </p>
      @endif

      {{-- Daftar SPPD pembentuk realisasi --}}
      <div class="mb-2 flex items-baseline justify-between gap-2">
        <p class="text-xs font-bold text-slate-600">SPPD pembentuk realisasi ({{ $detail['total'] }})</p>
        @if ($detail['total'] > $detail['shown'])
          <p class="text-[11px] text-slate-500">Menampilkan {{ $detail['shown'] }} kontributor terbesar</p>
        @endif
      </div>
      <div class="max-h-72 divide-y divide-slate-100 overflow-y-auto rounded border border-slate-100">
        @forelse ($detail['sppds'] as $row)
          <a href="{{ $row['url'] }}" wire:navigate class="flex items-center justify-between gap-3 p-2.5 text-sm transition hover:bg-slate-50">
            <div class="min-w-0">
              <p class="line-clamp-1 font-medium text-slate-700">{{ $row['purpose'] }}</p>
              <p class="text-[11px] text-slate-500">
                <i class="fa-regular fa-user"></i> {{ $row['user'] }}
                <span class="mx-1 text-slate-300">&bull;</span>
                <i class="fa-solid fa-location-dot"></i> {{ $row['tujuan'] }}
              </p>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-1">
              <span class="font-mono text-xs font-bold text-slate-700">Rp {{ number_format($row['cost'], 0, ',', '.') }}</span>
              <x-ui.badge :color="$row['color']" class="uppercase">{{ $row['status'] }}</x-ui.badge>
            </div>
          </a>
        @empty
          <p class="p-6 text-center text-sm text-slate-500">Belum ada SPPD disetujui/selesai pada item ini.</p>
        @endforelse
      </div>
    @else
      <div class="flex items-center justify-center p-8 text-slate-500">
        <i class="fa-solid fa-spinner fa-spin mr-2"></i> Memuat rincian…
      </div>
    @endif

    <x-slot:footer class="flex justify-end">
      <x-ui.button variant="secondary" size="sm" x-on:click="$wire.showDetail = false">Tutup</x-ui.button>
    </x-slot:footer>
  </x-ui.modal>

</div>
