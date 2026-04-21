@extends('layouts.app')

@section('title', 'Edit Anggaran')
@section('page-title', 'Edit Data Anggaran')

@section('content')
<div class="mb-6 flex items-center gap-3">
    <div class="p-2 bg-amber-100 rounded-lg">
        <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </div>
    <div>
        <h2 class="text-xl font-bold text-slate-900">Ubah Data DPA</h2>
        <p class="text-sm text-slate-500">Silahkan perbarui formulir di bawah untuk mengubah data anggaran.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Formulir Edit Anggaran</h3>
    </div>

    <form action="{{ route('master.budgets.update', $budget->id) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="space-y-0 border-t border-l border-slate-200">
            {{-- Department --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">SKPD</div>
                <div class="w-3/4 p-3">
                    @if(auth()->user()->hasRole('super_admin'))
                        <select name="department_id" class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $budget->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <div class="py-2 px-3 text-sm text-slate-600 bg-slate-100 rounded-lg border border-slate-200">
                            {{ auth()->user()->department->name }}
                        </div>
                    @endif
                    @error('department_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Tahun --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">Tahun</div>
                <div class="w-3/4 p-3">
                    <select name="year" class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        @for($y = date('Y') + 1; $y >= 2019; $y--)
                            <option value="{{ $y }}" {{ old('year', $budget->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Jenis Anggaran --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">Jenis Anggaran</div>
                <div class="w-3/4 p-3">
                    <select name="type" class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">-- Pilih Jenis Anggaran --</option>
                        <option value="Perjalanan Dinas Dalam Daerah" {{ old('type', $budget->type) == 'Perjalanan Dinas Dalam Daerah' ? 'selected' : '' }}>Perjalanan Dinas Dalam Daerah</option>
                        <option value="Perjalanan Dinas Luar Daerah" {{ old('type', $budget->type) == 'Perjalanan Dinas Luar Daerah' ? 'selected' : '' }}>Perjalanan Dinas Luar Daerah</option>
                        <option value="Bimtek" {{ old('type', $budget->type) == 'Bimtek' ? 'selected' : '' }}>Bimtek</option>
                        <option value="Perjalanan Lainnya" {{ old('type', $budget->type) == 'Perjalanan Lainnya' ? 'selected' : '' }}>Perjalanan Lainnya</option>
                    </select>
                    @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Program --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">Program</div>
                <div class="w-3/4 p-3">
                    <input type="text" name="program" value="{{ old('program', $budget->program) }}" placeholder="Contoh: Program Penunjang Urusan Pemerintahan..."
                           class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    @error('program') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Kegiatan --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">Kegiatan</div>
                <div class="w-3/4 p-3">
                    <input type="text" name="activity" value="{{ old('activity', $budget->activity) }}" placeholder="Contoh: Administrasi Umum Perangkat Daerah"
                           class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    @error('activity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Kode Rekening --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">Kode Rekening</div>
                <div class="w-3/4 p-3">
                    <input type="text" name="account_code" value="{{ old('account_code', $budget->account_code) }}" placeholder="Contoh: 1.01.01.2.06.01"
                           class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500 font-mono">
                    @error('account_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Uraian --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-start pt-6 font-semibold text-sm text-slate-700">Uraian</div>
                <div class="w-3/4 p-3">
                    <textarea name="description" rows="3" placeholder="Deskripsi lengkap anggaran..."
                              class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $budget->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Mata Anggaran --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">Mata Anggaran</div>
                <div class="w-3/4 p-3">
                    <select name="source" class="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">-- Pilih Mata Anggaran --</option>
                        <option value="APBD" {{ old('source', $budget->source) == 'APBD' ? 'selected' : '' }}>APBD</option>
                        <option value="APBD-P" {{ old('source', $budget->source) == 'APBD-P' ? 'selected' : '' }}>APBD-P</option>
                        <option value="APBN" {{ old('source', $budget->source) == 'APBN' ? 'selected' : '' }}>APBN</option>
                    </select>
                    @error('source') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Total Anggaran --}}
            <div class="flex border-b border-r border-slate-200">
                <div class="w-1/4 bg-slate-50 p-4 flex items-center font-semibold text-sm text-slate-700">Total Anggaran (Rp)</div>
                <div class="w-3/4 p-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">Rp</div>
                        <input type="number" name="total_amount" value="{{ old('total_amount', (int)$budget->total_amount) }}" placeholder="0"
                               class="w-full pl-10 rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    @error('total_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                Perbarui Data
            </button>
            <a href="{{ route('master.budgets.index') }}" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
