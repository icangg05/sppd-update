@extends('errors.layout')
@section('title', '500 - Terjadi Kesalahan Sistem')

@section('content')
    <div class="mb-6 text-amber-500">
        <i class="fa-solid fa-triangle-exclamation text-7xl drop-shadow-md"></i>
    </div>
    <h1 class="mb-2 text-7xl font-black tracking-tighter text-slate-800">500</h1>
    <h2 class="mb-3 text-xl font-bold text-slate-700">Terjadi Kesalahan Sistem</h2>
    <p class="mb-8 text-sm text-slate-600">Sistem kami sedang mengalami masalah teknis. Tim developer sedang berupaya untuk memperbaikinya secepat mungkin.</p>

    <a wire:navigate href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded bg-slate-800 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-slate-800/30 transition-all hover:-translate-y-1 hover:bg-slate-900 hover:shadow-xl">
        <i class="fa-solid fa-rotate-right"></i> Muat Ulang Halaman
    </a>
@endsection
