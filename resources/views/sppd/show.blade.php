@extends('layouts.app')
@section('title', 'Detail SPPD')
@section('page-title', 'Detail SPPD')

@section('content')
	<div class="flex flex-col gap-6 p-1">

		{{-- Header Halaman & Aksi --}}
		<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
			<div class="leading-tight">
				<h1 class="text-lg font-bold text-slate-800">Detail Surat Perjalanan Dinas</h1>
				<p class="text-sm font-mono text-slate-500 mt-1">
					<i class="fa-solid fa-hashtag text-xs text-slate-400 mr-1"></i>
					{{ $sppd->document_number ?? 'Belum memiliki nomor seri' }}
				</p>
			</div>

			<div class="flex flex-wrap items-center gap-2.5">
				{{-- Tombol Batalkan Pengajuan --}}
				@if ($sppd->status->value === 'in_progress' && (auth()->id() === $sppd->creator_id || auth()->id() === $sppd->user_id))
					<form action="{{ route('sppd.destroy', $sppd) }}" method="POST"
						onsubmit="return confirm('Batalkan dan hapus pengajuan SPPD ini secara permanen?')">
						@csrf
						@method('DELETE')
						<button type="submit"
							class="inline-flex items-center gap-1.5 rounded bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700">
							<i class="fa-solid fa-trash-can"></i> Batalkan Pengajuan
						</button>
					</form>
				@endif

				{{-- Tombol Portal Selanjutnya --}}
				@if (in_array($sppd->status->value, ['approved', 'completed']))
					<a href="{{ route('sppd.next', $sppd) }}"
						class="inline-flex items-center gap-1.5 rounded bg-amber-500 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-amber-600">
						<i class="fa-solid fa-share-from-square"></i> Portal Selanjutnya
					</a>
				@endif

				{{-- Dokumen SPT / SPPD dengan Warna yang Disesuaikan --}}
				<button type="button" id="document-modal-open"
					class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-200/80 hover:text-slate-900">
					<i class="fa-solid fa-file-pdf text-cyan-600 text-[13px]"></i>
					Lihat Dokumen
				</button>

				{{-- Garis Pemisah Vertikal (Separator) --}}
				<div class="hidden sm:block border-l border-slate-300 h-5 self-center mx-0.5"></div>

				{{-- Badge Status --}}
				<span
					class="badge-{{ $sppd->status->value }} inline-block rounded-sm px-2.5 py-1 text-xs font-bold uppercase tracking-wide">
					{{ $sppd->status->label() }}
				</span>

				{{-- Tombol Kembali --}}
				<a href="{{ route('sppd.index') }}"
					class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
					<i class="fa-solid fa-arrow-left text-slate-400"></i> Kembali
				</a>
			</div>
		</div>

		{{-- Konten Utama Grid --}}
		<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

			{{-- Kolom Kiri: Informasi Utama --}}
			<div class="space-y-6 xl:col-span-2">

				{{-- 1. Info Perjalanan --}}
				<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
						<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
							<i class="fa-solid fa-address-card text-cyan-600"></i> Informasi Perjalanan
						</h3>
					</div>
					<div class="p-5 grid grid-cols-1 gap-y-5 gap-x-8 sm:grid-cols-2">
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Pelaksana</p>
							<p class="text-sm font-bold text-slate-800">{{ $sppd->user->name }}</p>
							<p class="text-xs font-mono text-slate-500">{{ $sppd->user->nip ?? '-' }}</p>
						</div>
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Instansi Pengusul</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->budget?->department?->name ?? '-' }}</p>
						</div>
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Kategori Perjalanan</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->category?->name ?? '-' }}</p>
						</div>
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Domain Wilayah</p>
							<p class="text-sm font-semibold text-slate-800"><span
									class="bg-cyan-50 text-cyan-700 px-2 py-0.5 rounded border border-cyan-100 text-xs uppercase">{{ $sppd->domain->label() }}</span>
							</p>
						</div>
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Tanggal Pelaksanaan</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->start_date->translatedFormat('d M Y') }} <i
									class="fa-solid fa-arrow-right text-[10px] text-slate-400 mx-1"></i>
								{{ $sppd->end_date->translatedFormat('d M Y') }}</p>
							<p class="text-xs text-slate-500 mt-0.5"><i class="fa-regular fa-clock"></i> Durasi: {{ $sppd->duration_days }}
								hari</p>
						</div>
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Pembuat Dokumen</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->creator?->name ?? '-' }}</p>
						</div>
						<div class="sm:col-span-2 pt-3 border-t border-slate-100">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Maksud Perjalanan</p>
							<p class="text-sm font-medium text-slate-800 leading-relaxed">{{ $sppd->purpose }}</p>
						</div>
						@if ($sppd->notes)
							<div class="sm:col-span-2">
								<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Catatan Tambahan</p>
								<p class="text-sm text-slate-600 bg-amber-50 p-3 rounded border border-amber-200">{{ $sppd->notes }}</p>
							</div>
						@endif
					</div>
				</div>

				{{-- 2. Tujuan --}}
				@if ($sppd->destinations->count())
					<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
						<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
							<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
								<i class="fa-solid fa-map-location-dot text-cyan-600"></i> Lokasi Tujuan
							</h3>
						</div>
						<div class="p-5 space-y-3">
							@foreach ($sppd->destinations as $dest)
								<div class="flex items-start gap-3 rounded border border-slate-200 bg-slate-50 p-3 shadow-2xs">
									<div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
										<i class="fa-solid fa-location-dot text-sm"></i>
									</div>
									<div class="min-w-0 leading-tight">
										<p class="text-sm font-bold text-slate-800">
											{{ $dest->province->name }}{{ $dest->regency ? ', ' . $dest->regency->name : '' }}</p>
										@if ($dest->address)
											<p class="text-xs text-slate-500 mt-1">{{ $dest->address }}</p>
										@endif
									</div>
								</div>
							@endforeach
						</div>
					</div>
				@endif

				{{-- 3. Pengikut --}}
				@if ($sppd->followers->count())
					<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
						<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
							<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
								<i class="fa-solid fa-users text-cyan-600"></i> Daftar Pengikut
							</h3>
						</div>
						<div class="p-5 flex flex-wrap gap-2.5">
							@foreach ($sppd->followers as $f)
								<div class="inline-flex items-center gap-2 rounded border border-slate-200 bg-white px-2 py-1.5 shadow-2xs">
									<span
										class="flex size-6 shrink-0 items-center justify-center rounded bg-cyan-600 text-[10px] font-bold text-white shadow-2xs">
										{{ strtoupper(substr($f->user->name, 0, 1)) }}
									</span>
									<span class="text-sm font-semibold text-slate-700 pr-2">{{ $f->user->name }}</span>
								</div>
							@endforeach
						</div>
					</div>
				@endif

				{{-- 4. Rincian Biaya --}}
				@if ($sppd->costDetails->count())
					<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
						<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
							<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
								<i class="fa-solid fa-file-invoice-dollar text-cyan-600"></i> Rincian Biaya Anggaran
							</h3>
						</div>
						<div class="overflow-x-auto">
							<table class="w-full text-left text-sm text-slate-600">
								<thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
									<tr>
										<th class="px-5 py-3">Uraian / Deskripsi</th>
										<th class="px-5 py-3 text-right">Biaya Satuan</th>
										<th class="px-5 py-3 text-center">Qty</th>
										<th class="px-5 py-3 text-right">Subtotal</th>
									</tr>
								</thead>
								<tbody class="divide-y divide-slate-100">
									@php $total = 0; @endphp
									@foreach ($sppd->costDetails as $c)
										@php
											$sub = $c->unit_cost * $c->quantity;
											$total += $sub;
										@endphp
										<tr class="hover:bg-slate-50 transition-colors">
											<td class="px-5 py-3 font-medium text-slate-800">{{ $c->description }}</td>
											<td class="px-5 py-3 text-right">Rp {{ number_format($c->unit_cost, 0, ',', '.') }}</td>
											<td class="px-5 py-3 text-center bg-slate-50/50">{{ $c->quantity }}</td>
											<td class="px-5 py-3 text-right font-bold text-slate-800">Rp {{ number_format($sub, 0, ',', '.') }}</td>
										</tr>
									@endforeach
									<tr class="bg-cyan-50/50 border-t-2 border-slate-200">
										<td colspan="3" class="px-5 py-3 text-right font-bold text-slate-700 uppercase tracking-wider text-xs">Total
											Anggaran</td>
										<td class="px-5 py-3 text-right font-bold text-cyan-700">Rp {{ number_format($total, 0, ',', '.') }}</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				@endif
			</div>

			{{-- Kolom Kanan: Timeline & Aksi --}}
			<div class="space-y-6">

				{{-- Timeline Persetujuan --}}
				<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
						<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
							<i class="fa-solid fa-list-check text-cyan-600"></i> Alur Persetujuan
						</h3>
					</div>
					<div class="p-5">
						@if ($sppd->approvals->count())
							<div class="relative border-l-2 border-slate-100 ml-3 space-y-6">
								@foreach ($sppd->approvals->sortBy('step_order') as $ap)
									<div class="relative pl-6">
										{{-- Titik Timeline --}}
										<span
											class="absolute -left-3.25 top-0.5 flex size-6 items-center justify-center rounded-full text-[10px] font-bold text-white shadow-2xs ring-4 ring-white
                    {{ $ap->status->value === 'approved' ? 'bg-emerald-500' : ($ap->status->value === 'rejected' ? 'bg-rose-500' : ($ap->status->value === 'revision' ? 'bg-amber-500' : 'bg-slate-300')) }}">
											@if ($ap->status->value === 'approved')
												<i class="fa-solid fa-check"></i>
											@elseif($ap->status->value === 'rejected')
												<i class="fa-solid fa-xmark"></i>
											@else
												{{ $ap->step_order }}
											@endif
										</span>

										{{-- Konten Timeline --}}
										<div class="min-w-0 leading-tight">
											<p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $ap->role_label }}</p>
											<p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $ap->approver->name }}</p>
											<div class="mt-1.5">
												<span
													class="badge-{{ $ap->status->value }} px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase tracking-wider inline-block">{{ $ap->status->label() }}</span>
											</div>
											@if ($ap->notes)
												<p class="mt-2 rounded border border-slate-100 bg-slate-50 p-2 text-xs italic text-slate-600">
													<i class="fa-solid fa-quote-left text-slate-300 mr-1"></i> {{ $ap->notes }}
												</p>
											@endif
											@if ($ap->acted_at)
												<p class="mt-1.5 text-[10px] font-mono text-slate-400">
													<i class="fa-regular fa-clock mr-0.5"></i> {{ $ap->acted_at->translatedFormat('d M Y H:i') }}
												</p>
											@endif
										</div>
									</div>
								@endforeach
							</div>
						@else
							<p class="text-sm text-slate-400 italic text-center py-4">Belum ada alur persetujuan yang terbuat.</p>
						@endif
					</div>
				</div>

				{{-- Form Aksi (Approve/Reject) --}}
				@php
					$myApproval = $sppd->approvals
					    ->where('approver_id', auth()->id())
					    ->where('status', \App\Enums\ApprovalStatus::PENDING)
					    ->first();
					$lastApprovalStep = $sppd->approvals->max('step_order');
					$isFinalApproval = $myApproval && $myApproval->step_order === $lastApprovalStep;

					// Cek apakah ada step sebelumnya yang belum disetujui
					$hasUnapprovedPrevious = $myApproval
					    ? $sppd->approvals
					            ->where('step_order', '<', $myApproval->step_order)
					            ->where('status', '!=', \App\Enums\ApprovalStatus::APPROVED)
					            ->count() > 0
					    : false;
				@endphp

				@if ($myApproval)
					<div class="rounded border border-blue-200 bg-blue-50 shadow-md overflow-hidden">
						<div class="bg-blue-100/50 px-5 py-3 border-b border-blue-200">
							<h3 class="text-xs font-bold uppercase tracking-wider text-blue-800 flex items-center gap-2">
								<i class="fa-solid fa-bell text-blue-600 animate-pulse"></i> Menunggu Keputusan Anda
							</h3>
						</div>
						<div class="p-5">
							<p class="text-xs text-blue-700 mb-4 bg-white p-2 rounded border border-blue-100 font-medium">
								Anda bertindak sebagai <strong>{{ $myApproval->role_label }}</strong> (Langkah
								ke-{{ $myApproval->step_order }})
							</p>

							@if ($hasUnapprovedPrevious)
								{{-- Tampilkan keterangan, sembunyikan form --}}
								<div class="rounded border border-amber-300 bg-amber-50 p-4 text-center">
									<i class="fa-solid fa-clock-rotate-left text-amber-500 text-2xl mb-2"></i>
									<p class="text-xs font-bold text-amber-800 mb-1">Menunggu Langkah Sebelumnya</p>
									<p class="text-xs text-amber-700 leading-relaxed">
										Formulir persetujuan belum dapat ditampilkan karena masih ada langkah persetujuan sebelumnya yang belum
										selesai. Silakan tunggu hingga pejabat pada langkah sebelumnya menyelesaikan persetujuannya.
									</p>
								</div>
							@else
								@if ($isFinalApproval)
									<div class="mb-4 rounded border border-blue-200 bg-blue-100/50 p-3">
										<p class="text-xs font-bold text-blue-800">
											<i class="fa-solid fa-file-signature mr-1"></i> Langkah Terakhir TTE
										</p>
										<p class="text-xs text-blue-700 mt-1">Masukkan passphrase TTE Anda untuk menyetujui sekaligus mengirim
											permintaan penandatanganan elektronik SPPD.</p>
									</div>
								@endif

								<form action="{{ route('sppd.approve', $sppd) }}" method="POST" class="mb-3 space-y-3">
									@csrf
									<textarea name="notes"
									 class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 min-h-[60px]"
									 placeholder="Tambahkan catatan persetujuan (opsional)..."></textarea>

									@if ($isFinalApproval)
										<div>
											<label class="mb-1 block text-xs font-bold uppercase tracking-wider text-blue-800">Passphrase
												Penandatangan</label>
											<div class="relative">
												<input type="password" name="passphrase" id="passphrase-input" required minlength="4"
													class="w-full rounded border border-slate-300 bg-white pl-3 pr-10 py-2 text-sm text-slate-800 shadow-2xs focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
													placeholder="••••••••">
												<button type="button" onclick="togglePassphraseVisibility()"
													class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
													<i id="passphrase-eye-icon" class="fa-solid fa-eye text-sm"></i>
												</button>
											</div>
										</div>
									@endif

									<button type="submit"
										class="flex w-full items-center justify-center gap-2 rounded bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
										<i class="fa-solid fa-check-double"></i> Setujui Dokumen
									</button>
								</form>

								<form action="{{ route('sppd.reject', $sppd) }}" method="POST">
									@csrf
									<input type="hidden" name="notes" id="reject-notes">
									<button type="button" onclick="rejectSppd(this.form)"
										class="flex w-full items-center justify-center gap-2 rounded border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700">
										<i class="fa-solid fa-ban"></i> Tolak Dokumen
									</button>
								</form>
							@endif
						</div>
					</div>
				@endif

				{{-- Status Penandatanganan Elektronik (TTE) --}}
				@php
					$sppdSignature = $sppd->signatureFor('sppd');
				@endphp
				@if ($sppdSignature)
					<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
						<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3 flex justify-between items-center">
							<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
								<i class="fa-solid fa-file-shield text-cyan-600"></i> Status TTE Dokumen
							</h3>
						</div>
						<div class="p-5">
							<span
								class="badge-{{ $sppdSignature->status->value }} px-2 py-1 rounded text-xs font-bold uppercase tracking-wide inline-block mb-3">
								{{ $sppdSignature->status->label() }}
							</span>

							@if ($sppdSignature->error_message)
								<div class="mt-3 rounded border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-700">
									<i class="fa-solid fa-triangle-exclamation mr-1"></i> <strong>Error TTE:</strong>
									{{ $sppdSignature->error_message }}
								</div>
							@endif

							@if ($sppdSignature->status->value === 'signed')
								<p class="mt-2 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded p-2">
									<i class="fa-solid fa-circle-check mr-1"></i> Dokumen telah ditandatangani. Gunakan tombol <strong>Lihat
										Dokumen</strong> di atas untuk mengaksesnya.
								</p>
							@endif
						</div>
					</div>
				@endif

			</div>
		</div>

		{{-- Modal Dokumen Tersembunyi --}}
		@php
			$isApproved = in_array($sppd->status->value, ['approved', 'completed']);
			$sptSig = $sppd->signatureFor('spt');
			$sptIsApproved = $sptSig && $sptSig->status->value === 'signed' && $sptSig->signed_file_path;
		@endphp
		<div id="document-modal"
			class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 opacity-0 transition-opacity duration-200 backdrop-blur-2xs">
			<div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl">
				<div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
					<div class="flex items-center gap-2">
						<i class="fa-solid fa-folder-open text-cyan-600"></i>
						<div>
							<h3 class="text-sm font-bold text-slate-800">Dokumen SPPD</h3>
							<p class="text-[11px] text-slate-500">Pilih dokumen yang ingin dibuka</p>
						</div>
					</div>
					<button type="button" id="document-modal-close"
						class="rounded-full border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
						<i class="fa-solid fa-xmark"></i>
					</button>
				</div>

				<div class="space-y-2 p-4">
					{{-- SPT: jika sudah TTE, langsung ke URL file di storage --}}
					@if ($sptIsApproved)
						<a href="{{ Storage::url($sptSig->signed_file_path) }}" target="_blank"
							class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
							<i class="fa-solid fa-file-pdf"></i>
							<span>SPT <span
									class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800">TTE</span></span>
						</a>
					@else
						<a href="{{ route('sppd.stream.spt', $sppd) }}" target="_blank"
							class="flex items-center gap-2 rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-[11px] font-semibold text-cyan-700 transition hover:bg-cyan-100">
							<i class="fa-solid fa-file-pdf"></i>
							<span>SPT</span>
						</a>
					@endif

					{{-- SPPD Pelaksana --}}
					@php
						$sppdPelaksanaSig = $sppd->digitalSignatures
						    ->where('document_type', 'sppd_' . $sppd->user_id)
						    ->where('status', 'signed')
						    ->first();
					@endphp
					@if ($sppdPelaksanaSig && $sppdPelaksanaSig->signed_file_path)
						<a href="{{ Storage::url($sppdPelaksanaSig->signed_file_path) }}"
							target="_blank"
							class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
							<i class="fa-solid fa-file-lines"></i>
							<span>SPPD Pelaksana <span
									class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800">TTE</span></span>
						</a>
					@else
						<a href="{{ route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => $sppd->user_id]) }}" target="_blank"
							class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100">
							<i class="fa-solid fa-file-lines"></i>
							<span>SPPD Pelaksana</span>
						</a>
					@endif

					{{-- SPPD Pengikut --}}
					@foreach ($sppd->followers as $follower)
						@php
							$sppdFollowerSig = $sppd->digitalSignatures
							    ->where('document_type', 'sppd_' . $follower->user_id)
							    ->where('status', 'signed')
							    ->first();
						@endphp
						@if ($sppdFollowerSig && $sppdFollowerSig->signed_file_path)
							<a href="{{ Storage::url($sppdFollowerSig->signed_file_path) }}"
								target="_blank"
								class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
								<i class="fa-solid fa-user-group"></i>
								<span>SPPD {{ $follower->user->name }} <span
										class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800">TTE</span></span>
							</a>
						@else
							<a href="{{ route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => $follower->user_id]) }}" target="_blank"
								class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100">
								<i class="fa-solid fa-user-group"></i>
								<span>SPPD {{ $follower->user->name }}</span>
							</a>
						@endif
					@endforeach
				</div>

				<div class="border-t border-slate-100 px-4 py-3">
					<button type="button" id="document-modal-close-btn"
						class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
						Tutup
					</button>
				</div>
			</div>
		</div>

	</div>
