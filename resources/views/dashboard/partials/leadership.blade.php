{{-- Dashboard: Pimpinan / Approver --}}

{{-- KPI ringkas --}}
<div class="dash-enter grid grid-cols-2 gap-4 lg:grid-cols-4">
  @php
    $kpis = [
      ['label' => 'Menunggu Persetujuan', 'value' => $pendingApprovals->count(), 'icon' => 'fa-clipboard-check', 'tone' => 'amber'],
      ['label' => 'Menunggu TTE', 'value' => $pendingTteCount, 'icon' => 'fa-signature', 'tone' => 'violet'],
      ['label' => 'Selesai (lingkup)', 'value' => $stats['completed'], 'icon' => 'fa-circle-check', 'tone' => 'emerald'],
      ['label' => 'Ditolak (lingkup)', 'value' => $stats['rejected'], 'icon' => 'fa-circle-xmark', 'tone' => 'rose'],
    ];
  @endphp
  @foreach ($kpis as $kpi)
    <x-dashboard.stat-card :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :tone="$kpi['tone']" />
  @endforeach
</div>

<div class="dash-enter grid grid-cols-1 gap-5 lg:grid-cols-3">
  {{-- Antrean Persetujuan (tugas utama approver — diberi bobot 2/3 lebar) --}}
  <div class="flex flex-col rounded border border-slate-200 bg-white shadow-sm lg:col-span-2">
    <x-dashboard.panel-header title="Menunggu Persetujuan Anda" icon="fa-clipboard-check" tone="amber">
      @if ($pendingApprovals->isNotEmpty())
        <x-slot:badge>
          <span class="inline-flex items-center rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">{{ $pendingApprovals->count() }}</span>
        </x-slot:badge>
      @endif
      <a wire:navigate href="{{ route('sppd.index', ['filter' => 'approval']) }}"
        class="text-xs font-medium text-primary-600 hover:underline">Buka Semua</a>
    </x-dashboard.panel-header>
    {{-- Pemisah antar-item: lihat x-dashboard.sppd-row. --}}
    <div class="flex max-h-112 flex-col divide-y divide-slate-200 overflow-y-auto">
      @forelse ($pendingApprovals as $appr)
        <x-dashboard.sppd-row :sppd="$appr->sppdRequest" />
      @empty
        <div class="flex flex-col items-center justify-center p-8 text-slate-500">
          <i class="fa-solid fa-mug-hot mb-3 text-3xl text-slate-300"></i>
          <p class="text-sm">Tidak ada pengajuan menunggu persetujuan</p>
        </div>
      @endforelse
    </div>
  </div>

  <div class="flex flex-col gap-5">
    {{-- Antrean Tanda Tangan (jika bisa sign) --}}
    @if ($pendingSignatures->isNotEmpty())
      <div class="flex flex-col rounded border border-slate-200 bg-white shadow-sm">
        <x-dashboard.panel-header title="Menunggu Tanda Tangan" icon="fa-signature" tone="violet">
          <x-slot:badge>
            <span class="inline-flex items-center rounded bg-violet-100 px-2 py-0.5 text-xs font-bold text-violet-700">{{ $pendingSignatures->count() }}</span>
          </x-slot:badge>
        </x-dashboard.panel-header>
        <div class="flex max-h-44 flex-col divide-y divide-slate-200 overflow-y-auto">
          @foreach ($pendingSignatures as $sig)
            <a wire:navigate href="{{ route('sppd.show', $sig->sppdRequest) }}"
              class="flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors even:bg-zinc-100 hover:bg-primary-50">
              <span class="line-clamp-1 font-medium text-slate-700">{{ $sig->sppdRequest->purpose ?? '-' }}</span>
              <span class="shrink-0 rounded bg-violet-50 px-1.5 py-0.5 text-xs font-bold uppercase text-violet-600">
                {{ $sig->document_type }}
              </span>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    {{-- Keputusan terakhir --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white shadow-sm">
      <x-dashboard.panel-header title="Keputusan Terakhir Anda" icon="fa-gavel" tone="slate" />
      <div class="flex flex-col divide-y divide-slate-200">
        @forelse ($recentDecisions as $dec)
          <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm even:bg-zinc-100">
            <span class="line-clamp-1 text-slate-700">{{ $dec->sppdRequest->purpose ?? '-' }}</span>
            <x-ui.badge :color="$dec->status->value === 'approved' ? 'emerald' : 'red'" class="shrink-0 uppercase">
              {{ $dec->status->value === 'approved' ? 'Disetujui' : 'Ditolak' }}
            </x-ui.badge>
          </div>
        @empty
          <p class="p-6 text-center text-sm text-slate-500">Belum ada keputusan</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
