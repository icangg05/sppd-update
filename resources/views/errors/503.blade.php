@extends('errors.layout')
@section('title', '503 - Sedang Pemeliharaan')

@section('content')
    <div class="mb-6 text-cyan-500">
        <i class="fa-solid fa-screwdriver-wrench text-7xl drop-shadow-md"></i>
    </div>
    <h1 class="mb-2 text-7xl font-black tracking-tighter text-slate-800">503</h1>
    <h2 class="mb-3 text-xl font-bold text-slate-700">Sedang Dalam Pemeliharaan</h2>
    <p class="mb-8 text-sm text-slate-600">Sistem sedang ditingkatkan atau dalam perbaikan rutin untuk memberikan pengalaman terbaik. Silakan periksa kembali beberapa saat lagi.</p>

    <div class="inline-flex items-center gap-2 px-6 py-3 text-sm font-bold text-slate-500">
        <i class="fa-solid fa-clock animate-spin"></i> Menunggu Sistem...
    </div>
@endsection
