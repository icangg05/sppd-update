@php
    $canManage = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
    $canPrint = $hasActualExpenses && $hasCostDetails && $hasBendahara;
    $receipts = $sppd->advanceReceipts->where('amount', '>', 0);
    $rampungReason = 'Lengkapi pengeluaran riil, rincian biaya, dan bendahara pengeluaran terlebih dahulu';
@endphp

<div class="space-y-4 p-1" x-data="{ printDate: '{{ date('Y-m-d') }}' }">

    <x-sppd.doc-header :sppd="$sppd" title="Kuitansi Perjalanan"
        description="Isi nominal panjar tiap pegawai, simpan, lalu cetak kuitansi panjar atau rampung." />

    {{-- Hanya muncul bila ada prasyarat cetak yang belum terpenuhi. --}}
    <x-sppd.print-requirements :title="$hasBendahara ? 'Kuitansi rampung belum bisa dicetak' : 'Kuitansi belum bisa dicetak'"
        :items="[
            [
                'ok' => $hasBendahara,
                'label' => 'Belum ada pegawai berjabatan Bendahara Pengeluaran di ' . ($sppd->user->department->name ?? 'instansi ini') . '.',
            ],
            [
                'ok' => $hasActualExpenses,
                'label' => 'Laporan pengeluaran riil belum diisi.',
                'href' => route('sppd.actual-expenses', $sppd),
                'link' => 'Isi pengeluaran riil',
            ],
            [
                'ok' => $hasCostDetails,
                'label' => 'Rincian biaya perjalanan belum diisi.',
                'href' => route('sppd.final-costs', $sppd),
                'link' => 'Isi rincian biaya',
            ],
        ]" />

    <x-sppd.panel title="Panjar per Pegawai" icon="fa-receipt">
        <x-slot:aside>{{ $receipts->count() }}/{{ $people->count() }} terisi · Total <span class="font-mono font-semibold text-white">Rp {{ number_format($receipts->sum('amount'), 0, ',', '.') }}</span></x-slot:aside>

        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <label for="printDate" class="flex items-center gap-2 text-sm text-slate-600">
                Tanggal cetak
                <input id="printDate" type="date" x-model="printDate"
                    class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-800 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30" />
            </label>
            @if ($canManage)
                <x-ui.button type="button" variant="secondary" size="sm" wire:click="$set('showBulkModal', true)">
                    <x-slot:icon><i class="fa-solid fa-layer-group text-xs text-slate-500"></i></x-slot:icon>
                    Input Panjar Sekaligus
                </x-ui.button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="table-stack w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="w-12 px-4 py-2.5 text-center">No.</th>
                        <th class="px-4 py-2.5">Pegawai</th>
                        <th class="w-56 px-4 py-2.5">Nominal panjar</th>
                        <th class="px-4 py-2.5 text-right">Cetak kuitansi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($people as $index => $person)
                        @php
                            $receipt = $sppd->advanceReceipts->where('user_id', $person['id'])->first();
                            $hasPanjar = $receipt && $receipt->amount > 0;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="stack-hide px-4 py-3 text-center tabular-nums text-slate-500">{{ $index + 1 }}.</td>
                            <td data-label="Pegawai" class="stack-block px-4 py-3">
                                <div>
                                    <span class="block font-semibold text-slate-800">{{ $person['name'] }}</span>
                                    <x-ui.badge :color="$person['label'] === 'Pelaksana' ? 'cyan' : 'slate'" class="mt-1">{{ $person['label'] }}</x-ui.badge>
                                </div>
                            </td>
                            <td data-label="Nominal panjar" class="stack-block px-4 py-3">
                                <x-form.rupiah :model="'amounts.' . $person['id']" :disabled="!$canManage" placeholder="0"
                                    aria-label="Nominal panjar {{ $person['name'] }}" />
                                @if ($hasPanjar)
                                    <span class="mt-1 block whitespace-nowrap font-mono text-xs tabular-nums text-slate-500">No. {{ $receipt->receipt_number }}</span>
                                @endif
                            </td>
                            <td data-label="Cetak kuitansi" class="stack-block px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    @if ($hasPanjar && $hasBendahara)
                                        <x-sppd.print-link tone="amber"
                                            x-bind:href="'{{ route('sppd.stream.kuitansi-panjar', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate">
                                            Cetak Panjar
                                        </x-sppd.print-link>
                                    @else
                                        <x-sppd.print-link disabled
                                            :reason="$hasBendahara ? 'Isi lalu simpan nominal panjar terlebih dahulu' : 'Bendahara pengeluaran belum ada'">
                                            Cetak Panjar
                                        </x-sppd.print-link>
                                    @endif

                                    @if ($canPrint)
                                        <x-sppd.print-link icon="fa-file-invoice"
                                            x-bind:href="'{{ route('sppd.stream.kuitansi-rampung', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate">
                                            Cetak Rampung
                                        </x-sppd.print-link>
                                    @else
                                        <x-sppd.print-link disabled :reason="$rampungReason">Cetak Rampung</x-sppd.print-link>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($canManage)
            <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-600">
                    <i class="fa-solid fa-circle-info mr-1 text-slate-500"></i>
                    Kosongkan nominal lalu simpan untuk menghapus kuitansi panjar pegawai tersebut.
                </p>
                <x-ui.button type="button" variant="primary" class="w-full sm:w-auto" wire:click="saveAll" wire:target="saveAll">
                    <x-slot:icon><i class="fa-solid fa-floppy-disk text-xs"></i></x-slot:icon>
                    Simpan Semua Panjar
                </x-ui.button>
            </div>
        @endif
    </x-sppd.panel>

    {{-- Modal Input Panjar Sekaligus --}}
    <x-ui.modal show="$wire.showBulkModal" :closeable="false" title="Input Panjar Sekaligus"
        description="Satu nominal untuk semua pegawai" icon="fa-solid fa-layer-group text-primary-600">
        <div class="space-y-4">
            <p class="rounded border border-slate-200 bg-slate-50 p-3 text-sm leading-relaxed text-slate-600">
                Nominal diterapkan ke <strong class="font-semibold text-slate-800">semua pegawai</strong>. Pegawai yang
                nominal panjarnya <strong class="font-semibold text-slate-800">sudah terisi</strong> dilewati.
            </p>

            <x-form.rupiah model="bulkAmount" label="Nominal panjar" placeholder="Contoh: 1.500.000" />

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                <x-ui.button type="button" variant="secondary" x-on:click="$wire.showBulkModal = false; $wire.bulkAmount = ''">Batal</x-ui.button>
                <x-ui.button type="button" variant="primary" wire:click="applyBulk" wire:target="applyBulk">Terapkan Nominal</x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
