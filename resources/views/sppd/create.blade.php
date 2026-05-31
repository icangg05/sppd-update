@extends('layouts.app')

@section('title', 'Buat SPPD Baru')

@section('content')
	<div class="flex flex-col gap-4 p-1">

		{{-- Header Halaman --}}
		<div class="leading-tight">
			<h1 class="text-lg font-bold text-slate-800">Buat SPPD Baru</h1>
			<p class="text-xs text-slate-500 mt-0.5">Tahap 1: Pilih Pelaksana & Validasi Alur Pengajuan</p>
		</div>

		{{-- Formulir Utama Tahap 1 --}}
		<form action="{{ route('sppd.create.details') }}" method="GET" id="form-step-1">
			<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">

				{{-- Sub Header Card --}}
				<div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/75 px-5 py-3.5">
					<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-2">
						<i class="fa-solid fa-paste text-cyan-600"></i>
						Tahap 1: Pelaksana & Estimasi Alur
					</h3>
					<span
						class="flex size-6 items-center justify-center rounded-full bg-cyan-600 text-xs font-bold text-white shadow-2xs">1</span>
				</div>

				{{-- Input Grid Pemilihan Pelaksana & Domain --}}
				<div class="p-5 grid grid-cols-1 gap-5 md:grid-cols-2">
					<x-form.select name="user_id" id="user_id" label="Pelaksana Perjalanan Dinas" required class="select2">
						<option value="">— Pilih Pegawai yang Berangkat —</option>
						@foreach ($users as $u)
							<option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
								{{ $u->nip ? $u->nip . ' -' : '' }} {{ $u->name }}
							</option>
						@endforeach
					</x-form.select>

					<x-form.select name="domain" id="domain" label="Domain Perjalanan" required>
						<option value="dalam_daerah" {{ old('domain') == 'dalam_daerah' ? 'selected' : '' }}>Dalam Daerah</option>
						<option value="lddp" {{ old('domain') == 'lddp' ? 'selected' : '' }}>Luar Daerah Dalam Provinsi (LDDP)</option>
						<option value="ldlp" {{ old('domain') == 'ldlp' ? 'selected' : '' }}>Luar Daerah Luar Provinsi (LDLP)</option>
					</x-form.select>
				</div>

				{{-- Container Pratinjau Alur Alur Dokumen --}}
				<div id="workflow-preview" class="hidden border-t border-slate-100 bg-slate-50/50 p-5">
					<div class="flex items-center justify-between mb-4">
						<h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
							<i class="fa-solid fa-diagram-project text-slate-400"></i>
							Pratinjau Alur Persetujuan Dokumen
						</h4>
						<div id="workflow-status-badge"></div>
					</div>

					{{-- Grid Langkah Alur (Dipopulasikan melalui JS) --}}
					<div id="workflow-steps" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>

					{{-- Pesan Kesalahan / Validasi Alur Instansi --}}
					<div id="workflow-error-msg"
						class="mt-4 rounded border border-red-200 bg-red-50 p-3.5 text-xs text-red-700 hidden">
						<div class="flex gap-2">
							<i class="fa-solid fa-circle-exclamation shrink-0 mt-0.5"></i>
							<div>
								<span class="font-bold">Peringatan Validasi:</span>
								<span id="error-text">Alur pengajuan tidak lengkap. Harap lengkapi data pejabat di unit kerja Anda sebelum
									melanjutkan.</span>
							</div>
						</div>
					</div>

					{{-- Tombol Lanjutkan --}}
					<div class="mt-6 pt-4 border-t border-slate-200 flex justify-center">
						<button type="submit" id="btn-lanjut" disabled
							class="inline-flex items-center gap-2 rounded bg-cyan-600 px-12 py-2.5 text-sm font-semibold text-white shadow-md transition duration-200 hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none">
							<span>Lanjut Isi Detail SPPD</span>
							<i class="fa-solid fa-arrow-right text-xs"></i>
						</button>
					</div>
				</div>

			</div>
		</form>
	</div>
@endsection

