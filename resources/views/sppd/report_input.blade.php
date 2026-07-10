@extends('layouts.app')
@section('title', 'Laporan Hasil Perjalanan Dinas')

@section('content')
  <div class="p-1 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
          <i class="fa-solid fa-pen-to-square mr-2 text-emerald-600"></i>Laporan Perjalanan Dinas
        </h1>
      </div>
      <a wire:navigate href="{{ route('sppd.next', $sppd) }}"
        class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>

    {{-- Alert / Info Box --}}
    <div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 flex items-center gap-3">
        <div class="flex size-8 items-center justify-center rounded bg-emerald-100 text-emerald-600">
          <i class="fa-solid fa-user-tie text-sm"></i>
        </div>
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wide text-slate-700">Pelaksana</h3>
          <p class="font-bold text-slate-800">{{ $sppd->user->name }}</p>
        </div>
      </div>
      <div class="px-5 py-4">
        <div>
            <p class="text-[10px] font-bold uppercase text-slate-500 mb-1">Maksud Perjalanan</p>
            <p class="text-sm font-medium text-slate-700 leading-relaxed">{{ $sppd->purpose }}</p>
        </div>
      </div>
    </div>

    {{-- Form Laporan --}}
    <form action="{{ route('sppd.report.store', $sppd) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
      @csrf
      
      <div class="rounded border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
          <h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
            <i class="fa-solid fa-file-pen text-emerald-600"></i> Detail Laporan
          </h3>
        </div>
        
        <div class="p-5 space-y-5">
            <div>
                <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Tanggal Laporan <span class="text-rose-500">*</span></label>
                <input type="date" name="report_date" value="{{ $sppd->report?->report_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}" required class="w-full sm:w-64 rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-sm" {{ !auth()->user()->hasAnyRole(['admin_opd', 'super_admin']) ? 'disabled bg-slate-50 cursor-not-allowed' : '' }}>
            </div>

             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                <div>
                    <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">File Laporan (Dokumen) <span class="text-rose-500">*</span></label>
                    @if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
                        <input type="file" name="report_file" accept=".pdf,.doc,.docx" {{ !$sppd->report?->report_file ? 'required' : '' }} class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded p-1">
                        <p class="text-[10px] text-slate-500 mt-1">Format: PDF, DOC, DOCX (Maks. 20MB). Wajib diisi.</p>
                    @endif
                    @if($sppd->report?->report_file)
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-check text-emerald-500"></i> File Tersimpan
                            </span>
                            <a href="{{ asset('storage/' . $sppd->report->report_file) }}" target="_blank" class="text-xs font-bold text-emerald-600 hover:text-emerald-800 hover:underline">
                                Lihat File
                            </a>
                        </div>
                    @else
                        @if(!auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-1 text-[10px] font-medium text-rose-700 border border-rose-200">
                                    <i class="fa-solid fa-circle-xmark text-rose-500"></i> Belum Diunggah
                                </span>
                            </div>
                        @endif
                    @endif
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Foto Dokumentasi <span class="text-rose-500">*</span></label>
                    @if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
                        <input type="file" name="documentation_file" accept="image/*" {{ !$sppd->report?->documentation_file ? 'required' : '' }} class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded p-1">
                        <p class="text-[10px] text-slate-500 mt-1">Format: JPG, PNG (Maks. 20MB). Wajib diisi.</p>
                    @endif
                    @if($sppd->report?->documentation_file)
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-check text-emerald-500"></i> Foto Tersimpan
                            </span>
                            <a href="{{ asset('storage/' . $sppd->report->documentation_file) }}" target="_blank" class="text-xs font-bold text-emerald-600 hover:text-emerald-800 hover:underline">
                                Lihat Foto
                            </a>
                        </div>
                    @else
                        @if(!auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-1 text-[10px] font-medium text-rose-700 border border-rose-200">
                                    <i class="fa-solid fa-circle-xmark text-rose-500"></i> Belum Diunggah
                                </span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        
        @if(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
            <div class="bg-slate-50 px-5 py-3 border-t border-slate-200 flex justify-end gap-3 rounded-b">
                <x-ui.button type="submit" variant="success" class="px-6 py-2.5 font-bold shadow-sm cursor-pointer hover:scale-[1.02] active:scale-[0.98]">
                    <i class="fa-solid fa-save"></i>
                    {{ $sppd->report ? 'Perbarui Laporan' : 'Simpan Laporan' }}
                </x-ui.button>
            </div>
        @endif
      </div>
    </form>
  </div>
@endsection
