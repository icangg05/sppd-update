{{-- Dashboard: Pimpinan / Approver --}}

{{-- KPI ringkas --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
  @php
    $kpis = [
      ['label' => 'Menunggu Persetujuan', 'value' => $pendingApprovals->count(), 'icon' => 'fa-clipboard-check', 'tone' => 'bg-amber-50 text-amber-600'],
      ['label' => 'Menunggu TTE', 'value' => $pendingSignatures->count(), 'icon' => 'fa-signature', 'tone' => 'bg-violet-50 text-violet-600'],
      ['label' => 'Selesai (lingkup)', 'value' => $stats['completed'], 'icon' => 'fa-circle-check', 'tone' => 'bg-emerald-50 text-emerald-600'],
      ['label' => 'Ditolak (lingkup)', 'value' => $stats['rejected'], 'icon' => 'fa-circle-xmark', 'tone' => 'bg-rose-50 text-rose-600'],
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
  {{-- Antrean Persetujuan (utama) --}}
  <div class="flex flex-col rounded border border-slate-200 bg-white shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 p-4">
      <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
        <i class="fa-solid fa-clipboard-check text-amber-500"></i> Menunggu Persetujuan Anda
      </h3>
      <a wire:navigate href="{{ route('sppd.index', ['filter' => 'approval']) }}"
        class="text-xs font-medium text-cyan-600 hover:underline">Buka Semua</a>
    </div>
    <div class="flex max-h-96 flex-col divide-y divide-slate-100 overflow-y-auto">
      @forelse ($pendingApprovals as $appr)
        @php $sppd = $appr->sppdRequest; @endphp
        <a wire:navigate href="{{ route('sppd.show', $sppd) }}" class="flex flex-col gap-1.5 p-3 transition hover:bg-slate-50">
          <p class="line-clamp-1 text-sm font-medium text-slate-800">{{ $sppd->purpose }}</p>
          <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span class="flex items-center gap-1"><i class="fa-regular fa-user"></i> {{ $sppd->user->name ?? '-' }}</span>
            <span class="text-slate-300">&bull;</span>
            <span class="flex items-center gap-1"><i class="fa-solid fa-location-dot"></i>
              {{ $sppd->destinations->first()?->regency?->name ?? '-' }}</span>
            <span class="text-slate-300">&bull;</span>
            <span class="font-semibold text-slate-700">Rp {{ number_format($sppd->costDetails->sum('total'), 0, ',', '.') }}</span>
          </div>
        </a>
      @empty
        <div class="flex flex-col items-center justify-center p-8 text-slate-400">
          <i class="fa-solid fa-mug-hot mb-3 text-3xl text-slate-200"></i>
          <p class="text-sm">Tidak ada pengajuan menunggu persetujuan</p>
        </div>
      @endforelse
    </div>
  </div>

  <div class="flex flex-col gap-5">
    {{-- Antrean Tanda Tangan (jika bisa sign) --}}
    @if ($pendingSignatures->isNotEmpty())
      <div class="flex flex-col rounded border border-slate-200 bg-white shadow-md">
        <div class="border-b border-slate-100 p-4">
          <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
            <i class="fa-solid fa-signature text-violet-500"></i> Menunggu Tanda Tangan
          </h3>
        </div>
        <div class="flex max-h-44 flex-col divide-y divide-slate-100 overflow-y-auto">
          @foreach ($pendingSignatures as $sig)
            <a wire:navigate href="{{ route('sppd.show', $sig->sppdRequest) }}"
              class="flex items-center justify-between gap-3 p-3 text-sm transition hover:bg-slate-50">
              <span class="line-clamp-1 font-medium text-slate-700">{{ $sig->sppdRequest->purpose ?? '-' }}</span>
              <span class="shrink-0 rounded bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-violet-600">
                {{ $sig->document_type }}
              </span>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    {{-- Keputusan terakhir --}}
    <div class="flex flex-col rounded border border-slate-200 bg-white shadow-md">
      <div class="border-b border-slate-100 p-4">
        <h3 class="text-sm font-bold text-slate-800">Keputusan Terakhir Anda</h3>
      </div>
      <div class="flex flex-col divide-y divide-slate-100">
        @forelse ($recentDecisions as $dec)
          <div class="flex items-center justify-between gap-3 p-3 text-sm">
            <span class="line-clamp-1 text-slate-700">{{ $dec->sppdRequest->purpose ?? '-' }}</span>
            <x-ui.badge :color="$dec->status->value === 'approved' ? 'emerald' : 'red'" class="shrink-0 uppercase">
              {{ $dec->status->value === 'approved' ? 'Disetujui' : 'Ditolak' }}
            </x-ui.badge>
          </div>
        @empty
          <p class="p-6 text-center text-sm text-slate-400">Belum ada keputusan</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
