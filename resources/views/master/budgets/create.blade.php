@extends('layouts.app')
@section('title', 'Tambah Anggaran')
@section('page-title', 'Tambah Anggaran')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah Anggaran</h1>
    <p class="page-subtitle">Tambahkan pos anggaran perjalanan dinas baru</p>
  </div>
  <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary">← Kembali</x-ui.button>
</div>

<form method="POST" action="{{ route('master.budgets.store') }}">
  @csrf
  <div class="card p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <x-form.select
        name="department_id"
        label="Instansi"
        required
      >
        <option value="">— Pilih Instansi —</option>
        @foreach($departments as $d)
          <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
      </x-form.select>

      <x-form.input
        type="number"
        name="year"
        label="Tahun Anggaran"
        :value="old('year', date('Y'))"
        min="2020"
        max="2030"
        required
      />

      <x-form.input
        type="text"
        name="name"
        label="Nama Pos Anggaran"
        :value="old('name')"
        placeholder="Misal: Belanja Perjalanan Dinas Dalam Daerah"
        required
      />

      <x-form.input
        type="number"
        name="total_amount"
        label="Total Anggaran (Rp)"
        :value="old('total_amount')"
        min="0"
        step="1000"
        required
      />
    </div>
  </div>

  <div class="flex justify-end gap-3">
    <x-ui.button href="{{ route('master.budgets.index') }}" variant="secondary">Batal</x-ui.button>
    <x-ui.button type="submit">Simpan Anggaran</x-ui.button>
  </div>
</form>
@endsection
