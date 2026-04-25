@extends('layouts.app')

@section('content')
	<div class="mb-6 flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold text-slate-800">Buat SPPD Baru</h1>
			<p class="text-slate-500 text-sm">Tahap 1: Pilih Pelaksana & Validasi Alur</p>
		</div>
	</div>


	<form action="{{ route('sppd.create.details') }}" method="GET" id="form-step-1">
		{{-- TAHAP 1: DATA PELAKSANA & CEK ALUR --}}
		<div class="card p-6 mb-6 border-l-4 border-l-primary-500 shadow-sm">
			<div class="bg-primary-50 px-4 py-2 -mx-6 -mt-6 mb-4 border-b border-primary-100 flex justify-between items-center">
				<h3 class="font-bold text-primary-800 text-sm tracking-wide uppercase">TAHAP 1: PELAKSANA & ESTIMASI ALUR</h3>
				<span class="badge bg-primary-600 text-white rounded-full h-6 w-6 flex items-center justify-center p-0">1</span>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
				<div>
					<label for="user_id" class="form-label font-bold text-slate-700">Pelaksana Perjalanan Dinas <span
							class="text-red-500">*</span></label>
					<select name="user_id" id="user_id" class="form-select select2" required>
						<option value="">— Pilih Pegawai yang Berangkat —</option>
						@foreach ($users as $u)
							<option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
								{{ $u->nip }} — {{ $u->name }}
							</option>
						@endforeach
					</select>
				</div>
				<div>
					<label for="domain" class="form-label font-bold text-slate-700">Domain Perjalanan <span
							class="text-red-500">*</span></label>
					<select name="domain" id="domain" class="form-select" required>
						<option value="dalam_daerah" {{ old('domain') == 'dalam_daerah' ? 'selected' : '' }}>Dalam Daerah</option>
						<option value="lddp" {{ old('domain') == 'lddp' ? 'selected' : '' }}>Luar Daerah Dalam Provinsi (LDDP)</option>
						<option value="ldlp" {{ old('domain') == 'ldlp' ? 'selected' : '' }}>Luar Daerah Luar Provinsi (LDLP)</option>
					</select>
				</div>
			</div>

			{{-- Container Alur --}}
			<div id="workflow-preview" class="mt-8 hidden p-5 bg-slate-50 rounded-xl border border-slate-200">
				<div class="flex justify-between items-center mb-4">
					<h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
						<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
								d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
						</svg>
						Pratinjau Alur Persetujuan
					</h4>
					<div id="workflow-status-badge"></div>
				</div>

				<div id="workflow-steps" class="flex flex-col md:flex-row flex-wrap items-center gap-4">
					{{-- Steps will be injected here --}}
				</div>

				<div id="workflow-error-msg"
					class="mt-4 p-3 bg-rose-50 border border-rose-100 rounded-lg text-xs text-rose-700 hidden">
					<strong>Peringatan:</strong> <span id="error-text">Alur pengajuan tidak lengkap. Harap lengkapi data pejabat di unit
						kerja Anda sebelum melanjutkan.</span>
				</div>

				<div class="mt-6 pt-4 border-t border-slate-200 flex justify-center">
					<button type="submit" id="btn-lanjut" disabled
						class="btn-primary py-3 px-16 shadow-md disabled:bg-slate-300 disabled:cursor-not-allowed transition-all duration-300 transform scale-95 opacity-50">
						Lanjut Isi Detail SPPD &rarr;
					</button>
				</div>
			</div>
		</div>
	</form>
@endsection

@push('scripts')
	<script>
		$(document).ready(function() {
			// Event Listeners
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
					'<div class="py-4 text-slate-400 text-sm italic animate-pulse">Sedang memvalidasi alur...</div>'
				);
				$btnLanjut.prop('disabled', true).addClass('opacity-50 scale-95');
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
							`<p class="text-rose-600 font-bold p-3 bg-rose-50 border border-rose-200 rounded-lg w-full text-sm">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Aturan alur untuk kategori ini belum dibuat oleh Admin (Role Pelaksana: <span class="underline">${data.user.role.toUpperCase()}</span>).
                            </p>`
						);
						isComplete = false;
						$statusBadge.html(
							'<span class="px-2 py-1 bg-rose-100 text-rose-700 text-[10px] font-bold rounded uppercase">Belum Diatur</span>'
						);
					} else {
						$.each(data.steps, function(index, step) {
							if (step.status !== 'found') isComplete = false;

							const stepHtml = `
                                <div class="flex items-center gap-3 p-3 rounded-lg border ${step.status === 'found' ? 'bg-white border-slate-200 shadow-sm' : 'bg-rose-50 border-rose-300 ring-1 ring-rose-300'} min-w-[240px]">
                                    <div class="shrink-0 w-9 h-9 ${step.status === 'found' ? 'bg-green-500 text-white' : 'bg-rose-500 text-white'} rounded-full flex items-center justify-center text-sm font-bold shadow-sm">
                                        ${index + 1}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-extrabold ${step.status === 'found' ? 'text-slate-500' : 'text-rose-600'} uppercase tracking-tight leading-none mb-1">
                                            ${step.role_label}
                                        </p>
                                        <p class="text-[13px] font-bold ${step.status === 'found' ? 'text-slate-800' : 'text-rose-700 italic'} truncate" title="${step.approver_name}">
                                            ${step.approver_name}
                                        </p>
                                    </div>
                                </div>
                            `;
							$stepsContainer.append(stepHtml);
						});

						$statusBadge.html(isComplete ?
							'<span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded uppercase flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> Lengkap</span>' :
							'<span class="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded uppercase flex items-center gap-1">Tidak Lengkap</span>'
						);
					}

					// Cek Kop Surat
					if (!data.has_header) {
						isComplete = false;
						$errorMsg.removeClass('hidden');
						$('#error-text').html(
							`<strong>Kop Surat Belum Ada:</strong> Unit kerja <span class="font-bold underline">${data.user.department}</span> belum mengunggah Kop Surat. Harap hubungi Admin OPD Anda untuk melengkapi Kop Surat di menu Unit Kerja.`
						);
					}

					if (isComplete && data.steps.length > 0) {
						$btnLanjut.prop('disabled', false).removeClass('opacity-50 scale-95');
						$errorMsg.addClass('hidden');
					} else {
						$btnLanjut.prop('disabled', true).addClass('opacity-50 scale-95');
						$errorMsg.removeClass('hidden');
						if (!data.has_header) {
							// Already set above
						} else if (data.steps.length === 0) {
							$('#error-text').text('Aturan alur belum dibuat untuk kriteria pemohon/tujuan ini.');
						} else {
							$('#error-text').text(
								'Ada pejabat yang belum ditemukan dalam alur ini. Harap lengkapi data pejabat di menu Unit Kerja.'
							);
						}
					}
				} catch (error) {
					$stepsContainer.html('<p class="text-rose-600">Gagal memuat data alur.</p>');
				}
			}

			// Initial trigger if redirected back
			if ($('#user_id').val()) cekAlur();
		});
	</script>
@endpush
