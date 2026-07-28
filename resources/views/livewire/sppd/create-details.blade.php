<div class="flex flex-col gap-4 p-1" x-data="{ showConfirm: @entangle('showConfirmModal') }">

	{{-- Header Halaman --}}
	<div
		class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
		{{-- Watermark institusional (tipis, hanya karakter). --}}
		<i class="fa-solid fa-file-pen pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
			aria-hidden="true"></i>

		<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
			<div class="min-w-0 leading-tight">
				<span
					class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
					<i class="fa-solid fa-list-ol text-[9px]"></i> Langkah 2 dari 2
				</span>
				<h1 class="text-xl font-bold tracking-tight text-slate-800">Detail Perjalanan Dinas</h1>
				<p class="mt-1 text-xs text-slate-500">Isi detail & lengkapi data pengajuan.</p>
			</div>
			<x-ui.button href="{{ route('sppd.create', ['user_id' => $pelaksana->getRouteKey(), 'domain' => $domain]) }}" wire:navigate variant="secondary">
				<x-slot name="icon"><i class="fa-solid fa-arrow-left"></i></x-slot>
				Kembali ke Tahap 1
			</x-ui.button>
		</div>
	</div>



	{{-- autocomplete="off" (diwarisi semua field): autofill browser mengisi DOM tanpa memicu event,
	     sehingga Livewire mengirim nilai kosong/beda ke server. Tidak dikosongkan lewat JS karena
	     form ini juga dipakai untuk perbaikan SPPD dengan nilai bawaan dari server. --}}
	<form wire:submit.prevent="openConfirmModal" enctype="multipart/form-data" id="form-sppd-store" autocomplete="off">
		{{-- Ringkasan Informasi Pelaksana & Alur --}}
		<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
			<div class="rounded border border-slate-200 bg-white p-4 shadow-sm leading-tight flex flex-col justify-between">
				<div>
					<h4 class="text-xs font-bold uppercase tracking-wider text-primary-700 flex items-center gap-1.5 mb-2">
						<i class="fa-solid fa-user-tie"></i> Pelaksana
					</h4>
					<p class="font-bold text-slate-800 text-sm">{{ $pelaksana->name }}</p>
					<p class="text-xs text-slate-500 font-mono mt-0.5">{{ $pelaksana->nip }}</p>
				</div>
				<div class="mt-4 pt-3 border-t border-primary-200/60">
					<h4 class="text-xs font-bold uppercase tracking-wider text-primary-700 flex items-center gap-1.5">
						<i class="fa-solid fa-earth-asia"></i> Domain Perjalanan
					</h4>
					<span
						class="inline-block mt-1.5 rounded bg-white border border-primary-200 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-primary-800">
						{{ str_replace('_', ' ', $domain) }}
					</span>
				</div>
			</div>

			<div class="lg:col-span-2 rounded border border-slate-200 bg-white p-4 shadow-sm">
				<h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5 mb-3">
					<i class="fa-solid fa-diagram-project text-slate-500"></i> Pratinjau Alur Verifikasi / Persetujuan
				</h4>
				{{-- Alur Langkah — timeline horizontal ringkas (vertikal di mobile) --}}
				<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-0">
					@foreach ($steps as $step)
						@php
							$isFound = ($step['status'] ?? 'found') === 'found';
							$nodeClass = $isFound ? 'bg-emerald-600 ring-emerald-100' : 'bg-red-600 ring-red-100';
							$roleClass = $isFound ? 'text-emerald-700' : 'text-red-600';
							$nameClass = $isFound ? 'text-slate-800' : 'text-red-700 italic';
						@endphp

						<div class="flow-node flex min-w-0 flex-1 items-center gap-2.5 rounded-lg bg-white/70 px-2.5 py-2 ring-1 ring-slate-200/70 sm:bg-transparent sm:px-0 sm:py-0 sm:ring-0"
							style="animation-delay: {{ $loop->index * 90 }}ms">
							<span class="relative flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white shadow-sm ring-4 {{ $nodeClass }}">
								{{ $loop->iteration }}
								@if ($isFound)
									<i class="fa-solid fa-check absolute -bottom-0.5 -right-0.5 flex size-3.5 items-center justify-center rounded-full bg-white text-[7px] text-emerald-600 ring-1 ring-emerald-200"></i>
								@endif
							</span>
							<span class="min-w-0 leading-tight">
								<span class="block truncate text-[11px] font-bold uppercase tracking-wide {{ $roleClass }}">{{ $step['role_label'] }}</span>
								<span class="block truncate text-sm font-semibold {{ $nameClass }}" title="{{ $step['approver_name'] }}">{{ $step['approver_name'] }}</span>
							</span>
						</div>

						@if (! $loop->last)
							{{-- Konektor alur: vertikal di mobile, horizontal + chevron di desktop --}}
							<div class="flex shrink-0 items-center sm:px-1.5" aria-hidden="true">
								<span class="ml-3.5 h-4 w-0.5 rounded-full bg-slate-200 sm:hidden"></span>
								<span class="hidden h-0.5 w-6 rounded-full bg-slate-200 sm:block"></span>
								<i class="fa-solid fa-chevron-right hidden text-[10px] text-slate-300 sm:-ml-1 sm:block"></i>
							</div>
						@endif
					@endforeach
				</div>
			</div>
		</div>

		{{-- Data Perihal / Isi Telaah --}}
		<div class="mt-4 rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
			<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
				<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
					<span class="flex size-6 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600"><i class="fa-solid fa-file-pen text-[11px]"></i></span> Data Perihal & Justifikasi Perjalanan
				</h3>
			</div>
			<div class="p-5">
				<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
					<x-form.textarea wire:model="purpose" label="Perihal (Maksud Perjalanan Dinas)" rows="4" required
							placeholder="Contoh: Melaksanakan koordinasi terkait kerja sama media di Pemprov DKI Jakarta" />
					<x-form.textarea wire:model="problem" label="Persoalan" rows="4"
							placeholder="Contoh: Belum ada kesepakatan teknis publikasi antara dinas dan mitra media" />
					<x-form.textarea wire:model="facts" label="Fakta yang mempengaruhi" rows="4"
							placeholder="Contoh: Surat undangan Nomor 005/… tanggal 12 Juli 2026 dan target publikasi triwulan III" />
					<x-form.textarea wire:model="analysis" label="Analisis" rows="4"
							placeholder="Contoh: Koordinasi langsung diperlukan agar materi publikasi selesai sebelum batas waktu" />
				</div>
			</div>
		</div>

		{{-- Logistik & Tanggal Perjalanan --}}
		<div class="mt-4 rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
			<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
				<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
					<span class="flex size-6 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600"><i class="fa-solid fa-route text-[11px]"></i></span> Detail Logistik & Tanggal Perjalanan
				</h3>
			</div>
			<div class="p-5 space-y-5">
				{{-- Baris field logistik & tanggal --}}
				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
					<x-form.searchable-select wire:model="transport_type" name="transport_type" label="Jenis Angkutan"
						required placeholder="— Pilih —" searchPlaceholder="Cari angkutan..."
						:options="[['value' => '', 'label' => '— Pilih —'], 'Darat', 'Laut', 'Udara']" />
					<x-form.searchable-select wire:model="transport_name" name="transport_name" label="Nama Kendaraan"
						required placeholder="— Pilih —" searchPlaceholder="Cari kendaraan..."
						:options="[['value' => '', 'label' => '— Pilih —'], 'Motor', 'Mobil', 'Pesawat', 'Kapal', 'Kereta', 'Lainnya']" />
					<x-form.input type="text" wire:model="departure_place" label="Tempat Berangkat" required
							placeholder="Contoh: Kantor Dinas Kominfo Kendari" />
					<x-form.input type="date" wire:model="start_date" label="Tanggal Berangkat" required />
					<x-form.input type="date" wire:model="end_date" label="Tanggal Kembali" required />
				</div>

				{{-- Lokasi tujuan perjalanan --}}
				<div>
					<label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
						Lokasi Tujuan Perjalanan <span class="text-rose-500">*</span>
					</label>

					@if ($domain === 'dalam_daerah')
						<div id="dalam-daerah-fields" class="flex flex-col gap-2">
							<div
								class="inline-flex items-center gap-1.5 rounded bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 border border-slate-200/60 w-fit">
								<i class="fa-solid fa-location-dot text-slate-500"></i>
								Lokasi Basis: <span class="font-bold text-slate-700">Kota Kendari, Sulawesi Tenggara</span>
							</div>
							<input type="text" wire:model="destinations.0.address_only"
								class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500"
								required placeholder="Sebutkan instansi/tempat tujuan spesifik (misal: Kantor Gubernur, Kecamatan Poasia)">
							@error('destinations.0.address_only') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
						</div>
					@else
						<div id="multi-dest-fields">
							<div id="dest-wrap" class="space-y-2.5">
								@foreach ($destinations as $index => $dest)
									@php
										$provinceOptions = $provinces
											->filter(fn($p) => !($domain === 'ldlp' && $p->name === 'Sulawesi Tenggara'))
											->map(fn($p) => ['value' => $p->id, 'label' => $p->name])
											->values();
										$regencyOptions = $this->getRegenciesForProvince($dest['province_id'])
											->map(fn($r) => ['value' => $r->id, 'label' => $r->name])
											->values();
									@endphp
									<div class="dest-row grid grid-cols-1 {{ $domain === 'lddp' ? 'md:grid-cols-2' : 'md:grid-cols-3' }} gap-2 p-3 bg-slate-50/80 border border-slate-200 rounded relative items-start">
										<div class="{{ $domain === 'lddp' ? 'hidden' : '' }}">
											<x-form.searchable-select wire:model.live="destinations.{{ $index }}.province_id"
												:options="$provinceOptions" placeholder="— Provinsi —" searchPlaceholder="Cari provinsi..." required />
										</div>
										{{-- wire:key memuat province_id agar komponen di-render ulang saat
										     provinsi berubah, sehingga opsi kabupaten (yang di-init Alpine
										     sekali saat mount) ikut diperbarui. --}}
										<div wire:key="regency-{{ $index }}-{{ $dest['province_id'] ?: 'none' }}">
											<x-form.searchable-select wire:model="destinations.{{ $index }}.regency_id"
												:options="$regencyOptions" placeholder="— Kabupaten/Kota —" searchPlaceholder="Cari kabupaten/kota..." required />
										</div>
										<div class="flex gap-2">
											<input type="text" wire:model="destinations.{{ $index }}.address"
												class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 flex-1 shadow-2xs focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500"
												placeholder="Instansi / Alamat Spesifik Tujuan" required>
											@if (count($destinations) > 1)
												<button type="button" wire:click="removeDestination({{ $index }})" class="text-red-500 hover:text-red-700 transition px-1">
													<i class="fa-solid fa-trash-can text-base"></i>
												</button>
											@endif
										</div>
										@error("destinations.{$index}.address") <span class="text-xs text-red-500 col-span-full">{{ $message }}</span> @enderror
									</div>
								@endforeach
							</div>

							<div class="flex items-center gap-3 mt-2.5">
								@if (count($destinations) < 4)
									<button type="button" wire:click="addDestination"
										class="text-xs text-primary-600 font-bold hover:text-primary-700 flex items-center gap-1">
										<i class="fa-solid fa-circle-plus"></i> Tambah Lokasi Tujuan Lainnya
									</button>
								@endif
								<span class="text-xs text-slate-500">({{ count($destinations) }}/4 lokasi)</span>
								@if (count($destinations) >= 4)
									<span class="text-xs text-amber-600 font-semibold"><i class="fa-solid fa-circle-info"></i> Batas maksimal 4 tujuan</span>
								@endif
							</div>
						</div>
					@endif
				</div>
			</div>
		</div>

		{{-- Pembebanan Anggaran & Informasi Anggaran Tersedia --}}
		@php $selectedBudget = $budgets->firstWhere('id', $budget_id); @endphp
		<div class="grid grid-cols-1 gap-4 lg:grid-cols-2 mt-4">
			<div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
				<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
					<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
						<span class="flex size-6 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600"><i class="fa-solid fa-money-check-dollar text-[11px]"></i></span> Anggaran & Dokumen Pendukung
					</h3>
				</div>
				<div class="p-5 space-y-4">
					@php
						$budgetOptions = $budgets
							->map(fn($b) => ['value' => $b->id, 'label' => ($b->program ?? '-') . ' | ' . ($b->activity ?? '-')])
							->prepend(['value' => '', 'label' => '— Pilih —'])
							->values();
						$categoryOptions = $categories
							->map(fn($c) => ['value' => $c->id, 'label' => $c->name])
							->values();
					@endphp
					<div>
						<x-form.searchable-select wire:model.live="budget_id" name="budget_id"
							label="Sumber Anggaran / Kegiatan SKPD" required :options="$budgetOptions"
							placeholder="— Pilih Program / Kegiatan —" searchPlaceholder="Cari program / kegiatan..." />
						<p class="mt-1 text-[10px] text-slate-500">
							<i class="fa-solid fa-circle-info"></i>
							DPA anggaran yang tersedia merupakan DPA tahun anggaran berjalan ({{ now()->year }}).
						</p>
					</div>
					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						<x-form.searchable-select wire:model="category_id" name="category_id" label="Kategori Dinas"
							required :options="$categoryOptions" placeholder="— Pilih Kategori —" searchPlaceholder="Cari kategori..." />
						<x-form.searchable-select wire:model="urgency" name="urgency" label="Sifat Surat Dokumen"
							required :options="['Biasa', 'Segera']" placeholder="— Pilih —" searchPlaceholder="Cari..." />
					</div>
					<div>
						<label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">Undangan / Dokumen Pendukung</label>
						<input type="file" wire:model="attachment" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
						<p class="text-[10px] text-slate-500 mt-1">Format berkas: PDF, DOCX, JPG, PNG (Maks. 2MB)</p>
						@error('attachment') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
					</div>
				</div>
			</div>

			{{-- Informasi Anggaran Tersedia (muncul saat program / kegiatan dipilih) --}}
			<div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
				<div class="border-b border-slate-100 bg-emerald-50/60 px-5 py-3">
					<h3 class="text-xs font-bold tracking-wider text-emerald-700 uppercase flex items-center gap-1.5">
						<span class="flex size-6 shrink-0 items-center justify-center rounded bg-emerald-50 text-emerald-600"><i class="fa-solid fa-wallet text-[11px]"></i></span> Informasi Anggaran Tersedia
					</h3>
				</div>
				@if ($selectedBudget)
					<div class="p-5 space-y-3.5 flex-1">
						{{-- Sisa Pagu (Anggaran Tersedia) --}}
						<div class="rounded border {{ $selectedBudget->balance < 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} px-3 py-2.5">
							<span class="block text-[10px] font-bold uppercase tracking-wider {{ $selectedBudget->balance < 0 ? 'text-rose-600' : 'text-emerald-600' }}">Sisa Pagu Tersedia</span>
							<p class="mt-0.5 text-lg font-bold {{ $selectedBudget->balance < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
								Rp {{ number_format($selectedBudget->balance, 0, ',', '.') }}
							</p>
						</div>

						{{-- Bar Realisasi --}}
						<div>
							<div class="flex items-center justify-between text-[10px] font-semibold text-slate-500 mb-1">
								<span>Realisasi {{ $selectedBudget->realization_percentage }}%</span>
								<span>Rp {{ number_format($selectedBudget->realization, 0, ',', '.') }}</span>
							</div>
							<div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
								<div class="h-full rounded-full {{ $selectedBudget->realization_percentage >= 100 ? 'bg-rose-500' : 'bg-primary-500' }}"
									style="width: {{ min($selectedBudget->realization_percentage, 100) }}%"></div>
							</div>
						</div>

						{{-- Pagu Total --}}
						<div class="flex items-center justify-between border-t border-slate-200/70 pt-2.5 text-xs">
							<span class="font-medium text-slate-500">Pagu Total</span>
							<span class="font-bold text-slate-800">Rp {{ number_format($selectedBudget->total_amount, 0, ',', '.') }}</span>
						</div>

						{{-- Detail Program / Kegiatan --}}
						<div class="space-y-2 border-t border-slate-200/70 pt-2.5 leading-tight">
							<div>
								<span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Program</span>
								<p class="text-xs font-semibold text-primary-700">{{ $selectedBudget->program ?? '-' }}</p>
							</div>
							<div>
								<span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Kegiatan</span>
								<p class="text-xs text-slate-600">{{ $selectedBudget->activity ?? '-' }}</p>
							</div>
							@if ($selectedBudget->account_code)
								<div>
									<span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Kode Rekening</span>
									<span class="inline-block mt-0.5 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-mono font-medium text-slate-600 border border-slate-200/60">{{ $selectedBudget->account_code }}</span>
								</div>
							@endif
						</div>
					</div>
				@else
					<div class="flex flex-1 flex-col items-center justify-center p-8 text-center text-xs text-slate-500 italic">
						<i class="fa-solid fa-hand-pointer mb-2 block text-xl text-slate-300"></i>
						Pilih program / kegiatan anggaran untuk menampilkan informasi anggaran tersedia.
					</div>
				@endif
			</div>
		</div>

		{{-- Daftar Pengikut & Penomoran Dokumen Resmi --}}
		<div class="grid grid-cols-1 gap-4 lg:grid-cols-2 mt-4">
			<div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
				<div
					class="border-b border-slate-100 bg-slate-50/75 px-5 py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
					<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
						<span class="flex size-6 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600"><i class="fa-solid fa-users text-[11px]"></i></span> Daftar Pengikut (Opsional)
					</h3>
					{{-- Input Live Search Pengikut --}}
					<div class="relative w-full sm:w-48">
						<span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-500">
							<i class="fa-solid fa-magnifying-glass text-xs"></i>
						</span>
						<input type="text" wire:model.live.debounce.300ms="searchFollower"
							class="w-full rounded border border-slate-300 bg-white py-1 pl-8 pr-2.5 text-xs text-slate-700 placeholder-slate-400 focus:border-primary-500 focus:outline-hidden"
							placeholder="Cari nama pegawai...">
					</div>
				</div>

				<div class="p-4 bg-slate-50/50 flex-1 overflow-y-auto max-h-56">
					<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
						@forelse ($users as $u)
							@if ($u->id != $pelaksana->id)
								@php
									$isTraveling = in_array($u->id, $travelingFollowerIds);
									$isPending = !$isTraveling && in_array($u->id, $pendingFollowerIds);
									$isBusy = $isTraveling || $isPending;
								@endphp
								<label
									class="follower-item flex items-start gap-2.5 rounded border border-slate-200 bg-white p-2.5 shadow-2xs transition-colors hover:bg-slate-50 cursor-pointer {{ $isTraveling ? 'opacity-60 cursor-not-allowed bg-rose-50/50 border-rose-200' : ($isPending ? 'opacity-60 cursor-not-allowed bg-amber-50/50 border-amber-200' : '') }}">
									<input type="checkbox" wire:model.live="followers" value="{{ $u->id }}"
										class="follower-cb rounded border-slate-300 text-primary-600 focus:ring-primary-500 mt-0.5"
										@disabled($isBusy)>
									<div class="min-w-0 leading-tight">
										<span class="block text-xs font-semibold text-slate-700 truncate follower-name">{{ $u->name }}</span>
										@if ($u->nip)
												<span class="block text-[10px] text-slate-500 font-mono mt-0.5">{{ $u->nip }}</span>
											@endif
											@if ($u->employee_type === \App\Enums\EmployeeType::DPRD)
												<span class="block text-[10px] font-semibold text-primary-600 mt-0.5">Anggota DPRD</span>
											@endif
										@if ($isTraveling)
											<span class="inline-block text-[10px] text-rose-600 font-semibold mt-0.5"><i
													class="fa-solid fa-route"></i> Sedang dalam perjalanan dinas</span>
										@elseif ($isPending)
											<span class="inline-block text-[10px] text-amber-600 font-semibold mt-0.5"><i
													class="fa-solid fa-hourglass-half"></i> Dalam proses persetujuan</span>
										@endif
									</div>
								</label>
							@endif
						@empty
							<div class="col-span-full text-center py-8 text-xs font-medium text-slate-500 italic">
								@if ($searchFollower === '')
									<i class="fa-solid fa-user-slash text-base mb-1 block text-slate-300"></i> Belum ada pegawai di unit ini
								@else
									<i class="fa-solid fa-user-slash text-base mb-1 block text-slate-300"></i> Pegawai tidak ditemukan
								@endif
							</div>
						@endforelse
					</div>

					@if ($searchFollower === '' && $users->count() >= 100)
						<p class="mt-2 text-center text-[10px] font-medium text-slate-400 italic">
							<i class="fa-solid fa-circle-info"></i> Menampilkan 100 pegawai. Gunakan pencarian untuk menemukan lainnya.
						</p>
					@endif
				</div>
			</div>

			{{-- Administrasi Tanggal Pengesahan --}}
			<div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden">
				<div class="border-b border-slate-100 bg-slate-50/75 px-5 py-3">
					<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-1.5">
						<span class="flex size-6 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600"><i class="fa-solid fa-calendar-check text-[11px]"></i></span> Penomoran & Penanggalan Dokumen Resmi
					</h3>
				</div>
				<div class="p-5 space-y-4">
					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						@if ($isInspektorat)
							<x-form.input type="text" wire:model="document_number" label="Nomor Surat Tugas" placeholder="Contoh: 700/100/Insp/V/2026" wrapperClass="md:col-span-2" />
						@endif
							<x-form.input type="date" wire:model="spt_date" label="Tanggal Penerbitan SPT" required />
							<x-form.input type="date" wire:model="sppd_date" label="Tanggal Penerbitan SPPD" required />
					</div>

					{{-- Tombol Submit Pembuat Aksi --}}
					<div class="flex justify-end border-t border-slate-100 pt-4">
						<x-ui.button variant="primary" type="submit" wire:target="openConfirmModal" class="px-6">
							<x-slot name="icon"><i class="fa-solid fa-paper-plane text-xs"></i></x-slot>
							Buat & Ajukan SPPD
						</x-ui.button>
					</div>
				</div>
			</div>
		</div>
	</form>

	{{-- MODAL KONFIRMASI --}}
	@php $selectedBudget = $budgets->firstWhere('id', $budget_id); @endphp
	<x-ui.modal show="showConfirm" title="{{ $sppd_id ? 'Konfirmasi Perbaikan SPPD' : 'Konfirmasi Pengajuan' }}" icon="fa-solid fa-circle-question text-primary-600 text-base" maxWidth="max-w-3xl" :closeable="false">
		<div class="py-2 grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2">

			{{-- Kolom Kiri: Anggaran Tersedia --}}
			<div class="rounded border border-slate-200 bg-slate-50/50 overflow-hidden">
				<div class="border-b border-slate-100 bg-emerald-50/60 px-3 py-2 sm:px-4 sm:py-2.5">
					<h4 class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
						<i class="fa-solid fa-wallet"></i> Anggaran Tersedia
					</h4>
				</div>
				@if ($selectedBudget)
					<div class="p-3 space-y-2.5 sm:p-4 sm:space-y-3.5">
						{{-- Sisa Pagu (Anggaran Tersedia) --}}
						<div class="rounded border {{ $selectedBudget->balance < 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} px-3 py-2 sm:py-2.5">
							<span class="block text-[10px] font-bold uppercase tracking-wider {{ $selectedBudget->balance < 0 ? 'text-rose-600' : 'text-emerald-600' }}">Sisa Pagu Tersedia</span>
							<p class="mt-0.5 text-base sm:text-lg font-bold {{ $selectedBudget->balance < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
								Rp {{ number_format($selectedBudget->balance, 0, ',', '.') }}
							</p>
						</div>

						{{-- Bar Realisasi --}}
						<div>
							<div class="flex items-center justify-between text-[10px] font-semibold text-slate-500 mb-1">
								<span>Realisasi {{ $selectedBudget->realization_percentage }}%</span>
								<span>Rp {{ number_format($selectedBudget->realization, 0, ',', '.') }}</span>
							</div>
							<div class="h-1.5 sm:h-2 w-full rounded-full bg-slate-200 overflow-hidden">
								<div class="h-full rounded-full {{ $selectedBudget->realization_percentage >= 100 ? 'bg-rose-500' : 'bg-primary-500' }}"
									style="width: {{ min($selectedBudget->realization_percentage, 100) }}%"></div>
							</div>
						</div>

						{{-- Pagu Total --}}
						<div class="flex items-center justify-between border-t border-slate-200/70 pt-2 sm:pt-2.5 text-xs">
							<span class="font-medium text-slate-500">Pagu Total</span>
							<span class="font-bold text-slate-800">Rp {{ number_format($selectedBudget->total_amount, 0, ',', '.') }}</span>
						</div>

						{{-- Detail Program / Kegiatan (disembunyikan di mobile agar ringkas) --}}
						<div class="hidden sm:block space-y-2 border-t border-slate-200/70 pt-2.5 leading-tight">
							<div>
								<span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Program</span>
								<p class="text-xs font-semibold text-primary-700">{{ $selectedBudget->program ?? '-' }}</p>
							</div>
							<div>
								<span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Kegiatan</span>
								<p class="text-xs text-slate-600">{{ $selectedBudget->activity ?? '-' }}</p>
							</div>
							@if ($selectedBudget->account_code)
								<div>
									<span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Kode Rekening</span>
									<span class="inline-block mt-0.5 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-mono font-medium text-slate-600 border border-slate-200/60">{{ $selectedBudget->account_code }}</span>
								</div>
							@endif
						</div>
					</div>
				@else
					<div class="p-6 text-center text-xs text-slate-500 italic">
						<i class="fa-solid fa-circle-exclamation mb-1 block text-base text-slate-300"></i>
						Sumber anggaran belum dipilih.
					</div>
				@endif
			</div>

			{{-- Kolom Kanan: Pelaksana & Pengikut --}}
			<div class="space-y-3.5 text-sm text-slate-600">
				<div>
					<span class="block text-xs font-bold uppercase tracking-wider text-slate-500">Pegawai Pelaksana:</span>
					<p class="font-bold text-slate-800 mt-1 bg-slate-50 px-2.5 py-1.5 rounded border border-slate-200/60">
						<i class="fa-solid fa-user text-primary-600 mr-1.5"></i> {{ $pelaksana->name }}
					</p>
				</div>
				<div>
					<span class="block text-xs font-bold uppercase tracking-wider text-slate-500">Daftar Pengikut Dinas:</span>
					<div class="mt-1 border border-slate-200 rounded divide-y divide-slate-100 bg-slate-50/50 max-h-64 overflow-y-auto">
						@if ($isInspektorat)
							@forelse ($followers as $fId)
								@php $folUser = $selectedFollowerUsers->firstWhere('id', $fId); @endphp
								@if ($folUser)
									<div class="px-3 py-2 bg-white flex items-center justify-between gap-2">
										<span class="text-xs font-semibold text-slate-700 truncate">
											<i class="fa-solid fa-caret-right text-primary-600 mr-1.5"></i>{{ $folUser->name }}
										</span>
										<select wire:model="follower_positions.{{ $fId }}"
											class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 focus:border-primary-500 focus:outline-none shrink-0"
											required>
											<option value="">— Pilih Jabatan —</option>
											<option value="Penanggung Jawab">Penanggung Jawab</option>
											<option value="Pembantu Penanggung Jawab">Pembantu Penanggung Jawab</option>
											<option value="Pengendali Teknis">Pengendali Teknis</option>
											<option value="Ketua Tim">Ketua Tim</option>
											<option value="Anggota">Anggota</option>
											<option value="Admin Tim">Admin Tim</option>
										</select>
									</div>
									@error('follower_positions.' . $fId)
										<span class="text-xs text-rose-600 px-3 pb-1 block">{{ $message }}</span>
									@enderror
								@endif
							@empty
								<div class="px-3 py-2.5 text-xs text-slate-500 italic bg-white"><i class="fa-solid fa-user-minus mr-1"></i> Tidak ada pengikut</div>
							@endforelse
						@else
							@forelse ($followers as $fId)
								@php $folUser = $selectedFollowerUsers->firstWhere('id', $fId); @endphp
								@if ($folUser)
									<div class="px-3 py-2 text-xs font-semibold text-slate-700 bg-white"><i class="fa-solid fa-caret-right text-primary-600 mr-1.5"></i> {{ $folUser->name }}</div>
								@endif
							@empty
								<div class="px-3 py-2.5 text-xs text-slate-500 italic bg-white"><i class="fa-solid fa-user-minus mr-1"></i> Tidak ada pengikut</div>
							@endforelse
						@endif
					</div>
				</div>
				@if ($sppd_id)
					<p class="text-xs bg-amber-50 border border-amber-200 rounded p-2 text-amber-800">
						<i class="fa-solid fa-circle-info mr-0.5"></i> Revisi akan dikirim kepada pejabat approver dan selama proses tidak dapat dilakukan pengeditan data.
					</p>
				@else
					<p class="text-xs bg-amber-50 border border-amber-200 rounded p-2 text-amber-800">
						<i class="fa-solid fa-circle-info mr-0.5"></i> Pastikan perihal, tanggal perjalanan, dan rekening anggaran sudah benar sebelum mengajukan dokumen ke alur verifikasi.
					</p>
				@endif
			</div>
		</div>

		<div class="flex justify-end gap-2 border-t border-slate-100 pt-3 mt-2">
			<x-ui.button variant="secondary" size="sm" wire:click="closeConfirmModal">Periksa Kembali</x-ui.button>
			<x-ui.button variant="primary" size="sm" wire:click="submit" wire:target="submit">
				{{ $sppd_id ? 'Ya, Kirim Perbaikan' : 'Ya, Ajukan Sekarang' }}
			</x-ui.button>
		</div>
	</x-ui.modal>

</div>
