@extends('layouts.app')

@section('content')
	<div class="mb-6 flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold text-slate-800">Detail Perjalanan Dinas</h1>
			<p class="text-slate-500 text-sm">Tahap 2: Isi Detail & Lengkapi Data</p>
		</div>
		<a href="{{ route('sppd.create') }}"
			class="text-primary-600 hover:underline text-sm font-medium flex items-center gap-1">
			<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
			</svg>
			Kembali ke Tahap 1
		</a>
	</div>

	<form action="{{ route('sppd.store') }}" method="POST" enctype="multipart/form-data">
		@csrf
		{{-- Hidden Inputs dari Tahap 1 --}}
		<input type="hidden" name="user_id" value="{{ $pelaksana->id }}">
		<input type="hidden" name="domain" value="{{ $domain }}">

		{{-- RINGKASAN PELAKSANA & ALUR --}}
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
			<div class="lg:col-span-1">
				<div class="card p-5 bg-primary-50 border-primary-100 h-full">
					<h4 class="text-[10px] font-bold text-primary-600 uppercase tracking-widest mb-3">PELAKSANA</h4>
					<p class="font-bold text-slate-800">{{ $pelaksana->name }}</p>
					<p class="text-xs text-slate-500">{{ $pelaksana->nip }}</p>
					<div class="mt-4 pt-4 border-t border-primary-200">
						<p class="text-[10px] font-bold text-primary-600 uppercase tracking-widest">DOMAIN</p>
						<span class="uppercase badge bg-white text-primary-700 border-primary-200 mt-1">
							{{ ucwords(str_replace('_', ' ', $domain)) }}
						</span>
					</div>
				</div>
			</div>
			<div class="lg:col-span-2">
				<div class="card p-5 h-full">
					<h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">PRATINJAU ALUR PERSETUJUAN</h4>
					<div class="flex flex-wrap gap-3">
						@foreach ($steps as $step)
							<div class="flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-lg border border-slate-200">
								<div class="w-5 h-5 bg-green-500 text-white rounded-full flex items-center justify-center text-[10px] font-bold">
									{{ $loop->iteration }}
								</div>
								<div>
									<p class="text-[8px] font-bold text-slate-400 uppercase leading-none">{{ $step['role_label'] }}</p>
									<p class="text-[11px] font-semibold text-slate-700">{{ $step['approver_name'] }}</p>
								</div>
							</div>
						@endforeach
					</div>
				</div>
			</div>
		</div>

		{{-- DATA PERIHAL --}}
		<div class="card p-6 mb-6">
			<div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
				<h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">DATA PERIHAL</h3>
			</div>
			<div class="space-y-4">
				<div>
					<label for="purpose" class="form-label font-bold text-slate-700">Perihal (Maksud Perjalanan Dinas) <span
							class="text-red-500">*</span></label>
					<textarea name="purpose" id="purpose" class="form-textarea" rows="3" required
					 placeholder="Masukkan maksud perjalanan dinas secara lengkap...">{{ old('purpose', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}</textarea>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
					<div>
						<label for="problem" class="form-label font-medium">Persoalan</label>
						<textarea name="problem" id="problem" class="form-textarea" rows="2">{{ old('problem', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}</textarea>
					</div>
					<div>
						<label for="facts" class="form-label font-medium">Fakta yang mempengaruhi</label>
						<textarea name="facts" id="facts" class="form-textarea" rows="2">{{ old('facts', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}</textarea>
					</div>
					<div>
						<label for="analysis" class="form-label font-medium">Analisis</label>
						<textarea name="analysis" id="analysis" class="form-textarea" rows="2">{{ old('analysis', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}</textarea>
					</div>
				</div>
			</div>
		</div>

		{{-- DATA PERJALANAN --}}
		<div class="card p-6 mb-6">
			<div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
				<h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">DATA PERJALANAN</h3>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
				<div>
					<label for="transport_type" class="form-label">Jenis Angkutan</label>
					<select name="transport_type" id="transport_type" class="form-select">
						<option value="">— Pilih —</option>
						<option value="Darat" {{ old('transport_type', 'Darat') == 'Darat' ? 'selected' : '' }}>Darat</option>
						<option value="Laut" {{ old('transport_type') == 'Laut' ? 'selected' : '' }}>Laut</option>
						<option value="Udara" {{ old('transport_type') == 'Udara' ? 'selected' : '' }}>Udara</option>
					</select>
				</div>
				<div>
					<label for="transport_name" class="form-label">Angkutan</label>
					<select name="transport_name" id="transport_name" class="form-select">
						<option value="">— Pilih —</option>
						<option value="Motor" {{ old('transport_name') == 'Motor' ? 'selected' : '' }}>Motor</option>
						<option value="Mobil" {{ old('transport_name', 'Mobil') == 'Mobil' ? 'selected' : '' }}>Mobil</option>
						<option value="Pesawat" {{ old('transport_name') == 'Pesawat' ? 'selected' : '' }}>Pesawat</option>
						<option value="Kapal" {{ old('transport_name') == 'Kapal' ? 'selected' : '' }}>Kapal</option>
						<option value="Kereta" {{ old('transport_name') == 'Kereta' ? 'selected' : '' }}>Kereta</option>
						<option value="Lainnya" {{ old('transport_name') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
					</select>
				</div>
				<div>
					<label for="departure_place" class="form-label">Tempat Berangkat</label>
					<input type="text" name="departure_place" id="departure_place" class="form-input"
						placeholder="Misal: Kantor Walikota" value="{{ old('departure_place', 'Kantor Kominfo') }}">
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
				<label class="form-label block mb-2 font-bold text-slate-700">Lokasi Tujuan <span
						class="text-red-500">*</span></label>

				{{-- Field khusus dalam daerah --}}
				@if ($domain === 'dalam_daerah')
					<div id="dalam-daerah-fields">
						<div class="flex flex-col gap-2">
							<div
								class="flex items-center gap-2 text-xs text-slate-500 bg-slate-100 px-3 py-1.5 rounded-md border border-slate-200 w-fit">
								<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
								</svg>
								Lokasi Default: <span class="font-bold">Kota Kendari, Sulawesi Tenggara</span>
							</div>
							<input type="text" name="destinations[0][address_only]" class="form-input" required
								placeholder="Sebutkan tempat tujuan spesifik (misal: Kantor Gubernur, Kecamatan X)">
						</div>
					</div>
				@else
					{{-- Multi-destination logic (LDDP/LDLP) --}}
					<div id="multi-dest-fields">
						<div id="dest-wrap" class="space-y-3">
							<div
								class="dest-row grid grid-cols-1 {{ $domain === 'lddp' ? 'md:grid-cols-2' : 'md:grid-cols-3' }} gap-2 p-3 bg-slate-50 rounded-lg relative">
								<div class="{{ $domain === 'lddp' ? 'hidden' : '' }}">
									<select name="destinations[0][province_id]" class="form-select prov-sel" required>
										<option value="">— Provinsi —</option>
										@foreach ($provinces as $p)
											@if ($domain === 'ldlp' && $p->name === 'Sulawesi Tenggara')
												@continue
											@endif
											<option value="{{ $p->id }}"
												{{ $domain === 'lddp' && $p->name === 'Sulawesi Tenggara' ? 'selected' : '' }}>{{ $p->name }}
											</option>
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
						<div class="flex items-center gap-3 mt-3">
							<button type="button" id="btn-add-dest"
								class="text-xs text-primary-600 font-medium hover:underline">+ Tambah Lokasi Tujuan Lainnya</button>
							<span id="dest-counter" class="text-xs text-slate-400">(<span id="dest-count">1</span>/4 tujuan)</span>
							<span id="dest-max-info" class="hidden text-xs text-amber-600 font-medium">⚠ Maksimal 4 tujuan tercapai</span>
						</div>
					</div>
				@endif
			</div>
		</div>

		{{-- ANGGARAN & PENGIKUT --}}
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
			<div class="card p-6">
				<div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
					<h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">ANGGARAN & KATEGORI</h3>
				</div>
				<div class="space-y-4">
					<div>
						<label for="budget_id" class="form-label">Sumber Anggaran / Kegiatan <span
								class="text-red-500">*</span></label>
						<select name="budget_id" id="budget_id" class="form-select" required>
							<option value="">— Pilih Anggaran —</option>
							@foreach ($budgets as $b)
								<option value="{{ $b->id }}" {{ old('budget_id', 2) == $b->id ? 'selected' : '' }}>
									{{ $b->program ?? '-' }} | {{ $b->activity ?? '-' }}
								</option>
							@endforeach
						</select>
					</div>
					<div>
						<label for="category_id" class="form-label">Kategori Perjalanan <span class="text-red-500">*</span></label>
						<select name="category_id" id="category_id" class="form-select" required>
							<option value="">— Pilih —</option>
							@foreach ($categories as $c)
								<option value="{{ $c->id }}" {{ old('category_id', 1) == $c->id ? 'selected' : '' }}>
									{{ $c->name }}
								</option>
							@endforeach
						</select>
					</div>
					<div>
						<label for="urgency" class="form-label text-orange-600 font-bold">Sifat Pengajuan <span
								class="text-red-500">*</span></label>
						<select name="urgency" id="urgency" class="form-select border-orange-200" required>
							<option value="Biasa" {{ old('urgency', 'Biasa') == 'Biasa' ? 'selected' : '' }}>Biasa</option>
							<option value="Segera" {{ old('urgency') == 'Segera' ? 'selected' : '' }}>Segera</option>
						</select>
					</div>
					<div>
						<label for="attachment" class="form-label font-bold text-slate-700">Dokumen Pendukung <span
								class="text-xs font-normal text-slate-400">(Opsional, PDF/JPG/PNG)</span></label>
						<input type="file" name="attachment" id="attachment" class="form-input text-xs">
					</div>
				</div>
			</div>

			<div class="card p-6">
				<div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
					<h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">PENGIKUT (OPSIONAL)</h3>
				</div>
				<div class="max-h-48 overflow-y-auto p-3 bg-slate-50 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-2">
					@foreach ($users as $u)
						@if ($u->id != $pelaksana->id)
							@php $isActive = in_array($u->id, $activeFollowerIds); @endphp
							<label class="flex items-center gap-2 text-xs p-1 rounded {{ $isActive ? 'opacity-75 cursor-not-allowed bg-rose-50' : 'cursor-pointer hover:bg-slate-100' }}">
								<input type="checkbox" name="followers[]" value="{{ $u->id }}"
									class="rounded border-slate-300 {{ $isActive ? '' : 'text-primary-600' }}"
									{{ $isActive ? 'disabled' : '' }}>
								<span class="{{ $isActive ? 'text-rose-600 font-medium' : '' }}">
									{{ $u->name }}
									@if ($isActive)
										<span class="block text-[10px] text-rose-400 font-normal leading-tight">Sedang perjalanan dinas</span>
									@endif
								</span>
							</label>
						@endif
					@endforeach
				</div>
			</div>
		</div>


		{{-- TANGGAL SPPD DAN SPT --}}
		<div class="card p-6 mb-6">
			<div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100">
				<h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">ADMINISTRASI TANGGAL</h3>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div>
					<label for="spt_date" class="form-label">Tanggal SPT</label>
					<input type="date" name="spt_date" id="spt_date" value="{{ old('spt_date', '2026-04-21') }}"
						class="form-input">
				</div>
				<div>
					<label for="sppd_date" class="form-label">Tanggal SPPD</label>
					<input type="date" name="sppd_date" id="sppd_date" value="{{ old('sppd_date', '2026-04-23') }}"
						class="form-input">
				</div>
			</div>
		</div>

		<div class="flex justify-end gap-3 mb-10">
			<button type="submit" class="btn-primary px-12 py-3 text-lg shadow-lg">Buat & Ajukan SPPD</button>
		</div>
	</form>
@endsection

@push('scripts')
	<script>
		$(document).ready(function() {
			let di = 1;
			const seSultraId = @json($provinces->where('name', 'Sulawesi Tenggara')->first()?->id);
			const domain = '{{ $domain }}';

			// Event Listeners
			$(document).on('change', '.prov-sel', function() {
				loadRegencies($(this));
			});

			const MAX_DEST = 4;

			function updateDestCounter() {
				const count = $('#dest-wrap .dest-row').length;
				$('#dest-count').text(count);
				if (count >= MAX_DEST) {
					$('#btn-add-dest').addClass('hidden');
					$('#dest-max-info').removeClass('hidden');
				} else {
					$('#btn-add-dest').removeClass('hidden');
					$('#dest-max-info').addClass('hidden');
				}
			}

			function reindexDestinations() {
				$('#dest-wrap .dest-row').each(function(idx) {
					$(this).find('[name]').each(function() {
						const name = $(this).attr('name');
						$(this).attr('name', name.replace(/destinations\[\d+\]/, `destinations[${idx}]`));
					});
				});
			}

			$('#btn-add-dest').on('click', function() {
				addDest();
			});

			// Delegasi event hapus agar counter ikut update
			$(document).on('click', '.btn-remove-dest', function() {
				$(this).closest('.dest-row').remove();
				reindexDestinations();
				updateDestCounter();
			});

			function addDest() {
				const count = $('#dest-wrap .dest-row').length;
				if (count >= MAX_DEST) return; // Sudah maksimal

				const provColClass = domain === 'lddp' ? 'hidden' : '';
				const gridClass = domain === 'lddp' ? 'md:grid-cols-2' : 'md:grid-cols-3';

				const html = `
                    <div class="dest-row grid grid-cols-1 ${gridClass} gap-2 p-3 bg-slate-50 rounded-lg relative mt-2">
                        <div class="${provColClass}">
                            <select name="destinations[${count}][province_id]" class="form-select prov-sel" required>
                                <option value="">— Provinsi —</option>
                                @foreach ($provinces as $p)
                                    @if ($domain === 'ldlp' && $p->name === 'Sulawesi Tenggara') @continue @endif
                                    <option value="{{ $p->id }}" ${domain === 'lddp' && '{{ $p->name }}' === 'Sulawesi Tenggara' ? 'selected' : ''}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="destinations[${count}][regency_id]" class="form-select reg-sel" required>
                                <option value="">— Kabupaten/Kota —</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" name="destinations[${count}][address]" class="form-input flex-1" placeholder="Tempat Tujuan / Alamat" required>
                            <button type="button" class="btn-remove-dest text-rose-500 hover:text-rose-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>`;

				$('#dest-wrap').append(html);
				if (domain === 'lddp') {
					const $lastRow = $('#dest-wrap .dest-row').last();
					const $pSel = $lastRow.find('.prov-sel');
					$pSel.val(seSultraId);
					loadRegencies($pSel);
				}
				updateDestCounter();
			}

			function loadRegencies($provElement) {
				const $r = $provElement.closest('.dest-row').find('.reg-sel');
				if (!$provElement.val()) {
					$r.html('<option value="">— Kabupaten/Kota —</option>');
					return;
				}
				const currentVal = $r.val();
				$r.html('<option>Memuat...</option>');
				$.getJSON(`/api/provinces/${$provElement.val()}/regencies`, function(data) {
					let o = '<option value="">— Kabupaten/Kota —</option>';
					$.each(data, function(i, item) {
						// Filter: Jangan tampilkan Kendari jika domain adalah LDDP
						if (domain === 'lddp' && item.name.includes('Kendari')) {
							return true; // continue
						}
						o +=
							`<option value="${item.id}" ${item.id == currentVal ? 'selected' : ''}>${item.name}</option>`;
					});
					$r.html(o);
				});
			}

			// Initial Load for first row
			if (domain !== 'dalam_daerah') {
				$('.prov-sel').each(function() {
					if ($(this).val()) loadRegencies($(this));
				});
			}

		});
	</script>
@endpush
