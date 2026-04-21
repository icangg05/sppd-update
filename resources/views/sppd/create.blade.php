@extends('layouts.app')
@section('title', 'Buat SPPD')
@section('page-title', 'Buat SPPD Baru')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Buat SPPD Baru</h1>
    <p class="page-subtitle">Isi formulir pengajuan perjalanan dinas sesuai format telaah</p>
  </div>
  <a href="{{ route('sppd.index') }}" class="btn-secondary">← Kembali</a>
</div>

<form method="POST" action="{{ route('sppd.store') }}">
  @csrf

  {{-- DATA PERIHAL --}}
  <div class="card p-6 mb-6">
    <div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
      <h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">DATA PERIHAL</h3>
    </div>
    <div class="space-y-4">
      <div>
        <label for="recipient" class="form-label">Kepada</label>
        <select name="recipient" id="recipient" class="form-select">
          <option value="Walikota">Walikota</option>
          <option value="Sekretaris Daerah">Sekretaris Daerah</option>
          <option value="Asisten">Asisten</option>
          <option value="Kepala OPD">Kepala OPD</option>
        </select>
      </div>
      <div>
        <label for="purpose" class="form-label">Perihal (Maksud Perjalanan Dinas) <span class="text-red-500">*</span></label>
        <textarea name="purpose" id="purpose" class="form-textarea" rows="3" required>{{ old('purpose') }}</textarea>
        @error('purpose') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label for="problem" class="form-label">Persoalan</label>
        <textarea name="problem" id="problem" class="form-textarea" rows="2">{{ old('problem') }}</textarea>
      </div>
      <div>
        <label for="facts" class="form-label">Fakta yang mempengaruhi</label>
        <textarea name="facts" id="facts" class="form-textarea" rows="2">{{ old('facts') }}</textarea>
      </div>
      <div>
        <label for="analysis" class="form-label">Analisis</label>
        <textarea name="analysis" id="analysis" class="form-textarea" rows="2">{{ old('analysis') }}</textarea>
      </div>
    </div>
  </div>

  {{-- DATA PERJALANAN --}}
  <div class="card p-6 mb-6">
    <div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
      <h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">DATA PERJALANAN</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="transport_type" class="form-label">Jenis Angkutan</label>
        <select name="transport_type" id="transport_type" class="form-select">
          <option value="">— Pilih —</option>
          <option value="Darat">Darat</option>
          <option value="Laut">Laut</option>
          <option value="Udara">Udara</option>
        </select>
      </div>
      <div>
        <label for="transport_name" class="form-label">Angkutan</label>
        <input type="text" name="transport_name" id="transport_name" class="form-input" placeholder="Misal: Garuda Indonesia, Kendaraan Dinas">
      </div>
      <div>
        <label for="start_date" class="form-label">Tanggal Berangkat <span class="text-red-500">*</span></label>
        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="form-input" required>
      </div>
      <div>
        <label for="end_date" class="form-label">Tanggal Kembali <span class="text-red-500">*</span></label>
        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', date('Y-m-d')) }}" class="form-input" required>
      </div>
      <div>
        <label for="departure_place" class="form-label">Tempat Berangkat</label>
        <input type="text" name="departure_place" id="departure_place" class="form-input" value="Kota Kendari">
      </div>
      <div>
        <label class="form-label">Tempat Tujuan (Multi-tujuan)</label>
        <div id="dest-wrap" class="space-y-3">
          <div class="dest-row flex gap-2">
            <select name="destinations[0][province_id]" class="form-select prov-sel w-1/3" required>
              <option value="">Provinsi *</option>
              @foreach($provinces as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
            <select name="destinations[0][regency_id]" class="form-select reg-sel w-1/3"><option value="">Kab/Kota</option></select>
            <input type="text" name="destinations[0][address]" class="form-input w-1/3" placeholder="Alamat Spesifik">
          </div>
        </div>
        <button type="button" onclick="addDest()" class="text-xs text-primary-600 mt-2 hover:underline">+ Tambah Tujuan</button>
      </div>
      <div>
        <label for="domain" class="form-label">Domain Perjalanan</label>
        <select name="domain" id="domain" class="form-select" required>
          <option value="dalam_daerah">Dalam Daerah</option>
          <option value="luar_daerah">Luar Daerah</option>
          <option value="bimtek">Bimtek</option>
        </select>
      </div>
    </div>
  </div>

  {{-- DATA KEGIATAN --}}
  <div class="card p-6 mb-6">
    <div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
      <h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">DATA KEGIATAN</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="budget_id" class="form-label">Sumber Anggaran / Kegiatan <span class="text-red-500">*</span></label>
        <select name="budget_id" id="budget_id" class="form-select" required>
          <option value="">— Pilih —</option>
          @foreach($budgets as $b)
            <option value="{{ $b->id }}" {{ old('budget_id') == $b->id ? 'selected' : '' }}>{{ $b->department->name }} — {{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="category_id" class="form-label">Kategori Perjalanan <span class="text-red-500">*</span></label>
        <select name="category_id" id="category_id" class="form-select" required>
          <option value="">— Pilih —</option>
          @foreach($categories as $c)
            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="urgency" class="form-label">Kecepatan Telaah</label>
        <select name="urgency" id="urgency" class="form-select">
          <option value="Biasa">Biasa</option>
          <option value="Segera">Segera</option>
          <option value="Sangat Segera">Sangat Segera</option>
        </select>
      </div>
    </div>
  </div>

  {{-- DATA PELAKSANA DAN PENGIKUT --}}
  <div class="card p-6 mb-6">
    <div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
      <h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">DATA PELAKSANA DAN PENGIKUT</h3>
    </div>
    <div class="space-y-4">
      <div>
        <label for="user_id" class="form-label">Pelaksana Perjalanan Dinas <span class="text-red-500">*</span></label>
        <select name="user_id" id="user_id" class="form-select" required>
          <option value="">— Pilih —</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->nip }} || {{ $u->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Pengikut</label>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 p-3 bg-slate-50 rounded-lg">
          @foreach($users as $u)
            <label class="flex items-center gap-2 text-xs">
              <input type="checkbox" name="followers[]" value="{{ $u->id }}" class="rounded border-slate-300">
              {{ $u->name }}
            </label>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- TANGGAL SPPD DAN SPT --}}
  <div class="card p-6 mb-6">
    <div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
      <h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">TANGGAL SPPD DAN SPT</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="sppd_date" class="form-label">Tanggal SPPD</label>
        <input type="date" name="sppd_date" id="sppd_date" value="{{ old('sppd_date', date('Y-m-d')) }}" class="form-input">
      </div>
      <div>
        <label for="spt_date" class="form-label">Tanggal SPT</label>
        <input type="date" name="spt_date" id="spt_date" value="{{ old('spt_date', date('Y-m-d')) }}" class="form-input">
      </div>
    </div>
  </div>

  <div class="flex justify-end gap-3 mb-10">
    <a href="{{ route('sppd.index') }}" class="btn-secondary">Kembali</a>
    <button type="reset" class="btn-ghost text-orange-600 bg-orange-50">Reset</button>
    <button type="submit" class="btn-primary px-8">Buat Dokumen Perjalanan</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
let di=1;
function addDest(){
  const w=document.getElementById('dest-wrap');
  w.insertAdjacentHTML('beforeend',`
    <div class="dest-row flex gap-2 mt-2">
      <select name="destinations[${di}][province_id]" class="form-select prov-sel w-1/3" required>
        <option value="">Provinsi *</option>
        @foreach($provinces as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
      </select>
      <select name="destinations[${di}][regency_id]" class="form-select reg-sel w-1/3"><option value="">Kab/Kota</option></select>
      <input type="text" name="destinations[${di}][address]" class="form-input w-1/3" placeholder="Alamat Spesifik">
      <button type="button" onclick="this.closest('.dest-row').remove()" class="text-red-500">×</button>
    </div>
  `);
  di++;
}
document.addEventListener('change',function(e){
  if(!e.target.classList.contains('prov-sel'))return;
  const r=e.target.closest('.dest-row').querySelector('.reg-sel');
  r.innerHTML='<option>Memuat...</option>';
  if(!e.target.value){r.innerHTML='<option value="">—</option>';return;}
  fetch(`/api/provinces/${e.target.value}/regencies`).then(r=>r.json()).then(d=>{
    let o='<option value="">Kab/Kota</option>';
    d.forEach(i=>{o+=`<option value="${i.id}">${i.name}</option>`;});
    r.innerHTML=o;
  });
});
</script>
@endpush
