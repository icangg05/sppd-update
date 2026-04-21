@extends('layouts.app')

@section('title', 'Detail Anggaran')
@section('page-title', 'Detail Data Anggaran')

@section('content')
<div class="mb-6 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-900">Informasi DPA</h2>
            <p class="text-sm text-slate-500">Rincian lengkap dari dokumen pelaksanaan anggaran.</p>
        </div>
    </div>
    <div class="flex gap-2">
        @can('budget.edit')
        <a href="{{ route('master.budgets.edit', $budget->id) }}" class="btn-secondary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            Edit
        </a>
        @endcan
        <a href="{{ route('master.budgets.index') }}" class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Detail Utama</h3>
            </div>
            
            <div class="p-0">
                <table class="w-full text-left text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <th class="w-1/3 p-4 bg-slate-50/50 text-slate-600 font-medium">SKPD</th>
                            <td class="p-4 text-slate-900 font-semibold">{{ $budget->department->name }}</td>
                        </tr>
                        <tr>
                            <th class="p-4 bg-slate-50/50 text-slate-600 font-medium">Tahun Anggaran</th>
                            <td class="p-4 text-slate-900">{{ $budget->year }}</td>
                        </tr>
                        <tr>
                            <th class="p-4 bg-slate-50/50 text-slate-600 font-medium">Jenis Anggaran</th>
                            <td class="p-4 text-slate-900">{{ $budget->type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="p-4 bg-slate-50/50 text-slate-600 font-medium">Program</th>
                            <td class="p-4 text-slate-900">{{ $budget->program }}</td>
                        </tr>
                        <tr>
                            <th class="p-4 bg-slate-50/50 text-slate-600 font-medium">Kegiatan</th>
                            <td class="p-4 text-slate-900">{{ $budget->activity }}</td>
                        </tr>
                        <tr>
                            <th class="p-4 bg-slate-50/50 text-slate-600 font-medium">Kode Rekening</th>
                            <td class="p-4">
                                <code class="bg-slate-100 px-2 py-1 rounded font-mono text-slate-700">{{ $budget->account_code }}</code>
                            </td>
                        </tr>
                        <tr>
                            <th class="p-4 bg-slate-50/50 text-slate-600 font-medium">Mata Anggaran</th>
                            <td class="p-4">
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded text-xs font-bold">{{ $budget->source }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="p-4 bg-slate-50/50 text-slate-600 font-medium align-top">Uraian</th>
                            <td class="p-4 text-slate-700 leading-relaxed">{{ $budget->description }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Ringkasan Keuangan</h3>
            </div>
            
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Pagu</p>
                    <p class="text-2xl font-black text-slate-900">Rp {{ number_format($budget->total_amount, 0, ',', '.') }}</p>
                </div>
                
                <div class="pt-4 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Realisasi</p>
                    <p class="text-xl font-bold text-blue-600">Rp {{ number_format($budget->realization, 0, ',', '.') }}</p>
                </div>
                
                <div class="pt-4 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Sisa Anggaran</p>
                    <p class="text-xl font-bold {{ $budget->balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">Rp {{ number_format($budget->balance, 0, ',', '.') }}</p>
                </div>
                
                {{-- Progress Bar --}}
                @php
                    $percentage = $budget->total_amount > 0 ? min(100, round(($budget->realization / $budget->total_amount) * 100)) : 0;
                    $colorClass = $percentage > 90 ? 'bg-red-500' : ($percentage > 75 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <div class="pt-4 mt-2">
                    <div class="flex justify-between text-xs font-medium mb-1.5">
                        <span class="text-slate-600">Penyerapan</span>
                        <span class="text-slate-900">{{ $percentage }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2.5">
                        <div class="{{ $colorClass }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
