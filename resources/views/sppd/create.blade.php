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
					<label for="purpose" class="form-label">Perihal (Maksud Perjalanan Dinas) <span
							class="text-red-500">*</span></label>
					<textarea name="purpose" id="purpose" class="form-textarea" rows="3" required>{{ old('purpose') }}</textarea>
					@error('purpose')
						<p class="form-error">{{ $message }}</p>
					@enderror
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

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
				<div>
					<label for="domain" class="form-label">Domain Perjalanan <span class="text-red-500">*</span></label>
					<select name="domain" id="domain" class="form-select" onchange="toggleDomainFields(this.value)" required>
						<option value="dalam_daerah" {{ old('domain') == 'dalam_daerah' ? 'selected' : '' }}>Dalam Daerah</option>
						<option value="lddp" {{ old('domain') == 'lddp' ? 'selected' : '' }}>Luar Daerah Dalam Provinsi (LDDP)</option>
						<option value="ldlp" {{ old('domain') == 'ldlp' ? 'selected' : '' }}>Luar Daerah Luar Provinsi (LDLP)</option>
					</select>
				</div>
				<div>
					<label for="transport_type" class="form-label">Jenis Angkutan</label>
					<select name="transport_type" id="transport_type" class="form-select">
						<option value="">— Pilih —</option>
						<option value="Darat" {{ old('transport_type') == 'Darat' ? 'selected' : '' }}>Darat</option>
						<option value="Laut" {{ old('transport_type') == 'Laut' ? 'selected' : '' }}>Laut</option>
						<option value="Udara" {{ old('transport_type') == 'Udara' ? 'selected' : '' }}>Udara</option>
					</select>
				</div>
				<div>
					<label for="transport_name" class="form-label">Angkutan</label>
					<select name="transport_name" id="transport_name" class="form-select">
						<option value="">— Pilih —</option>
						<option value="Motor" {{ old('transport_name') == 'Motor' ? 'selected' : '' }}>Motor</option>
						<option value="Mobil" {{ old('transport_name') == 'Mobil' ? 'selected' : '' }}>Mobil</option>
						<option value="Pesawat" {{ old('transport_name') == 'Pesawat' ? 'selected' : '' }}>Pesawat</option>
						<option value="Kapal" {{ old('transport_name') == 'Kapal' ? 'selected' : '' }}>Kapal</option>
						<option value="Kereta" {{ old('transport_name') == 'Kereta' ? 'selected' : '' }}>Kereta</option>
						<option value="Lainnya" {{ old('transport_name') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
					</select>
				</div>
				<div>
					<label for="departure_place" class="form-label">Tempat Berangkat</label>
					<input type="text" name="departure_place" id="departure_place" class="form-input"
						placeholder="Misal: Kantor Walikota" value="{{ old('departure_place') }}">
				</div>
				<div>
					<label for="start_date" class="form-label">Tanggal Berangkat <span class="text-red-500">*</span></label>
					<input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
						class="form-input" required>
				</div>
				<div>
					<label for="end_date" class="form-label">Tanggal Kembali <span class="text-red-500">*</span></label>
					<input type="date" name="end_date" id="end_date" value="{{ old('end_date', date('Y-m-d')) }}"
						class="form-input" required>
				</div>
			</div>

			<div id="destination-section">
				<label class="form-label block mb-2">Lokasi Tujuan <span class="text-red-500">*</span></label>

				{{-- Field khusus dalam daerah --}}
				<div id="dalam-daerah-fields" class="hidden">
					<div class="flex flex-col gap-2">
						<div class="flex items-center gap-2 text-xs text-slate-500 bg-slate-100 px-3 py-1.5 rounded-md border border-slate-200 w-fit">
							<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
							Lokasi Default: <span class="font-bold">Kota Kendari, Sulawesi Tenggara</span>
						</div>
						<input type="text" name="destinations[0][address_only]" id="dest_address_only" class="form-input"
							placeholder="Sebutkan tempat tujuan spesifik (misal: Kantor Gubernur, Kecamatan X)">
					</div>
				</div>

				{{-- Multi-destination logic (LDDP/LDLP) --}}
				<div id="multi-dest-fields">
					<div id="dest-wrap" class="space-y-3">
						<div class="dest-row grid grid-cols-1 md:grid-cols-3 gap-2 p-3 bg-slate-50 rounded-lg relative">
							<div>
								<select name="destinations[0][province_id]" class="form-select prov-sel" required>
									<option value="">— Provinsi —</option>
									@foreach ($provinces as $p)
										<option value="{{ $p->id }}">{{ $p->name }}</option>
									@endforeach
								</select>
							</div>
							<div>
								<select name="destinations[0][regency_id]" class="form-select reg-sel" required>
									<option value="">— Kabupaten/Kota —</option>
								</select>
							</div>
							<div>
								<input type="text" name="destinations[0][address]" class="form-input"
									placeholder="Tempat Tujuan / Alamat Spesifik" required>
							</div>
						</div>
					</div>
					<button type="button" id="btn-add-dest" onclick="addDest()"
						class="text-xs text-primary-600 mt-3 font-medium hover:underline">+ Tambah Lokasi Tujuan Lainnya</button>
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
						@foreach ($budgets as $b)
							<option value="{{ $b->id }}" {{ old('budget_id') == $b->id ? 'selected' : '' }}>
								{{ $b->department->name }} — {{ $b->name }}</option>
						@endforeach
					</select>
				</div>
				<div>
					<label for="category_id" class="form-label">Kategori Perjalanan <span class="text-red-500">*</span></label>
					<select name="category_id" id="category_id" class="form-select" required>
						<option value="">— Pilih —</option>
						@foreach ($categories as $c)
							<option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}
							</option>
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
						@foreach ($users as $u)
							<option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->nip }} ||
								{{ $u->name }}</option>
						@endforeach
					</select>
				</div>
				<div>
					<label class="form-label">Pengikut</label>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-2 p-3 bg-slate-50 rounded-lg">
						@foreach ($users as $u)
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
					<input type="date" name="sppd_date" id="sppd_date" value="{{ old('sppd_date', date('Y-m-d')) }}"
						class="form-input">
				</div>
				<div>
					<label for="spt_date" class="form-label">Tanggal SPT</label>
					<input type="date" name="spt_date" id="spt_date" value="{{ old('spt_date', date('Y-m-d')) }}"
						class="form-input">
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
		let di = 1;
		const seSultraId = @json($provinces->where('name', 'Sulawesi Tenggara')->first()?->id);

		function toggleDomainFields(domain) {
			const dalamFields = document.getElementById('dalam-daerah-fields');
			const multiFields = document.getElementById('multi-dest-fields');
			const firstAddrOnly = document.getElementById('dest_address_only');
			const allRows = document.querySelectorAll('.dest-row');

			if (domain === 'dalam_daerah') {
				dalamFields.classList.remove('hidden');
				multiFields.classList.add('hidden');
				firstAddrOnly.setAttribute('required', 'required');
				firstAddrOnly.disabled = false;
				multiFields.querySelectorAll('select, input').forEach(el => el.disabled = true);
			} else {
				dalamFields.classList.add('hidden');
				multiFields.classList.remove('hidden');
				firstAddrOnly.removeAttribute('required');
				firstAddrOnly.disabled = true;
				multiFields.querySelectorAll('select, input').forEach(el => el.disabled = false);

				allRows.forEach(row => {
					const pSel = row.querySelector('.prov-sel');
					const rSel = row.querySelector('.reg-sel');
					
					if (domain === 'lddp') {
						pSel.value = seSultraId;
						pSel.closest('div').classList.add('hidden'); // Sembunyikan kolom provinsi
						loadRegencies(pSel);
					} else {
						pSel.closest('div').classList.remove('hidden'); // Tampilkan kolom provinsi
					}
				});
			}
		}

		function addDest() {
			const domain = document.getElementById('domain').value;
			const w = document.getElementById('dest-wrap');
			const provColClass = domain === 'lddp' ? 'hidden' : '';
			const provVal = domain === 'lddp' ? seSultraId : '';

			const html = `
				<div class="dest-row grid grid-cols-1 md:grid-cols-3 gap-2 p-3 bg-slate-50 rounded-lg relative mt-2">
					<div class="${provColClass}">
						<select name="destinations[${di}][province_id]" class="form-select prov-sel" required>
							<option value="">— Provinsi —</option>
							@foreach ($provinces as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
						</select>
					</div>
					<div>
						<select name="destinations[${di}][regency_id]" class="form-select reg-sel" required>
							<option value="">— Kabupaten/Kota —</option>
						</select>
					</div>
					<div class="flex gap-2">
						<input type="text" name="destinations[${di}][address]" class="form-input flex-1" placeholder="Tempat Tujuan / Alamat" required>
						<button type="button" onclick="this.closest('.dest-row').remove()" class="text-rose-500 hover:text-rose-700">
							<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
						</button>
					</div>
				</div>`;

			w.insertAdjacentHTML('beforeend', html);

			if (domain === 'lddp') {
				const lastRow = w.lastElementChild;
				const pSel = lastRow.querySelector('.prov-sel');
				pSel.value = seSultraId;
				loadRegencies(pSel);
			}
			di++;
		}

		function loadRegencies(provElement) {
			const r = provElement.closest('.dest-row').querySelector('.reg-sel');
			if (!provElement.value) {
				r.innerHTML = '<option value="">— Kabupaten/Kota —</option>';
				return;
			}
			const currentVal = r.value;
			r.innerHTML = '<option>Memuat...</option>';
			fetch(`/api/provinces/${provElement.value}/regencies`)
				.then(res => res.json())
				.then(data => {
					let o = '<option value="">— Kabupaten/Kota —</option>';
					data.forEach(i => {
						o += `<option value="${i.id}" ${i.id == currentVal ? 'selected' : ''}>${i.name}</option>`;
					});
					r.innerHTML = o;
				});
		}

		document.addEventListener('change', function(e) {
			if (e.target.classList.contains('prov-sel')) {
				loadRegencies(e.target);
			}
		});

		// Initial call
		document.addEventListener('DOMContentLoaded', function() {
			toggleDomainFields(document.getElementById('domain').value);
		});
	</script>
@endpush
