@extends('layouts.app')
@section('title', 'Kelola SPPD')

@section('content')
<div class="p-1 space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
        <i class="fa-solid fa-file-contract mr-2 text-emerald-600"></i>Kelola SPPD
      </h1>
    </div>
    <a href="{{ route('sppd.next', $sppd) }}" class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

      {{-- Daftar Personel --}}
      <div class="space-y-3">
        <p class="text-[10px] font-bold uppercase text-slate-400 mb-2">Daftar Pelaksana & Pengikut</p>

        {{-- Main Pelaksana --}}
        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-100 rounded-lg">
          <div class="flex items-center gap-3">
            <span class="text-xs font-bold text-slate-400 w-20">PELAKSANA:</span>
            <span class="text-sm font-bold text-slate-800">{{ $sppd->user->name }}</span>
          </div>
          <a href="{{ route('sppd.stream.sppd', $sppd) }}" target="_blank"
            class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-cyan-700">
            <i class="fa-solid fa-print"></i> CETAK
          </a>
        </div>

        {{-- Pengikut --}}
        @foreach ($sppd->followers as $f)
          <div class="flex items-center justify-between p-3 bg-white border border-slate-100 rounded-lg">
            <div class="flex items-center gap-3">
              <span class="text-xs font-bold text-slate-400 w-20">PENGIKUT:</span>
              <span class="text-sm font-semibold text-slate-700">{{ $f->user->name }}</span>
            </div>
            <a href="{{ route('sppd.stream.sppd', ['sppd' => $sppd->id, 'user_id' => $f->user_id]) }}" target="_blank"
              class="inline-flex items-center gap-1.5 rounded bg-slate-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-slate-700">
              <i class="fa-solid fa-print"></i> CETAK
            </a>
          </div>
        @endforeach
      </div>

      {{-- Status TTE --}}
      <div class="space-y-4">
        <div class="flex justify-between items-center py-2 border-b border-slate-100">
          <span class="text-xs font-bold text-slate-400 uppercase">Tanggal SPPD</span>
          <span class="text-sm font-bold text-slate-800">{{ $sppd->sppd_date?->translatedFormat('d F Y') ?? $sppd->created_at->translatedFormat('d F Y') }}</span>
        </div>

        @php $sppdSignature = $sppd->signatureFor('sppd'); @endphp

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase">Status TTE SPPD</p>
              <p class="text-sm font-bold text-slate-800 mt-1">
                {{ $sppdSignature ? $sppdSignature->status->label() : 'Belum Diproses' }}
              </p>
            </div>

            @if ($sppdSignature)
              <form action="{{ route('sppd.reset-tte', ['sppd' => $sppd->id, 'type' => 'sppd']) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded bg-rose-100 px-3 py-1.5 text-[10px] font-bold text-rose-700 hover:bg-rose-200 transition">
                  <i class="fa-solid fa-rotate-left"></i> RESET TTE
                </button>
              </form>
            @endif
          </div>

          @if ($sppdSignature?->signed_file_path)
            <div class="mt-4">
              <a href="{{ route('sppd.sign.download', ['sppd' => $sppd->id, 'signature' => $sppdSignature->id]) }}"
                class="inline-flex items-center gap-2 rounded bg-emerald-600 px-3 py-2 text-[11px] font-bold text-white hover:bg-emerald-700 transition">
                <i class="fa-solid fa-file-pdf"></i> DOWNLOAD PDF TTE
              </a>
            </div>
          @endif

          @if ($sppdSignature?->error_message)
            <p class="mt-2 text-[10px] text-rose-600 font-medium">
              <i class="fa-solid fa-circle-exclamation mr-1"></i> Error: {{ $sppdSignature->error_message }}
            </p>
          @endif
        </div>
      </div>
    </div>

    {{-- Footer Note --}}
    <div class="bg-slate-50 border-t border-slate-100 p-4 flex items-center gap-3 text-[11px] text-slate-500 italic">
      <i class="fa-solid fa-circle-info text-slate-400"></i>
      Sistem menghasilkan dokumen PDF yang sudah siap cetak atau ditandatangani secara elektronik.
    </div>
  </div>
</div>
@endsection
