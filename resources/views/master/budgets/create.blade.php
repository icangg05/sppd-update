@extends('layouts.app')
@section('title', 'Tambah Anggaran')
@section('page-title', 'Tambah Anggaran')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah Anggaran</h1>
    <p class="page-subtitle">Tambahkan pos anggaran perjalanan dinas baru</p>
  </div>
  <a href="{{ route('master.budgets.index') }}" class="btn-secondary">← Kembali</a>
</div>

<form method="POST" action="{{ route('master.budgets.store') }}">
  @csrf
  <div class="card p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Instansi <span class="text-red-500">*</span></label>
        <select name="department_id" class="form-select" required>
          <option value="">— Pilih Instansi —</option>
          @foreach($departments as $d)
            <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
          @endforeach
        </select>
        @error('department_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Tahun Anggaran <span class="text-red-500">*</span></label>
        <input type="number" name="year" value="{{ old('year', date('Y')) }}" class="form-input" min="2020" max="2030" required>
        @error('year') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Nama Pos Anggaran <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="Misal: Belanja Perjalanan Dinas Dalam Daerah" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Total Anggaran (Rp) <span class="text-red-500">*</span></label>
        <input type="number" name="total_amount" value="{{ old('total_amount') }}" class="form-input" min="0" step="1000" required>
        @error('total_amount') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <a href="{{ route('master.budgets.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Anggaran</button>
  </div>
</form>
@endsection
