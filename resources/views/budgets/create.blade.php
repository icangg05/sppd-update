@extends('layouts.app')

@section('title', 'Tambah Anggaran')
@section('page-title', 'Tambah Anggaran Baru')

@section('content')
<div class="mb-6 flex items-center gap-3">
    <div class="p-2 bg-emerald-100 rounded-lg">
        <i class="fa-solid fa-plus text-emerald-600 text-xl"></i>
    </div>
    <div>
        <h2 class="text-xl font-bold text-slate-900">Input Data DPA</h2>
        <p class="text-sm text-slate-500">Silahkan isi formulir di bawah untuk menambah data anggaran.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Formulir Anggaran</h3>
    </div>

    <form action="{{ route('master.budgets.store') }}" method="POST" class="p-6">
        @csrf

        <div class="space-y-4">
            @if(auth()->user()->hasRole('super_admin'))
                <x-form.select
                    name="department_id"
                    label="SKPD"
                    required
                >
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
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
                    <option value="{{ $y }}" {{ (old('year') ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </x-form.select>

            <x-form.select
                name="type"
                label="Jenis Anggaran"
            >
                <option value="">-- Pilih Jenis Anggaran --</option>
                <option value="Perjalanan Dinas Dalam Daerah" {{ old('type') == 'Perjalanan Dinas Dalam Daerah' ? 'selected' : '' }}>Perjalanan Dinas Dalam Daerah</option>
                <option value="Perjalanan Dinas Luar Daerah" {{ old('type') == 'Perjalanan Dinas Luar Daerah' ? 'selected' : '' }}>Perjalanan Dinas Luar Daerah</option>
                <option value="Bimtek" {{ old('type') == 'Bimtek' ? 'selected' : '' }}>Bimtek</option>
                <option value="Perjalanan Lainnya" {{ old('type') == 'Perjalanan Lainnya' ? 'selected' : '' }}>Perjalanan Lainnya</option>
            </x-form.select>

            <x-form.input
                type="text"
                name="program"
                label="Program"
                :value="old('program')"
                placeholder="Contoh: Program Penunjang Urusan Pemerintahan..."
            />

            <x-form.input
                type="text"
                name="activity"
                label="Kegiatan"
                :value="old('activity')"
                placeholder="Contoh: Administrasi Umum Perangkat Daerah"
            />

            <x-form.input
                type="text"
                name="account_code"
                label="Kode Rekening"
                :value="old('account_code')"
                placeholder="Contoh: 1.01.01.2.06.01"
                class="font-mono"
            />

            <x-form.textarea
                name="description"
                label="Uraian"
                :rows="3"
                placeholder="Deskripsi lengkap anggaran..."
                :value="old('description')"
            />

            <x-form.select
                name="source"
                label="Mata Anggaran"
            >
                <option value="">-- Pilih Mata Anggaran --</option>
                <option value="APBD" {{ old('source') == 'APBD' ? 'selected' : '' }}>APBD</option>
                <option value="APBD-P" {{ old('source') == 'APBD-P' ? 'selected' : '' }}>APBD-P</option>
                <option value="APBN" {{ old('source') == 'APBN' ? 'selected' : '' }}>APBN</option>
            </x-form.select>

            <x-form.input
                type="number"
                name="total_amount"
                label="Total Anggaran (Rp)"
                :value="old('total_amount')"
                placeholder="0"
            />
        </div>

        <div class="mt-8 flex items-center gap-3">
            <x-ui.button type="submit" variant="success" class="flex items-center gap-2">
                <i class="fa-solid fa-check"></i>
                Simpan Data
            </x-ui.button>
            <x-ui.button type="reset" variant="secondary" class="flex items-center gap-2">
                <i class="fa-solid fa-rotate-left"></i>
                Reset
            </x-ui.button>
            <x-ui.button href="{{ route('master.budgets.index') }}" variant="danger" class="flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
