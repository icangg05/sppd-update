@extends('layouts.app')
@section('title', 'Kelola SPT')

@section('content')
	<div class="mx-auto max-w-6xl space-y-6 p-1">

		{{-- Header (title card) — gaya kartu judul index, ditint emerald sesuai identitas SPT. --}}
		<div
			class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-emerald-50/50 px-5 py-4 shadow-sm">
			<i class="fa-solid fa-file-signature pointer-events-none absolute -right-3 -top-4 text-8xl text-emerald-500/6"
				aria-hidden="true"></i>
			{{-- Ornamen garis vertikal (selaras laman laporan) --}}
			<span class="pointer-events-none absolute inset-y-3 left-0 w-1 rounded-r bg-linear-to-b from-emerald-400/40 to-primary-400/40"
				aria-hidden="true"></span>

			<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
				<div class="min-w-0 leading-tight">
					<span
						class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
						<i class="fa-solid fa-print text-[10px]"></i> Cetak &amp; Status TTE
					</span>
					<h1 class="text-xl font-bold tracking-tight text-slate-800">Kelola SPT</h1>
					<p class="mt-1 text-sm text-slate-500">Cetak dokumen dan pantau status tanda tangan elektronik SPT.</p>
				</div>
				<x-ui.button href="{{ route('sppd.next', $sppd) }}" variant="secondary" class="shrink-0">
					<x-slot name="icon"><i class="fa-solid fa-arrow-left text-xs"></i></x-slot>
					Kembali
				</x-ui.button>
			</div>
		</div>

		<div class="dash-enter relative rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
			{{-- Ornamen: garis aksen kiri + watermark ikon dokumen di sudut. --}}
			<span class="absolute inset-y-0 left-0 w-1 bg-emerald-500/70" aria-hidden="true"></span>
			<i class="fa-solid fa-file-signature pointer-events-none absolute -bottom-6 -right-4 text-8xl text-emerald-500/6" aria-hidden="true"></i>

			{{-- Info Card --}}
			<div class="relative p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

				<div class="space-y-6">
					<div>
						<p class="text-xs font-bold uppercase text-slate-500">Pelaksana Tugas</p>
						<p class="text-sm font-bold text-slate-800 mt-1 uppercase mb-3">{{ $sppd->user->name }}</p>
						@php
							$sptSigTemp = $sppd->signatureFor('spt');
							$sptUrl =
							    $sptSigTemp && $sptSigTemp->status->value === 'signed' && $sptSigTemp->signed_file_path
							        ? Storage::url($sptSigTemp->signed_file_path)
							        : route('sppd.stream.spt', $sppd);
						@endphp
						<a href="{{ $sptUrl }}" target="_blank"
							class="inline-flex items-center gap-1.5 rounded bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-2xs transition hover:bg-emerald-700 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:ring-offset-1">
							<i class="fa-solid fa-print"></i> Cetak dokumen SPT
						</a>
						<p class="mt-2 text-xs text-slate-500 font-medium leading-relaxed">
							<i class="fa-solid fa-circle-info mr-1"></i>
							Sistem menghasilkan dokumen PDF yang sudah siap cetak atau ditandatangani secara elektronik.
						</p>
					</div>
				</div>

				<div class="space-y-4">
					<div class="flex justify-between items-center py-2 border-b border-slate-100">
						<span class="text-xs font-bold text-slate-500 uppercase">Tanggal Dokumen</span>
						<span
							class="text-sm font-bold text-slate-800">{{ $sppd->spt_date?->translatedFormat('d F Y') ?? $sppd->created_at->translatedFormat('d F Y') }}</span>
					</div>

					@php $sptSignature = $sppd->signatureFor('spt'); @endphp

					{{-- Status TTE --}}
					<div class="rounded border border-slate-200 bg-slate-50 p-4"
						@if ($sptSignature) data-tte-signature-id="{{ $sptSignature->id }}" @endif>
						<div class="flex justify-between items-start">
							<div>
								<p class="text-xs font-bold text-slate-500 uppercase">Status TTE</p>
								<div class="tte-badge-container mt-1.5">
									@if ($sptSignature && $sptSignature->status->value === 'signed')
										<span
											class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">
											Sudah Ditandatangani
										</span>
									@elseif ($sptSignature && $sptSignature->status->value === 'processing')
										<span
											class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 animate-pulse">
											<i class="fa-solid fa-spinner animate-spin text-xs"></i> Sedang Diproses
										</span>
									@elseif ($sptSignature && $sptSignature->status->value === 'rejected')
										<span
											class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">
											Gagal TTE
										</span>
									@else
										<span
											class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">
											Belum Diproses
										</span>
									@endif
								</div>

								@if ($sptSignature && $sptSignature->status->value === 'signed' && $sptSignature->signer)
									<div class="mt-2.5 text-xs text-slate-600 font-medium">
										<i class="fa-solid fa-signature text-slate-500 mr-1"></i>
										Penandatangan: <span class="text-slate-800 font-bold">{{ $sptSignature->signer->name }}</span>
										@if ($sptSignature->signer->nip)
											<span class="text-slate-500 font-normal">(NIP. {{ $sptSignature->signer->nip }})</span>
										@endif
									</div>
								@endif
							</div>
						</div>

						<div class="tte-error-container mt-2 {{ $sptSignature && $sptSignature->error_message ? '' : 'hidden' }}">
							<p class="text-xs text-rose-600 font-medium">
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
												`<span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">Sudah Ditandatangani</span>`;
											errorContainer.classList.add('hidden');
										} else if (sig.status === 'processing') {
											badgeContainer.innerHTML =
												`<span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 animate-pulse"><i class="fa-solid fa-spinner animate-spin text-xs"></i> Sedang Diproses</span>`;
											errorContainer.classList.add('hidden');
										} else if (sig.status === 'rejected') {
											badgeContainer.innerHTML =
												`<span class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">Gagal TTE</span>`;
											if (sig.error_message) {
												errorMessageText.textContent = sig.error_message;
												errorContainer.classList.remove('hidden');
											}
										} else {
											badgeContainer.innerHTML =
												`<span class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">Belum Diproses</span>`;
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
