{{-- Daftar SPPD terbaru. Param: $items (Collection<SppdRequest>), opsional $title --}}
<div class="flex flex-col overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
  <x-dashboard.panel-header :title="$title ?? 'SPPD Terbaru'" :subtitle="$items->count() . ' pengajuan terakhir'"
    icon="fa-clock-rotate-left" tone="primary">
    <a wire:navigate href="{{ route('sppd.index') }}"
      class="text-xs font-medium text-primary-600 transition hover:text-primary-800 hover:underline">
      Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i>
    </a>
  </x-dashboard.panel-header>

  {{-- Pemisah antar-item: lihat x-dashboard.sppd-row. --}}
  <div class="flex flex-col divide-y divide-slate-200">
    @forelse ($items as $item)
      <x-dashboard.sppd-row :sppd="$item">
        <x-slot:aside>
          <x-ui.badge :color="$item->status->color()" class="shrink-0 uppercase">{{ $item->status->label() }}</x-ui.badge>
        </x-slot:aside>
      </x-dashboard.sppd-row>
    @empty
      <div class="flex flex-col items-center justify-center p-8 text-slate-500">
        <i class="fa-solid fa-file-lines mb-3 text-3xl text-slate-300"></i>
        <p class="text-sm">Belum ada data SPPD</p>
      </div>
    @endforelse
  </div>
</div>
