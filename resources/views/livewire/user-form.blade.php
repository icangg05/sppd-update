<div x-data="{ showResetModal: false }">
	<div class="mx-auto max-w-4xl space-y-6 p-1">

		{{-- Header Halaman (title card) --}}
		<div
			class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
			{{-- Watermark institusional (tipis, hanya karakter). --}}
			<i class="fa-solid {{ $isEdit ? 'fa-user-pen' : 'fa-user-plus' }} pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
				aria-hidden="true"></i>

			<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
				<div class="min-w-0 leading-tight">
					<span
						class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
						<i class="fa-solid {{ $isEdit ? 'fa-pen-nib' : 'fa-user-plus' }} text-[9px]"></i>
						{{ $isEdit ? 'Perbarui Data' : 'Registrasi Pegawai' }}
					</span>
					<h1 class="text-xl font-bold tracking-tight text-slate-800">
						{{ $isEdit ? 'Edit' : 'Tambah' }} Pegawai
					</h1>
					<p class="mt-1 text-xs text-slate-500">
						{{ $isEdit ? 'Ubah informasi profil, penempatan, atau kredensial pengguna sistem' : 'Tambahkan pegawai baru ke dalam sistem' }}
					</p>
				</div>
				<div class="flex flex-wrap items-center gap-2">
				@if ($isEdit)
					<x-ui.button href="{{ route('master.users.show', array_filter(['user' => $user, 'type' => $listType])) }}"
						variant="primary" class="shrink-0">
						<x-slot name="icon">
							<i class="fa-solid fa-eye text-xs"></i>
						</x-slot>
						Lihat Detail
					</x-ui.button>
				@endif
				<x-ui.button href="{{ route('master.users.index', array_filter(['type' => $listType])) }}" variant="secondary"
					class="shrink-0">
					<x-slot name="icon">
						<i class="fa-solid fa-arrow-left text-xs"></i>
					</x-slot>
					Kembali
				</x-ui.button>
				</div>
			</div>
		</div>

		{{-- Main Form --}}
		<form wire:submit.prevent="save" class="space-y-6">

			{{-- ── Kelompok 1: Identitas Pegawai ── --}}
			<section class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
				<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
						<i class="fa-regular fa-id-card"></i>
					</div>
					<div>
						<h3 class="text-sm font-bold text-slate-800">Identitas Pegawai</h3>
						<p class="text-xs text-slate-500">Data diri dan status kepegawaian.</p>
					</div>
				</header>

				<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
					<div class="space-y-1">
						<x-form.input wire:model="name" name="name" label="Nama Lengkap" required
							placeholder="Contoh: Budi Santoso" />
					</div>

					<div class="space-y-1">
						@php
							// Opsi "Anggota DPRD" hanya tersedia pada konteks DPRD.
							$employeeTypeOptions = collect($employeeTypes)
								->when(! $this->isDprdContext(), fn($c) => $c->reject(fn($type) => $type->value === \App\Enums\EmployeeType::DPRD->value))
								->map(fn($type) => ['value' => $type->value, 'label' => $type->label()])
								->values()
								->all();
						@endphp
						<x-form.searchable-select wire:model.live="employee_type" name="employee_type"
							label="Tipe Status Pegawai" required :disabled="$this->isDprdContext()"
							:options="$employeeTypeOptions" placeholder="— Pilih Tipe Pegawai —"
							searchPlaceholder="Cari tipe..." />
						@if ($this->isDprdContext())
							<p class="text-xs text-slate-500">Terkunci sebagai Anggota DPRD pada formulir ini.</p>
						@endif
					</div>

					@unless ($this->isDprdContext())
						<div class="space-y-1">
							<x-form.input wire:model="nip" name="nip" label="NIP (Nomor Induk Pegawai)"
								placeholder="18 digit angka" class="font-mono" />
						</div>
					@endunless

					<div class="space-y-1">
						<x-form.input wire:model="nik" name="nik" label="NIK (Nomor Induk Kependudukan)"
							placeholder="16 digit angka" class="font-mono" />
					</div>
				</div>
			</section>

			{{-- ── Kelompok 2: Akun Sistem ── --}}
			<section class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
				<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
						<i class="fa-solid fa-key"></i>
					</div>
					<div>
						<h3 class="text-sm font-bold text-slate-800">Akun Sistem</h3>
						<p class="text-xs text-slate-500">Kredensial untuk masuk ke aplikasi.</p>
					</div>
				</header>

				<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
					<div class="space-y-1">
						{{-- Username dengan tombol generate username unik --}}
						<label for="username" class="block text-xs font-bold tracking-wide text-slate-600 uppercase">
							Username <span class="text-rose-500">*</span>
						</label>
						<div class="flex gap-2">
							<input wire:model="username" type="text" id="username" name="username" required
								placeholder="Contoh: budi.santoso"
								class="block w-full flex-1 rounded border px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 @error('username') border-red-400 @else border-slate-300 @enderror" />

							<button type="button" wire:click="generateUsername" wire:loading.attr="disabled"
								wire:target="generateUsername" title="Buat username unik otomatis dari Nama Lengkap"
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-primary-500 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-600 hover:text-white active:scale-95 disabled:opacity-50">
								<span wire:loading.remove wire:target="generateUsername">
									<i class="fa-solid fa-wand-magic-sparkles text-sm"></i> Generate
								</span>
								<span wire:loading wire:target="generateUsername">
									<i class="fa-solid fa-spinner fa-spin text-sm"></i> Membuat...
								</span>
							</button>
						</div>

						@error('username')
							<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
						@enderror
					</div>

					<div class="space-y-1">
						<x-form.input wire:model="email" type="email" name="email" label="Email Resmi"
							placeholder="email@contoh.com" />
					</div>

					<div class="space-y-1 sm:col-span-2">
						<x-form.input wire:model="password" type="password" name="password"
							label="{{ $isEdit ? 'Password Baru' : 'Password' }}"
							placeholder="{{ $isEdit ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 6 karakter (opsional)' }}" />
						@unless ($isEdit)
							<p class="text-xs text-slate-500">
								<i class="fa-solid fa-circle-info mr-1"></i>
								Jika dikosongkan, password default
								<strong class="font-mono text-slate-600">{{ config('users.default_password') }}</strong>
								akan digunakan.
							</p>
						@endunless
					</div>
				</div>
			</section>

			{{-- ── Kelompok 3: Kontak WhatsApp ── --}}
			<section class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
				<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
						<i class="fa-brands fa-whatsapp"></i>
					</div>
					<div>
						<h3 class="text-sm font-bold text-slate-800">Kontak WhatsApp</h3>
						<p class="text-xs text-slate-500">Dipakai untuk notifikasi & verifikasi sistem.</p>
					</div>
				</header>

				<div class="p-5 sm:p-6">
					<div class="space-y-1">
						{{-- Phone Field dengan tombol verifikasi/ganti nomor --}}
						<label for="phone" class="block text-xs font-bold uppercase tracking-wide text-slate-600">
							No. Telepon / WhatsApp
						</label>
						<div class="flex gap-2">
							<input
								wire:model="phone"
								type="tel"
								id="phone"
								name="phone"
								placeholder="Contoh: 08123456789"
								inputmode="numeric"
								pattern="[0-9+]*"
								@if ($phoneVerified) readonly @endif
								class="flex-1 block w-full rounded border px-3 py-2 text-sm text-slate-800 shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 @error('phone') border-red-400 @else border-slate-300 @enderror @if ($phoneVerified) bg-slate-50 cursor-not-allowed @endif" />

							@if ($phoneVerified)
								<button
									type="button"
									disabled
									class="inline-flex items-center gap-1.5 rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 whitespace-nowrap cursor-default">
									<i class="fa-solid fa-check-circle text-sm"></i> <span class="hidden sm:inline">Terverifikasi</span>
								</button>
								<button
									type="button"
									wire:click="confirmTestMessage"
									title="Tes kirim pesan ke nomor pegawai ini"
									class="inline-flex items-center gap-1.5 rounded border border-primary-500 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-600 hover:text-white active:scale-95 whitespace-nowrap">
									<i class="fa-solid fa-paper-plane text-sm"></i> <span class="hidden sm:inline">Tes Pesan</span>
								</button>
								<button
									type="button"
									@click="showResetModal = true"
									title="Ganti nomor WhatsApp"
									class="inline-flex items-center gap-1.5 rounded border border-amber-500 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-600 hover:text-white active:scale-95 whitespace-nowrap">
									<i class="fa-solid fa-rotate text-sm"></i> <span class="hidden sm:inline">Ganti</span>
								</button>
							@else
								<button
									type="button"
									wire:click="openVerifyModal"
									wire:loading.attr="disabled"
									wire:target="openVerifyModal"
									title="Verifikasi nomor WhatsApp ini"
									class="inline-flex items-center gap-1.5 rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-600 hover:text-white active:scale-95 whitespace-nowrap disabled:opacity-50">
									<span wire:loading.remove wire:target="openVerifyModal" class="inline-flex items-center gap-1.5">
										<i class="fa-brands fa-whatsapp text-sm"></i> <span class="hidden sm:inline">Verifikasi</span>
									</span>
									<span wire:loading wire:target="openVerifyModal" class="inline-flex items-center gap-1.5">
										<i class="fa-solid fa-spinner fa-spin text-sm"></i> <span class="hidden sm:inline">Memuat...</span>
									</span>
								</button>
							@endif
						</div>

						@if ($phoneVerified)
							<p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-green-600">
								<i class="fa-solid fa-circle-check mt-0.5"></i>
								<span>Nomor telah diverifikasi dan terkunci. Gunakan tombol <strong>Ganti</strong> jika ingin mengubahnya.</span>
							</p>
						@else
							<p class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-amber-600">
								<i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
								<span>Wajib verifikasi nomor dengan menekan tombol <strong>Verifikasi</strong> sebelum dapat menyimpan perubahan.</span>
							</p>
						@endif

						@error('phone')
							<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
						@enderror
					</div>
				</div>
			</section>

			{{-- ── Kelompok 4: Penempatan & Kewenangan ── --}}
			{{-- Catatan: TANPA .dash-enter. Animasi (animation-fill: both) membuat stacking
			     context permanen yang menjebak panel Jabatan (position: fixed) sehingga
			     tertutup action bar sticky meski z-index 9999. Lihat [[blade-livewire-pitfalls]]. --}}
			<section class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
				<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
						<i class="fa-solid fa-sitemap"></i>
					</div>
					<div>
						<h3 class="text-sm font-bold text-slate-800">Penempatan & Kewenangan</h3>
						<p class="text-xs text-slate-500">Unit kerja, pangkat, jabatan, dan hak akses.</p>
					</div>
				</header>

				<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
					<div class="space-y-1 sm:col-span-2">
						@php
							$departmentOptions = collect($departments)
								->map(fn($d) => ['value' => $d->id, 'label' => $d->display_name])
								->all();
						@endphp
						<x-form.searchable-select wire:model="department_id" name="department_id"
							label="Instansi / Unit Kerja (OPD)" required :options="$departmentOptions"
							placeholder="— Pilih Instansi / Unit Kerja —" searchPlaceholder="Cari instansi / unit kerja ..."
							hint="Pilih unit kerja tempat pegawai benar-benar ditempatkan. Daftar ditampilkan berjenjang (indentasi) sesuai struktur OPD induk → bidang/seksi; pilih unit yang paling spesifik." />
						<p class="text-xs text-slate-500 mt-1">
							<i class="fa-solid fa-circle-plus text-primary-500 mr-1"></i>Unit kerja belum ada?
							<a href="{{ route('master.departments.create') }}" target="_blank" rel="noopener"
								class="font-semibold text-primary-600 underline-offset-2 hover:underline">
								Tambah unit kerja baru <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
							</a>
						</p>
					</div>

					@unless ($this->isDprdContext())
						<div class="space-y-1">
							@php
								$rankOptions = collect($ranks)
									->map(fn($r) => ['value' => $r->id, 'label' => $r->group . ' — ' . $r->name])
									->prepend(['value' => '', 'label' => '— Tidak dipilih —'])
									->all();
							@endphp
							<x-form.searchable-select wire:model="rank_id" name="rank_id" label="Golongan / Pangkat"
								:options="$rankOptions" placeholder="— Pilih Pangkat —" searchPlaceholder="Cari pangkat..." />
						</div>

						<div class="space-y-1">
								{{-- Jabatan: dropdown dengan pencarian server-side (live) agar tidak memuat seluruh data jabatan --}}
							<label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
								Jabatan Struktural / Fungsional
							</label>
							<div x-data="{
									open: false,
									dropUp: false,
									highlighted: 0,
									coords: { top: 0, left: 0, width: 0 },
									position() {
										const r = this.$refs.trigger.getBoundingClientRect();
										const margin = 4;
										const panelH = this.$refs.panel?.offsetHeight || 320;
										const spaceBelow = window.innerHeight - r.bottom;
										const spaceAbove = r.top;
										this.dropUp = spaceBelow < panelH + margin && spaceAbove > spaceBelow;
										this.coords = {
											top: this.dropUp ? Math.max(margin, r.top - panelH - margin) : r.bottom + margin,
											left: r.left,
											width: r.width,
										};
									},
									items() {
										return this.$refs.list ? Array.from(this.$refs.list.querySelectorAll('[data-opt]')) : [];
									},
									move(dir) {
										const len = this.items().length;
										if (!len) return;
										let n = this.highlighted + dir;
										if (n < 0) n = len - 1;
										if (n >= len) n = 0;
										this.highlighted = n;
										this.$nextTick(() => this.items()[this.highlighted]?.scrollIntoView({ block: 'nearest' }));
									},
									pick() {
										(this.items()[this.highlighted] || this.items()[0])?.click();
									},
									canAutoFocus() {
										return window.matchMedia && window.matchMedia('(pointer: fine)').matches;
									},
									toggle() {
										this.open = !this.open;
										if (this.open) {
											this.highlighted = 0;
											this.$nextTick(() => { this.position(); if (this.canAutoFocus()) this.$refs.searchPosition?.focus(); });
										}
									},
									onOutside(e) {
										if (!this.open) return;
										if (this.$refs.trigger.contains(e.target)) return;
										if (this.$refs.panel?.contains(e.target)) return;
										this.open = false;
									},
								}"
								@keydown.escape.window="open = false"
								@click.window="onOutside($event)"
								@scroll.window.capture="if (open) position()"
								@resize.window="if (open) position()"
								class="relative">
								@php
									$selectedPositionLabel = $selectedPosition?->name;
								@endphp
								<button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open"
									class="flex w-full items-center justify-between gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-sm shadow-2xs transition focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500">
									<span class="truncate text-left {{ $selectedPositionLabel ? 'text-slate-800' : 'text-slate-500' }}">
										{{ $selectedPositionLabel ?? '— Pilih Jabatan —' }}
									</span>
									<i class="fa-solid fa-chevron-down text-xs text-slate-500 transition-transform"
										:class="open && 'rotate-180'"></i>
								</button>

								<div x-ref="panel" x-show="open" x-cloak
									x-transition:enter="transition ease-out duration-100"
									x-transition:enter-start="opacity-0 scale-95"
									x-transition:enter-end="opacity-100 scale-100"
									x-transition:leave="transition ease-in duration-75"
									x-transition:leave-start="opacity-100 scale-100"
									x-transition:leave-end="opacity-0 scale-95"
									:style="`position: fixed; top: ${coords.top}px; left: ${coords.left}px; width: ${coords.width}px; z-index: 9999; transform-origin: ${dropUp ? 'bottom' : 'top'};`"
									class="overflow-hidden rounded border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5">
									<div class="border-b border-slate-100 p-2">
										<div class="relative">
											<i class="fa-solid fa-magnifying-glass pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-[11px] text-slate-500" style="display:flex;"></i>
											<input x-ref="searchPosition" type="text" wire:model.live.debounce.300ms="searchPosition"
												@input="highlighted = 0"
												@keydown.arrow-down.prevent="move(1)"
												@keydown.arrow-up.prevent="move(-1)"
												@keydown.enter.prevent="pick()"
												@keydown.tab.prevent="move($event.shiftKey ? -1 : 1)"
												placeholder="Cari jabatan..."
												class="w-full rounded border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-2 text-sm outline-none focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500">
										</div>
									</div>
									<ul x-ref="list" class="max-h-56 overflow-auto py-1">
										<li wire:key="position-opt-none">
											<button type="button" data-opt wire:click="clearPosition"
												@click="open = false" @mouseenter="highlighted = 0"
												class="block w-full cursor-pointer px-3 py-2 text-left text-sm transition-colors"
												:class="highlighted === 0
													? 'bg-primary-100 text-primary-800'
													: ({{ empty($position_id) ? 'true' : 'false' }} ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-500 italic hover:bg-primary-50')">
												— Tanpa Jabatan —
											</button>
										</li>
										@foreach ($positions as $p)
											<li wire:key="position-opt-{{ $p->id }}">
												<button type="button" data-opt wire:click="selectPosition({{ $p->id }})"
													@click="open = false" @mouseenter="highlighted = {{ $loop->index + 1 }}"
													class="block w-full cursor-pointer px-3 py-2 text-left text-sm transition-colors"
													:class="highlighted === {{ $loop->index + 1 }}
														? 'bg-primary-100 text-primary-800'
														: ({{ $position_id == $p->id ? 'true' : 'false' }} ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-700 hover:bg-primary-50')">
													{{ $p->name }}
												</button>
											</li>
										@endforeach
										@if ($positions->isEmpty() && trim($searchPosition) !== '')
											<li class="px-3 py-2 text-xs text-slate-500">Jabatan tidak ditemukan.</li>
										@endif
										@if ($positionsHasMore)
											<li class="border-t border-slate-100 px-3 py-2 text-[11px] italic text-slate-500">
												Menampilkan 25 hasil teratas — persempit pencarian untuk yang lain.
											</li>
										@endif
									</ul>
								</div>
							</div>
							@error('position_id')
								<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
							@enderror
							<p class="text-xs text-slate-500">Jabatan pimpinan (Walikota, Sekda, Kepala OPD, dll.) hanya boleh
								dipangku satu pegawai aktif sesuai lingkupnya.</p>
							<p class="text-xs text-slate-500">Jabatan tidak ada?
								<a href="{{ route('master.position-requests.index') }}#ajukan-jabatan" target="_blank"
									class="font-semibold text-primary-600 hover:text-primary-700 hover:underline">
									Ajukan jabatan baru <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
								</a>
							</p>
						</div>
					@endunless

					<div class="space-y-1 sm:col-span-2">
						@php
							// Role DPRD hanya tersedia bila pegawai bertipe DPRD.
							$roleOptions = ($this->isDprdContext()
								? $roles->whereIn('name', ['pimpinan_dprd', 'anggota_dprd'])
								: ($this->isDprdMember()
									? $roles
									: $roles->whereNotIn('name', ['pimpinan_dprd', 'anggota_dprd'])))
								->map(fn($r) => ['value' => $r->name, 'label' => $r->label])
								->values()
								->all();
						@endphp
						<x-form.searchable-select wire:model.live="role" name="role" label="Role Otentikasi Sistem"
							required :options="$roleOptions" placeholder="— Pilih Role —" searchPlaceholder="Cari role..." />
						<p class="text-xs text-slate-500">Role kewenangan tunggal (Walikota, Sekda, Kepala/Sekretaris OPD,
							Camat, Lurah, dll.) hanya boleh dipegang satu pegawai aktif sesuai lingkupnya.</p>
					</div>
				</div>
			</section>

			{{-- ── Kelompok 5: Data Anggota DPRD (kondisional) ── --}}
			@if ($this->isDprdMember())
				<section class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
					<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
						<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
							<i class="fa-solid fa-landmark-dome"></i>
						</div>
						<div>
							<h3 class="text-sm font-bold text-slate-800">Data Anggota DPRD</h3>
							<p class="text-xs text-slate-500">Informasi ini digunakan pada dokumen SPT/SPPD Anggota DPRD.</p>
						</div>
					</header>

					<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
						<div class="space-y-1">
							@php
								$jabatanOptions = collect($dprdJabatans)
									->map(fn($jabatan) => ['value' => $jabatan->value, 'label' => $jabatan->label()])
									->all();
							@endphp
							<x-form.searchable-select wire:model.live="dprd_jabatan" name="dprd_jabatan" label="Jabatan DPRD"
								required :options="$jabatanOptions" placeholder="— Pilih Jabatan DPRD —"
								searchPlaceholder="Cari jabatan DPRD..." />
						</div>

						<div class="space-y-1">
							@php
								$partaiOptions = collect($dprdPartais)
									->map(fn($partaiOpt) => ['value' => $partaiOpt->value, 'label' => $partaiOpt->label()])
									->all();
							@endphp
							<x-form.searchable-select wire:model="partai" name="partai" label="Partai / Fraksi"
								:options="$partaiOptions" placeholder="— Pilih Partai / Fraksi —"
								searchPlaceholder="Cari partai..." />
						</div>
					</div>
				</section>
			@endif

			{{-- Form Actions --}}
			<div class="sticky bottom-4 z-10 flex items-center justify-end gap-3 rounded border border-slate-200 bg-white/85 p-3 shadow-lg shadow-slate-900/5 backdrop-blur-sm">
				<x-ui.button href="{{ route('master.users.index', array_filter(['type' => $listType])) }}" variant="secondary">
					Batal
				</x-ui.button>

				<x-ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
					<x-slot name="icon">
						<i class="fa-solid fa-floppy-disk text-xs"></i>
					</x-slot>
					{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pegawai' }}
				</x-ui.button>
			</div>
		</form>

		@include('livewire.partials.phone-verification')

	</div>
</div>