@endsection

@push('scripts')
	<script>
		function togglePassphraseVisibility() {
			const input = document.getElementById('passphrase-input');
			const icon = document.getElementById('passphrase-eye-icon');
			if (input && icon) {
				if (input.type === 'password') {
					input.type = 'text';
					icon.classList.remove('fa-eye');
					icon.classList.add('fa-eye-slash');
				} else {
					input.type = 'password';
					icon.classList.remove('fa-eye-slash');
					icon.classList.add('fa-eye');
				}
			}
		}

		function rejectSppd(form) {
			const reason = prompt('Masukkan alasan penolakan (Wajib diisi):');
			if (reason && reason.trim()) {
				form.querySelector('#reject-notes').value = reason;
				form.submit();
			}
		}

		const documentModalOpen = document.getElementById('document-modal-open');
		const documentModal = document.getElementById('document-modal');
		const documentModalClose = document.getElementById('document-modal-close');
		const documentModalCloseBtn = document.getElementById('document-modal-close-btn');

		function openDocumentModal() {
			if (!documentModal) return;
			documentModal.classList.remove('hidden');
			requestAnimationFrame(() => {
				documentModal.classList.remove('opacity-0');
				documentModal.classList.add('flex');
			});
		}

		function closeDocumentModal() {
			if (!documentModal) return;
			documentModal.classList.add('opacity-0');
			setTimeout(() => {
				documentModal.classList.add('hidden');
				documentModal.classList.remove('flex');
			}, 200);
		}

		documentModalOpen?.addEventListener('click', openDocumentModal);
		documentModalClose?.addEventListener('click', closeDocumentModal);
		documentModalCloseBtn?.addEventListener('click', closeDocumentModal);

		documentModal?.addEventListener('click', (event) => {
			if (event.target === documentModal) {
				closeDocumentModal();
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && documentModal && !documentModal.classList.contains('hidden')) {
				closeDocumentModal();
			}
		});
	</script>
@endpush
