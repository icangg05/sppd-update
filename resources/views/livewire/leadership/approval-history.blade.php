<div class="flex flex-col gap-4 p-1">

  {{-- Header Halaman --}}
  <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div class="leading-tight">
      <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <i class="fa-solid fa-clock-rotate-left text-primary-600"></i> Riwayat Persetujuan
      </h1>
      <p class="text-xs text-slate-500 mt-0.5">Jejak keputusan &amp; tanda tangan yang telah Anda lakukan</p>
    </div>
  </div>

  {{-- Bar Filter --}}
  <div class="rounded border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row">
      <div class="flex-1">
        <x-form.input name="search" wire:model.live.debounce.300ms="search"
          placeholder="Cari maksud, nomor surat, atau pelaksana..." wrapperClass="w-full" />
      </div>
      <div class="w-full sm:w-44">
        <select name="decision" wire:model.live="decision"
          class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500">
          <option value="">Semua Keputusan</option>
          <option value="approved">Disetujui</option>
          <option value="rejected">Ditolak</option>
        </select>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        @if ($search !== '' || $decision !== '')
          <button type="button" wire:click="resetFilters"
            class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <i class="fa-solid fa-rotate-left text-xs text-slate-500"></i> Reset
          </button>
        @else
          <button type="button" disabled
            class="inline-flex items-center gap-2 rounded border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-300 cursor-not-allowed">
            <i class="fa-solid fa-rotate-left text-xs text-slate-300"></i> Reset
          </button>
        @endif
      </div>
    </div>
  </div>

  {{-- Tabel Data --}}
  <div class="table-wrapper">
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
