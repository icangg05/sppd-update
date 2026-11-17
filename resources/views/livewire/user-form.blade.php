<div x-data="{ showUnlinkModal: false }">
	<div class="mx-auto max-w-5xl space-y-6 p-1">

		{{-- Header Halaman (title card) --}}
		<div
			class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
			{{-- Watermark institusional (tipis, hanya karakter). --}}
			<i class="fa-solid {{ $isEdit ? 'fa-user-pen' : 'fa-user-plus' }} pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
				aria-hidden="true"></i>

			<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
				<div class="min-w-0 leading-tight">
					<span
						class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
						<i class="fa-solid {{ $isEdit ? 'fa-pen-nib' : 'fa-user-plus' }} text-[9px]"></i>
						{{ $isEdit ? 'Perbarui Data' : ($tenagaMitra ? 'Registrasi Tenaga Mitra' : 'Registrasi Pegawai') }}
					</span>
					<h1 class="text-xl font-bold tracking-tight text-slate-800">
						{{ $isEdit ? 'Edit' : 'Tambah' }} {{ $tenagaMitra ? 'Tenaga Mitra' : 'Pegawai' }}
					</h1>
					<p class="mt-1 text-xs text-slate-500">
						@if ($tenagaMitra)
							Orang dari luar OPD sebagai pengikut dalam perjalanan dinas
						@else
							{{ $isEdit ? 'Ubah informasi profil, penempatan, atau kredensial pengguna sistem' : 'Tambahkan pegawai baru ke dalam sistem' }}
						@endif
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

		{{-- Pemilih mode (hanya saat menambah pegawai, bukan di menu DPRD / Walikota). wire:key memuat ulang
		     radio saat mode berganti agar tanda terpilih selalu sama dengan mode di server. --}}
		@if ($showModeSwitch)
			<section wire:key="mode-switch-{{ $tenagaMitra ? 'mitra' : 'pegawai' }}"
				class="dash-enter rounded border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
				<fieldset>
					<legend class="mb-2 block text-xs font-bold tracking-wide text-slate-600 uppercase">Jenis Data</legend>
					<div class="grid gap-2 sm:grid-cols-2">
						<x-form.radio name="pegawai_mode" id="pegawai-mode-pegawai" value="pegawai" :checked="! $tenagaMitra"
							wire:click="setTenagaMitra(false)" label="Pegawai"
							description="Pegawai dengan akun login, NIP, pangkat, jabatan, dan role."
							:error="false" labelClass="h-full w-full" />
						<x-form.radio name="pegawai_mode" id="pegawai-mode-tenaga-mitra" value="tenaga_mitra" :checked="$tenagaMitra"
							wire:click="setTenagaMitra(true)" label="Tenaga Mitra"
							description="Orang dari luar OPD sebagai pengikut dalam perjalanan dinas."
							:error="false" labelClass="h-full w-full" />
					</div>
				</fieldset>
			</section>
		@endif

		{{-- Main Form --}}
		<form wire:submit.prevent="save" class="space-y-6">

			{{-- Hemat scroll: Identitas Pegawai & Akun Sistem berdampingan (tinggi kartu disamakan),
			     Notifikasi Telegram selebar penuh di bawahnya agar tidak ada kolom yang kosong. --}}
			<div class="grid gap-6 lg:grid-cols-2">

			{{-- ── Kelompok 1: Identitas Pegawai (kolom kiri; Tenaga Mitra tanpa Akun Sistem, jadi selebar penuh) ── --}}
			<section class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm {{ $tenagaMitra ? 'lg:col-span-2' : '' }}">
				<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
						<i class="fa-regular fa-id-card"></i>
					</div>
					<div>
						<h3 class="text-sm font-bold text-slate-800">{{ $tenagaMitra ? 'Identitas' : 'Identitas Pegawai' }}</h3>
						<p class="text-xs text-slate-500">Data diri dan nomor identitas.</p>
					</div>
				</header>

				<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6 {{ $tenagaMitra ? 'lg:grid-cols-3' : 'lg:grid-cols-1' }}">
					<div class="space-y-1 {{ $tenagaMitra ? 'sm:col-span-2 lg:col-span-1' : '' }}">
						<x-form.input wire:model="name" name="name" label="Nama Lengkap" required
							placeholder="Contoh: Budi Santoso" />
					</div>

					{{-- Tenaga Mitra hanya diisi nama, NIP, NIK, dan jabatan (tanpa akun login). --}}
					@unless ($this->isDprdContext())
						<div class="space-y-1">
							<x-form.input wire:model="nip" name="nip" label="NIP (Nomor Induk Pegawai)"
								placeholder="18 digit angka" class="font-mono" x-digits inputmode="numeric" maxlength="18"
								autocomplete="off" />
						</div>
					@endunless

					<div class="space-y-1">
						<x-form.input wire:model="nik" name="nik" label="NIK (Nomor Induk Kependudukan)"
							placeholder="16 digit angka" class="font-mono" x-digits inputmode="numeric" maxlength="16"
							autocomplete="off" />
					</div>

					@unless ($tenagaMitra)
						<div class="space-y-1">
							<x-form.input wire:model="email" type="email" name="email" label="Email Resmi"
								placeholder="email@contoh.com" />
						</div>
					@endunless
				</div>
			</section>

			{{-- ── Kelompok 2: Akun Sistem (Tenaga Mitra tidak punya akun login) ── --}}
			@unless ($tenagaMitra)
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

				<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-1">
					<div class="space-y-1">
						{{-- Username dengan tombol generate username unik --}}
						<label for="username" class="block text-xs font-bold tracking-wide text-slate-600 uppercase">
							Username <span class="text-rose-500">*</span>
						</label>
						<div class="flex gap-2">
							<input wire:model="username" type="text" id="username" name="username" required
								x-username autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false"
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
						@else
							<p class="mt-1 text-xs text-slate-500">Huruf kecil, angka, dan titik saja (tanpa spasi atau simbol lain).</p>
						@enderror
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
			@endunless

			{{-- ── Kelompok 3: Notifikasi Telegram (lebar penuh) ── --}}
			@unless ($tenagaMitra)
			<section class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm lg:col-span-2">
				<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-sky-50 text-sky-500 ring-1 ring-sky-100">
						<i class="fa-brands fa-telegram"></i>
					</div>
					<div>
						<h3 class="text-sm font-bold text-slate-800">Notifikasi Telegram</h3>
						<p class="text-xs text-slate-500">Notifikasi pengajuan & persetujuan SPPD dikirim lewat Telegram.</p>
					</div>
				</header>

				<div class="p-5 sm:p-6">
					@include('livewire.partials.telegram-status', ['testable' => true])
				</div>
			</section>
			@endunless
			</div>

			{{-- ── Kelompok 4: Penempatan & Kewenangan (lebar penuh) ── --}}
			{{-- Anggota DPRD: unit kerja otomatis Sekretariat DPRD & role dari Jabatan DPRD, jadi bagian ini
			     disembunyikan (tetap tampil bila unit kerja belum bisa diisi otomatis). --}}
			@unless ($this->isDprdContext() && $department_id)
			{{-- Catatan: TANPA .dash-enter. Animasi (animation-fill: both) membuat stacking
			     context permanen yang menjebak panel Jabatan (position: fixed) sehingga
			     tertutup action bar sticky meski z-index 9999. Lihat [[blade-livewire-pitfalls]]. --}}
			<section class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
				<header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
						<i class="fa-solid fa-sitemap"></i>
					</div>
					<div>
						@if ($tenagaMitra)
							<h3 class="text-sm font-bold text-slate-800">Jabatan</h3>
							<p class="text-xs text-slate-500">{{ $showDepartmentPicker ? 'Unit kerja dan jabatan yang dicetak di dokumen SPT.' : 'Jabatan yang dicetak di dokumen SPT.' }}</p>
						@else
							<h3 class="text-sm font-bold text-slate-800">Penempatan & Kewenangan</h3>
							<p class="text-xs text-slate-500">{{ $showDepartmentPicker ? 'Unit kerja, pangkat, jabatan, dan hak akses.' : 'Pangkat, jabatan, dan hak akses.' }}</p>
						@endif
					</div>
				</header>

				<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
					{{-- Unit kerja hanya satu pilihan (admin OPD tanpa sub-unit) sudah terisi otomatis. --}}
					@if ($showDepartmentPicker)
						<div class="space-y-1 sm:col-span-2">
							<x-form.tree-select wire:model.live="department_id" name="department_id"
								label="Instansi / Unit Kerja (OPD)" required :nodes="$departmentTree"
								searchPlaceholder="Cari instansi / unit kerja ..." emptyText="Belum ada unit kerja dipilih."
								hint="Klik unit kerja tempat pegawai benar-benar ditempatkan. Tekan ▸ untuk membuka sub-unit di bawahnya; pilih unit yang paling spesifik." />
							{{-- Unit kerja baru hanya ditambahkan Super Admin. --}}
							@if (auth()->user()->hasRole('super_admin'))
								<p class="text-xs text-slate-500 mt-1">
									<i class="fa-solid fa-circle-plus text-primary-500 mr-1"></i>Unit kerja belum ada?
									<a href="{{ route('master.departments.create') }}" target="_blank" rel="noopener"
										class="font-semibold text-primary-600 underline-offset-2 hover:underline">
										Tambah unit kerja baru <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
									</a>
								</p>
							@endif
						</div>
					@endif

					@unless ($this->isDprdContext())
						@unless ($tenagaMitra)
						<div class="space-y-1">
							@php
								$rankOptions = collect($ranks)
									->map(fn($r) => ['value' => $r->id, 'label' => $r->name . ' (' . $r->group . ')'])
									->prepend(['value' => '', 'label' => '— Tidak dipilih —'])
									->all();
							@endphp
							<x-form.searchable-select wire:model="rank_id" name="rank_id" label="Golongan / Pangkat"
								:options="$rankOptions" placeholder="— Pilih Pangkat —" searchPlaceholder="Cari pangkat..." mutedEmpty />
						</div>
						@endunless

						@if ($tenagaMitra)
							{{-- Tenaga Mitra: nama jabatan diketik. Jabatan terdaftar yang cocok ditawarkan sebagai saran;
							     nama yang belum terdaftar menjadi jabatan baru saat disimpan. Panel saran memakai
							     position: fixed karena kartu ini overflow-hidden (lihat dropdown jabatan pegawai).
							     Keyboard: fokus tetap di kolom ketik. Tab masuk ke saran pertama, panah atas/bawah
							     berpindah antarsaran, Enter memakai saran yang disorot. Tab saat sudah berada di
							     saran (atau saat saran tidak tampil) berpindah ke kolom berikutnya seperti biasa. --}}
							<div class="space-y-1 sm:col-span-2"
								x-data="{
									open: false,
									highlighted: -1,
									// true setelah saran dijelajahi dengan keyboard. Sorotan dari hover mouse tidak
									// dihitung, jadi Tab tetap masuk ke saran pertama walau kursor kebetulan di atas daftar.
									navigating: false,
									// Panel dibuka ke atas bila ruang di bawah kolom tidak cukup dan ruang di atas lebih lega.
									// Saat ke atas, panel ditambatkan lewat `bottom` agar tetap menempel ke kolom walau jumlah
									// saran berubah. Tinggi daftar dibatasi ruang yang tersedia supaya panel tidak menempel
									// atau terpotong tepi layar.
									dropUp: false,
									coords: { top: 0, bottom: 0, left: 0, width: 0 },
									listMax: 224,
									position() {
										const r = this.$refs.input.getBoundingClientRect();
										const gap = 4, edge = 16, maxList = 224, minList = 120;
										// Pasang lebar panel lebih dulu agar tinggi judul dan saran diukur pada lebar sebenarnya
										// (saat pertama dibuka lebarnya masih 0 sehingga teksnya terlipat dan tampak sangat tinggi).
										if (this.$refs.panel) this.$refs.panel.style.width = `${r.width}px`;
										const headH = this.$refs.head?.offsetHeight || 32;
										const contentH = Math.min(maxList, this.$refs.list?.scrollHeight || maxList);
										const needed = headH + contentH + 2;
										const below = window.innerHeight - r.bottom - gap - edge;
										// Saat membuka ke atas, panel berhenti di bawah bar judul yang menempel di atas layar.
										const topEdge = Math.max(edge, (document.querySelector('header.sticky')?.getBoundingClientRect().bottom ?? 0) + 8);
										const above = r.top - gap - topEdge;
										this.dropUp = needed > below && above > below;
										const room = this.dropUp ? above : below;
										this.listMax = Math.max(minList, Math.min(maxList, room - headH - 2));
										this.coords = {
											top: r.bottom + gap,
											bottom: window.innerHeight - r.top + gap,
											left: r.left,
											width: r.width,
										};
									},
									init() {
										// Livewire mengganti isi saran setelah tiap ketikan; hitung ulang posisi agar
										// arah dan tinggi panel mengikuti jumlah saran yang baru.
										let frame = null;
										this._observer = new MutationObserver(() => {
											if (!this.open || frame) return;
											frame = requestAnimationFrame(() => { frame = null; this.position(); });
										});
										this._observer.observe(this.$el, { childList: true, subtree: true });
									},
									destroy() {
										this._observer?.disconnect();
									},
									show() {
										this.open = true;
										this.$nextTick(() => this.position());
									},
									close() {
										this.open = false;
										this.highlighted = -1;
										this.navigating = false;
									},
									items() {
										return this.$refs.list ? Array.from(this.$refs.list.querySelectorAll('[data-opt]')) : [];
									},
									highlight(n) {
										this.highlighted = n;
										this.navigating = true;
										this.$nextTick(() => this.items()[n]?.scrollIntoView({ block: 'nearest' }));
									},
									move(dir) {
										const len = this.items().length;
										if (!len) return;
										this.show();
										let n = this.highlighted + dir;
										if (n < 0) n = len - 1;
										if (n >= len) n = 0;
										this.highlight(n);
									},
									tab(e) {
										if (!e.shiftKey && this.open && !this.navigating && this.items().length) {
											e.preventDefault();
											this.highlight(0);
											return;
										}
										this.close();
									},
									pick(e) {
										const el = this.items()[this.highlighted];
										if (this.open && el) {
											e.preventDefault();
											el.click();
										}
									},
								}"
								@click.outside="close()"
								@keydown.escape="close()"
								@scroll.window.capture="if (open) position()"
								@resize.window="if (open) position()">
								<x-form.input wire:model.live.debounce.300ms="jabatanTenagaMitra" label="Jabatan" required
									placeholder="Ketik nama jabatan, misalnya Dosen Universitas Halu Oleo" autocomplete="off" loadingTarget="jabatanTenagaMitra"
									x-ref="input" x-on:focus="show()" x-on:input="show(); highlighted = -1; navigating = false"
									x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
									x-on:keydown.enter="pick($event)" x-on:keydown.tab="tab($event)" />

								@if ($jabatanSuggestions->isNotEmpty())
									<div x-show="open" x-cloak x-ref="panel"
										:style="`position: fixed; margin: 0; ${dropUp ? `bottom: ${coords.bottom}px` : `top: ${coords.top}px`}; left: ${coords.left}px; width: ${coords.width}px; z-index: 9999;`"
										class="overflow-hidden rounded border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5">
										<div x-ref="head" class="flex items-baseline justify-between gap-3 border-b border-slate-100 px-3 py-1.5 text-[11px] text-slate-500">
											<p class="font-bold tracking-wide uppercase">Jabatan yang sudah terdaftar</p>
											<p class="hidden sm:block">Tab / ↑ ↓ untuk memilih, Enter untuk memakai</p>
										</div>
										<ul x-ref="list" class="max-h-56 overflow-auto py-1" :style="`max-height: ${listMax}px`">
											@foreach ($jabatanSuggestions as $p)
												<li wire:key="jabatan-saran-{{ $p->id }}">
													@if ($p->unavailableReason)
														<div aria-disabled="true" class="flex cursor-not-allowed flex-wrap items-baseline justify-between gap-x-3 px-3 py-2 text-sm text-slate-400">
															<span>{{ $p->name }}</span>
															<span class="text-[11px] italic">{{ \Illuminate\Support\Str::ucfirst($p->unavailableReason) }}</span>
														</div>
													@else
														<button type="button" data-opt wire:click="pickJabatanTenagaMitra({{ $p->id }})"
															@click="close()" @mouseenter="highlighted = {{ $loop->index }}" tabindex="-1"
															class="block w-full cursor-pointer px-3 py-2 text-left text-sm transition-colors"
															:class="highlighted === {{ $loop->index }}
																? 'bg-primary-100 text-primary-800'
																: '{{ $position_id == $p->id ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-700 hover:bg-primary-50' }}'">
															{{ $p->name }}
														</button>
													@endif
												</li>
											@endforeach
											@if ($jabatanSuggestionsHasMore)
												<li class="border-t border-slate-100 px-3 py-2 text-[11px] italic text-slate-500">
													Menampilkan 10 hasil teratas. Ketik lebih lengkap untuk mempersempit.
												</li>
											@endif
										</ul>
									</div>
								@endif

								@if ($jabatanStatus === 'existing')
									<p class="text-xs font-medium text-emerald-700">
										<i class="fa-solid fa-circle-check mr-0.5"></i> Memakai jabatan yang sudah terdaftar.
									</p>
								@elseif ($jabatanStatus === 'new')
									<p class="text-xs text-slate-600">
										<i class="fa-solid fa-circle-plus mr-0.5 text-primary-600"></i>
										Belum terdaftar. <span class="font-semibold text-slate-800">{{ $jabatanBaru }}</span> akan ditambahkan sebagai jabatan baru saat disimpan.
									</p>
								@elseif ($jabatanStatus === 'struktural' && ! $errors->has('jabatanTenagaMitra'))
									<p class="text-xs font-medium text-amber-700">
										<i class="fa-solid fa-triangle-exclamation mr-0.5"></i>
										Nama ini sudah terdaftar sebagai jabatan struktural dan tidak bisa dipakai Tenaga Mitra. Gunakan nama jabatan lain.
									</p>
								@else
									<p class="text-xs text-slate-500">Pilih dari saran bila jabatannya sudah ada, atau ketik nama baru.</p>
								@endif
							</div>
						@else
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
												@if ($p->unavailableReason)
													<div aria-disabled="true" class="flex cursor-not-allowed flex-wrap items-baseline justify-between gap-x-3 px-3 py-2 text-sm text-slate-400">
														<span>{{ $p->name }}</span>
														<span class="text-[11px] italic">{{ \Illuminate\Support\Str::ucfirst($p->unavailableReason) }}</span>
													</div>
												@else
													<button type="button" data-opt wire:click="selectPosition({{ $p->id }})"
														@click="open = false" @mouseenter="highlighted = {{ $loop->index + 1 }}"
														class="block w-full cursor-pointer px-3 py-2 text-left text-sm transition-colors"
														:class="highlighted === {{ $loop->index + 1 }}
															? 'bg-primary-100 text-primary-800'
															: ({{ $position_id == $p->id ? 'true' : 'false' }} ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-700 hover:bg-primary-50')">
														{{ $p->name }}
													</button>
												@endif
											</li>
										@endforeach
										@if ($positions->isEmpty() && trim($searchPosition) !== '')
											<li class="space-y-2 px-3 py-2 text-xs text-slate-500">
												<p>Jabatan tidak ditemukan.</p>
												<x-ui.button size="sm" variant="secondary" class="w-full"
													x-on:click="open = false; $dispatch('open-position-form', { name: $wire.searchPosition })">
													<x-slot:icon><i class="fa-solid fa-plus text-[10px]"></i></x-slot:icon>
													Tambah "{{ trim($searchPosition) }}" sebagai jabatan baru
												</x-ui.button>
											</li>
										@endif
										@if ($positionsHasMore)
											<li class="border-t border-slate-100 px-3 py-2 text-[11px] italic text-slate-500">
												Menampilkan 25 hasil teratas. Persempit pencarian untuk melihat yang lain.
											</li>
										@endif
									</ul>
								</div>
							</div>
							@error('position_id')
								<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
							@enderror
							@if ($positionRequiredType)
								<p class="text-xs font-medium text-amber-700">
									<i class="fa-solid fa-triangle-exclamation mr-0.5"></i>
									Jabatan {{ $selectedPosition->name }} seharusnya berada di unit {{ $positionRequiredType->label() }}.
									Periksa kembali unit kerja pegawai. Role pimpinan tidak diberikan selama unitnya belum sesuai.
								</p>
							@endif
							<p class="text-xs text-slate-500">Jabatan tidak ada?
								<button type="button" x-on:click="$dispatch('open-position-form', { name: $wire.searchPosition })"
									class="font-semibold text-primary-600 hover:text-primary-700 hover:underline">
									<i class="fa-solid fa-plus text-[9px]"></i> Tambah jabatan baru
								</button>
							</p>
						</div>
						@endif
					@endunless

					{{-- Anggota DPRD: role tidak dipilih, otomatis dari Jabatan DPRD (lihat bagian Data Anggota DPRD).
					     Tenaga Mitra: role selalu Tenaga Mitra. --}}
					@unless ($this->isDprdContext() || $tenagaMitra)
						<div class="space-y-1 sm:col-span-2">
							{{-- Pilihan disimpan di Alpine: wire:key memuat ulang select hanya bila daftar role berubah. --}}
							<div wire:key="role-select-{{ md5(implode(',', array_column($roleOptions, 'value'))) }}">
								<x-form.searchable-select wire:model.live="role" name="role" label="Role Otentikasi Sistem"
									required :options="$roleOptions" placeholder="— Pilih Role —" searchPlaceholder="Cari role..."
									:disabled="! $this->canEditRole()" />
							</div>
							@if ($positionRole && $role === $positionRole)
								<p class="text-xs font-medium text-emerald-700">
									<i class="fa-solid fa-circle-check mr-0.5"></i>
									Role mengikuti jabatan <span class="font-semibold">{{ $selectedPosition->name }}</span>{{ $positionRoleAdjusted ? ', disesuaikan dengan alur SPPD di unit kerja ini' : '' }}.
								</p>
							@endif
							<p class="text-xs text-slate-500">
								@if ($this->canEditRole())
									Role terisi otomatis dari jabatan dan tetap bisa diubah.
								@else
									<i class="fa-solid fa-lock mr-0.5 text-[10px]"></i>
									Role mengikuti jabatan secara otomatis dan hanya dapat diubah oleh Super Admin.
								@endif
								Role kewenangan tunggal (Walikota, Sekda, {{ $kepalaLabel === 'Kepala OPD' ? 'Kepala/Sekretaris OPD' : $kepalaLabel . '/' . ($selectedDepartment?->roleLabel('sekretaris_opd', 'Sekretaris OPD') ?? 'Sekretaris OPD') }}, Camat, Lurah, dll.) hanya boleh dipegang
								satu pegawai aktif sesuai lingkupnya.
							</p>
						</div>
					@endunless
				</div>
			</section>
			@endunless

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
									->map(fn($jabatan) => ['value' => $jabatan->value, 'label' => $jabatan->label(true)])
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
					{{ $isEdit ? 'Simpan Perubahan' : ($tenagaMitra ? 'Simpan Tenaga Mitra' : 'Simpan Pegawai') }}
				</x-ui.button>
			</div>
		</form>

		@include('livewire.partials.telegram-link')
		
		{{-- Modal tambah jabatan — di luar <form> pegawai agar tidak menjadi form bersarang. --}}
		@unless ($this->isDprdContext())
			<livewire:position-form-modal />
		@endunless

	</div>
</div>
