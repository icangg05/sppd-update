<div class="flex flex-col gap-4 p-1">

  {{-- Header (title card) — mengikuti gaya kartu judul halaman index. --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-clock-rotate-left pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-gavel text-[9px]"></i>
          Jejak Keputusan
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ $approvals->total() }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Riwayat Persetujuan</h1>
        <p class="mt-1 text-xs text-slate-500">Jejak keputusan &amp; tanda tangan yang telah Anda lakukan</p>
      </div>
    </div>
  </div>

  {{-- Bar Filter --}}
  {{-- TANPA .dash-enter: animasi (fill: both) menjebak dropdown fixed searchable-select. --}}
  <div class="rounded border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
      <x-form.input name="search" wire:model.live.debounce.300ms="search" icon="fa-solid fa-magnifying-glass"
        loadingTarget="search" placeholder="Cari maksud, nomor surat, atau pelaksana..." wrapperClass="flex-1" />

      <x-form.searchable-select wire:model.live="decision" wrapperClass="w-full sm:w-48"
        placeholder="Semua Keputusan" searchPlaceholder="Cari keputusan..." :options="[
          ['value' => '', 'label' => 'Semua Keputusan'],
          ['value' => 'approved', 'label' => 'Disetujui'],
          ['value' => 'rejected', 'label' => 'Ditolak'],
        ]" />

      <x-ui.button variant="secondary" wire:click="resetFilters"
        :disabled="$search === '' && $decision === ''" class="w-full shrink-0 sm:w-auto">
        <x-slot:icon><i class="fa-solid fa-rotate-left text-xs text-slate-500"></i></x-slot:icon>
        Reset
      </x-ui.button>
    </div>
  </div>

  {{-- Tabel Data --}}
  <div class="dash-enter table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th class="w-12 text-center">No.</th>
          <th>Pelaksana / Instansi</th>
          <th>Maksud Perjalanan</th>
          <th class="w-32">Keputusan</th>
          <th class="w-36">Tanda Tangan</th>
          <th class="w-40">Waktu</th>
          <th class="w-24 text-right">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse ($approvals as $i => $appr)
          @php $sppd = $appr->sppdRequest; @endphp
          <tr wire:key="appr-{{ $appr->id }}">
            <td class="text-center text-xs font-semibold text-slate-500">
              {{ $approvals->firstItem() + $i }}.
            </td>

            <td>
              <p class="font-semibold text-slate-800">{{ $sppd?->user?->name ?? '-' }}</p>
              <p class="text-[11px] text-primary-600 mt-0.5 font-medium">{{ $sppd?->user?->department?->name ?? '-' }}</p>
            </td>

            <td class="max-w-xs">
              <p class="truncate font-medium text-slate-700" title="{{ $sppd?->purpose }}">
                {{ $sppd?->purpose ?? '-' }}
              </p>
              <p class="mt-0.5 truncate text-xs text-slate-500">
                <i class="fa-solid fa-location-dot"></i>
                {{ $sppd?->destinations->first()?->regency?->name ?? '-' }}
              </p>
            </td>

            <td class="whitespace-nowrap">
              <x-ui.badge :color="$appr->status->color()">{{ $appr->status->label() }}</x-ui.badge>
            </td>

            <td class="whitespace-nowrap">
              @if ($appr->signs_spt || $appr->signs_sppd)
                <div class="flex flex-wrap gap-1">
                  @if ($appr->signs_spt)
                    <span class="inline-flex items-center gap-1 rounded bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-violet-600">
                      <i class="fa-solid fa-signature"></i> SPT
                    </span>
                  @endif
                  @if ($appr->signs_sppd)
                    <span class="inline-flex items-center gap-1 rounded bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-violet-600">
                      <i class="fa-solid fa-signature"></i> SPPD
                    </span>
                  @endif
                </div>
              @else
                <span class="text-xs text-slate-300">&mdash;</span>
              @endif
            </td>

            <td class="whitespace-nowrap text-xs leading-normal">
              @if ($appr->acted_at)
                <p class="font-medium text-slate-700">{{ $appr->acted_at->translatedFormat('d F Y') }}</p>
                <p class="text-slate-500">{{ $appr->acted_at->format('H:i') }}</p>
              @else
                <span class="text-slate-300">-</span>
              @endif
            </td>

            <td class="whitespace-nowrap text-right">
              @if ($sppd)
                <a href="{{ route('sppd.show', $sppd) }}" wire:navigate
                  class="inline-flex items-center justify-center gap-1.5 rounded border border-slate-300 bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-200">
                  <i class="fa-solid fa-eye text-[10px] text-slate-500"></i> Lihat
                </a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="py-12 text-center text-slate-500">
              <div class="flex flex-col items-center justify-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-3xl text-slate-200"></i>
                <p class="text-sm">Belum ada riwayat keputusan.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    @if ($approvals->hasPages())
      <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3">
        {{ $approvals->links() }}
      </div>
    @endif
  </div>

</div>
