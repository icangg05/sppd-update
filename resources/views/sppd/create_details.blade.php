@extends('layouts.app')

@section('title', 'Detail Perjalanan Dinas')

@section('content')
	<div class="flex flex-col gap-4 p-1">

		{{-- Header Halaman --}}
		<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
			<div class="leading-tight">
				<h1 class="text-lg font-bold text-slate-800">Detail Perjalanan Dinas</h1>
				<p class="text-xs text-slate-500 mt-0.5">Tahap 2: Isi Detail & Lengkapi Data Pengajuan</p>
			</div>
			<x-ui.button href="{{ route('sppd.create') }}" variant="secondary"
				class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
				<x-slot name="icon">
					<i class="fa-solid fa-arrow-left text-xs text-slate-400"></i>
				</x-slot>
				Kembali ke Tahap 1
			</x-ui.button>
		</div>

		<form action="{{ route('sppd.store') }}" method="POST" enctype="multipart/form-data" id="form-sppd-store">
			@csrf
			<input type="hidden" name="user_id" id="hidden_user_id" value="{{ $pelaksana->id }}">
			<input type="hidden" name="user_name" id="hidden_user_name" value="{{ $pelaksana->name }}">
			<input type="hidden" name="domain" value="{{ $domain }}">

			{{-- Ringkasan Informasi Pelaksana & Alur --}}
			<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
				<div class="rounded border border-cyan-200 bg-cyan-50/50 p-4 shadow-sm leading-tight flex flex-col justify-between">
					<div>
						<h4 class="text-xs font-bold uppercase tracking-wider text-cyan-700 flex items-center gap-1.5 mb-2">
							<i class="fa-solid fa-user-tie"></i> Pelaksana
						</h4>
						<p class="font-bold text-slate-800 text-sm">{{ $pelaksana->name }}</p>
						<p class="text-xs text-slate-500 font-mono mt-0.5">{{ $pelaksana->nip }}</p>
					</div>
					<div class="mt-4 pt-3 border-t border-cyan-200/60">
						<h4 class="text-xs font-bold uppercase tracking-wider text-cyan-700 flex items-center gap-1.5">
							<i class="fa-solid fa-earth-asia"></i> Domain Perjalanan
						</h4>
						<span
							class="inline-block mt-1.5 rounded-sm bg-white border border-cyan-200 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-cyan-800">
							{{ str_replace('_', ' ', $domain) }}
						</span>
					</div>
				</div>

				<div class="lg:col-span-2 rounded border border-slate-200 bg-white p-4 shadow-sm">
					<h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5 mb-3">
						<i class="fa-solid fa-diagram-project text-slate-400"></i> Pratinjau Alur Verifikasi / Persetujuan
					</h4>
					<div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
						@foreach ($steps as $step)
							<div class="flex items-center gap-2.5 rounded border border-emerald-200 bg-emerald-50/50 p-2.5 shadow-2xs">
								<div
									class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white shadow-2xs">
									{{ $loop->iteration }}
								</div>
								<div class="min-w-0 leading-tight">
									<p class="text-xs font-bold uppercase tracking-wide text-emerald-600">{{ $step['role_label'] }}</p>
									<p class="text-sm font-semibold text-emerald-950 truncate mt-0.5" title="{{ $step['approver_name'] }}">
										{{ $step['approver_name'] }}</p>
								</div>
							</div>
						@endforeach
					</div>
				</div>
			</div>

			{{-- Data Perihal / Isi Telaah --}}
			<div class="mt-4 rounded border border-slate-200 bg-white shadow-md overflow-hidden">
				<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
					<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
						<i class="fa-solid fa-file-pen text-cyan-600"></i> Data Perihal & Justifikasi Perjalanan
					</h3>
				</div>
				<div class="p-5">
					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						<x-form.textarea
							name="purpose"
							label="Perihal (Maksud Perjalanan Dinas)"
							:rows="4"
							placeholder="Masukkan maksud perjalanan dinas secara lengkap..."
							required
							value="{{ old('purpose', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}" />
						<x-form.textarea
							name="problem"
							label="Persoalan"
							:rows="4"
							value="{{ old('problem', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}" />
						<x-form.textarea
							name="facts"
							label="Fakta yang mempengaruhi"
							:rows="4"
							value="{{ old('facts', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}" />
						<x-form.textarea
							name="analysis"
							label="Analisis"
							:rows="4"
							value="{{ old('analysis', 'Melaksanakan Koordinasi Terkait Kerjasama Media di TvOne Dan Koordinasi Terkait Aplikasi Jaki Dan Iklan Video Trone Di Pemprov DKI Jakarta') }}" />
					</div>
				</div>
			</div>

			{{-- Logistik & Tanggal Perjalanan --}}
			<div class="mt-4 rounded border border-slate-200 bg-white shadow-md overflow-hidden">
				<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
					<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
						<i class="fa-solid fa-route text-cyan-600"></i> Detail Logistik & Tanggal Perjalanan
					</h3>
				</div>
				<div class="p-5 space-y-5">
					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
						<x-form.select name="transport_type" label="Jenis Angkutan" required>
							<option value="">— Pilih —</option>
							<option value="Darat" {{ old('transport_type', 'Darat') == 'Darat' ? 'selected' : '' }}>Darat</option>
							<option value="Laut" {{ old('transport_type') == 'Laut' ? 'selected' : '' }}>Laut</option>
							<option value="Udara" {{ old('transport_type') == 'Udara' ? 'selected' : '' }}>Udara</option>
						</x-form.select>
						<x-form.select name="transport_name" label="Nama Kendaraan" required>
							<option value="">— Pilih —</option>
							<option value="Motor" {{ old('transport_name') == 'Motor' ? 'selected' : '' }}>Motor</option>
							<option value="Mobil" {{ old('transport_name', 'Mobil') == 'Mobil' ? 'selected' : '' }}>Mobil</option>
							<option value="Pesawat" {{ old('transport_name') == 'Pesawat' ? 'selected' : '' }}>Pesawat</option>
							<option value="Kapal" {{ old('transport_name') == 'Kapal' ? 'selected' : '' }}>Kapal</option>
							<option value="Kereta" {{ old('transport_name') == 'Kereta' ? 'selected' : '' }}>Kereta</option>
							<option value="Lainnya" {{ old('transport_name') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
						</x-form.select>
						<x-form.input
							name="departure_place"
							label="Tempat Berangkat"
							placeholder="Misal: Kantor Walikota"
							value="{{ old('departure_place', 'Kantor Kominfo') }}"
							required />
						<x-form.input type="date" name="start_date" label="Tanggal Berangkat"
							value="{{ old('start_date', date('Y-m-d')) }}" required />
						<x-form.input type="date" name="end_date" label="Tanggal Kembali" value="{{ old('end_date', date('Y-m-d')) }}"
							required />
					</div>

					<div>
						<label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
							Lokasi Tujuan Perjalanan <span class="text-rose-500">*</span>
						</label>

						@if ($domain === 'dalam_daerah')
							<div id="dalam-daerah-fields" class="flex flex-col gap-2">
								<div
									class="inline-flex items-center gap-1.5 rounded-sm bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 border border-slate-200/60 w-fit">
									<i class="fa-solid fa-location-dot text-slate-400"></i>
									Lokasi Basis: <span class="font-bold text-slate-700">Kota Kendari, Sulawesi Tenggara</span>
								</div>
								<input type="text" name="destinations[0][address_only]"
									class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500"
									required placeholder="Sebutkan instansi/tempat tujuan spesifik (misal: Kantor Gubernur, Kecamatan Poasia)">
							</div>
						@else
							<div id="multi-dest-fields">
								<div id="dest-wrap" class="space-y-2.5">
									<div
										class="dest-row grid grid-cols-1 {{ $domain === 'lddp' ? 'md:grid-cols-2' : 'md:grid-cols-3' }} gap-2 p-3 bg-slate-50/80 border border-slate-200 rounded">
										<div class="{{ $domain === 'lddp' ? 'hidden' : '' }}">
											<select name="destinations[0][province_id]"
												class="w-full rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-700 prov-sel"
												required>
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
											<select name="destinations[0][regency_id]"
												class="w-full rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-700 reg-sel"
												required>
												<option value="">— Kabupaten/Kota —</option>
											</select>
										</div>
										<div>
											<input type="text" name="destinations[0][address]"
												class="w-full rounded border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-800 placeholder-slate-400"
												placeholder="Instansi / Alamat Spesifik Tujuan" required>
										</div>
									</div>
								</div>
								<div class="flex items-center gap-3 mt-2.5">
									<button type="button" id="btn-add-dest"
										class="text-xs text-cyan-600 font-bold hover:text-cyan-700 flex items-center gap-1">
										<i class="fa-solid fa-circle-plus"></i> Tambah Lokasi Tujuan Lainnya
									</button>
									<span id="dest-counter" class="text-xs text-slate-400">(<span id="dest-count">1</span>/4 lokasi)</span>
									<span id="dest-max-info" class="hidden text-xs text-amber-600 font-semibold"><i
											class="fa-solid fa-circle-info"></i> Batas maksimal 4 tujuan</span>
								</div>
							</div>
						@endif
					</div>
				</div>
			</div>

			{{-- Pembebanan Anggaran & Pencarian Pengikut --}}
			<div class="grid grid-cols-1 gap-4 lg:grid-cols-2 mt-4">
				<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
						<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
							<i class="fa-solid fa-money-check-dollar text-cyan-600"></i> Anggaran & Dokumen Pendukung
						</h3>
					</div>
					<div class="p-5 space-y-4">
						<x-form.select name="budget_id" label="Sumber Anggaran / Kegiatan SKPD" required>
							<option value="">— Pilih Program / Kegiatan —</option>
							@foreach ($budgets as $b)
								<option class="pr-4" value="{{ $b->id }}" {{ old('budget_id', 2) == $b->id ? 'selected' : '' }}>
									{{ $b->program ?? '-' }} | {{ $b->activity ?? '-' }}
								</option>
							@endforeach
						</x-form.select>
						<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
							<x-form.select name="category_id" label="Kategori Dinas" required>
								<option value="">— Pilih Kategori —</option>
								@foreach ($categories as $c)
									<option value="{{ is_object($c) ? $c->id : $c->value }}"
										{{ old('category_id', 1) == (is_object($c) ? $c->id : $c->value) ? 'selected' : '' }}>
										{{ is_object($c) ? $c->name : $c->label() }}
									</option>
								@endforeach
							</x-form.select>
							<x-form.select name="urgency" label="Sifat Surat Dokumen" required>
								<option value="Biasa" {{ old('urgency', 'Biasa') == 'Biasa' ? 'selected' : '' }}>Biasa</option>
								<option value="Segera" {{ old('urgency') == 'Segera' ? 'selected' : '' }}>Segera</option>
							</x-form.select>
						</div>
						<x-form.file
							name="attachment"
							label="Undangan / Dokumen Pendukung"
							hint="Format berkas: PDF, DOCX, JPG, PNG (Maks. 2MB)"
							accept=".pdf,.docx,.jpg,.jpeg,.png" />
					</div>
				</div>

				<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden flex flex-col">
					<div
						class="border-b border-slate-100 bg-slate-50/75 px-5 py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
						<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
							<i class="fa-solid fa-users text-cyan-600"></i> Daftar Pengikut (Opsional)
						</h3>
						{{-- Input Live Search Pengikut --}}
						<div class="relative w-full sm:w-48">
							<span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
								<i class="fa-solid fa-magnifying-glass text-xs"></i>
							</span>
							<input type="text" id="search-follower"
								class="w-full rounded border border-slate-300 bg-white py-1 pl-8 pr-2.5 text-xs text-slate-700 placeholder-slate-400 focus:border-cyan-500 focus:outline-hidden"
								placeholder="Cari nama pegawai...">
						</div>
					</div>

					<div class="p-4 bg-slate-50/50 flex-1 overflow-y-auto max-h-56" id="follower-list-box">
						<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
							@foreach ($users as $u)
								@if ($u->id != $pelaksana->id)
									@php $isActive = in_array($u->id, $activeFollowerIds); @endphp
									<label
										class="follower-item flex items-start gap-2.5 rounded border border-slate-200 bg-white p-2.5 shadow-2xs transition-colors hover:bg-slate-50 cursor-pointer {{ $isActive ? 'opacity-60 cursor-not-allowed bg-rose-50/50 border-rose-200' : '' }}">
										<input type="checkbox" name="followers[]" value="{{ $u->id }}" data-name="{{ $u->name }}"
											class="follower-cb rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 mt-0.5"
											{{ $isActive ? 'disabled' : '' }}>
										<div class="min-w-0 leading-tight">
											<span class="block text-xs font-semibold text-slate-700 truncate follower-name">{{ $u->name }}</span>
											<span class="block text-[10px] text-slate-400 font-mono mt-0.5">{{ $u->nip }}</span>
											@if ($isActive)
												<span class="inline-block text-[10px] text-amber-600 font-semibold mt-0.5"><i
														class="fa-solid fa-route"></i> Sedang dalam perjalanan dinas</span>
											@endif
										</div>
									</label>
								@endif
							@endforeach
						</div>
						<div id="empty-follower-msg" class="hidden text-center py-8 text-xs font-medium text-slate-400 italic">
							<i class="fa-solid fa-user-slash text-base mb-1 block text-slate-300"></i> Pegawai tidak ditemukan
						</div>
					</div>
				</div>
			</div>

			{{-- Administrasi Tanggal Pengesahan --}}
			<div class="mt-4 rounded border border-slate-200 bg-white shadow-md overflow-hidden">
				<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
					<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
						<i class="fa-solid fa-calendar-check text-cyan-600"></i> Penomoran & Penanggalan Dokumen Resmi
					</h3>
				</div>
				<div class="p-5 grid grid-cols-1 gap-4 md:grid-cols-2">
					@if ($isInspektorat)
						<x-form.input
							name="document_number"
							label="Nomor Surat Tugas"
							placeholder="Contoh: 700/100/Insp/V/2026"
							value="{{ old('document_number', '090 / isi_nomor_surat_tugas / ST / INSP./ 2026') }}"
							class="md:col-span-2" />
					@endif
					<x-form.input type="date" name="spt_date" label="Tanggal Penerbitan SPT"
						value="{{ old('spt_date', '2026-04-21') }}" />
					<x-form.input type="date" name="sppd_date" label="Tanggal Penerbitan SPPD"
						value="{{ old('sppd_date', '2026-04-23') }}" />
				</div>
			</div>

			{{-- Tombol Submit Pembuat Aksi --}}
			<div class="mt-5 flex justify-end">
				<button type="button" id="btn-trigger-confirm"
					class="inline-flex items-center gap-2 rounded bg-cyan-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-cyan-700">
					<i class="fa-solid fa-paper-plane text-xs"></i>
					<span>Buat & Ajukan SPPD</span>
				</button>
			</div>
		</form>
	</div>

	{{-- MODAL KONFIRMASI (Tailwind Backdrop Fix) --}}
	<div id="modal-confirm"
		class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-2xs hidden transition-opacity duration-200 opacity-0">
		<div
			class="w-full max-w-lg transform rounded border border-slate-200 bg-white p-5 shadow-xl transition-all leading-tight">
			<div class="flex items-center gap-2 border-b border-slate-100 pb-3 text-slate-800">
				<i class="fa-solid fa-circle-question text-cyan-600 text-base"></i>
				<h3 class="text-base font-bold">Konfirmasi Pengajuan</h3>
			</div>

			<div class="py-4 space-y-3.5 text-sm text-slate-600">
				<div>
					<span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Pegawai Pelaksana:</span>
					<p id="confirm-pelaksana"
						class="font-bold text-slate-800 mt-1 bg-slate-50 px-2.5 py-1.5 rounded border border-slate-200/60"></p>
				</div>
				<div>
					<span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Daftar Pengikut Dinas:</span>
					<div id="confirm-pengikut-container"
						class="mt-1 border border-slate-200 rounded divide-y divide-slate-100 bg-slate-50/50 max-h-64 overflow-y-auto">
						{{-- Diisi via JS --}}
					</div>
				</div>
				<p class="text-xs text-slate-400 mt-2 bg-amber-50 border border-amber-200 rounded p-2 text-amber-800">
					<i class="fa-solid fa-circle-info mr-0.5"></i> Pastikan perihal, tanggal perjalanan, dan rekening anggaran sudah
					benar sebelum mengajukan dokumen ke alur verifikasi.
				</p>
			</div>

			<div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
				<button type="button" id="btn-modal-close"
					class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
					Periksa Kembali
				</button>
				<button type="button" id="btn-modal-submit"
					class="rounded bg-cyan-600 px-4 py-2 text-xs font-bold text-white hover:bg-cyan-700">
					Ya, Ajukan Sekarang
				</button>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		$(document).ready(function() {
			const seSultraId = @json($provinces->where('name', 'Sulawesi Tenggara')->first()?->id);
			const domain = '{{ $domain }}';
			const isInspektorat = @json($isInspektorat);
			const jabatanOptions = [
				'Penanggung Jawab',
				'Pembantu Penanggung Jawab',
				'Pengendali Teknis',
				'Ketua Tim',
				'Anggota',
				'Admin Tim',
			];
			const MAX_DEST = 4;

			// 1. Live Search Pengikut
			$('#search-follower').on('keyup', function() {
				const value = $(this).val().toLowerCase().trim();
				let matches = 0;

				$('.follower-item').each(function() {
					const name = $(this).find('.follower-name').text().toLowerCase();
					if (name.includes(value)) {
						$(this).removeClass('hidden');
						matches++;
					} else {
						$(this).addClass('hidden');
					}
				});

				if (matches === 0) {
					$('#empty-follower-msg').removeClass('hidden');
				} else {
					$('#empty-follower-msg').addClass('hidden');
				}
			});

			// 2. Logik Tambah/Hapus Multi Destinasi
			$(document).on('change', '.prov-sel', function() {
				loadRegencies($(this));
			});

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
						$(this).attr('name', name.replace(/destinations\[\d+\]/,
							`destinations[${idx}]`));
					});
				});
			}

			$('#btn-add-dest').on('click', function() {
				const count = $('#dest-wrap .dest-row').length;
				if (count >= MAX_DEST) return;

				const provColClass = domain === 'lddp' ? 'hidden' : '';
				const gridClass = domain === 'lddp' ? 'md:grid-cols-2' : 'md:grid-cols-3';

				const html = `
          <div class="dest-row grid grid-cols-1 ${gridClass} gap-2 p-3 bg-slate-50 border border-slate-200 rounded mt-2 relative">
            <div class="${provColClass}">
              <select name="destinations[${count}][province_id]" class="w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-700 prov-sel" required>
                <option value="">— Provinsi —</option>
                @foreach ($provinces as $p)
                  @if ($domain === 'ldlp' && $p->name === 'Sulawesi Tenggara') @continue @endif
                  <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <select name="destinations[${count}][regency_id]" class="w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-700 reg-sel" required>
                <option value="">— Kabupaten/Kota —</option>
              </select>
            </div>
            <div class="flex gap-2">
              <input type="text" name="destinations[${count}][address]" class="w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-800" placeholder="Instansi / Alamat" required>
              <button type="button" class="btn-remove-dest text-red-500 hover:text-red-700 transition">
                <i class="fa-solid fa-trash-can text-base"></i>
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
			});

			$(document).on('click', '.btn-remove-dest', function() {
				$(this).closest('.dest-row').remove();
				reindexDestinations();
				updateDestCounter();
			});

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
						if (domain === 'lddp' && item.name.includes('Kendari')) return true;
						o +=
							`<option value="${item.id}" ${item.id == currentVal ? 'selected' : ''}>${item.name}</option>`;
					});
					$r.html(o);
				});
			}

			if (domain !== 'dalam_daerah') {
				$('.prov-sel').each(function() {
					if ($(this).val()) loadRegencies($(this));
				});
			}

			// 3. Modal Konfirmasi Kustom Sebelum Submit
			const $modal = $('#modal-confirm');

			$('#btn-trigger-confirm').on('click', function() {
				// Validasi HTML default form bawaan browser sebelum memunculkan modal
				if (!$('#form-sppd-store')[0].checkValidity()) {
					$('#form-sppd-store')[0].reportValidity();
					return;
				}

				// Ambil Data Pelaksana & Pengikut
				$('#confirm-pelaksana').html(
					`<i class="fa-solid fa-user text-cyan-600 mr-1.5"></i> ${$('#hidden_user_name').val()}`
				);

				const $listContainer = $('#confirm-pengikut-container');
				$listContainer.empty();

				let checkedPengikut = 0;

				if (isInspektorat) {
					// Buat opsi jabatan dropdown
					let jabatanOpts = '<option value="">— Pilih Jabatan —</option>';
					jabatanOptions.forEach(j => {
						jabatanOpts += `<option value="${j}">${j}</option>`;
					});

					$('.follower-cb:checked').each(function() {
						const name = $(this).data('name');
						const uid = $(this).val();
						$listContainer.append(`
              <div class="px-3 py-2 bg-white flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-700 truncate">
                  <i class="fa-solid fa-caret-right text-cyan-600 mr-1.5"></i>${name}
                </span>
                <select name="follower_positions[${uid}]"
                  class="follower-jabatan-sel rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 focus:border-cyan-500 focus:outline-none shrink-0"
                  required>
                  ${jabatanOpts}
                </select>
              </div>
            `);
						checkedPengikut++;
					});
				} else {
					$('.follower-cb:checked').each(function() {
						const name = $(this).data('name');
						$listContainer.append(
							`<div class="px-3 py-2 text-xs font-semibold text-slate-700 bg-white"><i class="fa-solid fa-caret-right text-cyan-600 mr-1.5"></i> ${name}</div>`
						);
						checkedPengikut++;
					});
				}

				if (checkedPengikut === 0) {
					$listContainer.html(
						'<div class="px-3 py-2.5 text-xs text-slate-400 italic bg-white"><i class="fa-solid fa-user-minus mr-1"></i> Tidak ada pengikut</div>'
					);
				}

				// Tampilkan Modal dengan animasi halus
				$modal.removeClass('hidden');
				setTimeout(() => {
					$modal.removeClass('opacity-0').addClass('opacity-100');
				}, 20);
			});

			function hideModal() {
				$modal.removeClass('opacity-100').addClass('opacity-0');
				setTimeout(() => {
					$modal.addClass('hidden');
				}, 200);
			}

			$('#btn-modal-close').on('click', hideModal);

			$('#btn-modal-submit').on('click', function() {
				// Validasi jabatan pengikut untuk Inspektorat
				if (isInspektorat) {
					let allFilled = true;
					$('.follower-jabatan-sel').each(function() {
						if (!$(this).val()) {
							allFilled = false;
							$(this).addClass('border-rose-500');
						} else {
							$(this).removeClass('border-rose-500');
						}
					});
					if (!allFilled) {
						// Tampilkan pesan error di modal
						let $err = $('#modal-jabatan-error');
						if ($err.length === 0) {
							$err = $(
								'<p id="modal-jabatan-error" class="text-xs text-rose-600 font-semibold mt-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Harap pilih jabatan dalam perjalanan untuk setiap pengikut.</p>'
							);
							$('#confirm-pengikut-container').after($err);
						}
						return;
					}
					$('#modal-jabatan-error').remove();

					// Pindahkan select jabatan sebagai hidden input ke form utama
					// (select berada di dalam modal, bukan di form, jadi perlu dipindahkan)
					$('.follower-jabatan-sel').each(function() {
						const name = $(this).attr('name');
						const val = $(this).val();
						// Hapus input lama jika sudah ada
						$(`#form-sppd-store input[name="${name}"]`).remove();
						$('<input type="hidden">').attr({
							name: name,
							value: val
						}).appendTo('#form-sppd-store');
					});
				}

				$(this).prop('disabled', true).text('Memproses...');
				$('#form-sppd-store').submit();
			});
		});
	</script>
@endpush