@push('scripts')
	<script>
		$(document).ready(function() {
			$('#user_id, #domain').on('change', function() {
				cekAlur();
			});

			async function cekAlur() {
				const userId = $('#user_id').val();
				const domain = $('#domain').val();

				if (!userId) {
					$('#workflow-preview').addClass('hidden');
					return;
				}

				const $preview = $('#workflow-preview');
				const $stepsContainer = $('#workflow-steps');
				const $btnLanjut = $('#btn-lanjut');
				const $errorMsg = $('#workflow-error-msg');
				const $statusBadge = $('#workflow-status-badge');

				$preview.removeClass('hidden');
				$stepsContainer.html(
					'<div class="col-span-full py-6 text-center text-sm font-medium text-slate-400 italic"><i class="fa-solid fa-circle-notch fa-spin mr-2 text-cyan-600"></i>Memvalidasi alur instansi...</div>'
				);
				$btnLanjut.prop('disabled', true);
				$errorMsg.addClass('hidden');

				try {
					const data = await $.getJSON(`/api/sppd/workflow-preview`, {
						user_id: userId,
						domain: domain
					});

					$stepsContainer.empty();
					let isComplete = true;

					if (data.steps.length === 0) {
						$stepsContainer.html(
							`<div class="col-span-full rounded border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                            <i class="fa-solid fa-triangle-exclamation mr-2 text-red-500"></i>
                            Aturan alur untuk kategori ini belum dibuat oleh Admin (Role Pelaksana: <span class="font-bold underline">${data.user.role.toUpperCase()}</span>).
                          </div>`
						);
						isComplete = false;
						$statusBadge.html(
							'<span class="inline-block rounded-sm bg-red-50 border border-red-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-red-700">Belum Diatur</span>'
						);
					} else {
						$.each(data.steps, function(index, step) {
							if (step.status !== 'found') isComplete = false;

							const isFound = step.status === 'found';

							// Konfigurasi warna dinamis: Hijau jika ditemukan, Merah jika kosong
							const cardClass = isFound ?
								'bg-emerald-50/60 border-emerald-200 shadow-2xs' :
								'bg-red-50 border-red-200 ring-1 ring-red-200';

							const badgeClass = isFound ?
								'bg-emerald-600 text-white' :
								'bg-red-600 text-white';

							const labelClass = isFound ?
								'text-emerald-600 font-bold' :
								'text-red-500 font-bold';

							const nameClass = isFound ?
								'text-emerald-900 font-bold' :
								'text-red-700 italic font-medium';

							const stepHtml = `
                <div class="flex items-center gap-3 rounded border p-3 transition-all ${cardClass}">
                  <div class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold shadow-2xs ${badgeClass}">
                    ${index + 1}
                  </div>
                  <div class="flex-1 min-w-0 leading-tight">
                    <p class="text-xs uppercase tracking-wider ${labelClass}">
                      ${step.role_label}
                    </p>
                    <p class="text-sm mt-0.5 truncate ${nameClass}" title="${step.approver_name}">
                      ${step.approver_name}
                    </p>
                  </div>
                </div>
              `;
							$stepsContainer.append(stepHtml);
						});

						$statusBadge.html(isComplete ?
							'<span class="inline-flex items-center gap-1 rounded-sm bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-emerald-700"><i class="fa-solid fa-circle-check text-xs"></i> Lengkap</span>' :
							'<span class="inline-flex items-center gap-1 rounded-sm bg-amber-50 border border-amber-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-amber-700">Tidak Lengkap</span>'
						);
					}

					if (!data.has_header) {
						isComplete = false;
						$errorMsg.removeClass('hidden');
						$('#error-text').html(
							`<strong>Kop Surat Belum Ada:</strong> Unit kerja <span class="font-bold underline">${data.user.department}</span> belum mengunggah Kop Surat Resmi. Harap hubungi Admin OPD Anda untuk melengkapi data dokumen.`
						);
					}

					if (isComplete && data.steps.length > 0) {
						$btnLanjut.prop('disabled', false);
						$errorMsg.addClass('hidden');
					} else {
						$btnLanjut.prop('disabled', true);
						$errorMsg.removeClass('hidden');
						if (!data.has_header) {
							// Menghindari overwrite jika error kop surat sudah diatur di atas
						} else if (data.steps.length === 0) {
							$('#error-text').text(
								'Aturan alur verifikasi belum dikonfigurasi untuk kriteria pemohon atau tujuan ini.'
							);
						} else {
							$('#error-text').text(
								'Ada pejabat struktural yang belum ditentukan dalam alur ini. Harap lengkapi struktur organisasi di menu Unit Kerja.'
							);
						}
					}
				} catch (error) {
					$stepsContainer.html(
						'<div class="col-span-full text-center py-4 text-sm font-medium text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i> Gagal memuat data validasi alur.</div>'
					);
				}
			}

			if ($('#user_id').val()) cekAlur();
		});
	</script>
@endpush
