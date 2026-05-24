@extends('layouts.app')
@section('title', 'Rincian Biaya Perjalanan Dinas')

@section('content')
  <div class="p-1 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
      <div>
        <h1
          class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
          <i class="fa-solid fa-calculator mr-2 text-emerald-600"></i>Rincian Biaya Perjalanan
        </h1>
      </div>
      <a href="{{ route('sppd.next', $sppd) }}"
        class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>

    {{-- Alert Info --}}
    @if (!$sppd->pptk_id || !$bendahara)
      <div class="flex items-start gap-4 rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
          <i class="fa-solid fa-triangle-exclamation text-lg"></i>
        </div>
        <div>
          <h4 class="text-xs font-bold uppercase text-amber-900">Pembatasan Cetak</h4>
          <p class="mt-1 text-[11px] font-medium text-amber-800">Cetak rincian biaya tidak tersedia karena:</p>
          <ul class="mt-2 space-y-1 text-[11px] text-amber-800">
            @if (!$sppd->pptk_id)
            <li><i class="fa-solid fa-circle-xmark mr-1"></i> PPTK belum diatur.</li> @endif
            @if (!$bendahara)
            <li><i class="fa-solid fa-circle-xmark mr-1"></i> Bendahara Pengeluaran belum tersedia di OPD.</li> @endif
          </ul>
        </div>
      </div>
    @endif

    @php
      $people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
      foreach ($sppd->followers as $f) {
        $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
      }
      $categories = \App\Enums\CostCategory::cases();
    @endphp

    @foreach ($people as $person)
      @php
        $costs = $sppd->costDetails->where('user_id', $person['id']);
        $grandTotal = $costs->sum('total');
      @endphp
      <div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between bg-slate-50 px-5 py-4 border-b border-slate-200">
          <p class="text-sm font-bold text-slate-800 uppercase">{{ $person['label'] }}: {{ $person['name'] }}</p>
          <div class="flex gap-2">
            <button onclick="openCostModal('{{ $person['id'] }}', '{{ $person['name'] }}')"
              class="inline-flex items-center gap-1.5 rounded bg-emerald-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-emerald-700 transition">
              <i class="fa-solid fa-plus"></i> Tambah Biaya
            </button>
            @if ($costs->count() > 0)
              <a href="{{ route('sppd.stream.rincian-biaya', ['sppd' => $sppd, 'user_id' => $person['id']]) }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 rounded bg-slate-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-slate-700 transition {{ (!$sppd->pptk_id || !$bendahara) ? 'opacity-50 cursor-not-allowed' : '' }}">
                <i class="fa-solid fa-print"></i> Cetak Data
              </a>
            @endif
          </div>
        </div>

        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-[10px] uppercase text-slate-400">
            <tr>
              <th class="py-3 px-4 text-left">No.</th>
              <th class="py-3 px-4 text-left">Kategori</th>
              <th class="py-3 px-4 text-left">Keterangan</th>
              <th class="py-3 px-4 text-center">Item</th>
              <th class="py-3 px-4 text-right">Tarif</th>
              <th class="py-3 px-4 text-right">Total</th>
              <th class="py-3 px-4 text-center">Bukti</th>
              <th class="py-3 px-4 text-center">#</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($costs as $cost)
              <tr>
                {{-- Tambahkan kolom nomor di sini --}}
                <td class="py-3 px-4">{{ $loop->iteration }}.</td>

                <td class="py-3 px-4"><span
                    class="bg-slate-100 px-2 py-0.5 rounded text-[10px]">{{ $cost->cost_category->label() }}</span></td>
                <td class="py-3 px-4">
                  {{ $cost->description }}
                  @if($cost->airline_name)
                    <div class="text-[10px] text-slate-400">Maskapai: {{ $cost->airline_name }} | Tiket:
                      {{ $cost->ticket_number }}
                  </div>@endif
                </td>
                <td class="py-3 px-4 text-center">{{ $cost->quantity }}</td>
                <td class="py-3 px-4 text-right">Rp {{ number_format($cost->unit_cost, 0, ',', '.') }}</td>
                <td class="py-3 px-4 text-right font-bold">Rp {{ number_format($cost->total, 0, ',', '.') }}</td>
                <td class="py-3 px-4 text-center">
                  @if($cost->receipt_photo)
                    <a href="{{ asset('storage/' . $cost->receipt_photo) }}" target="_blank" class="text-emerald-600"><i
                        class="fa-solid fa-image"></i></a>
                  @else <span class="text-slate-300"><i class="fa-solid fa-ban"></i></span> @endif
                </td>
                <td class="py-3 px-4 text-center">
                  <div class="inline-flex gap-2">
                    <button onclick='openEditCostModal(@json($cost))' class="text-amber-600 hover:text-amber-800"><i
                        class="fa-solid fa-pen-to-square"></i></button>
                    <form action="{{ route('sppd.cost-details.destroy', [$sppd, $cost]) }}" method="POST"
                      onsubmit="return confirm('Hapus?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="text-rose-600 hover:text-rose-800"><i
                          class="fa-solid fa-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                {{-- Update colspan menjadi 8 karena ada tambahan kolom nomor --}}
                <td colspan="8" class="py-8 text-center text-slate-400 text-xs italic">Belum ada data biaya.</td>
              </tr>
            @endforelse
          </tbody>
          @if($grandTotal > 0)
            <tfoot class="bg-slate-50 border-t border-slate-200">
              <tr>
                <td colspan="4" class="py-3 px-4 font-bold text-right">GRAND TOTAL</td>
                <td class="py-3 px-4 text-right font-bold text-emerald-700">Rp {{ number_format($grandTotal, 0, ',', '.') }}
                </td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    @endforeach
  </div>

  {{-- Modal Tambah & Edit Biaya --}}
  <div id="costModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl animate-in fade-in zoom-in duration-300">
      <div class="flex items-center gap-3 mb-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
          <i class="fa-solid fa-plus"></i>
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-800">Tambah Rincian Biaya</h3>
          <p id="costUserName" class="text-[11px] font-medium text-slate-400 uppercase tracking-wider"></p>
        </div>
      </div>

      <form method="POST" action="{{ route('sppd.cost-details.store', $sppd) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="user_id" id="costUserId">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <x-form.select name="cost_category" label="Kategori Biaya" required>
              @foreach ($categories as $cat)
                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
              @endforeach
            </x-form.select>
          </div>
          <div class="col-span-2">
            <x-form.input name="description" label="Uraian Keterangan" placeholder="Contoh: Tiket Pesawat / Hotel"
              required />
          </div>
          <x-form.input name="airline_name" label="Nama Maskapai" placeholder="Opsional" />
          <x-form.input name="ticket_number" label="No. Tiket/Nota" placeholder="Opsional" />
          <x-form.input type="number" name="unit_cost" label="Tarif Satuan (Rp)" min="0" required />
          <x-form.input type="number" name="quantity" label="Jumlah (Item)" min="1" value="1" required />
          <div class="col-span-2">
            <x-form.file name="receipt_photo" label="Lampiran Bukti/Nota" hint="Max 20MB" accept="image/*" />
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button type="button" onclick="closeCostModal()"
            class="rounded-lg px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 transition">Batal</button>
          <button type="submit"
            class="rounded-lg bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 shadow-md transition">Simpan
            Biaya</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal Edit (Mirip dengan di atas) --}}
  <div id="editCostModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl animate-in fade-in zoom-in duration-300">
      <div class="flex items-center gap-3 mb-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
          <i class="fa-solid fa-pen-to-square"></i>
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-800">Edit Rincian Biaya</h3>
          <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Ubah data transaksi</p>
        </div>
      </div>

      <form id="editCostForm" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <x-form.select name="cost_category" id="editCostCategory" label="Kategori Biaya" required>
              @foreach ($categories as $cat)
                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
              @endforeach
            </x-form.select>
          </div>
          <div class="col-span-2">
            <x-form.input name="description" id="editCostDesc" label="Uraian Keterangan" required />
          </div>
          <x-form.input name="airline_name" id="editCostAirline" label="Nama Maskapai" />
          <x-form.input name="ticket_number" id="editCostTicket" label="No. Tiket/Nota" />
          <x-form.input type="number" name="unit_cost" id="editCostUnitCost" label="Tarif Satuan (Rp)" min="0" required />
          <x-form.input type="number" name="quantity" id="editCostQty" label="Jumlah (Item)" min="1" required />
          <div class="col-span-2">
            <x-form.file name="receipt_photo" label="Upload Bukti Baru" hint="Opsional" accept="image/*" />
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button type="button" onclick="closeEditCostModal()"
            class="rounded-lg px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 transition">Batal</button>
          <button type="submit"
            class="rounded-lg bg-amber-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-amber-700 shadow-md transition">Update
            Perubahan</button>
        </div>
      </form>
    </div>
  </div>

  @push('scripts')
    <script>
      // Pastikan fungsi ini bisa diakses dari mana saja
      window.openCostModal = function (userId, userName) {
        const modal = document.getElementById('costModal');
        if (modal) {
          document.getElementById('costUserId').value = userId;
          document.getElementById('costUserName').textContent = userName;
          modal.classList.remove('hidden');
          modal.classList.add('flex');
        }
      };

      window.closeCostModal = function () {
        const modal = document.getElementById('costModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      };

      window.openEditCostModal = function (cost) {
        const modal = document.getElementById('editCostModal');
        if (modal) {
          document.getElementById('editCostCategory').value = cost.cost_category;
          document.getElementById('editCostDesc').value = cost.description;
          document.getElementById('editCostAirline').value = cost.airline_name || '';
          document.getElementById('editCostTicket').value = cost.ticket_number || '';
          document.getElementById('editCostUnitCost').value = cost.unit_cost;
          document.getElementById('editCostQty').value = cost.quantity;

          // Set form action dengan URL yang benar
          document.getElementById('editCostForm').action = "{{ url('sppd/' . $sppd->id . '/cost-details') }}/" + cost.id;

          modal.classList.remove('hidden');
          modal.classList.add('flex');
        }
      };

      window.closeEditCostModal = function () {
        const modal = document.getElementById('editCostModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      };
    </script>
  @endpush
@endsection
