@extends('layouts.app')
@section('title', 'Kelola SPPD')

@section('content')
	<div class="p-1 space-y-6">

		{{-- Header --}}
		<div class="flex items-center justify-between">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
					<i class="fa-solid fa-file-contract mr-2 text-emerald-600"></i>Kelola SPPD
				</h1>
			</div>
			<a wire:navigate href="{{ route('sppd.next', $sppd) }}"
				class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
				<i class="fa-solid fa-arrow-left"></i> Kembali
			</a>
		</div>

		<div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
			<div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

				{{-- Daftar Personel --}}
				<div class="space-y-3">
					<div class="flex items-center justify-between mb-2">
						<p class="text-[10px] font-bold uppercase text-slate-500">Daftar Pelaksana & Pengikut</p>
						<span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
							{{ 1 + $sppd->followers->count() }} Orang
						</span>
					</div>

					<div class="relative mb-3">
						<input type="text" id="personel-search-input" placeholder="Cari nama pegawai..."
							class="w-full rounded border border-slate-300 bg-white pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500" />
						<i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-500 text-[10px]"></i>
					</div>

					<div class="relative overflow-hidden border border-slate-200 rounded bg-white shadow-xs">
						<div class="overflow-y-auto scrollbar-thin max-h-[420px]" id="personel-list-container">
							<table class="w-full text-left border-collapse">
								<thead>
									<tr class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
										<th class="py-2.5 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-12 text-center">No.</th>
										<th class="py-2.5 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Lengkap</th>
										<th class="py-2.5 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-24">Peran</th>
										<th class="py-2.5 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-24 text-center">Aksi
										</th>
									</tr>
								</thead>
								<tbody id="personel-list" class="divide-y divide-slate-100">
									{{-- Main Pelaksana --}}
									<tr class="hover:bg-slate-50/80 transition-colors"
										data-search-term="pelaksana {{ strtolower($sppd->user->name) }}">
										<td class="py-2 px-3 text-xs text-slate-500 text-center font-medium">1.</td>
										<td class="py-2 px-3">
											<span class="text-xs font-semibold text-slate-800">{{ $sppd->user->name }}</span>
										</td>
										<td class="py-2 px-3">
											<span
												class="inline-block bg-primary-50 text-primary-700 border border-primary-200/60 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">
												Pelaksana
											</span>
										</td>
										<td class="py-2 px-3 text-center">
											<a href="{{ route('sppd.stream.sppd', $sppd) }}" target="_blank"
												class="inline-flex items-center gap-1 rounded bg-primary-600 px-2 py-1 text-[9px] font-bold text-white transition hover:bg-primary-700 shadow-xs">
												<i class="fa-solid fa-print"></i> CETAK
											</a>
										</td>
									</tr>

									{{-- Pengikut --}}
									@foreach ($sppd->followers as $index => $f)
										<tr class="hover:bg-slate-50/80 transition-colors"
											data-search-term="pengikut {{ strtolower($f->user->name) }}">
											<td class="py-2 px-3 text-xs text-slate-500 text-center font-medium">{{ $index + 2 }}.</td>
											<td class="py-2 px-3">
												<span class="text-xs font-medium text-slate-700">{{ $f->user->name }}</span>
											</td>
											<td class="py-2 px-3">
												<span
													class="inline-block bg-slate-50 text-slate-600 border border-slate-200 px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase tracking-wider">
													Pengikut
												</span>
											</td>
											<td class="py-2 px-3 text-center">
												<a
													href="{{ route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($f->user_id)]) }}"
													target="_blank"
													class="inline-flex items-center gap-1 rounded bg-slate-600 px-2 py-1 text-[9px] font-bold text-white transition hover:bg-slate-700 shadow-xs">
													<i class="fa-solid fa-print"></i> CETAK
												</a>
											</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>

				{{-- Status TTE --}}
				<div class="space-y-4">
					<div class="flex justify-between items-center py-2 border-b border-slate-100">
						<span class="text-xs font-bold text-slate-500 uppercase">Tanggal SPPD</span>
						<span
							class="text-sm font-bold text-slate-800">{{ $sppd->sppd_date?->translatedFormat('d F Y') ?? $sppd->created_at->translatedFormat('d F Y') }}</span>
					</div>

					@php $sppdSignature = $sppd->signatureFor('sppd'); @endphp

					<div class="rounded border border-slate-200 bg-slate-50 p-4"
						@if ($sppdSignature) data-tte-signature-id="{{ $sppdSignature->id }}" @endif>
						<div class="flex justify-between items-start">
							<div>
								<p class="text-xs font-bold text-slate-500 uppercase">Status TTE SPPD</p>
								<div class="tte-badge-container mt-1.5">
									@if ($sppdSignature && $sppdSignature->status->value === 'signed')
										<span
											class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
											Sudah Ditandatangani
										</span>
									@elseif ($sppdSignature && $sppdSignature->status->value === 'processing')
										<span
											class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 animate-pulse">
											<i class="fa-solid fa-spinner animate-spin text-[10px]"></i> Sedang Diproses
										</span>
									@elseif ($sppdSignature && $sppdSignature->status->value === 'rejected')
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

								@if ($sppdSignature && $sppdSignature->status->value === 'signed' && $sppdSignature->signer)
									<div class="mt-2.5 text-[11px] text-slate-600 font-medium">
										<i class="fa-solid fa-signature text-slate-500 mr-1"></i>
										Penandatangan: <span class="text-slate-800 font-bold">{{ $sppdSignature->signer->name }}</span>
										@if ($sppdSignature->signer->nip)
											<span class="text-slate-500 font-normal">(NIP. {{ $sppdSignature->signer->nip }})</span>
										@endif
									</div>
								@endif
							</div>
						</div>

						<div class="tte-error-container mt-2 {{ $sppdSignature && $sppdSignature->error_message ? '' : 'hidden' }}">
							<p class="text-[10px] text-rose-600 font-medium">
								<i class="fa-solid fa-circle-exclamation mr-1"></i> Error: <span
									class="error-message-text">{{ $sppdSignature?->error_message }}</span>
							</p>
						</div>
					</div>
				</div>
			</div>

			{{-- Footer Note --}}
			<div class="bg-slate-50 border-t border-slate-100 p-4 flex items-center gap-3 text-[11px] text-slate-500 italic">
				<i class="fa-solid fa-circle-info text-slate-500"></i>
				Sistem menghasilkan dokumen PDF yang sudah siap cetak atau ditandatangani secara elektronik.
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		@if ($sppdSignature && $sppdSignature->status->value === 'processing')
			(function() {
				let pollInterval = setInterval(checkTteStatus, 5000);

				function checkTteStatus() {
					fetch("{{ route('sppd.sign.batch-status', $sppd) }}")
						.then(response => response.json())
						.then(data => {
							if (data.signatures) {
								const sig = data.signatures.find(s => s.id == "{{ $sppdSignature->id }}");
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
		@endif

		// Personnel search filter
		(function() {
			const searchInput = document.getElementById('personel-search-input');
			if (searchInput) {
				searchInput.addEventListener('input', function() {
					const query = this.value.toLowerCase().trim();
					const items = document.querySelectorAll('#personel-list tr[data-search-term]');
					items.forEach(item => {
						const searchTerm = item.getAttribute('data-search-term') || '';
						if (searchTerm.includes(query)) {
							item.style.display = '';
						} else {
							item.style.display = 'none';
						}
					});
				});
			}
		})();
	</script>
@endpush
