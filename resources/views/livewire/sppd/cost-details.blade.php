@php
    use Illuminate\Support\Js;
    $grandTotal = $sppd->costDetails->sum('total');
    $filledCount = $people->filter(fn ($p) => $sppd->costDetails->where('user_id', $p['id'])->count() > 0)->count();
    $canManage = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
    $printReady = $sppd->pptk_id && $hasBendahara;
@endphp

<div class="p-1 space-y-6" x-data="{
    printDate: '{{ date('Y-m-d') }}',
    rupiah(v) { const n = String(v ?? '').replace(/\D/g, ''); return n === '' ? '' : n.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); },
    digits(v) { return String(v ?? '').replace(/\D/g, ''); },
    blockNonDigit(e) { if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !/[0-9]/.test(e.key)) e.preventDefault(); },
}">

    {{-- Header (title card ala halaman index) --}}
    <div
        class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
        <i class="fa-solid fa-receipt pointer-events-none absolute -right-4 -top-5 text-8xl text-primary-500/6"
            aria-hidden="true"></i>
        <span class="pointer-events-none absolute inset-y-3 left-0 w-1 rounded-r bg-linear-to-b from-primary-400/40 to-emerald-400/40"
            aria-hidden="true"></span>

        <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div class="min-w-0 leading-tight">
                <span
                    class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
                    <i class="fa-solid fa-calculator text-[9px]"></i> Langkah Rincian Biaya
                </span>
                <h1 class="text-xl font-bold tracking-tight text-slate-800">Rincian Biaya Perjalanan</h1>
                <p class="mt-1 text-sm text-slate-500">Rinci komponen biaya perjalanan tiap personel, lalu cetak
                    rinciannya.</p>
            </div>
            <x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="secondary" class="shrink-0">
                <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
                Kembali
            </x-ui.button>
        </div>
    </div>

    {{-- Alert Info --}}
    @unless ($printReady)
        <div class="dash-enter flex items-start gap-4 rounded border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-amber-900">Cetak Belum Bisa Dilakukan</h4>
                <p class="mt-1 text-xs font-medium text-amber-800">Lengkapi hal berikut agar tombol cetak aktif:</p>
                <ul class="mt-2 space-y-1 text-xs text-amber-800">
                    @unless ($sppd->pptk_id)
                        <li><i class="fa-solid fa-circle-xmark mr-1.5 text-amber-500"></i> PPTK belum diatur.</li>
                    @endunless
                    @unless ($hasBendahara)
                        <li><i class="fa-solid fa-circle-xmark mr-1.5 text-amber-500"></i> Bendahara Pengeluaran belum
                            tersedia di OPD.</li>
                    @endunless
                </ul>
            </div>
        </div>
    @endunless

    {{-- Ringkasan cepat --}}
    <div class="dash-enter grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="relative overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm">
            <span class="absolute inset-y-0 left-0 w-1 bg-primary-500/70" aria-hidden="true"></span>
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                    <i class="fa-solid fa-people-group"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Personel</p>
                    <p class="text-lg font-bold text-slate-800">{{ $people->count() }} <span
                            class="text-sm font-medium text-slate-400">orang</span></p>
                </div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm">
            <span class="absolute inset-y-0 left-0 w-1 bg-amber-500/70" aria-hidden="true"></span>
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Sudah Diisi Biaya</p>
                    <p class="text-lg font-bold text-slate-800">{{ $filledCount }} <span
                            class="text-sm font-medium text-slate-400">/ {{ $people->count() }}</span></p>
                </div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded border border-slate-200 bg-white p-4 shadow-sm">
            <span class="absolute inset-y-0 left-0 w-1 bg-emerald-500/70" aria-hidden="true"></span>
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Keseluruhan</p>
                    <p class="text-lg font-bold text-emerald-700 font-mono">Rp
                        {{ number_format($grandTotal, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar: Tanggal Cetak & Input Massal --}}
    <div
        class="dash-enter flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
            <i class="fa-solid fa-calendar-day text-primary-600"></i>
            <label for="printDate" class="text-slate-500">Tanggal Cetak:</label>
            <input id="printDate" type="date" x-model="printDate"
                class="rounded border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 focus:border-primary-500 focus:ring-primary-500 shadow-sm" />
        </div>
        @if ($canManage)
            <x-ui.button type="button" wire:click="openBulk" variant="primary" class="w-full justify-center sm:w-auto">
                <x-slot name="icon"><i class="fa-solid fa-layer-group text-xs"></i></x-slot>
                Input Biaya Sekaligus
            </x-ui.button>
        @endif
    </div>

    {{-- Tabel --}}
    <div class="dash-enter overflow-x-auto rounded border border-slate-200 bg-white shadow-sm">
        <table class="table-stack w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <th class="py-3 px-4 text-center w-12 font-bold">No.</th>
                    <th class="py-3 px-4 w-60 font-bold">Pegawai</th>
                    <th class="py-3 px-4 font-bold">Daftar Rincian Biaya</th>
                    <th class="py-3 px-4 w-44 text-right font-bold">Total Biaya</th>
                    <th class="py-3 px-4 w-44 text-right font-bold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($people as $index => $person)
                    @php
                        $costs = $sppd->costDetails->where('user_id', $person['id']);
                        $total = $costs->sum('total');
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="stack-hide py-3 px-4 text-center font-semibold text-slate-500">{{ $index + 1 }}.</td>
                        <td data-label="Pegawai" class="stack-block py-3 px-4">
                            <div class="flex items-center flex-wrap gap-2">
                                @if ($person['label'] === 'Pelaksana')
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        <i class="fa-solid fa-user-check text-[8px]"></i> Pelaksana
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                        <i class="fa-solid fa-user text-[8px]"></i> Pengikut
                                    </span>
                                @endif
                                <span class="font-bold text-slate-800 uppercase">{{ $person['name'] }}</span>
                            </div>
                        </td>
                        <td data-label="Rincian Biaya" class="stack-block py-3 px-4">
                            <div class="space-y-1.5 max-w-xl">
                                @forelse ($costs as $cost)
                                    <div
                                        class="group flex items-center justify-between bg-slate-50 hover:bg-white border border-slate-200 hover:border-primary-200 rounded px-3 py-2 transition-all">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center gap-1 bg-primary-50 text-primary-700 ring-1 ring-inset ring-primary-600/15 px-2 py-0.5 rounded text-[11px] font-semibold">
                                                    <i class="fa-solid fa-tag text-[8px]"></i>{{ $cost->cost_category->label() }}</span>
                                                <span class="text-slate-700 font-medium">{{ $cost->description }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs text-slate-500 font-mono">{{ $cost->quantity }}
                                                    item &times; Rp {{ number_format($cost->unit_cost, 0, ',', '.') }} =
                                                    <span class="font-bold text-slate-700">Rp
                                                        {{ number_format($cost->total, 0, ',', '.') }}</span></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 opacity-100 transition-opacity md:opacity-0 md:group-hover:opacity-100">
                                            @if ($cost->receipt_photo)
                                                <a href="{{ asset('storage/' . $cost->receipt_photo) }}" target="_blank"
                                                    class="text-emerald-600 p-1 cursor-pointer" title="Lihat Bukti/Nota">
                                                    <i class="fa-solid fa-image text-xs"></i>
                                                </a>
                                            @endif
                                            @if ($canManage)
                                                <button type="button" wire:click="openEdit({{ $cost->id }})"
                                                    class="text-amber-600 hover:text-amber-800 p-1 cursor-pointer" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>
                                                <button type="button" wire:click="delete({{ $cost->id }})"
                                                    wire:confirm="Hapus biaya ini?"
                                                    class="text-rose-600 hover:text-rose-800 p-1 cursor-pointer" title="Hapus">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <span class="inline-flex items-center gap-1.5 text-slate-400 italic text-xs pl-1">
                                        <i class="fa-regular fa-circle-dashed"></i> Belum ada data biaya
                                    </span>
                                @endforelse
                            </div>
                        </td>
                        <td data-label="Total Biaya" class="py-3 px-4 text-right">
                            <span class="font-bold {{ $total > 0 ? 'text-emerald-700 font-mono' : 'text-slate-400' }}">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </span>
                        </td>
                        <td data-label="Aksi" class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if ($canManage)
                                    <button type="button"
                                        wire:click="openAdd({{ Js::from($person['id']) }}, {{ Js::from($person['name']) }})"
                                        class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer hover:scale-[1.03] active:scale-[0.97]">
                                        <i class="fa-solid fa-plus text-[10px]"></i> Tambah
                                    </button>
                                @endif
                                @if ($costs->count() > 0)
                                    <a :href="'{{ route('sppd.stream.rincian-biaya', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' + '&date=' + printDate"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded bg-primary-600 px-2.5 py-1.5 text-xs font-bold text-white hover:bg-primary-700 shadow-xs transition {{ $printReady ? 'hover:scale-[1.03] active:scale-[0.97]' : 'opacity-50 pointer-events-none cursor-not-allowed' }}">
                                        <i class="fa-solid fa-print text-[10px]"></i> Cetak
                                    </a>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex items-center gap-1 rounded bg-slate-100 border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-400 cursor-not-allowed">
                                        <i class="fa-solid fa-lock text-[10px]"></i> Cetak
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-200 bg-slate-50/80">
                    <td colspan="3" class="stack-hide py-3 px-4 text-right text-xs font-bold uppercase tracking-wide text-slate-500">
                        <i class="fa-solid fa-sack-dollar mr-1.5 text-emerald-600"></i> Total Keseluruhan
                    </td>
                    <td data-label="Total Keseluruhan" class="py-3 px-4 text-right font-mono text-sm font-bold text-emerald-700">
                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    </td>
                    <td class="stack-hide py-3 px-4"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Modal Tambah --}}
    <x-ui.modal show="$wire.showAddModal" :closeable="false" title="Tambah Rincian Biaya"
        icon="fa-solid fa-plus text-emerald-600" maxWidth="max-w-lg">
        <div class="mb-3 rounded bg-slate-50 px-3 py-2 border border-slate-100 text-xs font-semibold text-slate-600">
            Pegawai: <span class="font-bold text-slate-800 uppercase">{{ $addUserName }}</span>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <x-form.searchable-select wire:model="addCategory" :options="$categoryOptions" label="Kategori Biaya"
                    required searchPlaceholder="Cari kategori..." />
            </div>
            <div class="col-span-2">
                <x-form.input wire:model="addDescription" label="Uraian Keterangan"
                    placeholder="Contoh: Tiket Pesawat / Hotel" required />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Tarif Satuan (Rp)
                    <span class="text-rose-500">*</span></label>
                <div class="relative" x-data="{ v: @entangle('addUnitCost') }">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">Rp</span>
                    <input type="text" inputmode="numeric" placeholder="1.500.000" :value="rupiah(v)"
                        @input="v = digits($event.target.value)" @keydown="blockNonDigit($event)"
                        class="w-full rounded border border-slate-300 pl-9 pr-3 py-2 font-mono text-sm tabular-nums outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30">
                </div>
                @error('addUnitCost') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>
            <x-form.input type="number" wire:model="addQuantity" label="Jumlah (Item)" min="1" required />
            <div class="col-span-2">
                <x-form.file wire:model="addReceipt" label="Lampiran Bukti/Nota" hint="Max 20MB" accept="image/*" />
                <p wire:loading wire:target="addReceipt" class="mt-1 text-xs text-slate-500">
                    <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...</p>
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-3 border-t border-slate-100 pt-3">
            <x-ui.button type="button" variant="ghost" x-on:click="$wire.showAddModal = false">Batal</x-ui.button>
            <x-ui.button type="button" variant="success" wire:click="saveAdd" wire:target="saveAdd,addReceipt"
                wire:loading.attr="disabled">Simpan Biaya</x-ui.button>
        </div>
    </x-ui.modal>

    {{-- Modal Edit --}}
    <x-ui.modal show="$wire.showEditModal" :closeable="false" title="Edit Rincian Biaya"
        icon="fa-solid fa-pen-to-square text-amber-600" maxWidth="max-w-lg">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <x-form.searchable-select wire:model="editCategory" :options="$categoryOptions" label="Kategori Biaya"
                    required searchPlaceholder="Cari kategori..." />
            </div>
            <div class="col-span-2">
                <x-form.input wire:model="editDescription" label="Uraian Keterangan" required />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Tarif Satuan (Rp)
                    <span class="text-rose-500">*</span></label>
                <div class="relative" x-data="{ v: @entangle('editUnitCost') }">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">Rp</span>
                    <input type="text" inputmode="numeric" :value="rupiah(v)"
                        @input="v = digits($event.target.value)" @keydown="blockNonDigit($event)"
                        class="w-full rounded border border-slate-300 pl-9 pr-3 py-2 font-mono text-sm tabular-nums outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30">
                </div>
                @error('editUnitCost') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>
            <x-form.input type="number" wire:model="editQuantity" label="Jumlah (Item)" min="1" required />
            <div class="col-span-2">
                <x-form.file wire:model="editReceipt" label="Upload Bukti Baru" hint="Opsional" accept="image/*" />
                <p wire:loading wire:target="editReceipt" class="mt-1 text-xs text-slate-500">
                    <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...</p>
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-3 border-t border-slate-100 pt-3">
            <x-ui.button type="button" variant="ghost" x-on:click="$wire.showEditModal = false">Batal</x-ui.button>
            <x-ui.button type="button" variant="warning" wire:click="saveEdit" wire:target="saveEdit,editReceipt"
                wire:loading.attr="disabled">Update Perubahan</x-ui.button>
        </div>
    </x-ui.modal>

    {{-- Modal Input Sekaligus --}}
    <x-ui.modal show="$wire.showBulkModal" :closeable="false" title="Input Biaya Sekaligus"
        icon="fa-solid fa-layer-group text-emerald-600" maxWidth="max-w-xl">
        <div class="space-y-4">
            <div class="flex items-start gap-2.5 rounded bg-primary-50/70 p-3 border border-primary-100 text-xs text-slate-600 leading-relaxed">
                <i class="fa-solid fa-circle-info mt-0.5 text-primary-500"></i>
                <span>Rincian biaya ini akan ditambahkan ke <strong>semua pegawai yang dicentang</strong>. Anda dapat
                    memilih atau membatalkan pilihan pegawai di bawah.</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-form.searchable-select wire:model="bulkCategory" :options="$categoryOptions" label="Kategori Biaya"
                        required searchPlaceholder="Cari kategori..." />
                </div>
                <div class="col-span-2">
                    <x-form.input wire:model="bulkDescription" label="Uraian Keterangan"
                        placeholder="Contoh: Tiket Pesawat / Hotel" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Tarif Satuan (Rp)
                        <span class="text-rose-500">*</span></label>
                    <div class="relative" x-data="{ v: @entangle('bulkUnitCost') }">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">Rp</span>
                        <input type="text" inputmode="numeric" placeholder="0" :value="rupiah(v)"
                            @input="v = digits($event.target.value)" @keydown="blockNonDigit($event)"
                            class="w-full rounded border border-slate-300 pl-9 pr-3 py-2 font-mono text-sm tabular-nums outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    @error('bulkUnitCost') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-form.input type="number" wire:model="bulkQuantity" label="Jumlah (Item)" min="1" required />
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase text-slate-500">Pilih Pegawai Penerima</span>
                    <button type="button" wire:click="toggleSelectAll"
                        class="text-xs font-bold text-primary-600 hover:text-primary-800 transition cursor-pointer">
                        <span x-text="$wire.selectedUserIds.length === {{ $people->count() }} ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
                    </button>
                </div>
                <div class="max-h-48 overflow-y-auto border border-slate-200 rounded p-3 space-y-2 bg-slate-50/50">
                    @foreach ($people as $person)
                        <label class="flex items-center gap-2 cursor-pointer py-1 px-1.5 rounded hover:bg-slate-100 transition text-sm">
                            <input type="checkbox" wire:model.live="selectedUserIds" value="{{ $person['id'] }}"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="font-medium text-slate-700 uppercase">{{ $person['name'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedUserIds') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <x-ui.button type="button" variant="ghost" x-on:click="$wire.showBulkModal = false">Batal</x-ui.button>
                <x-ui.button type="button" variant="success" wire:click="saveBulk" wire:target="saveBulk"
                    wire:loading.attr="disabled" x-bind:disabled="$wire.selectedUserIds.length === 0">
                    Terapkan &amp; Simpan
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
