<div x-data="{ showResetModal: false }">
	<div class="p-1 space-y-6">

		{{-- Header Halaman --}}
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
					<i
						class="fa-solid {{ $isEdit ? 'fa-user-pen' : 'fa-user-plus' }} mr-2 text-cyan-600"></i>{{ $isEdit ? 'Edit' : 'Tambah' }}
					Pegawai
				</h1>
				<p class="mt-1 text-xs text-slate-500 font-medium">
					{{ $isEdit ? 'Ubah informasi profile, instansi, atau kredensial pengguna sistem' : 'Tambahkan pegawai baru ke dalam sistem' }}
				</p>
			</div>
			<x-ui.button href="{{ route('master.users.index', array_filter(['type' => $listType])) }}" variant="secondary"
				class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
				<x-slot name="icon">
					<i class="fa-solid fa-arrow-left text-xs"></i>
				</x-slot>
				Kembali
			</x-ui.button>
		</div>

		{{-- Main Form --}}
		<form wire:submit.prevent="save" class="space-y-6">
			<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
				{{-- Sub-header Card --}}
				<div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
					<h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
						<i class="fa-solid fa-user-gear text-cyan-500"></i>Formulir Data Kepegawaian
					</h3>
				</div>

				{{-- Form Fields --}}
				<div class="p-6">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-6">

						<div class="space-y-1">
							<x-form.input wire:model="name" name="name" label="Nama Lengkap" required
								placeholder="Contoh: Budi Santoso" class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							{{-- Username dengan tombol generate username unik --}}
							<label for="username" class="block text-xs font-bold tracking-wide text-slate-600 uppercase">
								Username <span class="text-rose-500">*</span>
							</label>
							<div class="flex gap-2">
								<input wire:model="username" type="text" id="username" name="username" required
									placeholder="Contoh: budi.santoso"
									class="block w-full flex-1 rounded border px-3 py-2 text-sm text-slate-800 placeholder-slate-400 shadow-2xs transition focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 @error('username') border-red-400 @else border-slate-300 @enderror" />

								<button type="button" wire:click="generateUsername" wire:loading.attr="disabled"
									wire:target="generateUsername" title="Buat username unik otomatis dari Nama Lengkap"
									class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-cyan-500 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-600 hover:text-white disabled:opacity-50">
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
							  placeholder="email@contoh.com" class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input wire:model="password" type="password" name="password"
								label="{{ $isEdit ? 'Password Baru' : 'Password' }}"
								placeholder="{{ $isEdit ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 6 karakter (opsional)' }}"
								class="focus:border-cyan-500 focus:ring-cyan-500" />
							@unless ($isEdit)
								<p class="text-xs text-slate-400">
									<i class="fa-solid fa-circle-info mr-1"></i>
									Jika dikosongkan, password default
									<strong class="font-mono text-slate-600">{{ config('users.default_password') }}</strong>
									akan digunakan.
								</p>
							@endunless
						</div>

						@unless ($this->isDprdContext())
							<div class="space-y-1">
								<x-form.input wire:model="nip" name="nip" label="NIP (Nomor Induk Pegawai)"
									placeholder="18 digit angka" class="font-mono focus:border-cyan-500 focus:ring-cyan-500" />
							</div>
						@endunless

						<div class="space-y-1">
							<x-form.input wire:model="nik" name="nik" label="NIK (Nomor Induk Kependudukan)"
								placeholder="16 digit angka" class="font-mono focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							{{-- Phone Field dengan tombol verifikasi/ganti nomor --}}
							<label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wide">
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
									class="flex-1 block w-full rounded border px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:ring-cyan-500 @error('phone') border-red-400 @else border-slate-300 @enderror @if ($phoneVerified) bg-slate-50 cursor-not-allowed @endif" />

								@if ($phoneVerified)
									<button
										type="button"
										disabled
										class="inline-flex items-center gap-1.5 rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 whitespace-nowrap cursor-default">
										<i class="fa-solid fa-check-circle text-sm"></i> Terverifikasi
									</button>
									<button
										type="button"
										@click="showResetModal = true"
										title="Ganti nomor WhatsApp"
										class="inline-flex items-center gap-1.5 rounded border border-amber-500 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-600 hover:text-white whitespace-nowrap">
										<i class="fa-solid fa-rotate text-sm"></i> Ganti
									</button>
								@else
									<button
										type="button"
										wire:click="openVerifyModal"
										wire:loading.attr="disabled"
										wire:target="openVerifyModal"
										title="Verifikasi nomor WhatsApp ini"
										class="inline-flex items-center gap-1.5 rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-600 hover:text-white whitespace-nowrap disabled:opacity-50">
										<span wire:loading.remove wire:target="openVerifyModal">
											<i class="fa-brands fa-whatsapp text-sm"></i> Verifikasi
										</span>
										<span wire:loading wire:target="openVerifyModal">
											<i class="fa-solid fa-spinner fa-spin text-sm"></i> Memuat...
										</span>
									</button>
								@endif
							</div>

							@if ($phoneVerified)
								<p class="text-xs text-green-600 mt-1 font-medium">
									<i class="fa-solid fa-circle-check mr-1"></i>
									Nomor telah diverifikasi dan terkunci. Gunakan tombol Ganti jika ingin mengubahnya.
								</p>
							@else
								<p class="text-xs text-amber-600 mt-1 font-medium">
									<i class="fa-solid fa-triangle-exclamation mr-1"></i>
									Wajib verifikasi nomor dengan menekan tombol <strong>Verifikasi</strong> sebelum dapat menyimpan perubahan.
								</p>
							@endif

							@error('phone')
								<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
							@enderror
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
								<p class="text-xs text-slate-400">Terkunci sebagai Anggota DPRD pada formulir ini.</p>
							@endif
						</div>

						<div class="sm:col-span-2 my-1">
							<hr class="border-slate-100">
						</div>

						<div class="space-y-1">
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
								<i class="fa-solid fa-circle-plus text-cyan-500 mr-1"></i>Unit kerja belum ada?
								<a href="{{ route('master.departments.create') }}" target="_blank" rel="noopener"
									class="font-semibold text-cyan-600 underline-offset-2 hover:underline">
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
										class="flex w-full items-center justify-between gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-sm shadow-2xs transition focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500">
										<span class="truncate text-left {{ $selectedPositionLabel ? 'text-slate-800' : 'text-slate-400' }}">
											{{ $selectedPositionLabel ?? '— Pilih Jabatan —' }}
										</span>
										<i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
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
										class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5">
										<div class="border-b border-slate-100 p-2">
											<div class="relative">
												<i class="fa-solid fa-magnifying-glass pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-[11px] text-slate-400" style="display:flex;"></i>
												<input x-ref="searchPosition" type="text" wire:model.live.debounce.300ms="searchPosition"
													@input="highlighted = 0"
													@keydown.arrow-down.prevent="move(1)"
													@keydown.arrow-up.prevent="move(-1)"
													@keydown.enter.prevent="pick()"
													@keydown.tab.prevent="move($event.shiftKey ? -1 : 1)"
													placeholder="Cari jabatan..."
													class="w-full rounded border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-2 text-sm outline-none focus:border-cyan-500 focus:bg-white focus:ring-1 focus:ring-cyan-500">
											</div>
										</div>
										<ul x-ref="list" class="max-h-56 overflow-auto py-1">
											<li wire:key="position-opt-none">
												<button type="button" data-opt wire:click="clearPosition"
													@click="open = false" @mouseenter="highlighted = 0"
													class="block w-full cursor-pointer px-3 py-2 text-left text-sm transition-colors"
													:class="highlighted === 0
														? 'bg-cyan-100 text-cyan-800'
														: ({{ empty($position_id) ? 'true' : 'false' }} ? 'bg-cyan-50 font-semibold text-cyan-700' : 'text-slate-500 italic hover:bg-cyan-50')">
													— Tanpa Jabatan —
												</button>
											</li>
											@foreach ($positions as $p)
												<li wire:key="position-opt-{{ $p->id }}">
													<button type="button" data-opt wire:click="selectPosition({{ $p->id }})"
														@click="open = false" @mouseenter="highlighted = {{ $loop->index + 1 }}"
														class="block w-full cursor-pointer px-3 py-2 text-left text-sm transition-colors"
														:class="highlighted === {{ $loop->index + 1 }}
															? 'bg-cyan-100 text-cyan-800'
															: ({{ $position_id == $p->id ? 'true' : 'false' }} ? 'bg-cyan-50 font-semibold text-cyan-700' : 'text-slate-700 hover:bg-cyan-50')">
														{{ $p->name }}
													</button>
												</li>
											@endforeach
											@if ($positions->isEmpty() && trim($searchPosition) !== '')
												<li class="px-3 py-2 text-xs text-slate-400">Jabatan tidak ditemukan.</li>
											@endif
											@if ($positionsHasMore)
												<li class="border-t border-slate-100 px-3 py-2 text-[11px] italic text-slate-400">
													Menampilkan 25 hasil teratas — persempit pencarian untuk yang lain.
												</li>
											@endif
										</ul>
									</div>
								</div>
								@error('position_id')
									<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
								@enderror
								<p class="text-xs text-slate-400">Jabatan pimpinan (Walikota, Sekda, Kepala OPD, dll.) hanya boleh
									dipangku satu pegawai aktif sesuai lingkupnya.</p>
								<p class="text-xs text-slate-400">Jabatan tidak ada?
									<a href="{{ route('master.position-requests.index') }}#ajukan-jabatan" target="_blank"
										class="font-semibold text-cyan-600 hover:text-cyan-700 hover:underline">
										Ajukan jabatan baru <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
									</a>
								</p>
							</div>
						@endunless

						<div class="space-y-1">
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
							<p class="text-xs text-slate-400">Role kewenangan tunggal (Walikota, Sekda, Kepala/Sekretaris OPD,
								Camat, Lurah, dll.) hanya boleh dipegang satu pegawai aktif sesuai lingkupnya.</p>
						</div>

						{{-- Data khusus Anggota DPRD --}}
						@if ($this->isDprdMember())
							<div class="sm:col-span-2 my-1">
								<hr class="border-slate-100">
							</div>

							<div class="sm:col-span-2">
								<h4 class="text-xs font-bold uppercase tracking-wide text-cyan-700 flex items-center gap-2">
									<i class="fa-solid fa-landmark-dome text-cyan-500"></i>Data Anggota DPRD
								</h4>
								<p class="mt-0.5 text-xs text-slate-400">Informasi ini digunakan pada dokumen SPT/SPPD Anggota DPRD.</p>
							</div>

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
						@endif

					</div>
				</div>
			</div>

			{{-- Form Actions --}}
			<div class="flex justify-end gap-3">
				<x-ui.button href="{{ route('master.users.index', array_filter(['type' => $listType])) }}" variant="secondary"
					class="inline-flex items-center rounded border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
					Batal
				</x-ui.button>

				<x-ui.button type="submit"
					class="inline-flex items-center gap-2 rounded bg-cyan-600 px-6 py-2.5 text-sm font-bold text-white shadow-md shadow-cyan-200 transition hover:bg-cyan-700 hover:shadow-lg">
					<x-slot name="icon">
						<i class="fa-solid fa-floppy-disk text-xs"></i>
					</x-slot>
					Simpan Perubahan
				</x-ui.button>
			</div>
		</form>

		@include('livewire.partials.phone-verification')

	</div>
</div>
