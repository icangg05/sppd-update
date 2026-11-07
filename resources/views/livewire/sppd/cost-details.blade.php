@php
    use Illuminate\Support\Js;
    $grandTotal = $sppd->costDetails->sum('total');
    $filledCount = $people->filter(fn ($p) => $sppd->costDetails->where('user_id', $p['id'])->count() > 0)->count();
    $canManage = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
    $printReady = $sppd->pptk_id && $hasBendahara;
    // Tombol ikon kecil per item, sama dengan tombol aksi tabel di halaman lain (Edit kuning, Hapus merah).
    $iconBtn = 'inline-flex size-7 shrink-0 items-center justify-center rounded border text-xs transition-colors focus:outline-none focus-visible:ring-2';
    $uploadHint = 'Format gambar JPG, PNG · maks. ' . app(\App\Services\AppSettings::class)->uploadMaxLabel(true) . ' · dikompres otomatis · opsional';
@endphp

<div class="space-y-4 p-1" x-data="{ printDate: '{{ date('Y-m-d') }}' }">

    <x-sppd.doc-header :sppd="$sppd" title="Rincian Biaya Perjalanan"
        description="Rinci komponen biaya perjalanan tiap pegawai, lalu cetak rinciannya." />

    <x-sppd.print-requirements :items="[
        ['ok' => (bool) $sppd->pptk_id, 'label' => 'PPTK belum diatur.', 'href' => route('sppd.next', $sppd), 'link' => 'Atur di Portal Dokumen'],
        ['ok' => $hasBendahara, 'label' => 'Belum ada pegawai berjabatan Bendahara Pengeluaran di ' . ($sppd->user->department->name ?? 'instansi ini') . '.'],
    ]" />

    <x-sppd.panel title="Biaya per Pegawai" icon="fa-calculator">
        <x-slot:aside>{{ $filledCount }}/{{ $people->count() }} terisi · Total <span class="font-mono font-semibold text-white">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span></x-slot:aside>

        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <label for="printDate" class="flex items-center gap-2 text-sm text-slate-600">
                Tanggal cetak
                <input id="printDate" type="date" x-model="printDate"
                    class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-800 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30" />
            </label>
            @if ($canManage)
                <x-ui.button type="button" variant="secondary" size="sm" wire:click="openBulk">
                    <x-slot name="icon"><i class="fa-solid fa-layer-group text-xs text-slate-500"></i></x-slot>
                    Input Biaya Sekaligus
                </x-ui.button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="table-stack w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="w-12 px-4 py-2.5 text-center">No.</th>
                        <th class="w-44 px-4 py-2.5">Pegawai</th>
                        <th class="px-4 py-2.5">Rincian biaya</th>
                        <th class="w-36 px-4 py-2.5 text-right">Total</th>
                        <th class="w-52 px-4 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($people as $index => $person)
                        @php
                            $costs = $sppd->costDetails->where('user_id', $person['id']);
                            $total = $costs->sum('total');
                        @endphp
                        <tr class="align-top transition-colors hover:bg-slate-50/60">
                            <td class="stack-hide px-4 py-3 text-center tabular-nums text-slate-500">{{ $index + 1 }}.</td>
                            <td data-label="Pegawai" class="stack-block px-4 py-3">
                                <div>
                                    <span class="block font-semibold text-slate-800">{{ $person['name'] }}</span>
                                    <x-ui.badge :color="$person['label'] === 'Pelaksana' ? 'cyan' : 'slate'" class="mt-1">{{ $person['label'] }}</x-ui.badge>
                                </div>
                            </td>
                            <td data-label="Rincian biaya" class="stack-block px-4 py-3">
                                @if ($costs->isNotEmpty())
                                    <ul class="max-w-xl divide-y divide-slate-100 rounded border border-slate-200 bg-white">
                                        @foreach ($costs as $cost)
                                            {{-- Layar sempit: nominal & tombol turun ke baris kedua, uraian tidak terjepit. --}}
                                            <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2">
                                                <div class="min-w-0 flex-1 basis-28">
                                                    <p class="break-words text-slate-800">{{ $cost->description }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-500">
                                                        {{ $cost->cost_category->label() }} ·
                                                        <span class="whitespace-nowrap font-mono tabular-nums">{{ $cost->quantity }} × Rp {{ number_format($cost->unit_cost, 0, ',', '.') }}</span>
                                                    </p>
                                                </div>
                                                <span class="shrink-0 whitespace-nowrap font-mono text-sm tabular-nums text-slate-800">Rp {{ number_format($cost->total, 0, ',', '.') }}</span>
                                                <span class="flex shrink-0 items-center gap-1">
                                                    @if ($cost->receipt_photo)
                                                        <x-ui.gallery-link :src="asset('storage/' . $cost->receipt_photo)" group="bukti-biaya"
                                                            :caption="$person['name'] . ' · ' . $cost->description"
                                                            class="{{ $iconBtn }} cursor-pointer border-slate-300 bg-slate-50 text-slate-600 hover:bg-slate-100 focus-visible:ring-slate-400"
                                                            title="Lihat bukti/nota" aria-label="Lihat bukti {{ $cost->description }}">
                                                            <i class="fa-solid fa-image"></i>
                                                        </x-ui.gallery-link>
                                                    @endif
                                                    @if ($canManage)
                                                        <button type="button" wire:click="openEdit({{ $cost->id }})" aria-label="Edit {{ $cost->description }}" title="Edit"
                                                            class="{{ $iconBtn }} border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 focus-visible:ring-amber-400">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>
                                                        <button type="button" wire:click="confirmDelete({{ $cost->id }})" aria-label="Hapus {{ $cost->description }}" title="Hapus"
                                                            class="{{ $iconBtn }} border-red-200 bg-red-50 text-red-600 hover:bg-red-100 focus-visible:ring-red-400">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-sm text-slate-500">Belum ada rincian biaya.@if ($canManage) Klik <span class="font-semibold text-slate-700">Tambah</span> untuk mengisi.@endif</span>
                                @endif
                            </td>
                            <td data-label="Total" class="px-4 py-3 text-right">
                                <span class="whitespace-nowrap font-mono font-bold tabular-nums {{ $total > 0 ? 'text-slate-800' : 'text-slate-500' }}">Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </td>
                            <td data-label="Aksi" class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($canManage)
                                        <x-ui.button type="button" variant="secondary" size="sm"
                                            wire:click="openAdd({{ Js::from($person['id']) }}, {{ Js::from($person['name']) }})">
                                            <x-slot name="icon"><i class="fa-solid fa-plus text-[10px] text-slate-500"></i></x-slot>
                                            Tambah
                                        </x-ui.button>
                                    @endif
                                    @if ($costs->isEmpty())
                                        <x-sppd.print-link disabled reason="Isi rincian biaya terlebih dahulu">Cetak</x-sppd.print-link>
                                    @elseif (!$printReady)
                                        <x-sppd.print-link disabled reason="Atur PPTK dan bendahara pengeluaran terlebih dahulu">Cetak</x-sppd.print-link>
                                    @else
                                        <x-sppd.print-link
                                            x-bind:href="'{{ route('sppd.stream.rincian-biaya', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate">
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
    <x-ui.modal show="$wire.showAddModal" :closeable="false" title="Tambah Rincian Biaya" :description="$addUserName"
        icon="fa-solid fa-plus text-primary-600" maxWidth="max-w-lg">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <x-form.searchable-select wire:model="addCategory" :options="$categoryOptions" label="Kategori biaya"
                    required searchPlaceholder="Cari kategori..." />
            </div>
            <div class="col-span-2">
                <x-form.input wire:model="addDescription" label="Uraian" placeholder="Contoh: Tiket pesawat / hotel" required />
            </div>
            <x-form.rupiah model="addUnitCost" label="Tarif satuan" placeholder="1.500.000" required />
            <x-form.input type="number" wire:model="addQuantity" label="Jumlah item" min="1" required />
            <div class="col-span-2">
                <x-form.file wire:model="addReceipt" label="Bukti / nota" accept="image/*" :hint="$uploadHint" />
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-3">
            <x-ui.button type="button" variant="secondary" x-on:click="$wire.showAddModal = false">Batal</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="saveAdd" wire:target="saveAdd,addReceipt">Simpan</x-ui.button>
        </div>
    </x-ui.modal>

    {{-- Modal Edit --}}
    <x-ui.modal show="$wire.showEditModal" :closeable="false" title="Edit Rincian Biaya"
        icon="fa-solid fa-pen text-amber-600" maxWidth="max-w-lg">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <x-form.searchable-select wire:model="editCategory" :options="$categoryOptions" label="Kategori biaya"
                    required searchPlaceholder="Cari kategori..." />
            </div>
            <div class="col-span-2">
                <x-form.input wire:model="editDescription" label="Uraian" required />
            </div>
            <x-form.rupiah model="editUnitCost" label="Tarif satuan" required />
            <x-form.input type="number" wire:model="editQuantity" label="Jumlah item" min="1" required />
            <div class="col-span-2">
                <x-form.file wire:model="editReceipt" label="Ganti bukti / nota" accept="image/*" :hint="$uploadHint" />
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-3">
            <x-ui.button type="button" variant="secondary" x-on:click="$wire.showEditModal = false">Batal</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="saveEdit" wire:target="saveEdit,editReceipt">Simpan Perubahan</x-ui.button>
        </div>
    </x-ui.modal>

    {{-- Modal Input Sekaligus --}}
    <x-ui.modal show="$wire.showBulkModal" :closeable="false" title="Input Biaya Sekaligus"
        description="Tambahkan satu rincian biaya ke beberapa pegawai" icon="fa-solid fa-layer-group text-primary-600" maxWidth="max-w-xl">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-form.searchable-select wire:model="bulkCategory" :options="$categoryOptions" label="Kategori biaya"
                        required searchPlaceholder="Cari kategori..." />
                </div>
                <div class="col-span-2">
                    <x-form.input wire:model="bulkDescription" label="Uraian" placeholder="Contoh: Tiket pesawat / hotel" required />
                </div>
                <x-form.rupiah model="bulkUnitCost" label="Tarif satuan" placeholder="0" required />
                <x-form.input type="number" wire:model="bulkQuantity" label="Jumlah item" min="1" required />
            </div>

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
    <x-ui.modal show="$wire.showDeleteModal" :closeable="false" title="Hapus Rincian Biaya"
        description="Tindakan ini tidak dapat dibatalkan" icon="fa-solid fa-trash text-red-600">
        <p class="text-sm text-slate-600">
            Rincian biaya <span class="font-bold text-slate-800">{{ $deletingLabel ?: 'ini' }}</span> beserta
            bukti/notanya akan dihapus permanen.
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
