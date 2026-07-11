@php
    $canManage = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
    $canPrint = $hasActualExpenses && $hasCostDetails && $hasBendahara;
@endphp

<div class="p-1 space-y-4" x-data="{
    printDate: '{{ date('Y-m-d') }}',
    rupiah(v) { const n = String(v ?? '').replace(/\D/g, ''); return n === '' ? '' : n.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); },
    digits(v) { return String(v ?? '').replace(/\D/g, ''); },
    blockNonDigit(e) { if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !/[0-9]/.test(e.key)) e.preventDefault(); },
}">

    {{-- Header (title card) --}}
    <div
        class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-emerald-50/50 px-5 py-4 shadow-sm">
        <i class="fa-solid fa-file-invoice-dollar pointer-events-none absolute -right-3 -top-4 text-8xl text-emerald-500/6"
            aria-hidden="true"></i>
        {{-- Ornamen garis vertikal (selaras laman laporan) --}}
        <span class="pointer-events-none absolute inset-y-3 left-0 w-1 rounded-r bg-linear-to-b from-emerald-400/40 to-primary-400/40"
            aria-hidden="true"></span>

        <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div class="min-w-0 leading-tight">
                <span
                    class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
                    <i class="fa-solid fa-receipt text-[10px]"></i> Panjar &amp; Rampung
                    <span class="ml-1 tabular-nums text-emerald-600/70">· {{ $people->count() }}</span>
                </span>
                <h1 class="text-xl font-bold tracking-tight text-slate-800">Kuitansi Perjalanan</h1>
                <p class="mt-1 text-sm text-slate-500">Input panjar dan cetak kuitansi panjar/rampung per pegawai.</p>
            </div>
            <x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="secondary" class="shrink-0">
                <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
                Kembali
            </x-ui.button>
        </div>
    </div>

    {{-- Alert Bendahara --}}
    @unless ($hasBendahara)
        <div class="dash-enter flex items-start gap-3 rounded border border-amber-200 bg-amber-50/70 p-4">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 ring-1 ring-amber-200">
                <i class="fa-solid fa-triangle-exclamation text-xs"></i>
            </div>
            <div class="text-xs text-amber-900">
                <p class="font-bold">Bendahara belum ditetapkan</p>
                <p class="mt-1 leading-relaxed text-amber-800">
                    Instansi <strong>{{ $sppd->user->department->name ?? '-' }}</strong> memerlukan pegawai berjabatan
                    "Bendahara Pengeluaran" untuk dapat mencetak kuitansi.
                </p>
            </div>
        </div>
    @endunless

    {{-- Informasi Penting: syarat cetak Kuitansi Rampung (di atas kartu tanggal). --}}
    <div class="dash-enter flex items-start gap-4 rounded border border-primary-300 border-l-4 border-l-primary-600 bg-linear-to-br from-primary-50 to-primary-100/70 p-4 shadow-sm ring-1 ring-inset ring-primary-500/10">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
            <i class="fa-solid fa-circle-info text-lg"></i>
        </div>
        <div>
            <h4 class="text-xs font-bold uppercase text-primary-900">Informasi Penting</h4>
            <p class="mt-1 text-xs font-medium text-primary-800 leading-relaxed">
                Untuk dapat mencetak <strong>Kuitansi Rampung</strong>, pastikan seluruh data berikut telah dilengkapi:
            </p>
            <ul class="mt-2 flex flex-wrap gap-2 text-xs">
                <li class="flex items-center gap-1.5 {{ $hasActualExpenses ? 'text-emerald-700' : 'text-rose-600' }}">
                    <i class="fa-solid {{ $hasActualExpenses ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-400' }}"></i>
                    Laporan Pengeluaran Riil
                </li>
                <li class="flex items-center gap-1.5 {{ $hasCostDetails ? 'text-emerald-700' : 'text-rose-600' }}">
                    <i class="fa-solid {{ $hasCostDetails ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-400' }}"></i>
                    Rincian Biaya Perjalanan
                </li>
                <li class="flex items-center gap-1.5 {{ $hasBendahara ? 'text-emerald-700' : 'text-rose-600' }}">
                    <i class="fa-solid {{ $hasBendahara ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-400' }}"></i>
                    Bendahara Pengeluaran Aktif
                </li>
            </ul>
        </div>
    </div>

    {{-- Pengaturan Tanggal Cetak & Panjar Massal --}}
    <div class="dash-enter flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded border border-l-2 border-slate-200 border-l-emerald-400 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                <i class="fa-solid fa-calendar-day text-emerald-600 text-sm"></i>
                <span>Pilih Tanggal Cetak Kuitansi:</span>
            </div>
            <div class="w-full sm:w-48">
                <input type="date" x-model="printDate"
                    class="w-full rounded border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 shadow-2xs outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30" />
            </div>
        </div>
        @if ($canManage)
            <x-ui.button type="button" variant="primary" wire:click="$set('showBulkModal', true)" class="w-full sm:w-auto">
                <x-slot:icon><i class="fa-solid fa-layer-group text-xs"></i></x-slot:icon>
                Input Panjar Massal
            </x-ui.button>
        @endif
    </div>

    {{-- Daftar Personel --}}
    <div class="dash-enter overflow-x-auto rounded border border-l-2 border-slate-200 border-l-emerald-400 bg-white shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <th class="py-3 px-4 text-center w-12">No.</th>
                    <th class="py-3 px-4">Pegawai</th>
                    <th class="py-3 px-4 w-56">Nominal Kuitansi Panjar (Rp)</th>
                    <th class="py-3 px-4 text-right w-64">Aksi Cetak</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @foreach ($people as $index => $person)
                    @php
                        $receipt = $sppd->advanceReceipts->where('user_id', $person['id'])->first();
                        $hasPanjar = $receipt && $receipt->amount > 0;
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-center font-mono font-semibold text-slate-500">{{ $index + 1 }}.</td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                @if ($person['label'] === 'Pelaksana')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Pelaksana</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">Pengikut</span>
                                @endif
                                <span class="font-bold text-slate-800 uppercase">{{ $person['name'] }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex flex-col gap-1">
                                <div class="relative max-w-45" x-data="{ v: @entangle('amounts.'.$person['id']) }">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-xs font-semibold text-slate-400">Rp</span>
                                    <input type="text" inputmode="numeric" :value="rupiah(v)"
                                        @input="v = digits($event.target.value)" @keydown="blockNonDigit($event)"
                                        @disabled(!$canManage)
                                        class="w-full rounded border border-slate-300 pl-8 pr-2 py-1.5 font-mono text-sm tabular-nums text-slate-800 shadow-2xs outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 disabled:bg-slate-50 disabled:border-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed"
                                        placeholder="Nominal panjar">
                                </div>
                                @if ($hasPanjar)
                                    <span class="pl-1 font-mono text-xs tabular-nums text-slate-500">No: {{ $receipt->receipt_number }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Cetak Panjar --}}
                                @if ($hasPanjar && $hasBendahara)
                                    <a :href="'{{ route('sppd.stream.kuitansi-panjar', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate"
                                        target="_blank"
                                        class="text-nowrap inline-flex items-center gap-1 rounded bg-amber-600 px-2.5 py-1.5 text-xs font-bold text-white shadow-2xs transition hover:bg-amber-700 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/50">
                                        <i class="fa-solid fa-print"></i> Cetak Panjar
                                    </a>
                                @endif

                                {{-- Cetak Rampung --}}
                                @if ($canPrint)
                                    <a :href="'{{ route('sppd.stream.kuitansi-rampung', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate"
                                        target="_blank"
                                        class="text-nowrap inline-flex items-center gap-1 rounded bg-primary-600 px-2.5 py-1.5 text-xs font-bold text-white shadow-2xs transition hover:bg-primary-700 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50">
                                        <i class="fa-solid fa-file-invoice"></i> Cetak Rampung
                                    </a>
                                @else
                                    <button type="button" disabled title="Lengkapi pengeluaran riil, rincian biaya, dan bendahara aktif terlebih dahulu"
                                        class="text-nowrap inline-flex items-center gap-1 rounded bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-400 cursor-not-allowed border border-slate-200">
                                        <i class="fa-solid fa-lock"></i> Cetak Rampung
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($canManage)
        <div class="mt-4 flex flex-col sm:flex-row gap-4 items-center justify-between bg-slate-50 p-3 rounded border border-slate-200">
            <span class="text-xs text-slate-500 font-medium">
                <i class="fa-solid fa-circle-info text-slate-500 mr-1"></i>
                Kosongkan nominal panjar untuk menghapus data kuitansi panjar pegawai tersebut.
            </span>
            <x-ui.button type="button" variant="success" class="w-full sm:w-auto" wire:click="saveAll"
                wire:target="saveAll" wire:loading.attr="disabled">
                <x-slot:icon>
                    <i class="fa-solid fa-floppy-disk text-xs" wire:loading.remove wire:target="saveAll"></i>
                    <i class="fa-solid fa-circle-notch fa-spin text-xs" wire:loading wire:target="saveAll"></i>
                </x-slot:icon>
                <span wire:loading.remove wire:target="saveAll">Simpan Semua Panjar</span>
                <span wire:loading wire:target="saveAll">Menyimpan…</span>
            </x-ui.button>
        </div>
    @endif

    {{-- Modal Input Panjar Sekaligus --}}
    <x-ui.modal show="$wire.showBulkModal" :closeable="false" title="Input Panjar Sekaligus" icon="fa-solid fa-layer-group text-emerald-600">
        <div class="space-y-4">
            <div class="rounded bg-slate-50 p-3 border border-slate-200 text-xs text-slate-600 leading-relaxed">
                <p class="mb-1 font-bold text-slate-700">Aturan penerapan</p>
                Nominal ini akan diterapkan ke <strong class="font-semibold text-slate-700">semua pegawai</strong>. Pegawai yang nominal panjarnya <strong class="font-semibold text-slate-700">sudah terisi</strong> akan otomatis dilewati.
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nominal Panjar</label>
                <div class="relative" x-data="{ v: @entangle('bulkAmount') }">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">Rp</span>
                    <input type="text" inputmode="numeric" :value="rupiah(v)"
                        @input="v = digits($event.target.value)" @keydown="blockNonDigit($event)"
                        class="w-full rounded border border-slate-300 pl-9 pr-3 py-2 font-mono text-sm tabular-nums outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
                        placeholder="Contoh: 1.500.000">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="ghost" x-on:click="$wire.showBulkModal = false; $wire.bulkAmount = ''">Batal</x-ui.button>
                <x-ui.button type="button" variant="primary" wire:click="applyBulk" wire:target="applyBulk"
                    wire:loading.attr="disabled">Terapkan Nominal</x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
