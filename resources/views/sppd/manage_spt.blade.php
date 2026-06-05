@extends('layouts.app')
@section('title', 'Kelola SPT')

@section('content')
	<div class="p-1 space-y-6">

		{{-- Header --}}
		<div class="flex items-center justify-between">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
					<i class="fa-solid fa-file-signature mr-2 text-emerald-600"></i>Kelola SPT
				</h1>
			</div>
			<a wire:navigate href="{{ route('sppd.next', $sppd) }}"
				class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
				<i class="fa-solid fa-arrow-left"></i> Kembali
			</a>
		</div>

		<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
			{{-- Info Card --}}
			<div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

				<div class="space-y-6">
					<div>
						<p class="text-[10px] font-bold uppercase text-slate-400">Pelaksana Tugas</p>
						<p class="text-sm font-bold text-slate-800 mt-1 uppercase mb-3">{{ $sppd->user->name }}</p>
						@php
							$sptSigTemp = $sppd->signatureFor('spt');
							$sptUrl =
							    $sptSigTemp && $sptSigTemp->status->value === 'signed' && $sptSigTemp->signed_file_path
							        ? Storage::url($sptSigTemp->signed_file_path)
							        : route('sppd.stream.spt', $sppd);
						@endphp
						<a href="{{ $sptUrl }}" target="_blank"
							class="inline-flex items-center gap-1.5 rounded bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-700">
							<i class="fa-solid fa-print"></i> CETAK DOKUMEN SPT
						</a>
					</div>
				</div>

				<div class="space-y-4">
					<div class="flex justify-between items-center py-2 border-b border-slate-100">
						<span class="text-xs font-bold text-slate-400 uppercase">Tanggal Dokumen</span>
						<span
							class="text-sm font-bold text-slate-800">{{ $sppd->spt_date?->translatedFormat('d F Y') ?? $sppd->created_at->translatedFormat('d F Y') }}</span>
					</div>

					@php $sptSignature = $sppd->signatureFor('spt'); @endphp

					{{-- Status TTE --}}
					<div class="rounded-lg border border-slate-200 bg-slate-50 p-4"
						@if ($sptSignature) data-tte-signature-id="{{ $sptSignature->id }}" @endif>
						<div class="flex justify-between items-start">
							<div>
								<p class="text-xs font-bold text-slate-500 uppercase">Status TTE</p>
								<div class="tte-badge-container mt-1.5">
									@if ($sptSignature && $sptSignature->status->value === 'signed')
										<span
											class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
											Sudah Ditandatangani
										</span>
									@elseif ($sptSignature && $sptSignature->status->value === 'processing')
										<span
											class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 animate-pulse">
											<i class="fa-solid fa-spinner animate-spin text-[10px]"></i> Sedang Diproses
										</span>
									@elseif ($sptSignature && $sptSignature->status->value === 'rejected')
										<span
											class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
											Gagal TTE
										</span>
									@else
										<span
											class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
											Belum Diproses
										</span>
									@endif
								</div>

								@if ($sptSignature && $sptSignature->status->value === 'signed' && $sptSignature->signer)
									<div class="mt-2.5 text-[11px] text-slate-600 font-medium">
										<i class="fa-solid fa-signature text-slate-400 mr-1"></i>
										Penandatangan: <span class="text-slate-800 font-bold">{{ $sptSignature->signer->name }}</span>
										@if ($sptSignature->signer->nip)
											<span class="text-slate-500 font-normal">(NIP. {{ $sptSignature->signer->nip }})</span>
										@endif
									</div>
								@endif
							</div>
						</div>

						<div class="tte-error-container mt-2 {{ $sptSignature && $sptSignature->error_message ? '' : 'hidden' }}">
							<p class="text-[10px] text-rose-600 font-medium">
								<i class="fa-solid fa-circle-exclamation mr-1"></i> Error: <span
									class="error-message-text">{{ $sptSignature?->error_message }}</span>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	@if ($sptSignature && $sptSignature->status->value === 'processing')
		<script>
			(function() {
				let pollInterval = setInterval(checkTteStatus, 5000);

				function checkTteStatus() {
					fetch("{{ route('sppd.sign.batch-status', $sppd) }}")
						.then(response => response.json())
						.then(data => {
							if (data.signatures) {
								const sig = data.signatures.find(s => s.id == "{{ $sptSignature->id }}");
								if (sig) {
									const container = document.querySelector(`[data-tte-signature-id="${sig.id}"]`);
									if (container) {
										const badgeContainer = container.querySelector('.tte-badge-container');
										const errorContainer = container.querySelector('.tte-error-container');
										const errorMessageText = container.querySelector('.error-message-text');

										if (sig.status === 'signed') {
											badgeContainer.innerHTML =
												`<span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Sudah Ditandatangani</span>`;
											errorContainer.classList.add('hidden');
										} else if (sig.status === 'processing') {
											badgeContainer.innerHTML =
												`<span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 animate-pulse"><i class="fa-solid fa-spinner animate-spin text-[10px]"></i> Sedang Diproses</span>`;
											errorContainer.classList.add('hidden');
										} else if (sig.status === 'rejected') {
											badgeContainer.innerHTML =
												`<span class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Gagal TTE</span>`;
											if (sig.error_message) {
												errorMessageText.textContent = sig.error_message;
												errorContainer.classList.remove('hidden');
											}
										} else {
											badgeContainer.innerHTML =
												`<span class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Belum Diproses</span>`;
											errorContainer.classList.add('hidden');
										}
									}
								}
							}

							if (!data.is_processing) {
								clearInterval(pollInterval);
								setTimeout(() => {
									window.location.reload();
								}, 1000);
							}
						})
						.catch(err => console.error("Error polling TTE status:", err));
				}
			})();
		</script>
	@endif
@endpush
