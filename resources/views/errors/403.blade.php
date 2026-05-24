@extends('errors.layout')
@section('title', '403 - Akses Ditolak')

@section('content')
    <div class="mb-6 text-rose-500">
        <i class="fa-solid fa-shield-halved text-7xl drop-shadow-md"></i>
    </div>
    <h1 class="mb-2 text-7xl font-black tracking-tighter text-slate-800">403</h1>
    <h2 class="mb-3 text-xl font-bold text-slate-700">Akses Ditolak</h2>
    <p class="mb-8 text-sm text-slate-600">Anda tidak memiliki kredensial atau izin yang cukup untuk mengakses halaman ini. Silakan hubungi Administrator.</p>

    <button onclick="window.history.back()" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-slate-800/30 transition-all hover:-translate-y-1 hover:bg-slate-900 hover:shadow-xl">
        <i class="fa-solid fa-arrow-left"></i> Kembali Sebelumnya
    </button>
@endsection
