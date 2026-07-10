@extends('layouts.app')
@section('title', 'Rincian Biaya Perjalanan Dinas')

@section('content')
    @php
        $people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
        foreach ($sppd->followers as $f) {
            $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
        }
        $categories = \App\Enums\CostCategory::cases();
    @endphp

    <div class="p-1 space-y-6" x-data="{
        printDate: '{{ date('Y-m-d') }}',
        showAddModal: false,
        showEditModal: false,
        showBulkModal: false,

        addUserId: '',
        addUserName: '',

        editCostId: null,
        editCategory: '',
        editDescription: '',
        editUnitCost: '',
        editQuantity: '',
        editActionUrl: '',

        bulkCategory: '{{ $categories[0]->value ?? '' }}',
        bulkDescription: '',
        bulkUnitCost: '',
        bulkQuantity: 1,
        selectedUserIds: [
            @foreach ($people as $person) '{{ $person['id'] }}', @endforeach
        ],
        allUsers: [
            @foreach ($people as $person) { id: '{{ $person['id'] }}', name: {{ json_encode($person['name']) }} }, @endforeach
        ],

        toggleSelectAll() {
            if (this.selectedUserIds.length === this.allUsers.length) {
                this.selectedUserIds = [];
            } else {
                this.selectedUserIds = this.allUsers.map(u => u.id);
            }
        },

        openAdd(userId, userName) {
            this.addUserId = userId;
            this.addUserName = userName;
            this.showAddModal = true;
        },

        openEdit(cost) {
            this.editCostId = cost.id;
            this.editCategory = cost.cost_category;
            this.editDescription = cost.description;
            this.editUnitCost = cost.unit_cost;
            this.editQuantity = cost.quantity;
            this.editActionUrl = '{{ url('sppd/' . $sppd->id . '/cost-details') }}/' + cost.id;
            this.showEditModal = true;
        }
    }">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-11 shrink-0 items-center justify-center rounded bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                    <i class="fa-solid fa-calculator text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Rincian Biaya Perjalanan</h1>
                    <p class="mt-0.5 text-sm text-slate-500">Rinci komponen biaya perjalanan dan cetak rinciannya per personel.</p>
                </div>
            </div>
            <x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="secondary" class="shrink-0">
                <x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
                Kembali
            </x-ui.button>
        </div>

        {{-- Alert Info --}}
        @if (!$sppd->pptk_id || !$bendahara)
            <div class="flex items-start gap-4 rounded border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase text-amber-900">Pembatasan Cetak</h4>
                    <p class="mt-1 text-[11px] font-medium text-amber-800">Cetak rincian biaya tidak tersedia karena:</p>
                    <ul class="mt-2 space-y-1 text-[11px] text-amber-800">
                        @if (!$sppd->pptk_id)
                            <li><i class="fa-solid fa-circle-xmark mr-1"></i> PPTK belum diatur.</li>
                        @endif
                        @if (!$bendahara)
                            <li><i class="fa-solid fa-circle-xmark mr-1"></i> Bendahara Pengeluaran belum tersedia di OPD.
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

        {{-- Toolbar: Tanggal Cetak & Input Massal --}}
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded border border-slate-200 shadow-sm">
            {{-- Kiri: Info jumlah + Pilih Tanggal Cetak --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                    <i class="fa-solid fa-people-group text-emerald-600 text-sm"></i>
                    <span>Total: {{ $people->count() }} Pegawai</span>
                </div>

                <div class="hidden sm:block h-4 w-px bg-slate-300"></div>

                <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                    <i class="fa-solid fa-calendar-day text-emerald-600 text-sm"></i>
                    <span class="text-slate-500">Tanggal Cetak:</span>
                    <input type="date" x-model="printDate"
                        class="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:ring-emerald-500 shadow-sm" />
                </div>
            </div>
            {{-- Kanan: Tombol Input Sekaligus --}}
            @if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
            <div>
                <button type="button" @click="showBulkModal = true"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 transition shadow-sm cursor-pointer hover:scale-[1.02] active:scale-[0.98]">
                    <i class="fa-solid fa-layer-group text-slate-500"></i>
                    Input Sekaligus
                </button>
            </div>
            @endif
        </div>

        {{-- Table Section --}}
        <div class="overflow-x-auto rounded border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr
                        class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
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
                            <td class="py-3 px-4 text-center font-semibold text-slate-500">
                                {{ $index + 1 }}.
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center flex-wrap gap-2">
                                        @if ($person['label'] === 'Pelaksana')
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                Pelaksana
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                                Pengikut
                                            </span>
                                        @endif
                                        <span class="font-bold text-slate-800 uppercase">{{ $person['name'] }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="space-y-1.5 max-w-xl">
                                    @forelse($costs as $cost)
                                        <div
                                            class="group flex items-center justify-between bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded px-2.5 py-1.5 transition-all">
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="bg-slate-200 px-1.5 py-0.5 rounded text-[9px]">{{ $cost->cost_category->label() }}</span>
                                                    <span
                                                        class="text-slate-700 font-medium">{{ $cost->description }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span
                                                        class="text-[10px] text-slate-500 font-mono">{{ $cost->quantity }}
                                                        Item x Rp {{ number_format($cost->unit_cost, 0, ',', '.') }} =
                                                        <span class="font-bold text-slate-600">Rp
                                                            {{ number_format($cost->total, 0, ',', '.') }}</span></span>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                @if ($cost->receipt_photo)
                                                    <a href="{{ asset('storage/' . $cost->receipt_photo) }}"
                                                        target="_blank" class="text-emerald-600 p-0.5 cursor-pointer"
                                                        title="Lihat Bukti/Nota">
                                                        <i class="fa-solid fa-image text-[10px]"></i>
                                                    </a>
                                                @endif
                                                @if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
                                                <button type="button" @click='openEdit(@json($cost))'
                                                    class="text-amber-600 hover:text-amber-800 p-0.5 cursor-pointer"
                                                    title="Edit">
                                                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                                </button>
                                                <form action="{{ route('sppd.cost-details.destroy', [$sppd, $cost]) }}"
                                                    method="POST" onsubmit="return confirm('Hapus biaya ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-rose-600 hover:text-rose-800 p-0.5 cursor-pointer"
                                                        title="Hapus">
                                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-slate-500 italic text-[11px] pl-1">Belum ada data biaya</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span
                                    class="font-bold {{ $total > 0 ? 'text-emerald-700 font-mono text-xs' : 'text-slate-500' }}">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
                                    <button type="button"
                                        @click="openAdd('{{ $person['id'] }}', {{ json_encode($person['name']) }})"
                                        class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2 py-1 text-[10px] font-bold text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer hover:scale-[1.03] active:scale-[0.97]">
                                        <i class="fa-solid fa-plus text-[8px]"></i> Tambah
                                    </button>
                                    @endif
                                    @if ($costs->count() > 0)
                                        <a
                                            :href="'{{ route('sppd.stream.rincian-biaya', ['sppd' => $sppd, 'user_id' => $person['id']]) }}' +
                                            '&date=' + printDate"
                      target="_blank"
                      class="inline-flex items-center gap-1 rounded bg-slate-600 px-2 py-1 text-[10px] font-bold text-white hover:bg-slate-700 shadow-xs transition {{ !$sppd->pptk_id || !$bendahara ? 'opacity-50 pointer-events-none cursor-not-allowed' : 'hover:scale-[1.03] active:scale-[0.97]' }}">
                      <i class="fa-solid fa-print text-[8px]"></i> Cetak
                    </a>
@else
<button type="button" disabled
                      class="inline-flex items-center gap-1 rounded bg-slate-100 border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-500 cursor-not-allowed">
                      <i class="fa-solid fa-lock text-[8px]"></i> Cetak
                    </button>
@endif
                </div>
              </td>
            </tr>
@endforeach
        </tbody>
      </table>
    </div>

    {{-- Modal Tambah Biaya (Per Pegawai) --}}
    <x-ui.modal show="showAddModal" title="Tambah Rincian Biaya" icon="fa-solid fa-plus text-emerald-600" maxWidth="max-w-lg">
      <div class="mb-3 rounded bg-slate-50 px-3 py-2 border border-slate-100 text-xs font-semibold text-slate-600">
        Pegawai: <span class="font-bold text-slate-800 uppercase" x-text="addUserName"></span>
      </div>
      <form method="POST" action="{{ route('sppd.cost-details.store', $sppd) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="user_id" :value="addUserId">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <x-form.select name="cost_category" label="Kategori Biaya" required>
              @foreach ($categories as $cat)
<option value="{{ $cat->value }}">{{ $cat->label() }}</option>
@endforeach
            </x-form.select>
          </div>
          <div class="col-span-2">
            <x-form.input name="description" label="Uraian Keterangan" placeholder="Contoh: Tiket Pesawat / Hotel" required />
          </div>
          <x-form.input type="number" name="unit_cost" label="Tarif Satuan (Rp)" min="0" required />
          <x-form.input type="number" name="quantity" label="Jumlah (Item)" min="1" value="1" required />
          <div class="col-span-2">
            <x-form.file name="receipt_photo" label="Lampiran Bukti/Nota" hint="Max 20MB" accept="image/*" />
          </div>
        </div>
        <div class="mt-4 flex justify-end gap-3 border-t border-slate-100 pt-3">
          <button type="button" @click="showAddModal = false"
            class="rounded px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 transition cursor-pointer">Batal</button>
          <button type="submit"
            class="rounded bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition cursor-pointer">Simpan
            Biaya</button>
        </div>
      </form>
    </x-ui.modal>

    {{-- Modal Edit Biaya --}}
    <x-ui.modal show="showEditModal" title="Edit Rincian Biaya" icon="fa-solid fa-pen-to-square text-amber-600" maxWidth="max-w-lg">
      <form :action="editActionUrl" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <x-form.select name="cost_category" label="Kategori Biaya" required>
              @foreach ($categories as $cat)
<option value="{{ $cat->value }}" x-bind:selected="editCategory === '{{ $cat->value }}
                                                '">{{ $cat->label() }}
                                            </option>
                                    @endforeach
                                    </x-form.select>
                                </div>
                                <div class="col-span-2">
                                    <x-form.input name="description" label="Uraian Keterangan" required
                                        x-model="editDescription" />
                                </div>
                                <x-form.input type="number" name="unit_cost" label="Tarif Satuan (Rp)" min="0"
                                    required x-model="editUnitCost" />
                                <x-form.input type="number" name="quantity" label="Jumlah (Item)" min="1" required
                                    x-model="editQuantity" />
                                <div class="col-span-2">
                                    <x-form.file name="receipt_photo" label="Upload Bukti Baru" hint="Opsional"
                                        accept="image/*" />
                                </div>
        </div>
        <div class="mt-4 flex justify-end gap-3 border-t border-slate-100 pt-3">
            <button type="button" @click="showEditModal = false"
                class="rounded px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 transition cursor-pointer">Batal</button>
            <button type="submit"
                class="rounded bg-amber-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-amber-700 shadow-sm transition cursor-pointer">Update
                Perubahan</button>
        </div>
        </form>
        </x-ui.modal>

        {{-- Modal Input Biaya Sekaligus --}}
        <x-ui.modal show="showBulkModal" title="Input Biaya Sekaligus" icon="fa-solid fa-layer-group text-emerald-600"
            maxWidth="max-w-xl">
            <div class="space-y-4">
                <div class="rounded bg-slate-50 p-3 border border-slate-200 text-[11px] text-slate-600 leading-relaxed">
                    <p class="font-bold uppercase mb-1">
                        <i class="fa-solid fa-circle-info mr-1 text-slate-500"></i> Petunjuk:
                    </p>
                    Rincian biaya ini akan ditambahkan ke **semua pegawai yang dicentang**. Anda dapat memilih atau
                    membatalkan
                    pilihan pegawai di bawah.
                </div>

                <form method="POST" action="{{ route('sppd.cost-details.store', $sppd) }}">
                    @csrf
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="col-span-2">
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Kategori Biaya</label>
                            <select name="cost_category" x-model="bulkCategory"
                                class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Uraian
                                Keterangan</label>
                            <input type="text" name="description" x-model="bulkDescription"
                                class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Contoh: Tiket Pesawat / Hotel" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Tarif Satuan
                                (Rp)</label>
                            <input type="number" name="unit_cost" x-model="bulkUnitCost"
                                class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="0" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Jumlah (Item)</label>
                            <input type="number" name="quantity" x-model="bulkQuantity" min="1"
                                class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase text-slate-500">Pilih Pegawai Penerima</span>
                            <button type="button" @click="toggleSelectAll()"
                                class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800 transition cursor-pointer">
                                <span
                                    x-text="selectedUserIds.length === allUsers.length ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <div
                            class="max-h-48 overflow-y-auto border border-slate-200 rounded p-3 space-y-2 bg-slate-50/50">
                            <template x-for="user in allUsers" :key="user.id">
                                <label
                                    class="flex items-center gap-2 cursor-pointer py-1 px-1.5 rounded hover:bg-slate-100 transition text-xs">
                                    <input type="checkbox" name="user_ids[]" :value="user.id"
                                        x-model="selectedUserIds"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700 uppercase" x-text="user.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button"
                            @click="showBulkModal = false; bulkDescription = ''; bulkUnitCost = ''; bulkQuantity = 1"
                            class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" :disabled="selectedUserIds.length === 0"
                            class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            Terapkan & Simpan
                        </button>
                    </div>
                </form>
            </div>
        </x-ui.modal>

    </div>
@endsection
