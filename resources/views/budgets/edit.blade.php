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

        <div class="space-y-4">
            @if(auth()->user()->hasRole('super_admin'))
                <x-form.select
                    name="department_id"
                    label="SKPD"
                    required
                >
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id', $budget->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </x-form.select>
            @else
                <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                <div class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-600">
                    {{ auth()->user()->department->name }}
                </div>
            @endif

            <x-form.select
                name="year"
                label="Tahun"
                required
            >
                @for($y = date('Y') + 1; $y >= 2019; $y--)
                    <option value="{{ $y }}" {{ old('year', $budget->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </x-form.select>

            <x-form.select
                name="type"
                label="Jenis Anggaran"
            >
                <option value="">-- Pilih Jenis Anggaran --</option>
                <option value="Perjalanan Dinas Dalam Daerah" {{ old('type', $budget->type) == 'Perjalanan Dinas Dalam Daerah' ? 'selected' : '' }}>Perjalanan Dinas Dalam Daerah</option>
                <option value="Perjalanan Dinas Luar Daerah" {{ old('type', $budget->type) == 'Perjalanan Dinas Luar Daerah' ? 'selected' : '' }}>Perjalanan Dinas Luar Daerah</option>
                <option value="Bimtek" {{ old('type', $budget->type) == 'Bimtek' ? 'selected' : '' }}>Bimtek</option>
                <option value="Perjalanan Lainnya" {{ old('type', $budget->type) == 'Perjalanan Lainnya' ? 'selected' : '' }}>Perjalanan Lainnya</option>
            </x-form.select>

            <x-form.input
                type="text"
                name="program"
                label="Program"
                :value="old('program', $budget->program)"
                placeholder="Contoh: Program Penunjang Urusan Pemerintahan..."
            />

            <x-form.input
                type="text"
                name="activity"
                label="Kegiatan"
                :value="old('activity', $budget->activity)"
                placeholder="Contoh: Administrasi Umum Perangkat Daerah"
            />

            <x-form.input
                type="text"
                name="account_code"
                label="Kode Rekening"
                :value="old('account_code', $budget->account_code)"
                placeholder="Contoh: 1.01.01.2.06.01"
                class="font-mono"
            />

            <x-form.textarea
                name="description"
                label="Uraian"
                :rows="3"
                placeholder="Deskripsi lengkap anggaran..."
                :value="old('description', $budget->description)"
            />

            <x-form.select
                name="source"
                label="Mata Anggaran"
            >
                <option value="">-- Pilih Mata Anggaran --</option>
                <option value="APBD" {{ old('source', $budget->source) == 'APBD' ? 'selected' : '' }}>APBD</option>
                <option value="APBD-P" {{ old('source', $budget->source) == 'APBD-P' ? 'selected' : '' }}>APBD-P</option>
                <option value="APBN" {{ old('source', $budget->source) == 'APBN' ? 'selected' : '' }}>APBN</option>
            </x-form.select>

            <x-form.input
                type="number"
                name="total_amount"
                label="Total Anggaran (Rp)"
                :value="old('total_amount', (int)$budget->total_amount)"
                placeholder="0"
            />
        </div>

        <div class="mt-8 flex items-center gap-3">
            <x-ui.button type="submit" variant="success" class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                Perbarui Data
            </x-ui.button>
            <x-ui.button href="{{ route('master.budgets.index') }}" variant="danger" class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Batal
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
