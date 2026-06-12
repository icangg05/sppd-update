<div class="flex flex-col gap-6 p-1"
	x-data="{ showDocModal: @entangle('showDocumentModal'), showApproveModal: @entangle('showApproveModal'), showRejectModal: @entangle('showRejectModal'), showRevisionModal: @entangle('showRevisionModal'), showPassphrase: false }"
	@if ($isProcessing) wire:poll.5s @endif>

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
			{{-- Tombol Edit Perbaikan --}}
			@if ($sppd->status->value === 'in_progress' && $sppd->revision_note && auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
				<a href="{{ route('sppd.create.details', ['sppd_id' => $sppd->id]) }}" wire:navigate
					class="inline-flex items-center gap-1.5 rounded border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100 hover:text-amber-800">
					<i class="fa-solid fa-pen-to-square text-amber-600"></i> Edit Perbaikan
				</a>
			@endif

			{{-- Tombol Batalkan Pengajuan --}}
			@if ($sppd->status->value === 'in_progress' && auth()->user()->hasAnyRole(['admin_opd', 'super_admin']))
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
				<a href="{{ route('sppd.next', $sppd) }}" wire:navigate
					class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-cyan-700">
					<span>Selanjutnya</span>
					<i class="fa-solid fa-arrow-right text-[10px]"></i>
				</a>
			@endif

			{{-- Dokumen SPT / SPPD --}}
			<button type="button" @click="showDocModal = true"
				class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-200/80 hover:text-slate-900">
				<i class="fa-solid fa-file-pdf text-cyan-600 text-[13px]"></i>
				Lihat Dokumen
			</button>

			{{-- Garis Pemisah --}}
			<div class="hidden sm:block border-l border-slate-300 h-5 self-center mx-0.5"></div>

			{{-- Badge Status --}}
			<span
				class="badge-{{ $sppd->status->value }} inline-block rounded-sm px-2.5 py-1 text-xs font-bold uppercase tracking-wide">
				{{ $sppd->status->label() }}
			</span>

			{{-- Tombol Kembali --}}
			<a href="{{ $from === 'approval' ? route('sppd.index', ['filter' => 'approval']) : route('sppd.index', array_filter(\App\Livewire\Sppd\SppdIndex::savedFilters())) }}" wire:navigate
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
						<a href="{{ route('master.users.show', $sppd->user) }}" class="text-sm font-bold text-cyan-600 hover:text-cyan-700 hover:underline inline-flex items-center gap-1">
							<span>{{ $sppd->user->name }}</span>
							<i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
						</a>
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
						<p class="text-sm font-semibold text-slate-800">
							<span class="bg-cyan-50 text-cyan-700 px-2 py-0.5 rounded border border-cyan-100 text-xs uppercase">{{ $sppd->domain->label() }}</span>
						</p>
					</div>
					<div>
						<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Sifat Surat Dokumen</p>
						<p class="text-sm font-semibold text-slate-800">
							<span class="inline-block rounded-sm px-2 py-0.5 text-xs font-bold uppercase tracking-wide border
								@if (strtolower($sppd->urgency) === 'segera')
									bg-rose-50 text-rose-700 border-rose-100
								@else
									bg-slate-50 text-slate-700 border-slate-200
								@endif">
								{{ $sppd->urgency ?? 'Biasa' }}
							</span>
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
					@if ($sppd->problem)
						<div class="sm:col-span-2 pt-3 border-t border-slate-100">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Persoalan</p>
							<p class="text-sm text-slate-600 bg-slate-50 p-3 rounded border border-slate-200 leading-relaxed">{{ $sppd->problem }}</p>
						</div>
					@endif
					@if ($sppd->facts)
						<div class="sm:col-span-2 pt-3 border-t border-slate-100">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Fakta-Fakta Yang Mempengaruhi</p>
							<p class="text-sm text-slate-600 bg-slate-50 p-3 rounded border border-slate-200 leading-relaxed">{{ $sppd->facts }}</p>
						</div>
					@endif
					@if ($sppd->analysis)
						<div class="sm:col-span-2 pt-3 border-t border-slate-100">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Analisis</p>
							<p class="text-sm text-slate-600 bg-slate-50 p-3 rounded border border-slate-200 leading-relaxed">{{ $sppd->analysis }}</p>
						</div>
					@endif
					@if ($sppd->notes)
						<div class="sm:col-span-2 pt-3 border-t border-slate-100">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Catatan Tambahan</p>
							<p class="text-sm text-slate-600 bg-amber-50 p-3 rounded border border-amber-200 leading-relaxed">{{ $sppd->notes }}</p>
						</div>
					@endif
					@if ($sppd->attachment)
						<div class="sm:col-span-2">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Dokumen Pendukung</p>
							<div class="flex items-center gap-3 rounded border border-slate-200 bg-slate-50 p-3 shadow-2xs">
								<div class="flex size-8 shrink-0 items-center justify-center rounded bg-amber-100 text-amber-600">
									<i class="fa-solid fa-paperclip text-sm"></i>
								</div>
								<div class="min-w-0 flex-1">
									<p class="text-xs text-slate-500 font-medium">Lampiran Dokumen</p>
									<a href="{{ \Illuminate\Support\Facades\Storage::url($sppd->attachment) }}" target="_blank"
										class="text-sm font-bold text-cyan-600 hover:text-cyan-700 hover:underline truncate block">
										{{ basename($sppd->attachment) }}
									</a>
								</div>
								<a href="{{ \Illuminate\Support\Facades\Storage::url($sppd->attachment) }}" target="_blank"
									class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-100">
									<i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Buka
								</a>
							</div>
						</div>
					@endif
				</div>
			</div>

			{{-- Pembebanan Anggaran --}}
			@if ($sppd->budget)
				<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
						<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
							<i class="fa-solid fa-money-check-dollar text-cyan-600"></i> Pembebanan Anggaran
						</h3>
					</div>
					<div class="p-5 grid grid-cols-1 gap-y-5 gap-x-8 sm:grid-cols-2">
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">No. Rekening Anggaran</p>
							<p class="text-sm font-semibold text-slate-800 font-mono">{{ $sppd->budget->account_code ?? '-' }}</p>
						</div>
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Program</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->budget->program ?? '-' }}</p>
						</div>
						<div class="sm:col-span-2">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Kegiatan</p>
							<p class="text-sm font-semibold text-slate-800">{{ $sppd->budget->activity ?? '-' }}</p>
						</div>
						<div class="sm:col-span-2 pt-3 border-t border-slate-100">
							<p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Anggaran Tersedia (Sisa Anggaran)</p>
							<p class="text-lg font-extrabold text-emerald-600">
								Rp {{ number_format($sppd->budget->balance, 0, ',', '.') }}
							</p>
							<p class="text-xs text-slate-500 mt-1">
								Pagu: Rp {{ number_format($sppd->budget->total_amount, 0, ',', '.') }} | Realisasi: Rp {{ number_format($sppd->budget->realization, 0, ',', '.') }}
							</p>
						</div>
					</div>
				</div>
			@endif

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
							<a href="{{ route('master.users.show', $f->user) }}" class="inline-flex items-center gap-2 rounded border border-slate-200 bg-white px-2 py-1.5 shadow-2xs hover:border-cyan-300 hover:bg-cyan-50/10 transition group">
								<span
									class="flex size-6 shrink-0 items-center justify-center rounded bg-cyan-600 text-[10px] font-bold text-white shadow-2xs group-hover:bg-cyan-700 transition">
									{{ strtoupper(substr($f->user->name, 0, 1)) }}
								</span>
								<div class="leading-tight pr-1">
									<span class="block text-sm font-semibold text-slate-700 group-hover:text-cyan-600 transition">{{ $f->user->name }}</span>
									@if ($f->travel_position)
										<span class="block text-[10px] font-bold uppercase tracking-wide text-indigo-600 mt-0.5">
											<i class="fa-solid fa-id-badge mr-0.5"></i>{{ $f->travel_position }}
										</span>
									@endif
								</div>
							</a>
						@endforeach
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
								@php
									$displayStatus = $ap->status->value;
									if ($sppd->status->value === 'rejected' && $displayStatus === 'pending') {
									    $displayStatus = 'skipped';
									} elseif ($sppd->revision_note && $sppd->reviser_id === $ap->approver_id && $displayStatus === 'pending') {
									    $displayStatus = 'revision';
									}
								@endphp
								<div class="relative pl-6">
									<span
										class="absolute -left-3.25 top-0.5 flex size-6 items-center justify-center rounded-full text-[10px] font-bold shadow-2xs ring-4 ring-white
                                        @if ($displayStatus === 'approved') bg-emerald-500 text-white
                                        @elseif ($displayStatus === 'rejected') bg-rose-500 text-white
                                        @elseif ($displayStatus === 'revision') bg-amber-500 text-white
                                        @elseif ($displayStatus === 'skipped') bg-slate-200 text-slate-400
                                        @else bg-slate-300 text-white @endif">
										@if ($displayStatus === 'approved')
											<i class="fa-solid fa-check"></i>
										@elseif ($displayStatus === 'rejected')
											<i class="fa-solid fa-xmark"></i>
										@elseif ($displayStatus === 'skipped')
											<i class="fa-solid fa-minus text-[9px]"></i>
										@else
											{{ $ap->step_order }}
										@endif
									</span>
									<div class="min-w-0 leading-tight {{ $displayStatus === 'skipped' ? 'opacity-50' : '' }}">
										<p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $ap->role_label }}</p>
										<p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $ap->approver->name }}</p>
										<div class="mt-1.5">
											@if ($displayStatus === 'skipped')
												<span
													class="bg-slate-100 text-slate-400 border border-slate-200 px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase tracking-wider inline-block">Tidak
													Dilanjutkan</span>
											@else
												<span
													class="badge-{{ $displayStatus }} px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase tracking-wider inline-block">
													@if ($displayStatus === 'rejected')
														Ditolak
													@elseif ($displayStatus === 'revision')
														Revisi
													@else
														{{ $ap->status->label() }}
													@endif
												</span>
											@endif
										</div>
										@if ($ap->notes)
											<p class="mt-2 rounded border border-slate-100 bg-slate-50 p-2 text-xs italic text-slate-600">
												<i class="fa-solid fa-quote-left text-slate-300 mr-1"></i> {{ $ap->notes }}
											</p>
										@elseif ($displayStatus === 'revision')
											<p class="mt-2 rounded border border-slate-100 bg-slate-50 p-2 text-xs italic text-slate-600">
												<i class="fa-solid fa-quote-left text-slate-300 mr-1"></i> {{ $sppd->revision_note }}
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

				$hasUnapprovedPrevious = $myApproval
				    ? $sppd->approvals
				            ->where('step_order', '<', $myApproval->step_order)
				            ->where('status', '!=', \App\Enums\ApprovalStatus::APPROVED)
				            ->count() > 0
				    : false;
			@endphp

			@if ($myApproval)
				@php
					$isSigningInProgress = $isProcessing;
					$myFailedSignature = $sppd->digitalSignatures
					    ->where('signer_id', auth()->id())
					    ->where('status', \App\Enums\SignatureStatus::REJECTED)
					    ->first();
				@endphp
				<div class="rounded border border-blue-200 bg-blue-50 shadow-md overflow-hidden">
					<div class="bg-blue-100/50 px-5 py-3 border-b border-blue-200">
						<h3 class="text-xs font-bold uppercase tracking-wider text-blue-800 flex items-center gap-2">
							<i
								class="fa-solid fa-bell text-blue-600 @if ($isSigningInProgress) animate-pulse @endif"></i>
							@if ($isSigningInProgress)
								Proses TTE Sedang Berjalan
							@else
								Menunggu Keputusan Anda
							@endif
						</h3>
					</div>
					<div class="p-5">
						@if ($isSigningInProgress)
							<div class="rounded border border-amber-300 bg-amber-50 p-4 text-center">
								<i class="fa-solid fa-spinner animate-spin text-amber-500 text-2xl mb-2"></i>
								<p class="text-xs font-bold text-amber-800 mb-1">TTE Sedang Diproses</p>
								<p class="text-xs text-amber-700 leading-relaxed">
									Proses tanda tangan elektronik sedang berjalan di background. Halaman akan diperbarui otomatis.
								</p>
								<button type="button" wire:click="cancelTte" wire:loading.attr="disabled"
									class="mt-4 inline-flex items-center gap-1.5 rounded border border-rose-200 bg-white px-3.5 py-2 text-xs font-bold text-rose-600 shadow-2xs transition hover:bg-rose-50 hover:text-rose-700 cursor-pointer">
									<i class="fa-solid fa-circle-xmark"></i> Batalkan Proses TTE
								</button>
							</div>
						@elseif ($hasUnapprovedPrevious)
							<div class="rounded border border-amber-300 bg-amber-50 p-4 text-center">
								<i class="fa-solid fa-clock-rotate-left text-amber-500 text-2xl mb-2"></i>
								<p class="text-xs font-bold text-amber-800 mb-1">Menunggu Langkah Sebelumnya</p>
								<p class="text-xs text-amber-700 leading-relaxed">
									Formulir persetujuan belum dapat ditampilkan karena masih ada langkah sebelumnya yang belum selesai.
								</p>
							</div>
						@else
							<p class="text-xs text-blue-700 mb-4 bg-white p-2 rounded border border-blue-100 font-medium">
								Anda bertindak sebagai <strong>{{ $myApproval->role_label }}</strong> (Langkah
								ke-{{ $myApproval->step_order }})
							</p>

							@if ($myFailedSignature)
								@php
									$parsedError = $this->parseTteError($myFailedSignature->error_message);
								@endphp
								<div
									class="mb-4 rounded border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800 shadow-2xs leading-normal">
									<div class="flex gap-2.5">
										<i class="fa-solid {{ $parsedError['icon'] }} text-rose-600 text-sm mt-0.5 shrink-0"></i>
										<div>
											<p class="font-bold">Gagal Melakukan TTE:</p>
											<p class="mt-0.5">{{ $parsedError['message'] }}</p>
										</div>
									</div>
								</div>
							@endif

							@if ($needsTte)
								@if (!auth()->user()->nik)
									<div class="mb-4 rounded border border-rose-200 bg-rose-50 p-3">
										<p class="text-xs font-bold text-rose-800"><i class="fa-solid fa-triangle-exclamation mr-1"></i> NIK Belum
											Terdaftar</p>
										<p class="text-xs text-rose-700 mt-1 leading-normal">Profil Anda belum memiliki NIK. Silakan lengkapi
											terlebih dahulu.</p>
									</div>
								@else
									<div class="mb-4 rounded border border-blue-200 bg-blue-100/50 p-3">
										<p class="text-xs font-bold text-blue-800"><i class="fa-solid fa-file-signature mr-1"></i> Penandatanganan
											Elektronik (TTE)</p>
										<p class="text-xs text-blue-700 mt-1">Masukkan passphrase TTE Anda untuk menyetujui dan menandatangani
											dokumen.</p>
									</div>
								@endif
							@endif

							@if ($errors->has('passphrase') || $errors->has('approveNotes'))
								<div class="mb-4 rounded border border-rose-200 bg-rose-50 p-3">
									<p class="text-xs font-bold text-rose-800"><i class="fa-solid fa-circle-exclamation mr-1"></i> TTE Gagal</p>
									@error('passphrase')
										<p class="text-xs text-rose-700 mt-1">{{ $message }}</p>
									@enderror
									@error('approveNotes')
										<p class="text-xs text-rose-700 mt-1">{{ $message }}</p>
									@enderror
								</div>
							@endif

							<div class="mb-3 space-y-3">
								<textarea wire:model="approveNotes"
								 class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 min-h-[60px]"
								 placeholder="Tambahkan catatan persetujuan (opsional)..."></textarea>

								@if ($needsTte && auth()->user()->nik)
									<div>
										<label class="mb-1 block text-xs font-bold uppercase tracking-wider text-blue-800">Passphrase
											Penandatangan</label>
										<div class="relative">
											<input :type="showPassphrase ? 'text' : 'password'" wire:model="passphrase" required minlength="4"
												class="w-full rounded border border-slate-300 bg-white pl-3 pr-10 py-2 text-sm text-slate-800 shadow-2xs focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
												placeholder="••••••••">
											<button type="button" @click="showPassphrase = !showPassphrase"
												class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
												<i :class="showPassphrase ? 'fa-eye-slash' : 'fa-eye'" class="fa-solid text-sm"></i>
											</button>
										</div>
									</div>
								@endif

								@if ($needsTte && !auth()->user()->nik)
									<button type="button" disabled
										class="flex w-full items-center justify-center gap-2 rounded bg-slate-200 px-4 py-2.5 text-sm font-bold text-slate-400 cursor-not-allowed border border-slate-300">
										<i class="fa-solid fa-lock"></i> Setujui Dokumen (NIK Belum Ada)
									</button>
								@else
									<button type="button" @click="showApproveModal = true"
										class="flex w-full items-center justify-center gap-2 rounded bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
										<i class="fa-solid fa-check-double"></i> Setujui Dokumen
									</button>
								@endif
							</div>

							<div class="flex flex-col gap-2">
								<button type="button" @click="showRejectModal = true"
									class="flex w-full items-center justify-center gap-2 rounded border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700 cursor-pointer">
									<i class="fa-solid fa-ban"></i> Tolak Dokumen
								</button>
								<button type="button" @click="showRevisionModal = true"
									class="flex w-full items-center justify-center gap-2 rounded border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-600 transition hover:bg-amber-100 hover:text-amber-700 cursor-pointer">
									<i class="fa-solid fa-rotate-left"></i> Kembalikan untuk Revisi
								</button>
							</div>
						@endif
					</div>
				</div>
			@endif

			{{-- Status TTE --}}
			@php
				$hasAnyTteConfigured = $sppd->approvals->contains(fn($app) => $app->signs_spt || $app->signs_sppd);
				$hasSppdSigner = $sppd->approvals->contains(fn($app) => $app->signs_sppd);
				$hasSptSigner = $sppd->approvals->contains(fn($app) => $app->signs_spt);
				$showSppdStatus = !$hasAnyTteConfigured || $hasSppdSigner;
				$showSptStatus = !$hasAnyTteConfigured || $hasSptSigner;
				$sppdSig = $sppd->signatureFor('sppd');
				$sptSig = $sppd->signatureFor('spt');
			@endphp

			@if ($showSppdStatus || $showSptStatus)
				<div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
					<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3 flex items-center justify-between">
						<h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
							<i class="fa-solid fa-file-shield text-cyan-600"></i> Status TTE Dokumen
						</h3>
					</div>
					<div class="p-4 divide-y divide-slate-100 max-h-[350px] overflow-y-auto">
						{{-- SPT --}}
						@if ($showSptStatus)
							@include('livewire.sppd.partials.tte-row', [
								'sig' => $sptSig,
								'label' => 'Dokumen SPT',
								'name' => $sppd->user->name,
							])
						@endif

						{{-- SPPD Pelaksana --}}
						@if ($showSppdStatus)
							@include('livewire.sppd.partials.tte-row', [
								'sig' => $sppdSig,
								'label' => 'SPPD (Pelaksana)',
								'name' => $sppd->user->name,
							])
						@endif

						{{-- SPPD Pengikut --}}
						@foreach ($sppd->followers as $follower)
							@php
								$followerSig = $sppd->digitalSignatures->where('document_type', 'sppd_' . $follower->user_id)->first();
							@endphp
							@include('livewire.sppd.partials.tte-row', [
								'sig' => $followerSig,
								'label' => 'SPPD (Pengikut)',
								'name' => $follower->user->name,
							])
						@endforeach
					</div>
				</div>
			@endif
		</div>
	</div>

	{{-- Modal Dokumen --}}
	@php
		$sptIsApproved = $sptSig && $sptSig->status->value === 'signed' && $sptSig->signed_file_path;
	@endphp
	<x-ui.modal show="showDocModal" title="Dokumen SPPD" description="Pilih dokumen yang ingin dibuka"
		icon="fa-solid fa-folder-open text-cyan-600">
		<div class="space-y-2">
			@if ($sptIsApproved)
				<a href="{{ \Illuminate\Support\Facades\Storage::url($sptSig->signed_file_path) }}" target="_blank"
					class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
					<i class="fa-solid fa-file-pdf"></i>
					<span>SPT <span
							class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800">TTE</span></span>
				</a>
			@else
				<a href="{{ route('sppd.stream.spt', $sppd) }}" target="_blank"
					class="flex items-center gap-2 rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-[11px] font-semibold text-cyan-700 transition hover:bg-cyan-100">
					<i class="fa-solid fa-file-pdf"></i><span>SPT</span>
				</a>
			@endif

			@php
				$sppdPelaksanaSig = $sppd->digitalSignatures
				    ->where('document_type', 'sppd_' . $sppd->user_id)
				    ->where('status', 'signed')
				    ->first();
			@endphp
			@if ($sppdPelaksanaSig && $sppdPelaksanaSig->signed_file_path)
				<a href="{{ \Illuminate\Support\Facades\Storage::url($sppdPelaksanaSig->signed_file_path) }}" target="_blank"
					class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
					<i class="fa-solid fa-file-lines"></i>
					<span>SPPD Pelaksana <span
							class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800">TTE</span></span>
				</a>
			@else
				<a
					href="{{ route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($sppd->user_id)]) }}"
					target="_blank"
					class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100">
					<i class="fa-solid fa-file-lines"></i><span>SPPD Pelaksana</span>
				</a>
			@endif

			@foreach ($sppd->followers as $follower)
				@php
					$sppdFollowerSig = $sppd->digitalSignatures
					    ->where('document_type', 'sppd_' . $follower->user_id)
					    ->where('status', 'signed')
					    ->first();
				@endphp
				@if ($sppdFollowerSig && $sppdFollowerSig->signed_file_path)
					<a href="{{ \Illuminate\Support\Facades\Storage::url($sppdFollowerSig->signed_file_path) }}" target="_blank"
						class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
						<i class="fa-solid fa-user-group"></i>
						<span>SPPD {{ $follower->user->name }} <span
								class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800">TTE</span></span>
					</a>
				@else
					<a
						href="{{ route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($follower->user_id)]) }}"
						target="_blank"
						class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100">
						<i class="fa-solid fa-user-group"></i><span>SPPD {{ $follower->user->name }}</span>
					</a>
				@endif
			@endforeach

			@if ($sppd->attachment)
				<div class="border-t border-slate-200 my-2 pt-2">
					<p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Lampiran Lainnya</p>
					<a href="{{ \Illuminate\Support\Facades\Storage::url($sppd->attachment) }}" target="_blank"
						class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] font-semibold text-amber-700 transition hover:bg-amber-100">
						<i class="fa-solid fa-paperclip"></i>
						<span>Dokumen Pendukung (Lampiran)</span>
					</a>
				</div>
			@endif
		</div>
		<x-slot:footer>
			<button type="button" @click="showDocModal = false"
				class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
				Tutup
			</button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Modal Konfirmasi Setujui --}}
	<x-ui.modal show="showApproveModal" title="Konfirmasi Persetujuan"
		description="Pastikan data dokumen sudah benar sebelum menyetujui" icon="fa-solid fa-check-double text-emerald-600">
		<p class="text-sm text-slate-600 leading-relaxed">
			Apakah Anda yakin ingin menyetujui dokumen SPPD ini?
			@if ($needsTte && auth()->user()->nik)
				<span class="block mt-2 text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
					<i class="fa-solid fa-file-signature mr-1"></i>
					Proses tanda tangan elektronik (TTE) akan dijalankan setelah konfirmasi.
				</span>
			@endif
		</p>

		<x-slot:footer class="flex gap-2">
			<button type="button" @click="showApproveModal = false"
				class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
				Batal
			</button>
			<button type="button" wire:click="approve" wire:loading.attr="disabled"
				class="flex-1 rounded-xl bg-emerald-600 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-emerald-700 shadow-sm">
				Ya, Setujui
			</button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Modal Tolak --}}
	<x-ui.modal show="showRejectModal" title="Tolak Pengajuan SPPD" description="Berikan alasan penolakan dokumen ini"
		icon="fa-solid fa-ban text-rose-600">
		<label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Alasan Penolakan <span
				class="text-rose-500">*</span></label>
		<textarea wire:model="rejectNotes" required
		 class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-rose-500 focus:outline-hidden focus:ring-1 focus:ring-rose-500 min-h-[100px]"
		 placeholder="Masukkan alasan penolakan secara jelas..."></textarea>
		@error('rejectNotes')
			<span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
		@enderror

		<x-slot:footer class="flex gap-2">
			<button type="button" @click="showRejectModal = false"
				class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
				Batal
			</button>
			<button type="button" wire:click="reject"
				class="flex-1 rounded-xl bg-rose-600 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-rose-700 shadow-sm">
				Tolak Dokumen
			</button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Modal Revisi --}}
	<x-ui.modal show="showRevisionModal" title="Kembalikan untuk Revisi"
		description="Berikan catatan perbaikan yang diperlukan" icon="fa-solid fa-rotate-left text-amber-600">
		<label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Keterangan / Catatan Revisi
			<span class="text-rose-500">*</span></label>
		<textarea wire:model="revisionNotes" required
		 class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 min-h-[100px]"
		 placeholder="Jelaskan bagian mana saja yang perlu diperbaiki..."></textarea>
		@error('revisionNotes')
			<span class="text-xs text-amber-600 mt-1 block">{{ $message }}</span>
		@enderror

		<x-slot:footer class="flex gap-2">
			<button type="button" @click="showRevisionModal = false"
				class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
				Batal
			</button>
			<button type="button" wire:click="revision"
				class="flex-1 rounded-xl bg-amber-600 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-amber-700 shadow-sm">
				Kembalikan
			</button>
		</x-slot:footer>
	</x-ui.modal>

</div>
