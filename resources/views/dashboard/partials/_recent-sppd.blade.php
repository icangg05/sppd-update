{{-- Daftar SPPD terbaru. Param: $items (Collection<SppdRequest>), opsional $title --}}
<div class="flex flex-col rounded border border-slate-200 bg-white shadow-sm">
  <div class="flex items-center justify-between border-b border-slate-100 p-4">
    <div class="flex items-center gap-2.5">
      <div class="flex size-7 items-center justify-center rounded bg-primary-50 text-primary-500">
        <i class="fa-solid fa-clock-rotate-left text-xs"></i>
      </div>
      <div>
        <h3 class="text-sm font-bold text-slate-800">{{ $title ?? 'SPPD Terbaru' }}</h3>
        <p class="text-xs text-slate-500">{{ $items->count() }} pengajuan terakhir</p>
      </div>
    </div>
    <a wire:navigate href="{{ route('sppd.index') }}"
      class="text-xs font-medium text-primary-600 transition hover:text-primary-800 hover:underline">
      Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i>
    </a>
  </div>

  <div class="flex max-h-72 flex-col gap-2 overflow-y-auto p-3">
    @forelse ($items as $item)
      <a wire:navigate href="{{ route('sppd.show', $item) }}"
        class="flex flex-col gap-2 rounded border border-transparent p-2.5 transition hover:border-slate-100 hover:bg-slate-50 focus:outline-none focus-visible:border-primary-200 focus-visible:bg-primary-50/50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
        <div class="flex items-start justify-between gap-3">
          <p class="line-clamp-2 text-sm font-medium leading-snug text-slate-800 lg:line-clamp-1">{{ $item->purpose }}</p>
          <x-ui.badge :color="$item->status->color()" class="shrink-0 uppercase">{{ $item->status->label() }}</x-ui.badge>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
          <span class="flex items-center gap-1"><i class="fa-regular fa-user"></i> {{ $item->user->name ?? '-' }}</span>
          <span class="text-slate-300">&bull;</span>
          <span class="flex items-center gap-1"><i class="fa-solid fa-location-dot"></i>
            {{ $item->destinations->first()?->regency?->name ?? '-' }}</span>
          <span class="text-slate-300">&bull;</span>
          <span class="font-mono font-semibold text-slate-700">Rp {{ number_format($item->costDetails->sum('total'), 0, ',', '.') }}</span>
        </div>
      </a>
    @empty
      <div class="flex flex-col items-center justify-center p-8 text-slate-500">
        <i class="fa-solid fa-file-lines mb-3 text-3xl text-slate-300"></i>
        <p class="text-sm">Belum ada data SPPD</p>
      </div>
    @endforelse
  </div>
</div>
