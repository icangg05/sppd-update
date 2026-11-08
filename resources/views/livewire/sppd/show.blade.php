<div class="flex flex-col gap-5 p-1"
	x-data="{ showDocModal: @entangle('showDocumentModal'), showApproveModal: @entangle('showApproveModal'), showRejectModal: @entangle('showRejectModal'), showRevisionModal: @entangle('showRevisionModal'), showCancelTteModal: @entangle('showCancelTteModal'), showCancelModal: false, showPassphrase: false }"
	@if ($isProcessing) wire:poll.5s @endif>

	{{-- Gaya bersama halaman ini: kartu datar, header kartu biru solid senada bar atas aplikasi (latar halaman biru muda, jadi header muda tak terbedakan), label kalimat biasa (bukan huruf besar). --}}
	@php
		$card = 'dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm';
		$cardTitle = 'flex items-center gap-2 bg-primary-700 px-5 py-3 text-sm font-bold text-white';
		$label = 'text-xs font-medium text-slate-500';
		$value = 'mt-0.5 text-sm font-semibold text-slate-800';
		// Daftar field bergaris: celah 1px di atas latar slate-200 menjadi garis pembatas antarsel,
		// sehingga jarak antardata bisa rapat tanpa terlihat menyatu.
		$fieldGrid = 'grid grid-cols-1 gap-px bg-slate-200 @lg:grid-cols-2';
		$fieldCell = 'bg-white px-5 py-2.5';
		// Tombol sekunder polos, setara x-ui.button secondary. Tidak memakai komponen karena komponen
		// menonaktifkan tombol di setiap request Livewire, termasuk polling TTE 5 detik di halaman ini.
		$secondaryBase = 'inline-flex items-center gap-2 rounded border bg-white px-4 py-2 text-sm font-medium shadow-2xs transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1';
		$secondaryBtn = $secondaryBase . ' border-slate-300 text-slate-700';
		$backUrl = $from === 'approval'
		    ? route('sppd.index', ['filter' => 'approval'])
		    : route('sppd.index', array_filter(\App\Livewire\Sppd\SppdIndex::savedFilters()));
		$canManage = auth()->user()->hasAnyRole(['admin_opd', 'super_admin']);
		// Halaman detail pegawai hanya bisa dibuka Super Admin & Admin OPD.
		$canOpenUsers = $canManage;
		$isInProgress = $sppd->status->value === 'in_progress';
		$dateRange = $sppd->start_date->isSameDay($sppd->end_date)
		    ? $sppd->start_date->translatedFormat('d M Y')
		    : $sppd->start_date->translatedFormat('d M Y') . ' s/d ' . $sppd->end_date->translatedFormat('d M Y');
	@endphp

	{{-- Kepala halaman: tautan kembali, identitas SPPD + status, lalu aksi. Biru hanya untuk aksi utama.
	     Aksi turun ke baris kedua di bawah xl agar tidak terlipat saat zoom 125%. --}}
	<div class="dash-enter rounded border border-slate-200 bg-white px-5 py-4 shadow-sm">
		<a href="{{ $backUrl }}" wire:navigate
			class="inline-flex items-center gap-1.5 rounded text-xs font-semibold text-slate-500 transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
			<i class="fa-solid fa-arrow-left text-[10px]"></i>
			{{ $from === 'approval' ? 'Kembali ke Persetujuan' : 'Kembali ke Daftar SPPD' }}
		</a>

		<div class="mt-2 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
			<div class="min-w-0">
				<div class="flex flex-wrap items-center gap-x-3 gap-y-1">
					<h1 class="text-xl font-bold tracking-tight text-slate-800">Detail Surat Perjalanan Dinas</h1>
					<x-ui.badge :status="$sppd->status->value" class="rounded px-2 py-0.5 text-xs font-bold">
						{{ $sppd->status->label() }}
					</x-ui.badge>
				</div>
				<p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
					<span class="inline-flex items-center gap-1.5">
						<i class="fa-solid fa-user text-xs text-slate-400"></i>{{ $sppd->user->name }}
					</span>
					<span class="inline-flex items-center gap-1.5">
						<i class="fa-regular fa-calendar text-xs text-slate-400"></i>{{ $dateRange }}
					</span>
					{{-- Nomor dokumen hanya dipakai Inspektorat (diinput di form SPPD). --}}
					@if ($sppd->showsDocumentNumber())
						<span class="inline-flex items-center gap-1.5 font-mono">
							<i class="fa-solid fa-hashtag text-xs text-slate-400"></i>{{ $sppd->document_number ?: 'Belum memiliki nomor dokumen' }}
						</span>
					@endif
				</p>
			</div>

			<div class="flex flex-wrap items-center gap-2">
				@if ($isInProgress && $sppd->revision_note && $canManage)
					<a href="{{ route('sppd.create.details', ['sppd_id' => $sppd->id]) }}" wire:navigate
						class="inline-flex items-center gap-2 rounded border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 shadow-2xs transition-colors hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1">
						<i class="fa-solid fa-pen-to-square text-xs"></i> Edit Perbaikan
					</a>
				@endif

				@if ($isInProgress && $canManage)
					<button type="button" @click="showCancelModal = true"
						class="inline-flex items-center gap-2 rounded border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 shadow-2xs transition-colors hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-1">
						<i class="fa-solid fa-trash-can text-xs"></i> Batalkan Pengajuan
					</button>
				@endif

				<button type="button" @click="showDocModal = true" class="{{ $secondaryBtn }}">
					<i class="fa-solid fa-file-pdf text-xs text-slate-500"></i> Lihat Dokumen
				</button>

				{{-- Salin tautan publik sekali klik. Clipboard API hanya ada di konteks aman (https/localhost);
				     di luar itu disalin lewat textarea sementara. Label "Tersalin" ditumpuk di sel yang sama
				     dengan label awal agar lebar tombol tidak berubah. --}}
				<button type="button" @click="copy()" :title="url" aria-label="Salin link publik SPPD"
					x-data="{
						copied: false,
						timer: null,
						url: @js($sppd->publicUrl()),
						async copy() {
							try {
								await navigator.clipboard.writeText(this.url);
							} catch (e) {
								const field = Object.assign(document.createElement('textarea'), { value: this.url, readOnly: true });
								field.style.cssText = 'position:fixed;top:0;opacity:0';
								document.body.appendChild(field);
								field.select();
								const ok = document.execCommand('copy');
								field.remove();
								if (! ok) {
									this.$dispatch('toast', { type: 'error', message: 'Link publik gagal disalin. Salin manual: ' + this.url });
									return;
								}
							}
							this.copied = true;
							clearTimeout(this.timer);
							this.timer = setTimeout(() => this.copied = false, 2000);
							this.$dispatch('toast', { type: 'success', message: 'Link publik disalin. Tempel di pesan untuk dibagikan.' });
						},
					}"
					class="{{ $secondaryBase }}" :class="copied ? 'border-emerald-300 text-emerald-700' : 'border-slate-300 text-slate-700'">
					<span class="inline-grid">
						<span class="col-start-1 row-start-1 inline-flex items-center gap-2" :class="copied && 'invisible'">
							<i class="fa-regular fa-copy text-xs text-slate-500"></i> Salin Link Publik
						</span>
						<span x-show="copied" style="display: none" class="col-start-1 row-start-1 inline-flex items-center justify-center gap-2">
							<i class="fa-solid fa-check text-xs"></i> Link Tersalin
						</span>
					</span>
				</button>

				@if (in_array($sppd->status->value, ['approved', 'completed']))
					<a href="{{ route('sppd.next', ['sppd' => $sppd, 'from' => 'show']) }}" wire:navigate
						class="group inline-flex items-center gap-2 rounded bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs transition-colors hover:bg-primary-700 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-1">
						Selanjutnya
						<i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
					</a>
				@endif
			</div>
		</div>
	</div>

	{{-- Alasan revisi / penolakan dari pejabat penyetuju, ditonjolkan di atas konten. --}}
	@if ($sppd->status->value === 'in_progress' && $sppd->revision_note)
		@php $revisionStep = $sppd->approvals->firstWhere('approver_id', $sppd->reviser_id); @endphp
		<x-ui.alert variant="warning" icon="fa-pen-to-square" title="Pengajuan perlu diperbaiki" class="dash-enter">
			<p class="whitespace-pre-line">{{ $sppd->revision_note }}</p>
			@if ($sppd->reviser)
				<p class="mt-1 text-xs">
					Catatan revisi dari <strong class="font-semibold">{{ $sppd->reviser->name }}</strong>{{ $revisionStep ? ' (' . $revisionStep->role_label . ')' : '' }}.
				</p>
			@endif
		</x-ui.alert>
	@elseif ($sppd->status->value === 'rejected')
		@php $rejectedStep = $sppd->approvals->first(fn ($ap) => $ap->status->value === 'rejected'); @endphp
		<x-ui.alert variant="danger" icon="fa-circle-xmark" title="Pengajuan ditolak" class="dash-enter">
			<p class="whitespace-pre-line">{{ $sppd->rejection_note ?: ($rejectedStep?->notes ?: 'Tidak ada alasan yang dicatat.') }}</p>
			@if ($rejectedStep)
				<p class="mt-1 text-xs">
					Ditolak oleh <strong class="font-semibold">{{ $rejectedStep->approver->name }}</strong> ({{ $rejectedStep->role_label }}){{ $rejectedStep->acted_at ? ' pada ' . $rejectedStep->acted_at->translatedFormat('d M Y H:i') . ' WITA' : '' }}.
				</p>
			@endif
		</x-ui.alert>
	@endif

	{{-- Konten utama: dua kolom mulai lg (kolom kanan lebar tetap) agar alur persetujuan & formulir
	     keputusan tetap di samping rincian saat zoom 125%, tidak turun ke bawah halaman. --}}
	<div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_19rem] xl:grid-cols-[minmax(0,1fr)_22rem]">

		{{-- Kolom kiri: rincian. @container agar jumlah kolom field mengikuti lebar kolom, bukan layar. --}}
		<div class="@container min-w-0 space-y-5">

			{{-- Informasi Perjalanan --}}
			<section class="{{ $card }}">
				<h2 class="{{ $cardTitle }}">
					<i class="fa-solid fa-address-card text-xs text-primary-100"></i> Informasi Perjalanan
				</h2>
				<dl class="{{ $fieldGrid }}">
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Pelaksana</dt>
						<dd class="mt-0.5">
							@if ($canOpenUsers)
								{{-- Langsung ke kartu riwayat perjalanan di profil pegawai. --}}
								<a href="{{ route('master.users.show', $sppd->user) }}#riwayat" wire:navigate
									title="Lihat riwayat perjalanan {{ $sppd->user->name }}"
									class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:underline">
									{{ $sppd->user->name }}
									<i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
								</a>
							@else
								<span class="text-sm font-semibold text-slate-800">{{ $sppd->user->name }}</span>
							@endif
							<span class="block font-mono text-xs text-slate-500">{{ $sppd->user->nip ?? '-' }}</span>
						</dd>
					</div>
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Tanggal pelaksanaan</dt>
						<dd class="{{ $value }}">{{ $dateRange }}</dd>
						<dd class="text-xs text-slate-500">{{ $sppd->duration_days }} hari</dd>
					</div>
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Kategori perjalanan</dt>
						<dd class="{{ $value }}">{{ $sppd->category?->name ?? '-' }}</dd>
					</div>
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Domain wilayah</dt>
						<dd class="{{ $value }}">{{ $sppd->domain->label() }}</dd>
					</div>
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Sifat surat</dt>
						<dd class="mt-0.5">
							@if (strtolower((string) $sppd->urgency) === 'segera')
								<span class="inline-block rounded border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700">Segera</span>
							@else
								<span class="text-sm font-semibold text-slate-800">{{ $sppd->urgency ?? 'Biasa' }}</span>
							@endif
						</dd>
					</div>
					<div class="{{ $fieldCell }}">
						<dt class="{{ $label }}">Pembuat dokumen</dt>
						<dd class="{{ $value }}">{{ $sppd->creator?->name ?? '-' }}</dd>
					</div>
					{{-- Nama instansi bisa panjang, jadi selebar kartu dan menutup baris ganjil. --}}
					<div class="{{ $fieldCell }} @lg:col-span-2">
						<dt class="{{ $label }}">Instansi pengusul</dt>
						<dd class="{{ $value }}">{{ $sppd->budget?->department?->name ?? '-' }}</dd>
					</div>
				</dl>

				{{-- Uraian telaah: teks utuh tanpa kotak per paragraf, dipisah garis; semua uraian bergaya sama. --}}
				@php
					$narratives = array_filter([
					    'Maksud perjalanan' => $sppd->purpose,
					    'Persoalan' => $sppd->problem,
					    'Fakta-fakta yang mempengaruhi' => $sppd->facts,
					    'Analisis' => $sppd->analysis,
					]);
				@endphp
				@if ($narratives)
					<dl class="divide-y divide-slate-200 border-t border-slate-200">
						@foreach ($narratives as $title => $text)
							<div class="px-5 py-2.5">
								<dt class="{{ $label }}">{{ $title }}</dt>
								<dd class="mt-1 max-w-[75ch] whitespace-pre-line text-sm leading-relaxed font-medium text-slate-800">{{ $text }}</dd>
							</div>
						@endforeach
					</dl>
				@endif

				@if ($sppd->notes)
					<div class="border-t border-slate-200 px-5 py-2.5">
						<p class="{{ $label }}">Catatan tambahan</p>
						<p class="mt-1 max-w-[75ch] rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm leading-relaxed text-amber-900">{{ $sppd->notes }}</p>
					</div>
				@endif

				@if ($sppd->attachment)
					@php
						$attachmentUrl = \Illuminate\Support\Facades\Storage::url($sppd->attachment);
						$attachmentName = basename($sppd->attachment);
						$smallSecondaryBtn = 'inline-flex shrink-0 items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-2xs transition-colors hover:bg-slate-50';
					@endphp
					<div class="flex items-center gap-3 border-t border-slate-200 px-5 py-3">
						<i class="fa-solid fa-paperclip text-sm text-slate-400"></i>
						<div class="min-w-0 flex-1">
							<p class="{{ $label }}">Dokumen pendukung</p>
							<p class="truncate text-sm font-medium text-slate-800" title="{{ $attachmentName }}">{{ $attachmentName }}</p>
						</div>
						@if ($sppd->attachmentIsImage())
							<x-ui.gallery-link :src="$attachmentUrl" group="lampiran" :caption="$attachmentName" class="{{ $smallSecondaryBtn }}">
								<i class="fa-solid fa-eye text-[10px] text-slate-500"></i> Lihat
							</x-ui.gallery-link>
						@else
							<a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="{{ $smallSecondaryBtn }}">
								<i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-500"></i> Buka
							</a>
						@endif
					</div>
				@endif
			</section>

			{{-- Pembebanan Anggaran --}}
			@if ($sppd->budget)
				@php
					$budget = $sppd->budget;
					$balance = $budget->balance;
				@endphp
				<section class="{{ $card }}">
					<h2 class="{{ $cardTitle }}">
						<i class="fa-solid fa-money-check-dollar text-xs text-primary-100"></i> Pembebanan Anggaran
					</h2>
					<dl class="{{ $fieldGrid }}">
						<div class="{{ $fieldCell }}">
							<dt class="{{ $label }}">Kode rekening</dt>
							<dd class="{{ $value }} font-mono">{{ $budget->account_code ?? '-' }}</dd>
						</div>
						<div class="{{ $fieldCell }}">
							<dt class="{{ $label }}">Program</dt>
							<dd class="{{ $value }}">{{ $budget->program ?? '-' }}</dd>
						</div>
						<div class="{{ $fieldCell }} @lg:col-span-2">
							<dt class="{{ $label }}">Kegiatan</dt>
							<dd class="{{ $value }}">{{ $budget->activity ?? '-' }}</dd>
						</div>
					</dl>
					<dl class="grid grid-cols-1 divide-y divide-slate-200 border-t border-slate-200 bg-slate-50/60 @md:grid-cols-3 @md:divide-x @md:divide-y-0">
						<div class="px-5 py-3">
							<dt class="{{ $label }}">Pagu</dt>
							<dd class="{{ $value }} font-mono">Rp {{ number_format($budget->total_amount, 0, ',', '.') }}</dd>
						</div>
						<div class="px-5 py-3">
							<dt class="{{ $label }}">Realisasi</dt>
							<dd class="{{ $value }} font-mono">Rp {{ number_format($budget->realization, 0, ',', '.') }}</dd>
						</div>
						<div class="px-5 py-3">
							<dt class="{{ $label }}">Sisa anggaran</dt>
							<dd class="mt-0.5 font-mono text-sm font-bold {{ $balance < 0 ? 'text-rose-700' : 'text-emerald-700' }}">Rp {{ number_format($balance, 0, ',', '.') }}</dd>
						</div>
					</dl>
				</section>
			@endif

			{{-- Lokasi Tujuan --}}
			@if ($sppd->destinations->count())
				<section class="{{ $card }}">
					<h2 class="{{ $cardTitle }}">
						<i class="fa-solid fa-map-location-dot text-xs text-primary-100"></i> Lokasi Tujuan
					</h2>
					<ul class="divide-y divide-slate-200">
						@foreach ($sppd->destinations as $dest)
							<li class="flex items-start gap-3 px-5 py-3">
								<i class="fa-solid fa-location-dot mt-0.5 text-sm text-slate-400"></i>
								<div class="min-w-0">
									<p class="text-sm font-semibold text-slate-800">{{ $dest->location_label }}</p>
									@if ($dest->address)
										<p class="mt-0.5 text-xs text-slate-500">{{ $dest->address }}</p>
									@endif
								</div>
							</li>
						@endforeach
					</ul>
				</section>
			@endif

			{{-- Pengikut --}}
			@if ($sppd->followers->count())
				<section class="{{ $card }}">
					<h2 class="{{ $cardTitle }}">
						<i class="fa-solid fa-users text-xs text-primary-100"></i> Daftar Pengikut
						<span class="font-medium text-primary-100">({{ $sppd->followers->count() }})</span>
					</h2>
					<ul class="flex flex-wrap gap-2 px-5 py-4">
						@foreach ($sppd->followers as $f)
							@php $followerTag = $canOpenUsers ? 'a' : 'span'; @endphp
							<li>
								<{{ $followerTag }} @if ($canOpenUsers) href="{{ route('master.users.show', $f->user) }}#riwayat-pengikut" wire:navigate title="Lihat riwayat perjalanan {{ $f->user->name }}" @endif
									class="inline-flex items-center gap-2 rounded border border-slate-200 bg-white px-2.5 py-1.5 {{ $canOpenUsers ? 'transition-colors hover:border-slate-300 hover:bg-slate-50' : '' }}">
									<span class="flex size-6 shrink-0 items-center justify-center rounded bg-slate-100 text-[11px] font-bold text-slate-600">
										{{ strtoupper(substr($f->user->name, 0, 1)) }}
									</span>
									<span class="leading-tight">
										<span class="block text-sm font-semibold text-slate-800">{{ $f->user->name }}</span>
										@if ($f->travel_position)
											<span class="block text-xs text-slate-500">{{ $f->travel_position }}</span>
										@endif
									</span>
								</{{ $followerTag }}>
							</li>
						@endforeach
					</ul>
				</section>
			@endif
		</div>

		{{-- Kolom Kanan: Timeline & Aksi --}}
		<div class="min-w-0 space-y-5">

			{{-- Timeline Persetujuan --}}
			<section class="{{ $card }}">
				<h2 class="{{ $cardTitle }}">
					<i class="fa-solid fa-list-check text-xs text-primary-100"></i> Alur Persetujuan
				</h2>
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
                                        @elseif ($displayStatus === 'skipped') bg-slate-200 text-slate-500
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
										<p class="text-xs font-medium text-slate-500">{{ $ap->role_label }}</p>
										<p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $ap->approver->name }}</p>
										<div class="mt-1.5">
											@if ($displayStatus === 'skipped')
												<span
													class="inline-block rounded border border-slate-200 bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">Tidak
													Dilanjutkan</span>
											@else
												<x-ui.badge :status="$displayStatus" class="inline-block rounded px-2 py-0.5 text-[11px] font-bold">
													@if ($displayStatus === 'rejected')
														Ditolak
													@elseif ($displayStatus === 'revision')
														Revisi
													@else
														{{ $ap->status->label() }}
													@endif
												</x-ui.badge>
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
											<p class="mt-1.5 font-mono text-[11px] text-slate-500">
												<i class="fa-regular fa-clock mr-0.5"></i> {{ $ap->acted_at->translatedFormat('d M Y H:i') }} WITA
											</p>
										@endif
									</div>
								</div>
							@endforeach
						</div>
					@else
						<p class="py-4 text-center text-sm text-slate-500">Belum ada alur persetujuan.</p>
					@endif
				</div>
			</section>

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
				<section class="dash-enter overflow-hidden rounded border border-primary-200 bg-white shadow-sm">
					<div class="bg-primary-700 px-5 py-3">
						<h2 class="flex items-center gap-2 text-sm font-bold text-white">
							<i
								class="fa-solid fa-bell text-primary-100 @if ($isSigningInProgress) animate-pulse motion-reduce:animate-none @endif"></i>
							@if ($isSigningInProgress)
								Proses TTE Sedang Berjalan
							@else
								Menunggu Keputusan Anda
							@endif
						</h2>
					</div>
					<div class="p-5">
						@if ($isSigningInProgress)
							<div class="rounded border border-amber-300 bg-amber-50 p-4 text-center">
								<i class="fa-solid fa-spinner animate-spin text-amber-500 text-2xl mb-2"></i>
								<p class="text-xs font-bold text-amber-800 mb-1">TTE Sedang Diproses</p>
								<p class="text-xs text-amber-700 leading-relaxed">
									Proses tanda tangan elektronik sedang berjalan di background. Halaman akan diperbarui otomatis.
								</p>
								{{-- wire:target: tanpa ini tombol ikut nonaktif di setiap polling 5 detik dan klik bisa terabaikan. --}}
								<button type="button" wire:click="confirmCancelTte" wire:loading.attr="disabled" wire:target="confirmCancelTte"
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
							<p class="mb-4 rounded border border-slate-200 bg-slate-50 p-2 text-xs font-medium text-slate-700">
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
									<div class="mb-4 rounded border border-primary-100 bg-primary-50 p-3">
										<p class="text-xs font-bold text-primary-900"><i class="fa-solid fa-file-signature mr-1"></i> Penandatanganan
											Elektronik (TTE)</p>
										<p class="mt-1 text-xs text-primary-800">Masukkan passphrase TTE Anda untuk menyetujui dan menandatangani
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
								 class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500 min-h-[60px]"
								 placeholder="Tambahkan catatan persetujuan (opsional)..."></textarea>

								@if ($needsTte && auth()->user()->nik)
									<div>
										<label for="tte-signer-key" class="mb-1 block text-xs font-semibold text-slate-700">Passphrase
											Penandatangan</label>
										{{-- Passphrase TTE tidak boleh tersimpan di browser. Prompt "Simpan/Update password" hanya muncul
										     untuk input type="password" (atribut autocomplete diabaikan untuk prompt ini), jadi dipakai input
										     teks yang disamarkan CSS -webkit-text-security. Tanpa ini Chrome menawarkan menimpa password
										     login tersimpan dengan passphrase. Browser yang tidak mendukung penyamaran CSS kembali ke
										     type="password" agar passphrase tetap tidak terlihat. id/name sengaja tanpa kata "password".
										     autocapitalize/autocorrect/spellcheck dimatikan agar keyboard HP tidak mengubah passphrase. --}}
										<div class="relative" x-data="{ canMask: window.CSS?.supports?.('-webkit-text-security', 'disc') ?? false }">
											<input id="tte-signer-key" type="text" :type="!canMask && !showPassphrase ? 'password' : 'text'"
												wire:model="passphrase" required minlength="4"
												autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
												data-lpignore="true" data-1p-ignore data-bwignore data-form-type="other"
												style="-webkit-text-security: disc"
												:style="{ '-webkit-text-security': showPassphrase ? 'none' : 'disc' }"
												class="w-full rounded border border-slate-300 bg-white pl-3 pr-10 py-2 text-sm text-slate-800 shadow-2xs focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500"
												placeholder="••••••••">
											<button type="button" @click="showPassphrase = !showPassphrase"
												:aria-label="showPassphrase ? 'Sembunyikan passphrase' : 'Tampilkan passphrase'"
												class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-600">
												<i :class="showPassphrase ? 'fa-eye-slash' : 'fa-eye'" class="fa-solid text-sm"></i>
											</button>
										</div>
									</div>
								@endif

								@if ($needsTte && !auth()->user()->nik)
									<button type="button" disabled
										class="flex w-full items-center justify-center gap-2 rounded bg-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 cursor-not-allowed border border-slate-300">
										<i class="fa-solid fa-lock"></i> Setujui Dokumen (NIK Belum Ada)
									</button>
								@else
									<button type="button" wire:click="openApproveModal" wire:loading.attr="disabled"
										class="flex w-full items-center justify-center gap-2 rounded bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1">
										<i class="fa-solid fa-check-double"></i> Setujui Dokumen
									</button>
								@endif
							</div>

							<div class="flex flex-col gap-2">
								<button type="button" wire:click="openRejectModal" wire:loading.attr="disabled"
									class="flex w-full cursor-pointer items-center justify-center gap-2 rounded border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-1">
									<i class="fa-solid fa-ban"></i> Tolak Dokumen
								</button>
								<button type="button" wire:click="openRevisionModal" wire:loading.attr="disabled"
									class="flex w-full cursor-pointer items-center justify-center gap-2 rounded border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-600 transition hover:bg-amber-100 hover:text-amber-700 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1">
									<i class="fa-solid fa-rotate-left"></i> Kembalikan untuk Revisi
								</button>
							</div>
						@endif
					</div>
				</section>
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

				$tteRows = [];
				if ($showSptStatus) {
				    $tteRows[] = ['sig' => $sptSig, 'label' => 'Dokumen SPT', 'name' => $sppd->user->name];
				}
				if ($showSppdStatus) {
				    $tteRows[] = ['sig' => $sppdSig, 'label' => 'SPPD pelaksana', 'name' => $sppd->user->name];
				}
				foreach ($sppd->followers as $follower) {
				    $tteRows[] = [
				        'sig' => $sppd->digitalSignatures->where('document_type', 'sppd_' . $follower->user_id)->first(),
				        'label' => 'SPPD pengikut',
				        'name' => $follower->user->name,
				    ];
				}
				$tteSignedCount = collect($tteRows)->filter(fn($row) => $row['sig']?->status->value === 'signed')->count();
			@endphp

			@if ($showSppdStatus || $showSptStatus)
				<section class="{{ $card }}">
					<h2 class="{{ $cardTitle }}">
						<i class="fa-solid fa-file-shield text-xs text-primary-100"></i> Status TTE Dokumen
						<span class="ml-auto font-mono text-xs font-medium text-primary-100">{{ $tteSignedCount }}/{{ count($tteRows) }} selesai</span>
					</h2>
					<div class="max-h-[420px] divide-y divide-slate-200 overflow-y-auto">
						@foreach ($tteRows as $row)
							@include('livewire.sppd.partials.tte-row', $row)
						@endforeach
					</div>
				</section>
			@endif
		</div>
	</div>

	{{-- Modal Dokumen --}}
	@php
		$sptIsApproved = $sptSig && $sptSig->status->value === 'signed' && $sptSig->signed_file_path;
	@endphp
	<x-ui.modal show="showDocModal" title="Dokumen SPPD" description="Pilih dokumen yang ingin dibuka"
		icon="fa-solid fa-folder-open text-primary-600" :closeable="false">
		<div class="space-y-2">
			@if ($sptIsApproved)
				<a href="{{ \Illuminate\Support\Facades\Storage::url($sptSig->signed_file_path) }}" target="_blank"
					class="flex items-center gap-2 rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100">
					<i class="fa-solid fa-file-pdf"></i>
					<span>SPT <span
							class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">TTE</span></span>
				</a>
			@else
				<a href="{{ route('sppd.stream.spt', $sppd) }}" target="_blank"
					class="flex items-center gap-2 rounded border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 transition hover:bg-primary-100">
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
					class="flex items-center gap-2 rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100">
					<i class="fa-solid fa-file-lines"></i>
					<span>SPPD Pelaksana <span
							class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">TTE</span></span>
				</a>
			@else
				<a
					href="{{ route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($sppd->user_id)]) }}"
					target="_blank"
					class="flex items-center gap-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
					<i class="fa-solid fa-file-lines"></i><span>SPPD Pelaksana</span>
				</a>
			@endif

			@if ($sppd->followers->count())
				<div class="border-t border-slate-200 my-2 pt-2">
					<p class="mb-1.5 text-xs font-medium text-slate-500">
						SPPD Pengikut <span class="text-slate-500">({{ $sppd->followers->count() }})</span>
					</p>
					<div class="max-h-64 space-y-2 overflow-y-auto pr-1">
						@foreach ($sppd->followers as $follower)
							@php
								$sppdFollowerSig = $sppd->digitalSignatures
								    ->where('document_type', 'sppd_' . $follower->user_id)
								    ->where('status', 'signed')
								    ->first();
							@endphp
							@if ($sppdFollowerSig && $sppdFollowerSig->signed_file_path)
								<a href="{{ \Illuminate\Support\Facades\Storage::url($sppdFollowerSig->signed_file_path) }}" target="_blank"
									class="flex items-center gap-2 rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100">
									<i class="fa-solid fa-user-group"></i>
									<span>SPPD {{ $follower->user->name }} <span
											class="ml-1 rounded bg-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">TTE</span></span>
								</a>
							@else
								<a
									href="{{ route('sppd.stream.sppd', ['sppd' => $sppd, 'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($follower->user_id)]) }}"
									target="_blank"
									class="flex items-center gap-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
									<i class="fa-solid fa-user-group"></i><span>SPPD {{ $follower->user->name }}</span>
								</a>
							@endif
						@endforeach
					</div>
				</div>
			@endif

			@if ($sppd->attachment)
				<div class="border-t border-slate-200 my-2 pt-2">
					<p class="mb-1.5 text-xs font-medium text-slate-500">Lampiran Lainnya</p>
					@if ($sppd->attachmentIsImage())
						<x-ui.gallery-link :src="\Illuminate\Support\Facades\Storage::url($sppd->attachment)" group="lampiran"
							:caption="basename($sppd->attachment)"
							class="flex items-center gap-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-100">
							<i class="fa-solid fa-paperclip"></i>
							<span>Dokumen Pendukung (Lampiran)</span>
						</x-ui.gallery-link>
					@else
						<a href="{{ \Illuminate\Support\Facades\Storage::url($sppd->attachment) }}" target="_blank"
							class="flex items-center gap-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-100">
							<i class="fa-solid fa-paperclip"></i>
							<span>Dokumen Pendukung (Lampiran)</span>
						</a>
					@endif
				</div>
			@endif
		</div>
		<x-slot:footer>
			<button type="button" @click="showDocModal = false"
				class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
				Tutup
			</button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Modal Konfirmasi Setujui --}}
	<x-ui.modal show="showApproveModal" title="Konfirmasi Persetujuan" :closeable="false"
		description="Pastikan data dokumen sudah benar sebelum menyetujui" icon="fa-solid fa-check-double text-emerald-600">
		<p class="text-sm text-slate-600 leading-relaxed">
			Apakah Anda yakin ingin menyetujui dokumen SPPD ini?
			@if ($needsTte && auth()->user()->nik)
				<span class="mt-2 block rounded border border-primary-100 bg-primary-50 px-3 py-2 text-xs text-primary-800">
					<i class="fa-solid fa-file-signature mr-1"></i>
					Proses tanda tangan elektronik (TTE) akan dijalankan setelah konfirmasi.
				</span>
			@endif
		</p>

		<x-slot:footer class="flex gap-2">
			<button type="button" @click="showApproveModal = false"
				class="flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
				Batal
			</button>
			<button type="button" wire:click="approve" wire:loading.attr="disabled"
				class="flex-1 rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 shadow-sm">
				Ya, Setujui
			</button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Modal Tolak --}}
	<x-ui.modal show="showRejectModal" title="Tolak Pengajuan SPPD" description="Berikan alasan penolakan dokumen ini"
		icon="fa-solid fa-ban text-rose-600" :closeable="false">
		<label class="mb-1.5 block text-xs font-semibold text-slate-700">Alasan Penolakan <span
				class="text-rose-500">*</span></label>
		<textarea wire:model="rejectNotes" required
		 class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-rose-500 focus:outline-hidden focus:ring-1 focus:ring-rose-500 min-h-[100px]"
		 placeholder="Masukkan alasan penolakan secara jelas..."></textarea>
		@error('rejectNotes')
			<span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
		@enderror

		<x-slot:footer class="flex gap-2">
			<button type="button" @click="showRejectModal = false"
				class="flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
				Batal
			</button>
			<button type="button" wire:click="reject"
				class="flex-1 rounded bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 shadow-sm">
				Tolak Dokumen
			</button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Modal Batalkan Pengajuan: tidak bisa ditutup dengan klik luar/Esc, wajib pilih tombol. --}}
	@if ($isInProgress && $canManage)
		<x-ui.modal show="showCancelModal" :closeable="false" title="Batalkan Pengajuan SPPD"
			description="Pengajuan yang dibatalkan tidak dapat dikembalikan" icon="fa-solid fa-trash-can text-rose-600">
			<form id="cancel-sppd-form" action="{{ route('sppd.destroy', $sppd) }}" method="POST">
				@csrf
				@method('DELETE')
				<p class="text-sm text-slate-600">
					Batalkan dan hapus pengajuan SPPD <span class="font-bold text-slate-800">{{ $sppd->user->name }}</span>
					({{ $dateRange }}) secara permanen? Alur persetujuan yang sedang berjalan ikut dihentikan.
				</p>
			</form>

			<x-slot:footer class="flex gap-2">
				<button type="button" @click="showCancelModal = false"
					class="flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
					Kembali
				</button>
				<button type="submit" form="cancel-sppd-form"
					class="flex flex-1 items-center justify-center gap-2 rounded bg-rose-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70"
					{{-- Dinonaktifkan setelah form terkirim agar tidak terkirim dua kali. --}}
					@click="setTimeout(() => $el.disabled = true)">
					<i class="fa-solid fa-trash-can text-xs"></i> Batalkan Pengajuan
				</button>
			</x-slot:footer>
		</x-ui.modal>
	@endif

	{{-- Modal Revisi --}}
	<x-ui.modal show="showRevisionModal" title="Kembalikan untuk Revisi" :closeable="false"
		description="Berikan catatan perbaikan yang diperlukan" icon="fa-solid fa-rotate-left text-amber-600">
		<label class="mb-1.5 block text-xs font-semibold text-slate-700">Keterangan / Catatan Revisi
			<span class="text-rose-500">*</span></label>
		<textarea wire:model="revisionNotes" required
		 class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 min-h-[100px]"
		 placeholder="Jelaskan bagian mana saja yang perlu diperbaiki..."></textarea>
		@error('revisionNotes')
			<span class="text-xs text-amber-600 mt-1 block">{{ $message }}</span>
		@enderror

		<x-slot:footer class="flex gap-2">
			<button type="button" @click="showRevisionModal = false"
				class="flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
				Batal
			</button>
			<button type="button" wire:click="revision"
				class="flex-1 rounded bg-amber-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 shadow-sm">
				Kembalikan
			</button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Modal Batalkan Proses TTE. Sengaja tidak dibungkus @if: bila TTE selesai saat modal
	     terbuka, server menutupnya (showCancelTteModal = false) agar animasi tutup & kunci
	     scroll body tetap berjalan normal. --}}
	<x-ui.modal show="showCancelTteModal" :closeable="false" title="Batalkan Proses TTE?"
		description="Dokumen belum akan ditandatangani" icon="fa-solid fa-circle-xmark text-rose-600">
		<p class="text-sm text-slate-600">
			Proses tanda tangan elektronik yang sedang berjalan akan dihentikan. Anda dapat mengulang
			persetujuan dan TTE setelahnya.
		</p>

		<x-slot:footer class="flex gap-2">
			<x-ui.button variant="secondary" @click="showCancelTteModal = false" wire:loading.attr="disabled" wire:target="cancelTte"
				class="flex-1 justify-center">
				Kembali
			</x-ui.button>
			<x-ui.button variant="danger" wire:click="cancelTte" wire:loading.attr="disabled" wire:target="cancelTte"
				class="flex-1 justify-center">
				<span wire:loading.remove wire:target="cancelTte" class="inline-flex items-center gap-2">
					<i class="fa-solid fa-circle-xmark"></i> Ya, Batalkan TTE
				</span>
				<span wire:loading wire:target="cancelTte" class="inline-flex items-center gap-2">
					<i class="fa-solid fa-spinner fa-spin"></i> Membatalkan...
				</span>
			</x-ui.button>
		</x-slot:footer>
	</x-ui.modal>

	{{-- Animasi layar penuh saat seluruh dokumen penanda tangan ini selesai di-TTE. --}}
	@include('livewire.sppd.partials.tte-complete')

</div>
