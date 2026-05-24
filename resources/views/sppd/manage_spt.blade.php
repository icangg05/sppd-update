@extends('layouts.app')
@section('title', 'Kelola SPT')

@section('content')
<div class="p-1 space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
        <i class="fa-solid fa-file-signature mr-2 text-emerald-600"></i>Kelola SPT
      </h1>
    </div>
    <a href="{{ route('sppd.next', $sppd) }}" class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
    {{-- Info Card --}}
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

      <div class="space-y-6">
        {{-- Peringatan --}}
        <div class="rounded-lg border-l-4 border-rose-500 bg-rose-50 p-4">
          <p class="text-[11px] font-bold text-rose-800 uppercase tracking-wider mb-1">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Catatan Penting
          </p>
          <p class="text-xs text-rose-600 italic leading-relaxed">
            Jika file SPT tidak dapat diunduh atau barcode tidak muncul, silakan gunakan fitur Reset TTE di samping.
          </p>
        </div>

        <div>
          <p class="text-[10px] font-bold uppercase text-slate-400">Pelaksana Tugas</p>
          <p class="text-sm font-bold text-slate-800 mt-1 uppercase">{{ $sppd->user->name }}</p>
        </div>
      </div>

      <div class="space-y-4">
        <div class="flex justify-between items-center py-2 border-b border-slate-100">
          <span class="text-xs font-bold text-slate-400 uppercase">Tanggal Dokumen</span>
          <span class="text-sm font-bold text-slate-800">{{ $sppd->spt_date?->translatedFormat('d F Y') ?? $sppd->created_at->translatedFormat('d F Y') }}</span>
        </div>

        @php $sptSignature = $sppd->signatureFor('spt'); @endphp

        {{-- Status TTE --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase">Status TTE</p>
              <p class="text-sm font-bold text-slate-800 mt-1">
                {{ $sptSignature ? $sptSignature->status->label() : 'Belum Diproses' }}
              </p>
            </div>

            @if ($sptSignature)
              <form action="{{ route('sppd.reset-tte', ['sppd' => $sppd->id, 'type' => 'spt']) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded bg-rose-100 px-3 py-1.5 text-[10px] font-bold text-rose-700 hover:bg-rose-200 transition">
                  <i class="fa-solid fa-rotate-left"></i> Reset TTE
                </button>
              </form>
            @endif
          </div>

          @if ($sptSignature?->signed_file_path)
            <div class="mt-4">
              <a href="{{ route('sppd.sign.download', ['sppd' => $sppd->id, 'signature' => $sptSignature->id]) }}"
                class="inline-flex items-center gap-2 rounded bg-emerald-600 px-3 py-2 text-[11px] font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                <i class="fa-solid fa-file-pdf"></i> Download PDF TTE
              </a>
            </div>
          @endif

          @if ($sptSignature?->error_message)
            <p class="mt-2 text-[10px] text-rose-600 font-medium">
              <i class="fa-solid fa-circle-exclamation mr-1"></i> Error: {{ $sptSignature->error_message }}
            </p>
          @endif
        </div>
      </div>
    </div>

    {{-- Action Footer --}}
    <div class="bg-slate-50 border-t border-slate-100 p-6 text-center">
      <a href="{{ route('sppd.stream.spt', $sppd) }}" target="_blank"
         class="inline-flex items-center gap-2 rounded bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 hover:-translate-y-0.5">
        <i class="fa-solid fa-print"></i> CETAK DOKUMEN SPT
      </a>
    </div>
  </div>
</div>
@endsection
