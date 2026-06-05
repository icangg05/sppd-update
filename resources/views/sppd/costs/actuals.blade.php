@extends('layouts.app')
@section('title', 'Laporan Pengeluaran Rill')

@section('content')
  <div class="p-1 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
      <div>
        <h1
          class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
          <i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>Laporan Pengeluaran Rill
        </h1>
      </div>
      <a wire:navigate href="{{ route('sppd.next', $sppd) }}"
        class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>

    {{-- PPTK Selection --}}
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <p class="text-[10px] font-bold uppercase text-slate-400">Pejabat Pelaksana Teknis Kegiatan (PPTK)</p>
          @if ($sppd->pptk_id)
            <p class="text-sm font-bold text-slate-800 mt-1">{{ $sppd->pptk->name }} <span
                class="text-slate-400 font-normal">{{ $sppd->pptk->nip ? '— NIP. ' . $sppd->pptk->nip : '' }}</span></p>
          @else
            <p class="text-sm font-bold text-rose-600 mt-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Belum
              diatur</p>
          @endif
        </div>
        <form action="{{ route('sppd.update-pptk', $sppd) }}" method="POST" class="flex items-center gap-2">
          @csrf @method('PUT')
          <select name="pptk_id"
            class="rounded border border-slate-300 px-3 py-1.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            required>
            <option value="">-- Pilih PPTK --</option>
            @foreach ($pptkCandidates as $candidate)
              <option value="{{ $candidate->id }}">{{ $candidate->name }}
                {{ $candidate->nip ? '(' . $candidate->nip . ')' : '' }}</option>
            @endforeach
          </select>
          <button type="submit"
            class="rounded bg-slate-800 px-4 py-1.5 text-sm font-bold text-white hover:bg-slate-900 transition">
            <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan
          </button>
        </form>
      </div>
    </div>

    {{-- Alert Info --}}
    <div class="flex items-start gap-3 rounded-lg border border-cyan-200 bg-cyan-50 p-4 text-[11px] text-cyan-800">
      <i class="fa-solid fa-circle-info mt-0.5 text-cyan-600"></i>
      <p>Pengaturan <strong>PPTK</strong> wajib dilakukan sebelum Anda dapat mencetak dokumen Laporan Pengeluaran Rill.
      </p>
    </div>

    {{-- Expenses Section --}}
    @php
      $people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
      foreach ($sppd->followers as $f) {
        $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
      }
    @endphp

    @foreach ($people as $person)
      @php
        $expenses = $sppd->actualExpenses->where('user_id', $person['id']);
        $total = $expenses->sum('amount');
      @endphp
      <div class="rounded border border-slate-200 bg-white overflow-hidden shadow-sm">
        <div class="flex items-center justify-between bg-slate-50 px-5 py-4 border-b border-slate-200">
          <p class="text-sm font-bold text-slate-800 uppercase">{{ $person['label'] }}: {{ $person['name'] }}</p>
          <div class="flex gap-2">
            <button onclick="openExpenseModal('{{ $person['id'] }}', '{{ $person['name'] }}')"
              class="inline-flex items-center gap-1.5 rounded bg-emerald-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-emerald-700 transition">
              <i class="fa-solid fa-plus"></i> Tambah Data
            </button>

            @if ($expenses->count() > 0)
              <a wire:navigate href="{{ route('sppd.stream.pengeluaran-riil', ['sppd' => $sppd, 'user_id' => $person['id']]) }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 rounded bg-slate-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-slate-700 transition {{ !$sppd->pptk_id ? 'opacity-50 cursor-not-allowed' : '' }}">
                <i class="fa-solid fa-print"></i> Cetak Data
              </a>
            @endif
          </div>
        </div>

        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-[10px] uppercase text-slate-400">
            <tr>
              <th class="py-3 px-5 text-left">No</th>
              <th class="py-3 px-5 text-left">Uraian</th>
              <th class="py-3 px-5 text-right">Tarif</th>
              <th class="py-3 px-5 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($expenses as $i => $expense)
              <tr>
                <td class="py-3 px-5">{{ $loop->iteration }}</td>
                <td class="py-3 px-5">{{ $expense->description }}</td>
                <td class="py-3 px-5 text-right font-medium">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                <td class="py-3 px-5 text-center">
                  <div class="inline-flex gap-2">
                    <button
                      onclick="openEditExpenseModal('{{ $expense->id }}', '{{ $expense->description }}', '{{ $expense->amount }}')"
                      class="text-amber-600 hover:text-amber-800"><i class="fa-solid fa-pen-to-square"></i></button>
                    <form action="{{ route('sppd.actual-expenses.destroy', [$sppd, $expense]) }}" method="POST"
                      onsubmit="return confirm('Hapus data?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="text-rose-600 hover:text-rose-800"><i
                          class="fa-solid fa-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="py-8 text-center text-slate-400 text-xs italic">Belum ada data pengeluaran</td>
              </tr>
            @endforelse
          </tbody>
          @if ($total > 0)
            <tfoot class="bg-slate-50 border-t border-slate-200">
              <tr>
                <td colspan="2" class="py-3 px-5 font-bold text-right">TOTAL</td>
                <td class="py-3 px-5 text-right font-bold text-emerald-700">Rp {{ number_format($total, 0, ',', '.') }}</td>
                <td></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    @endforeach
  </div>

  {{-- LETAKKAN DI PALING BAWAH, TEPAT SEBELUM @endsection --}}

  {{-- Modal Tambah --}}
  <div id="expenseModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
      <h3 class="mb-1 text-lg font-bold text-slate-800">Tambah Pengeluaran Riil</h3>
      <p id="expenseUserName" class="mb-4 text-sm text-slate-500"></p>

      <form method="POST" action="{{ route('sppd.actual-expenses.store', $sppd) }}">
        @csrf
        <input type="hidden" name="user_id" id="expenseUserId">
        <div class="mb-4">
          <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Uraian</label>
          <input type="text" name="description" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
            placeholder="Contoh: Tiket pesawat" required>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Tarif (Rp)</label>
          <input type="number" name="amount" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
            placeholder="0" required>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeExpenseModal()"
            class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
          <button type="submit"
            class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal Edit --}}
  <div id="editExpenseModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
      <h3 class="mb-4 text-lg font-bold text-slate-800">Edit Pengeluaran Riil</h3>
      <form id="editExpenseForm" method="POST">
        @csrf @method('PUT')
        <div class="mb-4">
          <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Uraian</label>
          <input type="text" name="description" id="editExpenseDesc"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Tarif (Rp)</label>
          <input type="number" name="amount" id="editExpenseAmount"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeEditExpenseModal()"
            class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
          <button type="submit"
            class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Simpan</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    // Pastikan fungsi ini tersedia secara global
    window.openExpenseModal = function (userId, userName) {
      const modal = document.getElementById('expenseModal');
      if (modal) {
        document.getElementById('expenseUserId').value = userId;
        document.getElementById('expenseUserName').textContent = userName;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      } else {
        console.error("Modal dengan ID 'expenseModal' tidak ditemukan di DOM!");
      }
    };

    window.closeExpenseModal = function () {
      const modal = document.getElementById('expenseModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    };

    // Tambahan untuk Edit Modal
    window.openEditExpenseModal = function (expenseId, desc, amount) {
      document.getElementById('editExpenseDesc').value = desc;
      document.getElementById('editExpenseAmount').value = amount;
      // Gunakan URL helper yang benar
      document.getElementById('editExpenseForm').action = "{{ url('sppd/' . $sppd->id . '/actual-expenses') }}/" + expenseId;
      document.getElementById('editExpenseModal').classList.remove('hidden');
      document.getElementById('editExpenseModal').classList.add('flex');
    };

    window.closeEditExpenseModal = function () {
      document.getElementById('editExpenseModal').classList.add('hidden');
      document.getElementById('editExpenseModal').classList.remove('flex');
    };
  </script>
@endpush
