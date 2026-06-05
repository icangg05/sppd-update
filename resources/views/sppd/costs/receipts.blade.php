@extends('layouts.app')
@section('title', 'Kuitansi')

@section('content')
<div class="p-1 space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
        <i class="fa-solid fa-file-invoice-dollar mr-2 text-emerald-600"></i>Kuitansi Perjalanan
      </h1>
    </div>
    <a wire:navigate href="{{ route('sppd.next', $sppd) }}" class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  @php
    $people = collect([['id' => $sppd->user->id, 'name' => $sppd->user->name, 'label' => 'Pelaksana']]);
    foreach ($sppd->followers as $f) {
        $people->push(['id' => $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
    }
    $hasExpenses = $sppd->actualExpenses->count() > 0 || $sppd->costDetails->count() > 0;
    $hasBendahara = $bendahara !== null;
  @endphp

  {{-- Alert Bendahara --}}
  @if (!$hasBendahara)
    <div class="rounded-lg border-l-4 border-amber-500 bg-amber-50 p-4 text-amber-800 text-xs font-medium">
      <p class="font-bold uppercase"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Bendahara Belum Ditetapkan</p>
      <p class="mt-1">Instansi <strong>{{ $sppd->user->department->name ?? '-' }}</strong> memerlukan pegawai dengan role "Bendahara Pengeluaran" untuk mencetak kuitansi.</p>
    </div>
  @endif

  {{-- Daftar Personel --}}
  <div class="space-y-4">
    @foreach ($people as $person)
      @php
        $receipt = $sppd->advanceReceipts->where('user_id', $person['id'])->first();
        $canPrint = $hasExpenses && $hasBendahara;
      @endphp

      <div class="rounded border border-slate-200 bg-white p-5 shadow-md transition hover:border-slate-300">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <p class="text-[10px] font-bold uppercase text-slate-400">{{ $person['label'] }}</p>
            <p class="text-sm font-bold text-slate-800 uppercase mt-0.5">{{ $person['name'] }}</p>
            @if ($receipt && $receipt->amount > 0)
              <div class="mt-2 flex items-center gap-3 text-xs bg-slate-50 px-2 py-1 rounded border border-slate-100">
                <span class="text-slate-500"><i class="fa-solid fa-hashtag"></i> {{ $receipt->receipt_number }}</span>
                <span class="font-bold text-emerald-600"><i class="fa-solid fa-money-bill-wave"></i> Rp {{ number_format($receipt->amount, 0, ',', '.') }}</span>
              </div>
            @endif
          </div>

          <div class="flex flex-wrap gap-2">
            {{-- Tombol Panjar --}}
            <button onclick="openPanjarModal('{{ $person['id'] }}', '{{ $person['name'] }}', '{{ $receipt?->amount ?? 0 }}')"
              class="inline-flex items-center gap-1.5 rounded bg-emerald-600 px-3 py-2 text-[10px] font-bold text-white hover:bg-emerald-700 transition">
              <i class="fa-solid {{ ($receipt && $receipt->amount > 0) ? 'fa-pen-to-square' : 'fa-plus' }}"></i>
              {{ ($receipt && $receipt->amount > 0) ? 'Edit Panjar' : 'Input Panjar' }}
            </button>

            {{-- Cetak Panjar --}}
            @if ($receipt && $receipt->amount > 0 && $hasBendahara)
              <a wire:navigate href="{{ route('sppd.stream.kuitansi-panjar', ['sppd' => $sppd, 'user_id' => $person['id']]) }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded bg-amber-600 px-3 py-2 text-[10px] font-bold text-white hover:bg-amber-700 transition">
                <i class="fa-solid fa-print"></i> Cetak Panjar
              </a>
            @endif

            {{-- Cetak Rampung --}}
            @if ($canPrint)
              <a wire:navigate href="{{ route('sppd.stream.kuitansi-rampung', ['sppd' => $sppd, 'user_id' => $person['id']]) }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-2 text-[10px] font-bold text-white hover:bg-cyan-700 transition">
                <i class="fa-solid fa-file-invoice"></i> Cetak Rampung
              </a>
            @else
              <button disabled class="inline-flex items-center gap-1.5 rounded bg-slate-100 px-3 py-2 text-[10px] font-bold text-slate-400 cursor-not-allowed border border-slate-200">
                <i class="fa-solid fa-lock"></i> Cetak Rampung
              </button>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="flex items-start gap-4 rounded-lg border border-cyan-200 bg-cyan-50 p-4 shadow-sm">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
      <i class="fa-solid fa-circle-info text-lg"></i>
    </div>
    <div>
      <h4 class="text-xs font-bold uppercase text-cyan-900">Informasi Penting</h4>
      <p class="mt-1 text-[11px] font-medium text-cyan-800 leading-relaxed">
        Untuk dapat mencetak <strong>Kuitansi Rampung</strong>, pastikan seluruh data berikut telah dilengkapi:
      </p>
      <ul class="mt-2 flex flex-wrap gap-2 text-[11px] text-cyan-700">
        <li class="flex items-center gap-1.5"><i class="fa-solid fa-check-circle text-cyan-500"></i> Laporan Pengeluaran Rill</li>
        <li class="flex items-center gap-1.5"><i class="fa-solid fa-check-circle text-cyan-500"></i> Rincian Biaya Perjalanan</li>
        <li class="flex items-center gap-1.5"><i class="fa-solid fa-check-circle text-cyan-500"></i> Bendahara Pengeluaran Aktif</li>
      </ul>
    </div>
  </div>

  {{-- Modal Panjar --}}
  <div id="panjarModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
      <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Input Kuitansi Panjar</h3>
      <p id="panjarUserName" class="text-[11px] text-slate-500 mt-1"></p>

      <form id="panjarForm" method="POST" action="{{ route('sppd.advance-receipts.store', $sppd) }}" class="mt-4">
        @csrf
        <input type="hidden" name="user_id" id="panjarUserId">
        <div class="mb-4">
          <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Jumlah Panjar (Rp)</label>
          <input type="number" name="amount" id="panjarAmount" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0" required>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closePanjarModal()" class="rounded border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
          <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Simpan Data</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  <script>
    function openPanjarModal(userId, userName, currentAmount) {
      document.getElementById('panjarUserId').value = userId;
      document.getElementById('panjarUserName').textContent = userName;
      document.getElementById('panjarAmount').value = currentAmount > 0 ? currentAmount : '';
      document.getElementById('panjarModal').classList.replace('hidden', 'flex');
    }
    function closePanjarModal() {
      document.getElementById('panjarModal').classList.replace('flex', 'hidden');
    }
  </script>
@endpush
