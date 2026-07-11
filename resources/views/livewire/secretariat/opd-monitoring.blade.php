<div class="flex flex-col gap-4 p-1">

  {{-- Header (title card) — mengikuti gaya kartu judul halaman index. --}}
  <div
    class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-solid fa-clipboard-check pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-list-check text-[9px]"></i>
          Tindak Lanjut OPD
          <span class="ml-1 tabular-nums text-primary-600/70">· {{ $sppds->total() }}</span>
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Monitoring Tindak Lanjut SPPD</h1>
        <p class="mt-1 max-w-2xl text-xs text-slate-500">Kejar kelengkapan administrasi perjalanan dinas di lingkup
          OPD Anda: pegawai yang sedang dinas, laporan &amp; realisasi biaya yang belum lengkap.</p>
      </div>
    </div>
  </div>

  {{-- Kartu Ringkasan / Tab --}}
  <div class="dash-enter grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
    {{-- Sedang dinas hari ini --}}
    <button type="button" wire:click="setTab('ongoing')"
      class="flex items-center gap-3 rounded border bg-white p-4 text-left shadow-sm transition {{ $tab === 'ongoing' ? 'border-primary-500 ring-1 ring-primary-500' : 'border-slate-200 hover:border-primary-300' }}">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600">
        <i class="fa-solid fa-person-walking-luggage"></i>
      </span>
      <span class="leading-tight">
        <span class="block text-xl font-bold text-slate-800">{{ $counts['ongoing'] }}</span>
        <span class="block text-xs text-slate-500">Sedang dinas hari ini</span>
      </span>
    </button>

    {{-- Belum unggah laporan --}}
    <button type="button" wire:click="setTab('no_report')"
      class="flex items-center gap-3 rounded border bg-white p-4 text-left shadow-sm transition {{ $tab === 'no_report' ? 'border-primary-500 ring-1 ring-primary-500' : 'border-slate-200 hover:border-primary-300' }}">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
        <i class="fa-solid fa-file-circle-exclamation"></i>
      </span>
      <span class="leading-tight">
        <span class="block text-xl font-bold text-slate-800">{{ $counts['no_report'] }}</span>
        <span class="block text-xs text-slate-500">Belum unggah laporan</span>
      </span>
    </button>

    {{-- Belum input realisasi biaya --}}
    <button type="button" wire:click="setTab('no_expense')"
      class="flex items-center gap-3 rounded border bg-white p-4 text-left shadow-sm transition {{ $tab === 'no_expense' ? 'border-primary-500 ring-1 ring-primary-500' : 'border-slate-200 hover:border-primary-300' }}">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
        <i class="fa-solid fa-money-check-dollar"></i>
      </span>
      <span class="leading-tight">
        <span class="block text-xl font-bold text-slate-800">{{ $counts['no_expense'] }}</span>
        <span class="block text-xs text-slate-500">Belum input realisasi biaya</span>
      </span>
    </button>

    {{-- Menunggu paraf saya (tautan ke mode persetujuan di index) --}}
    <a href="{{ route('sppd.index', ['filter' => 'approval']) }}" wire:navigate
      class="flex items-center gap-3 rounded border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-emerald-300">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
        <i class="fa-solid fa-signature"></i>
      </span>
      <span class="leading-tight">
        <span class="block text-xl font-bold text-slate-800">{{ $counts['my_pending'] }}</span>
        <span class="block text-xs text-slate-500">Menunggu paraf saya <i class="fa-solid fa-arrow-right ml-0.5 text-[10px]"></i></span>
      </span>
    </a>
  </div>

  {{-- Bar Pencarian --}}
  <div class="dash-enter rounded border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
      <x-form.input name="search" wire:model.live.debounce.300ms="search" icon="fa-solid fa-magnifying-glass"
        loadingTarget="search" placeholder="Cari pelaksana, maksud, atau nomor surat..." wrapperClass="flex-1" />

      <x-ui.button variant="secondary" wire:click="resetFilters" :disabled="$search === ''"
        class="w-full shrink-0 sm:w-auto">
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
          <th class="w-40">Tanggal</th>
          <th class="w-28">Status</th>
          <th class="w-32 text-right">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse ($sppds as $i => $sppd)
          <tr wire:key="mon-{{ $sppd->id }}">
            <td class="text-center text-xs font-semibold text-slate-500">
              {{ $sppds->firstItem() + $i }}.
            </td>

            <td>
              <p class="font-semibold text-slate-800">{{ $sppd->user->name ?? '-' }}</p>
              <p class="text-[11px] text-primary-600 mt-0.5 font-medium">{{ $sppd->user->department?->name ?? '-' }}</p>
            </td>

            <td class="max-w-xs">
              <p class="truncate font-medium text-slate-700" title="{{ $sppd->purpose }}">{{ $sppd->purpose }}</p>
              <p class="mt-0.5 truncate text-xs text-slate-500">
                <i class="fa-solid fa-location-dot"></i>
                {{ $sppd->destinations->first()?->regency?->name ?? '-' }}
              </p>
            </td>

            <td class="whitespace-nowrap text-xs leading-normal">
              <p class="font-medium text-slate-700">{{ $sppd->start_date->translatedFormat('d M Y') }}</p>
              <p class="text-slate-500">s/d {{ $sppd->end_date->translatedFormat('d M Y') }}</p>
            </td>

            <td class="whitespace-nowrap">
              <x-ui.badge :status="$sppd->status->value" class="px-2.5 py-1 text-xs">{{ $sppd->status->label() }}</x-ui.badge>
            </td>

            <td class="whitespace-nowrap text-right">
              <div class="inline-flex items-center gap-1.5">
                @if ($tab === 'no_report')
                  <a href="{{ route('sppd.report-input', $sppd) }}" wire:navigate
                    class="inline-flex items-center justify-center gap-1.5 rounded border border-amber-300 bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700 shadow-2xs transition hover:bg-amber-100">
                    <i class="fa-solid fa-file-arrow-up text-[10px]"></i> Laporan
                  </a>
                @elseif ($tab === 'no_expense')
                  <a href="{{ route('sppd.actual-expenses', $sppd) }}" wire:navigate
                    class="inline-flex items-center justify-center gap-1.5 rounded border border-rose-300 bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-700 shadow-2xs transition hover:bg-rose-100">
                    <i class="fa-solid fa-money-check-dollar text-[10px]"></i> Realisasi
                  </a>
                @endif
                <a href="{{ route('sppd.show', $sppd) }}" wire:navigate
                  class="inline-flex items-center justify-center gap-1.5 rounded border border-slate-300 bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-200">
                  <i class="fa-solid fa-eye text-[10px] text-slate-500"></i> Lihat
                </a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="py-12 text-center text-slate-500">
              <div class="flex flex-col items-center justify-center gap-2">
                <i class="fa-solid fa-clipboard-check text-3xl text-slate-200"></i>
                <p class="text-sm">
                  @if ($tab === 'ongoing')
                    Tidak ada pegawai yang sedang dinas hari ini.
                  @elseif ($tab === 'no_report')
                    Semua laporan perjalanan sudah lengkap. 🎉
                  @else
                    Semua realisasi biaya sudah diinput. 🎉
                  @endif
                </p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    @if ($sppds->hasPages())
      <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3">
        {{ $sppds->links() }}
      </div>
    @endif
  </div>

</div>
