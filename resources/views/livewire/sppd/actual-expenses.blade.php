@php
    use Illuminate\Support\Js;
    use Vinkla\Hashids\Facades\Hashids;
    $grandTotal = $sppd->actualExpenses->sum('amount');
    $filledCount = $people->filter(fn ($p) => $sppd->actualExpenses->where('user_id', $p['id'])->count() > 0)->count();
    $canManage = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
    // Tombol ikon kecil per item, sama dengan tombol aksi tabel di halaman lain (Edit kuning, Hapus merah).
    $iconBtn = 'inline-flex size-7 shrink-0 items-center justify-center rounded border text-xs transition-colors focus:outline-none focus-visible:ring-2';
@endphp

<div class="space-y-4 p-1" x-data="{ printDate: '{{ date('Y-m-d') }}' }">

    <x-sppd.doc-header :sppd="$sppd" title="Laporan Pengeluaran Riil"
        description="Catat pengeluaran riil tiap pegawai, lalu cetak dokumennya per personel." />

    <x-sppd.print-requirements :items="[
        ['ok' => $hasPptk, 'label' => 'PPTK belum diatur.', 'href' => route('sppd.next', $sppd), 'link' => 'Atur di Portal Dokumen'],
    ]" />

    <x-sppd.panel title="Pengeluaran per Pegawai" icon="fa-hand-holding-dollar">
        <x-slot:aside>{{ $filledCount }}/{{ $people->count() }} terisi · Total <span class="font-mono font-semibold text-white">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span></x-slot:aside>

        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <label for="printDate" class="flex items-center gap-2 text-sm text-slate-600">
                Tanggal cetak
                <input id="printDate" type="date" x-model="printDate"
                    class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-800 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30" />
            </label>
            @if ($canManage)
                <x-ui.button variant="secondary" size="sm" wire:click="openBulk">
                    <x-slot name="icon"><i class="fa-solid fa-layer-group text-xs text-slate-500"></i></x-slot>
                    Input Pengeluaran Sekaligus
                </x-ui.button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="table-stack w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="w-12 px-4 py-2.5 text-center">No.</th>
                        <th class="w-44 px-4 py-2.5">Pegawai</th>
                        <th class="px-4 py-2.5">Rincian pengeluaran</th>
                        <th class="w-36 px-4 py-2.5 text-right">Total</th>
                        <th class="w-52 px-4 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($people as $index => $person)
                        @php
                            $expenses = $sppd->actualExpenses->where('user_id', $person['id']);
                            $total = $expenses->sum('amount');
                        @endphp
                        <tr class="align-top transition-colors hover:bg-slate-50/60">
                            <td class="stack-hide px-4 py-3 text-center tabular-nums text-slate-500">{{ $index + 1 }}.</td>
                            <td data-label="Pegawai" class="stack-block px-4 py-3">
                                <div>
                                    <span class="block font-semibold text-slate-800">{{ $person['name'] }}</span>
                                    <x-ui.badge :color="$person['label'] === 'Pelaksana' ? 'cyan' : 'slate'" class="mt-1">{{ $person['label'] }}</x-ui.badge>
                                </div>
                            </td>
                            <td data-label="Rincian pengeluaran" class="stack-block px-4 py-3">
                                @if ($expenses->isNotEmpty())
                                    <ul class="max-w-xl divide-y divide-slate-100 rounded border border-slate-200 bg-white">
                                        @foreach ($expenses as $exp)
                                            {{-- Layar sempit: nominal & tombol turun ke baris kedua, uraian tidak terjepit. --}}
                                            <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2">
                                                <span class="min-w-0 flex-1 basis-28 break-words text-slate-800">{{ $exp->description }}</span>
                                                <span class="shrink-0 whitespace-nowrap font-mono text-sm tabular-nums text-slate-800">Rp {{ number_format($exp->amount, 0, ',', '.') }}</span>
                                                @if ($canManage)
                                                    <span class="flex shrink-0 items-center gap-1">
                                                        <button type="button" wire:click="openEdit({{ $exp->id }})" aria-label="Edit {{ $exp->description }}" title="Edit"
                                                            class="{{ $iconBtn }} border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 focus-visible:ring-amber-400">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>
                                                        <button type="button" wire:click="confirmDelete({{ $exp->id }})" aria-label="Hapus {{ $exp->description }}" title="Hapus"
                                                            class="{{ $iconBtn }} border-red-200 bg-red-50 text-red-600 hover:bg-red-100 focus-visible:ring-red-400">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-sm text-slate-500">Belum ada pengeluaran.@if ($canManage) Klik <span class="font-semibold text-slate-700">Tambah</span> untuk mengisi.@endif</span>
                                @endif
                            </td>
                            <td data-label="Total" class="px-4 py-3 text-right">
                                <span class="whitespace-nowrap font-mono font-bold tabular-nums {{ $total > 0 ? 'text-slate-800' : 'text-slate-500' }}">Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </td>
                            <td data-label="Aksi" class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($canManage)
                                        <x-ui.button variant="secondary" size="sm"
                                            wire:click="openAdd({{ Js::from($person['id']) }}, {{ Js::from($person['name']) }})">
                                            <x-slot name="icon"><i class="fa-solid fa-plus text-[10px] text-slate-500"></i></x-slot>
                                            Tambah
                                        </x-ui.button>
                                    @endif
                                    @if ($expenses->isEmpty())
                                        <x-sppd.print-link disabled reason="Isi pengeluaran terlebih dahulu">Cetak</x-sppd.print-link>
                                    @elseif (!$hasPptk)
                                        <x-sppd.print-link disabled reason="Atur PPTK terlebih dahulu">Cetak</x-sppd.print-link>
                                    @else
                                        <x-sppd.print-link
                                            x-bind:href="'{{ route('sppd.stream.pengeluaran-riil', ['sppd' => $sppd, 'user_id' => Hashids::encode($person['id'])]) }}' + '&date=' + printDate">
                                            Cetak
                                        </x-sppd.print-link>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-200 bg-slate-50">
                        <td colspan="3" class="stack-hide px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-600">Total keseluruhan</td>
                        <td data-label="Total keseluruhan" class="whitespace-nowrap px-4 py-3 text-right font-mono font-bold tabular-nums text-slate-900">
                            Rp {{ number_format($grandTotal, 0, ',', '.') }}
                        </td>
                        <td class="stack-hide px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-sppd.panel>

    {{-- Modal Tambah --}}
    <x-ui.modal show="$wire.showAddModal" :closeable="false" title="Tambah Pengeluaran Riil" :description="$addUserName"
        icon="fa-solid fa-plus text-primary-600">
        <div class="space-y-4">
            <x-form.input wire:model="addDescription" label="Uraian biaya" placeholder="Contoh: Tiket pesawat" required />
            <x-form.rupiah model="addAmount" label="Nominal biaya" placeholder="Contoh: 1.500.000" required />
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                <x-ui.button type="button" variant="secondary" x-on:click="$wire.showAddModal = false">Batal</x-ui.button>
                <x-ui.button type="button" variant="primary" wire:click="saveAdd" wire:target="saveAdd">Simpan</x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    {{-- Modal Edit --}}
    <x-ui.modal show="$wire.showEditModal" :closeable="false" title="Edit Pengeluaran Riil" icon="fa-solid fa-pen text-amber-600">
        <div class="space-y-4">
            <x-form.input wire:model="editDescription" label="Uraian biaya" required />
            <x-form.rupiah model="editAmount" label="Nominal biaya" required />
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                <x-ui.button type="button" variant="secondary" x-on:click="$wire.showEditModal = false">Batal</x-ui.button>
                <x-ui.button type="button" variant="primary" wire:click="saveEdit" wire:target="saveEdit">Simpan Perubahan</x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    {{-- Modal Input Sekaligus --}}
    <x-ui.modal show="$wire.showBulkModal" :closeable="false" title="Input Pengeluaran Sekaligus"
        description="Tambahkan satu pengeluaran ke beberapa pegawai" icon="fa-solid fa-layer-group text-primary-600">
        <div class="space-y-4">
            <x-form.input wire:model="bulkDescription" label="Uraian biaya" placeholder="Contoh: Uang harian" required />
            <x-form.rupiah model="bulkAmount" label="Nominal biaya" placeholder="Contoh: 150.000" required />

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-600">Pegawai penerima</span>
                    <button type="button" wire:click="toggleSelectAll"
                        class="cursor-pointer rounded text-xs font-semibold text-primary-700 transition hover:text-primary-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <span x-text="$wire.selectedUserIds.length === {{ $people->count() }} ? 'Batal pilih semua' : 'Pilih semua'"></span>
                    </button>
                </div>
                <div class="max-h-48 divide-y divide-slate-100 overflow-y-auto rounded border border-slate-200">
                    @foreach ($people as $person)
                        <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-slate-50">
                            <input type="checkbox" wire:model.live="selectedUserIds" value="{{ $person['id'] }}"
                                class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                            <span class="font-medium text-slate-800">{{ $person['name'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedUserIds') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                <x-ui.button type="button" variant="secondary" x-on:click="$wire.showBulkModal = false">Batal</x-ui.button>
                <x-ui.button type="button" variant="primary" wire:click="saveBulk" wire:target="saveBulk"
                    x-bind:disabled="$wire.selectedUserIds.length === 0">
                    Terapkan &amp; Simpan
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    {{-- Modal Konfirmasi Hapus (hanya bisa ditutup lewat tombol) --}}
    <x-ui.modal show="$wire.showDeleteModal" :closeable="false" title="Hapus Pengeluaran Riil"
        description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash text-red-600">
        <p class="text-sm text-slate-600">
            Pengeluaran <span class="font-bold text-slate-800">{{ $deletingLabel ?: 'ini' }}</span> akan dihapus permanen.
        </p>
        <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-3">
            <x-ui.button type="button" variant="secondary" x-on:click="$wire.showDeleteModal = false"
                wire:click="closeDeleteModal">Batal</x-ui.button>
            <x-ui.button type="button" variant="danger" wire:click="delete" wire:target="delete">
                <x-slot name="icon"><i class="fa-solid fa-trash text-xs"></i></x-slot>
                Hapus
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
