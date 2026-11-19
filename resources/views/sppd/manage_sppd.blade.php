@extends('layouts.app')
@section('title', 'Kelola SPPD')

@section('content')
	@php
		// Setiap personel punya dokumen SPPD & tanda tangan sendiri (sppd_{user_id}).
		// Sudah TTE → tautan langsung ke berkas bertanda tangan (tanpa stream); belum → PDF
		// di-generate lewat stream. Berkas yang tidak ada di disk server ini diberi catatan.
		$makePerson = function ($user, string $role, $signature, string $streamUrl) {
		    $signedFile = $signature?->status->value === 'signed' && $signature->signed_file_path ? $signature->signed_file_path : null;

		    return [
		        'user' => $user,
		        'role' => $role,
		        'signature' => $signature,
		        'url' => $signedFile ? Storage::url($signedFile) : $streamUrl,
		        'file_missing' => $signedFile && ! Storage::disk('public')->exists($signedFile),
		    ];
		};

		$personnel = collect([
		    $makePerson($sppd->user, 'Pelaksana', $sppd->signatureFor('sppd'), route('sppd.stream.sppd', $sppd)),
		])->concat(
		    $sppd->followers->map(fn($f) => $makePerson(
		        $f->user,
		        'Pengikut',
		        $sppd->digitalSignatures->where('document_type', 'sppd_' . $f->user_id)->sortByDesc('created_at')->first(),
		        route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($f->user_id)]),
		    )),
		);
		$signedCount = $personnel->filter(fn($p) => $p['signature']?->status->value === 'signed')->count();
		$anyProcessing = $personnel->contains(fn($p) => $p['signature']?->status->value === 'processing');
		$sppdDate = $sppd->sppd_date ?? $sppd->created_at;
	@endphp

	<div class="mx-auto max-w-6xl space-y-4 p-1">

		<x-sppd.doc-header :sppd="$sppd" title="Surat Perintah Perjalanan Dinas"
			description="Cetak SPPD tiap personel dan pantau status tanda tangan elektroniknya." />

		<x-sppd.panel title="Dokumen SPPD per Personel" icon="fa-file-contract">
			<x-slot:aside>{{ $personnel->count() }} orang · {{ $signedCount }}/{{ $personnel->count() }} sudah TTE</x-slot:aside>

			<div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
				<p class="text-sm text-slate-600">Tanggal SPPD <span class="ml-1 font-semibold text-slate-800">{{ $sppdDate->translatedFormat('d F Y') }}</span></p>
				@if ($personnel->count() > 5)
					<div class="relative w-full sm:w-64">
						<i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500"></i>
						<input type="search" id="personel-search-input" placeholder="Cari nama pegawai..." aria-label="Cari nama pegawai"
							class="w-full rounded border border-slate-300 bg-white py-1.5 pl-8 pr-3 text-sm text-slate-800 placeholder-slate-400 shadow-2xs outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30" />
					</div>
				@endif
			</div>

			<div class="overflow-x-auto">
				<table class="table-stack w-full border-collapse text-left text-sm">
					<thead>
						<tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
							<th class="w-12 px-4 py-2.5 text-center">No.</th>
							<th class="px-4 py-2.5">Nama</th>
							<th class="w-28 px-4 py-2.5">Peran</th>
							{{-- Tanpa lebar tetap: kolom ini mengambil sisa ruang agar keterangan TTE cukup 2 baris. --}}
							<th class="px-4 py-2.5">Status TTE</th>
							<th class="w-28 px-4 py-2.5 text-right">Aksi</th>
						</tr>
					</thead>
					<tbody id="personel-list" class="divide-y divide-slate-100">
						@foreach ($personnel as $i => $person)
							@php $status = $person['signature']?->status->value; @endphp
							<tr class="transition-colors hover:bg-slate-50" data-search-term="{{ strtolower($person['role'] . ' ' . $person['user']->name) }}">
								<td class="stack-hide px-4 py-3 text-center tabular-nums text-slate-500">{{ $i + 1 }}.</td>
								<td data-label="Nama" class="stack-block px-4 py-3">
									<span class="block font-semibold text-slate-800">{{ $person['user']->name }}</span>
									@if ($person['user']->nip)
										<span class="block font-mono text-xs text-slate-500">{{ $person['user']->nip }}</span>
									@endif
								</td>
								<td data-label="Peran" class="px-4 py-3">
									<x-ui.badge :color="$person['role'] === 'Pelaksana' ? 'cyan' : 'slate'">{{ $person['role'] }}</x-ui.badge>
								</td>
								{{-- Ringkas 2 baris: badge + peringatan berkas di baris pertama, penanda tangan · waktu di baris kedua. --}}
								<td data-label="Status TTE" class="stack-block px-4 py-3">
									<div class="flex flex-wrap items-center gap-x-2 gap-y-1">
										<x-sppd.tte-badge :status="$status" :testing="(bool) $person['signature']?->isSimulated()" />
										@if ($person['file_missing'])
											<span class="inline-flex items-center gap-1 text-xs font-medium text-rose-700" title="Berkas TTE tidak ditemukan di server ini.">
												<i class="fa-solid fa-triangle-exclamation text-[10px]"></i>Berkas tidak ada di server ini
											</span>
										@endif
									</div>
									@if ($status === 'signed')
										<span class="mt-1 block text-xs text-slate-500">
											<span class="font-semibold text-slate-700">{{ $person['signature']->signer?->name }}</span>@if ($person['signature']->signed_at) · <span class="whitespace-nowrap tabular-nums">{{ $person['signature']->signed_at->translatedFormat('d M Y H:i') }} WITA</span>@endif
										</span>
									@elseif ($status === 'rejected' && $person['signature']->error_message)
										<span class="mt-1 block text-xs text-rose-700">{{ $person['signature']->error_message }}</span>
									@endif
								</td>
								<td data-label="Aksi" class="px-4 py-3 text-right">
									<x-sppd.print-link :href="$person['url']">Cetak</x-sppd.print-link>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			<p class="border-t border-slate-200 bg-slate-50 px-5 py-3 text-xs text-slate-600">
				<i class="fa-solid fa-circle-info mr-1 text-slate-500"></i>
				Bila SPPD personel sudah ditandatangani elektronik, tombol Cetak membuka dokumen bertanda tangan.
			</p>
		</x-sppd.panel>
	</div>

	@if ($anyProcessing)
		@include('sppd.partials.tte-poll')
	@endif

	@if ($personnel->count() > 5)
		@push('scripts')
			<script>
				(function() {
					const input = document.getElementById('personel-search-input');
					if (!input) return;
					input.addEventListener('input', () => {
						const query = input.value.toLowerCase().trim();
						document.querySelectorAll('#personel-list tr[data-search-term]').forEach((row) => {
							row.style.display = row.dataset.searchTerm.includes(query) ? '' : 'none';
						});
					});
				})();
			</script>
		@endpush
	@endif
@endsection
