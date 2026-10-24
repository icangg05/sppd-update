<div class="flex flex-col gap-4 p-1">

	{{-- Header Halaman --}}
	<div
		class="dash-enter relative overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
		{{-- Watermark institusional (tipis, hanya karakter). --}}
		<i class="fa-solid fa-file-pen pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
			aria-hidden="true"></i>

		<div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
			<div class="min-w-0 leading-tight">
				<span
					class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
					<i class="fa-solid fa-list-ol text-[9px]"></i> Langkah 1 dari 2
				</span>
				<h1 class="text-xl font-bold tracking-tight text-slate-800">Buat SPPD Baru</h1>
				<p class="mt-1 text-xs text-slate-500">Pilih pelaksana & validasi alur pengajuan.</p>
			</div>
			<x-ui.button variant="secondary" href="{{ route('sppd.index') }}">
				<x-slot name="icon"><i class="fa-solid fa-arrow-left"></i></x-slot>
				Kembali
			</x-ui.button>
		</div>
	</div>

	{{-- Formulir Utama Tahap 1 --}}
	<form wire:submit.prevent="submit" id="form-step-1">
		<div class="rounded border border-slate-200 bg-white shadow-sm overflow-hidden">

			{{-- Sub Header Card --}}
			<div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/75 px-5 py-3.5">
				<h3 class="text-xs font-bold tracking-wider text-slate-600 uppercase flex items-center gap-2">
					<span class="flex size-6 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600"><i class="fa-solid fa-paste text-[11px]"></i></span>
					Tahap 1: Pelaksana & Estimasi Alur
				</h3>
				<span
					class="flex size-6 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white shadow-2xs">1</span>
			</div>

			{{-- Input Grid Pemilihan Pelaksana & Domain --}}
			@php
				$domainOptions = collect(\App\Enums\SppdDomain::cases())
					->map(fn($d) => ['value' => $d->value, 'label' => $d->label()])
					->all();
				$selectedPrefix = $selectedUser
					? ($selectedUser->isDprdMember() ? 'Anggota DPRD' : $selectedUser->nip)
					: null;
				$selectedUserLabel = $selectedUser
					? trim(($selectedPrefix ? $selectedPrefix . ' - ' : '') . $selectedUser->name)
					: null;
			@endphp
			<div class="p-5 grid grid-cols-1 gap-5 md:grid-cols-2">
				{{-- Pelaksana: dropdown dengan pencarian server-side (live) agar tidak memuat seluruh pegawai --}}
				<div>
					<label class="mb-1.5 block text-xs font-bold tracking-wide text-slate-600 uppercase">
						Pelaksana Perjalanan Dinas <span class="text-rose-500">*</span>
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
							toggle() {
								this.open = !this.open;
								if (this.open) {
									this.highlighted = 0;
									this.$nextTick(() => { this.position(); this.$refs.searchUser?.focus(); });
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
						{{-- Proxy validasi native (dropdown kustom tidak punya input); backend tetap memvalidasi user_id. --}}
						<input type="text" tabindex="-1" aria-hidden="true" inputmode="none" autocomplete="off" required
							value="{{ $user_id }}"
							class="pointer-events-none absolute inset-x-0 bottom-0 h-px w-full opacity-0">
						<button type="button" x-ref="trigger" @click="toggle()"
							class="flex w-full items-center justify-between gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-sm shadow-2xs transition focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500">
							<span class="truncate text-left {{ $selectedUserLabel ? 'text-slate-800' : 'text-slate-500' }}">
								{{ $selectedUserLabel ?? '— Pilih Pegawai yang Berangkat —' }}
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
									<input x-ref="searchUser" type="text" wire:model.live.debounce.300ms="searchUser"
										@input="highlighted = 0"
										@keydown.arrow-down.prevent="move(1)"
										@keydown.arrow-up.prevent="move(-1)"
										@keydown.enter.prevent="pick()"
										@keydown.tab.prevent="move($event.shiftKey ? -1 : 1)"
										placeholder="Cari nama / NIP pegawai..."
										class="w-full rounded border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-2 text-sm outline-none focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500">
								</div>
							</div>
							<ul x-ref="list" class="max-h-56 overflow-auto py-1">
								@forelse ($users as $u)
									<li wire:key="user-opt-{{ $u->id }}">
										<button type="button" data-opt wire:click="selectUser({{ $u->id }})"
											@click="open = false" @mouseenter="highlighted = {{ $loop->index }}"
											class="block w-full cursor-pointer px-3 py-2 text-left text-sm transition-colors"
											:class="highlighted === {{ $loop->index }}
												? 'bg-primary-100 text-primary-800'
												: ({{ $user_id == $u->id ? 'true' : 'false' }} ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-700 hover:bg-primary-50')">
											@php $prefix = $u->isDprdMember() ? 'Anggota DPRD' : $u->nip; @endphp
											{{ trim(($prefix ? $prefix . ' - ' : '') . $u->name) }}
											@if ($origin = $userOrigins[$u->id] ?? null)
												<span class="block truncate text-[11px] font-normal text-slate-500">
													<span class="font-semibold text-slate-600">{{ $origin['instansi'] }}</span>@if ($origin['unit']) › {{ $origin['unit'] }}@endif
												</span>
											@endif
										</button>
									</li>
								@empty
									<li class="px-3 py-2 text-xs text-slate-500">
										{{ trim($searchUser) !== '' ? 'Pegawai tidak ditemukan.' : 'Belum ada pegawai.' }}
									</li>
								@endforelse
								@if ($usersHasMore)
									<li class="border-t border-slate-100 px-3 py-2 text-[11px] italic text-slate-500">
										Menampilkan 25 hasil teratas. Persempit pencarian untuk melihat yang lain.
									</li>
								@endif
							</ul>
						</div>
					</div>
					@error('user_id')
						<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
					@enderror
					@if ($pelaksanaOrigin)
						<p class="mt-1.5 flex items-start gap-1.5 text-xs text-slate-500">
							<i class="fa-solid fa-building mt-0.5 text-[10px] text-slate-400"></i>
							<span>
								Instansi: <strong class="font-semibold text-slate-700">{{ $pelaksanaOrigin['instansi'] }}</strong>
								@if ($pelaksanaOrigin['unit'])
									› {{ $pelaksanaOrigin['unit'] }}
								@endif
							</span>
						</p>
					@endif
				</div>

				<x-form.searchable-select wire:model.live="domain" name="domain" label="Domain Perjalanan" required
					:options="$domainOptions" placeholder="— Pilih Domain —" searchPlaceholder="Cari domain..." />
			</div>

			{{-- Container Pratinjau Alur Alur Dokumen --}}
			@if ($user_id)
				<div id="workflow-preview" class="border-t border-slate-100 bg-slate-50/50 p-5 animate-fadeIn">
					<div class="mb-4 flex items-center justify-between gap-3">
						<h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
							<i class="fa-solid fa-diagram-project text-slate-500"></i>
							Pratinjau Alur Persetujuan Dokumen
						</h4>
						<div>
							@if ($isComplete)
								<span class="inline-flex items-center gap-1 rounded bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-emerald-700">
									<i class="fa-solid fa-circle-check text-xs"></i> Lengkap
								</span>
							@elseif ($errorMessage !== '')
								<span class="inline-block rounded bg-red-50 border border-red-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-red-700">
									Tidak Lengkap
								</span>
							@else
								<span class="inline-block rounded bg-slate-50 border border-slate-200 px-2 py-0.5 text-xs font-bold tracking-wide uppercase text-slate-700 animate-pulse">
									Memeriksa...
								</span>
							@endif
						</div>
					</div>

					{{-- Alur Langkah — timeline horizontal ringkas (vertikal di mobile) --}}
					<div id="workflow-steps" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-0">
						@forelse ($steps as $index => $step)
							@php
								$isFound = $step['status'] === 'found';
								// Beberapa kandidat pejabat: dipilih di sini, lalu disimpan sebagai atasan.
								$isChoose = $step['status'] === 'choose';
								$nodeClass = $isFound ? 'bg-emerald-600 ring-emerald-100' : ($isChoose ? 'bg-amber-500 ring-amber-100' : 'bg-red-600 ring-red-100');
								$roleClass = $isFound ? 'text-emerald-700' : ($isChoose ? 'text-amber-700' : 'text-red-600');
								$nameClass = $isFound ? 'text-slate-800' : ($isChoose ? 'text-amber-700 italic' : 'text-red-700 italic');
								$approverName = $isChoose
									? 'Belum dipilih (' . count($step['candidates'] ?? []) . ' kandidat)'
									: $step['approver_name'];
								// Ikon edit: Kabid/Asisten dipilih ulang (chooseStep), role tunggal diganti pejabatnya.
								$editAction = $isFound ? ($editActions[$step['role']] ?? null) : null;
							@endphp

							<div class="flow-node flex min-w-0 flex-1 items-center gap-2.5 rounded-lg bg-white/70 px-2.5 py-2 ring-1 ring-slate-200/70 sm:bg-transparent sm:px-0 sm:py-0 sm:ring-0"
								style="animation-delay: {{ $index * 90 }}ms">
								<span class="relative flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white shadow-sm ring-4 {{ $nodeClass }}">
									{{ $index + 1 }}
									@if ($isFound)
										<i class="fa-solid fa-check absolute -bottom-0.5 -right-0.5 flex size-3.5 items-center justify-center rounded-full bg-white text-[7px] text-emerald-600 ring-1 ring-emerald-200"></i>
									@endif
								</span>
								<span class="min-w-0 leading-tight">
									{{-- Teks panjang turun ke baris kedua (maks. 2 baris), bukan terpotong. --}}
									{{-- Baris jabatan + ikon ganti rata bawah agar label tetap dekat ke nama; sedikit jarak supaya tombol tidak menempel. --}}
									<span class="flex items-end gap-2 {{ $editAction ? 'mb-0.5' : '' }}">
										<span class="line-clamp-2 text-[11px] font-bold uppercase tracking-wide {{ $roleClass }}">{{ $step['role_label'] }}</span>
										{{-- Pejabat terisi tapi keliru/berganti: pilih ulang atau ganti lewat wizard Lengkapi. --}}
										@if ($editAction)
											@php $editTitle = ($editAction === 'chooseStep' ? 'Pilih ' : 'Ganti ') . $step['role_label']; @endphp
											<button type="button" wire:click="{{ $editAction }}('{{ $step['role'] }}')"
												title="{{ $editTitle }}" aria-label="{{ $editTitle }}"
												{{-- Gaya ikon edit yang sama dengan tabel Data Jabatan. --}}
												class="inline-flex size-6 shrink-0 items-center justify-center rounded border border-amber-300 bg-amber-50 text-amber-700 shadow-xs transition-colors hover:border-amber-400 hover:bg-amber-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-amber-500">
												<i class="fa-solid fa-pen-to-square text-[11px]"></i>
											</button>
										@endif
									</span>
									<span class="line-clamp-2 text-sm font-semibold {{ $nameClass }}" title="{{ $approverName }}">{{ $approverName }}</span>
									{{-- Beberapa kandidat: dipilih per tahap, berurutan dari tahap pertama. --}}
									@if ($isChoose)
										@if ($canManagePelaksana && $step['subordinate_id'])
											<x-ui.button variant="warning" size="xs" wire:click="chooseStep('{{ $step['role'] }}')" class="mt-1">
												<x-slot:icon><i class="fa-solid fa-hand-pointer text-[9px]"></i></x-slot:icon>
												Pilih
											</x-ui.button>
										@else
											<span class="mt-1 block text-[11px] text-slate-500">
												{{ $canManagePelaksana ? 'Pilih tahap sebelumnya dulu' : 'Diatur Admin instansi pelaksana / Super Admin' }}
											</span>
										@endif
									@endif
									{{-- Pejabat kosong dilengkapi per tahap; Sekda/Wali Kota hanya oleh Admin Setda / Super Admin. --}}
									@if ($step['status'] === 'not_found')
										@if ($completable[$step['role']] ?? false)
											<x-ui.button variant="warning" size="xs" wire:click="openOfficial('{{ $step['role'] }}')" class="mt-1">
												<x-slot:icon><i class="fa-solid fa-user-plus text-[9px]"></i></x-slot:icon>
												Lengkapi
											</x-ui.button>
										@else
											<span class="mt-1 block text-[11px] text-slate-500">
												Diatur {{ in_array($step['role'], \App\Services\SppdWorkflowService::CITY_WIDE_ROLES, true) ? 'Admin Sekretariat Daerah' : 'Admin instansi pelaksana' }} / Super Admin
											</span>
										@endif
									@endif
								</span>
							</div>

							@if (! $loop->last)
								{{-- Konektor alur: vertikal di mobile, horizontal + chevron di desktop --}}
								<div class="flex shrink-0 items-center sm:px-1.5" aria-hidden="true">
									<span class="ml-3.5 h-4 w-0.5 rounded bg-slate-200 sm:hidden"></span>
									<span class="hidden h-0.5 w-6 rounded bg-slate-200 sm:block"></span>
									<i class="fa-solid fa-chevron-right hidden text-[10px] text-slate-300 sm:-ml-1 sm:block"></i>
								</div>
							@endif
						@empty
							@if ($errorMessage === '')
								<div class="w-full py-6 text-center text-sm font-medium text-slate-500 italic">
									<i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary-600"></i>Memvalidasi alur instansi...
								</div>
							@else
								{{-- Belum ada langkah yang bisa ditampilkan: pegawai sedang bertugas (sementara → amber)
								     atau alur belum dibuat (memblokir → merah). --}}
								@php $isBusy = $errorMessageName !== ''; @endphp
								<x-ui.alert class="w-full" :variant="$isBusy ? 'warning' : 'danger'"
									:icon="$isBusy ? 'fa-user-clock' : 'fa-link-slash'"
									:title="$isBusy ? 'Belum dapat membuat SPPD' : 'Alur persetujuan belum tersedia'">
									@if ($isBusy)
										Pegawai <strong class="font-semibold">{{ $errorMessageName }}</strong> {{ $errorMessage }}
									@elseif ($errorMessageRole !== '')
										{{ $errorMessage }} <span class="whitespace-nowrap">(Role: <strong class="font-semibold">{{ $errorMessageRole }}</strong>)</span>
									@else
										{!! $errorMessage !!}
									@endif
								</x-ui.alert>
							@endif
						@endforelse
					</div>

					{{-- Pesan Kesalahan / Validasi Alur Instansi — hanya bila ada langkah yang
					     tampil (kasus tanpa langkah pesannya sudah menyatu di kartu empty-state). --}}
					@if ($errorMessage !== '' && count($steps) > 0)
						@php
							$alertTitle = match (true) {
								$errorMessageName !== '' => 'Pegawai sedang bertugas',
								collect($steps)->contains('status', 'not_found') => 'Pejabat alur belum lengkap',
								collect($steps)->contains('status', 'choose') => 'Pejabat alur belum dipilih',
								default => 'Data dokumen belum lengkap',
							};
						@endphp
						<x-ui.alert id="workflow-error-msg" class="mt-4.5" :variant="$errorMessageName !== '' ? 'warning' : 'danger'" :title="$alertTitle">
							@if ($errorMessageName !== '')
								Pegawai <strong class="font-semibold">{{ $errorMessageName }}</strong> {{ $errorMessage }}
							@elseif ($errorMessageRole !== '')
								{{ $errorMessage }} <span class="whitespace-nowrap">(Role Pelaksana: <strong class="font-semibold">{{ $errorMessageRole }}</strong>)</span>
							@else
								{!! $errorMessage !!}
							@endif
						</x-ui.alert>
					@endif

					{{-- Tombol Lanjutkan --}}
					<div class="mt-6 pt-4 border-t border-slate-200 flex justify-center">
						<x-ui.button variant="primary" type="submit" :disabled="!$isComplete" class="px-8">
							Lanjut Isi Detail SPPD
							<i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
						</x-ui.button>
					</div>
				</div>
			@endif

		</div>
	</form>

	{{-- Wizard Lengkapi / Ganti Pejabat — dibuka per tahap lewat tombol Lengkapi (tahap kosong)
	     atau ikon edit (tahap terisi) di node alur:
	     1 pilih pegawai → 2 pilih/tambah jabatan (bila jabatannya belum sesuai) → 3 konfirmasi.
	     Jabatan & role tersimpan permanen, jadi berlaku juga untuk pengajuan berikutnya. --}}
	@php $outgoing = $official['outgoing'] ?? null; @endphp
	<x-ui.modal show="$wire.showOfficialModal" :title="($outgoing ? 'Ganti ' : 'Lengkapi ') . ($official['label'] ?? 'Pejabat')"
		:description="$official ? 'Langkah ' . $official['step'] . ' dari 3' : null"
		:icon="$outgoing ? 'fa-solid fa-user-pen text-amber-600' : 'fa-solid fa-user-plus text-amber-600'" maxWidth="max-w-2xl">
		@if ($selectedUser && $official)
			@php
				$wizardSteps = [1 => $outgoing ? 'Pilih pengganti' : 'Pilih pegawai', 2 => 'Jabatan', 3 => 'Konfirmasi'];
				$currentStep = $official['step'];
				$candidate = $official['candidate'];
			@endphp
			<div class="space-y-4">
				{{-- Penanda langkah --}}
				<ol class="flex items-center gap-2 text-[11px] font-semibold">
					@foreach ($wizardSteps as $n => $stepLabel)
						@php
							$done = $currentStep > $n;
							$active = $currentStep === $n;
						@endphp
						<li class="flex items-center gap-1.5 {{ $active ? 'text-primary-700' : ($done ? 'text-emerald-700' : 'text-slate-400') }}">
							<span class="flex size-5 items-center justify-center rounded-full text-[10px] font-bold {{ $active ? 'bg-primary-600 text-white' : ($done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
								@if ($done)<i class="fa-solid fa-check text-[9px]"></i>@else{{ $n }}@endif
							</span>
							{{ $stepLabel }}
						</li>
						@if (! $loop->last)
							<li class="h-px w-6 bg-slate-200" aria-hidden="true"></li>
						@endif
					@endforeach
				</ol>

				@if ($pelaksanaOrigin)
					<p class="text-xs text-slate-500">
						<i class="fa-solid fa-building mr-1 text-[10px] text-slate-400"></i>Instansi: <strong class="font-semibold text-slate-700">{{ $pelaksanaOrigin['instansi'] }}</strong>
						@if ($pelaksanaOrigin['unit']) › {{ $pelaksanaOrigin['unit'] }} @endif
					</p>
				@endif

				@if ($currentStep === 1)
					{{-- Langkah 1: pilih pegawai --}}
					<p class="text-sm text-slate-600">
						@if ($outgoing)
							Siapa yang menggantikan <strong class="font-semibold text-slate-800">{{ $outgoing['name'] }}</strong>
							sebagai <strong class="font-semibold text-slate-800">{{ $official['label'] }}</strong>?
						@else
							Siapa yang menjabat <strong class="font-semibold text-slate-800">{{ $official['label'] }}</strong>?
						@endif
						@if ($official['cityWide'])
							<span class="block text-xs text-slate-500">Calon dari pegawai Sekretariat Daerah.</span>
						@endif
					</p>

					<x-form.input wire:model.live.debounce.300ms="officialSearch" icon="fa-solid fa-magnifying-glass"
						loadingTarget="officialSearch" placeholder="Cari nama / NIP pegawai..." data-autofocus />

					<ul class="max-h-[45vh] divide-y divide-slate-100 overflow-y-auto rounded border border-slate-200">
						@forelse ($official['candidates'] as $row)
							<li wire:key="official-candidate-{{ $row['id'] }}" class="flex items-center gap-3 px-3 py-2">
								<div class="min-w-0 flex-1 leading-tight">
									<p class="truncate text-sm font-semibold text-slate-800">
										{{ $row['name'] }}
										@if ($row['id'] === $selectedUser->id)
											<x-ui.badge bg="bg-primary-50" text="text-primary-700" class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold">Pelaksana</x-ui.badge>
										@endif
									</p>
									<p class="truncate text-[11px] text-slate-500">
										{{ $row['nip'] ?: 'Tanpa NIP' }} · {{ $row['position'] ?? 'Jabatan belum diisi' }}
									</p>
								</div>
								@if ($row['fits'])
									<x-ui.badge bg="bg-emerald-50" text="text-emerald-700" class="shrink-0 px-1.5 py-0.5 text-[10px]">Jabatan sesuai</x-ui.badge>
								@endif
								<x-ui.button variant="secondary" class="shrink-0 px-2.5 py-1 text-xs"
									wire:click="pickOfficialCandidate({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="pickOfficialCandidate">
									Pilih
								</x-ui.button>
							</li>
						@empty
							<li class="px-3 py-3 text-xs italic text-slate-500">
								{{ trim($officialSearch) !== '' ? 'Pegawai tidak ditemukan. Coba kata kunci lain.' : 'Belum ada pegawai Staf di instansi ini.' }}
							</li>
						@endforelse
					</ul>

					<p class="text-xs text-slate-500">
						Pegawainya belum terdaftar?
						<a href="{{ route('master.users.create') }}" target="_blank" rel="noopener" class="font-semibold text-primary-700 underline-offset-2 hover:underline">Tambah pegawai</a>
						di tab baru, lalu cari lagi di sini.
					</p>
				@elseif ($currentStep === 2)
					{{-- Langkah 2: jabatan pegawai belum sesuai → pilih atau tambah jabatan --}}
					<p class="text-sm text-slate-600">
						Jabatan <strong class="font-semibold text-slate-800">{{ $candidate['name'] }}</strong>
						({{ $candidate['position'] ?? 'belum diisi' }}) belum sesuai. Pilih jabatan {{ $official['label'] }}:
					</p>

					<ul class="max-h-[35vh] divide-y divide-slate-100 overflow-y-auto rounded border border-slate-200">
						@forelse ($official['positions'] as $pos)
							@php $selected = $pos['id'] === $officialPositionId; @endphp
							<li wire:key="official-position-{{ $pos['id'] }}"
								class="flex items-center gap-3 px-3 py-2 {{ $selected ? 'bg-primary-50' : '' }}">
								<div class="min-w-0 flex-1 leading-tight">
									<p class="truncate text-sm font-semibold {{ $pos['holder'] ? 'text-slate-400' : 'text-slate-800' }}">
										{{ $pos['name'] }}
										@if ($pos['suggested'])
											<x-ui.badge bg="bg-amber-50" text="text-amber-700" class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold">Disarankan</x-ui.badge>
										@endif
									</p>
									@if ($pos['holder'])
										<p class="truncate text-[11px] text-slate-400">Sudah dijabat {{ $pos['holder'] }}</p>
									@elseif ($pos['handover'])
										<p class="truncate text-[11px] text-slate-500">Jabatan {{ $pos['handover'] }} saat ini, dialihkan ke pengganti</p>
									@endif
								</div>
								<x-ui.button :variant="$selected ? 'primary' : 'secondary'" class="shrink-0 px-2.5 py-1 text-xs"
									wire:click="chooseOfficialPosition({{ $pos['id'] }})" :disabled="(bool) $pos['holder']">
									@if ($selected)<i class="fa-solid fa-check text-[10px]"></i> Dipilih @else Pilih @endif
								</x-ui.button>
							</li>
						@empty
							<li class="px-3 py-3 text-xs italic text-slate-500">Belum ada jabatan {{ $official['label'] }}. Tambahkan di bawah.</li>
						@endforelse
					</ul>

					{{-- Jabatan belum ada di daftar: isi lengkap lewat form Tambah Jabatan (sama dengan
					     halaman Data Jabatan); tingkatnya dikunci ke tahap ini, lalu langsung terpilih. --}}
					<div class="flex flex-wrap items-center justify-between gap-2">
						<p class="text-xs text-slate-500">Jabatannya belum ada di daftar?</p>
						<x-ui.button variant="secondary" x-on:click="$dispatch('open-position-form', { role: '{{ $official['role'] }}' })">
							<x-slot:icon><i class="fa-solid fa-plus text-xs"></i></x-slot:icon>
							Tambah jabatan baru
						</x-ui.button>
					</div>
				@else
					{{-- Langkah 3: konfirmasi --}}
					<dl class="divide-y divide-slate-100 rounded border border-slate-200 text-sm">
						<div class="flex gap-3 px-3 py-2">
							<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $outgoing ? 'Pengganti' : 'Pegawai' }}</dt>
							<dd class="min-w-0 font-semibold text-slate-800">{{ $candidate['name'] }} <span class="font-normal text-slate-500">· {{ $candidate['nip'] ?: 'Tanpa NIP' }}</span></dd>
						</div>
						<div class="flex gap-3 px-3 py-2">
							<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Jabatan</dt>
							<dd class="min-w-0 text-slate-800">
								@if ($official['position'])
									<span class="text-slate-400 line-through">{{ $candidate['position'] ?? 'Belum diisi' }}</span>
									<i class="fa-solid fa-arrow-right mx-1 text-[10px] text-slate-400"></i>
									<strong class="font-semibold">{{ $official['position']['name'] }}</strong>
								@else
									{{ $candidate['position'] }} <span class="text-slate-500">(tetap)</span>
								@endif
							</dd>
						</div>
						<div class="flex gap-3 px-3 py-2">
							<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Peran</dt>
							<dd class="min-w-0 font-semibold text-slate-800">{{ $official['label'] }}</dd>
						</div>
						@if ($outgoing)
							<div class="flex gap-3 px-3 py-2">
								<dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Pejabat lama</dt>
								<dd class="min-w-0 text-slate-800">
									<strong class="font-semibold">{{ $outgoing['name'] }}</strong> menjadi {{ $outgoing['newRole'] }}.
									@if ($outgoing['released'])
										<span class="block text-xs text-slate-500">Jabatan {{ $outgoing['released'] }} dilepas. Atur jabatan barunya lewat Data Pegawai.</span>
									@endif
								</dd>
							</div>
						@endif
					</dl>
					@if ($outgoing && $outgoing['pending'] > 0)
						<x-ui.alert variant="warning" icon="fa-hourglass-half">
							{{ $outgoing['name'] }} masih memiliki <strong class="font-semibold">{{ $outgoing['pending'] }} persetujuan SPPD</strong> yang menunggu. Persetujuan itu tetap pada {{ $outgoing['name'] }}.
						</x-ui.alert>
					@endif
					<p class="text-xs text-slate-500">{{ $official['cityWide'] ? 'Berlaku untuk seluruh pengajuan SPPD.' : 'Berlaku juga untuk pengajuan berikutnya dari instansi ini.' }}</p>
				@endif
			</div>
		@endif

		<x-slot name="footer" class="flex items-center justify-between gap-2">
			@if (($official['step'] ?? 1) > 1)
				<x-ui.button variant="secondary" wire:click="officialBack">
					<x-slot:icon><i class="fa-solid fa-arrow-left text-xs"></i></x-slot:icon>
					Kembali
				</x-ui.button>
			@else
				<x-ui.button variant="secondary" wire:click="$set('showOfficialModal', false)">Tutup</x-ui.button>
			@endif

			@if (($official['step'] ?? 1) === 2)
				<x-ui.button variant="primary" wire:click="officialNext" :disabled="! $officialPositionId">
					Lanjut <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
				</x-ui.button>
			@elseif (($official['step'] ?? 1) === 3)
				<x-ui.button variant="primary" wire:click="confirmOfficial" wire:loading.attr="disabled" wire:target="confirmOfficial">
					<x-slot:icon><i class="fa-solid fa-check text-xs"></i></x-slot:icon>
					{{ $outgoing ? 'Ganti Pejabat' : 'Tetapkan' }}
				</x-ui.button>
			@endif
		</x-slot>
	</x-ui.modal>

	{{-- Modal Pilih Pejabat tahap — pilihan disimpan sebagai atasan langsung bawahan tahap itu
	     (pelaksana, atau pejabat tahap sebelumnya), jadi pengajuan berikutnya tidak perlu memilih lagi. --}}
	<x-ui.modal show="$wire.showSupervisorModal" :title="$supervisorModal['title'] ?? 'Pilih pejabat'"
		icon="fa-solid fa-user-tie text-amber-600" maxWidth="max-w-2xl">
		@if ($supervisorModal)
			<div class="space-y-4">
				<div class="space-y-1">
					<p class="text-sm text-slate-600">{{ $supervisorModal['intro'] }}</p>
					@if ($supervisorModal['hint'])
						<p class="text-xs text-slate-500">{{ $supervisorModal['hint'] }}</p>
					@endif
				</div>

				<x-form.input wire:model.live.debounce.300ms="supervisorSearch" icon="fa-solid fa-magnifying-glass"
					loadingTarget="supervisorSearch" placeholder="Cari nama / NIP pejabat..." data-autofocus />

				<ul class="max-h-[45vh] divide-y divide-slate-100 overflow-y-auto rounded border border-slate-200">
					@forelse ($supervisorModal['candidates'] as $row)
						@php $selected = $row['id'] === $supervisorChoiceId; @endphp
						{{-- Calon tahap atasan bisa berasal dari beberapa jabatan: dikelompokkan per jabatan. --}}
						@if ($supervisorModal['hint'] && ($loop->first || $row['role_label'] !== $supervisorModal['candidates'][$loop->index - 1]['role_label']))
							<li wire:key="supervisor-group-{{ $loop->index }}" class="sticky top-0 bg-slate-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $row['role_label'] }}</li>
						@endif
						<li wire:key="supervisor-candidate-{{ $row['id'] }}" class="flex items-center gap-3 px-3 py-2 {{ $selected ? 'bg-primary-50' : '' }}">
							<div class="min-w-0 flex-1 leading-tight">
								<p class="truncate text-sm font-semibold text-slate-800">
									{{ $row['name'] }}
									@if ($row['id'] === $supervisorModal['suggestedId'])
										<x-ui.badge bg="bg-amber-50" text="text-amber-700" class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold">Dipakai rekan satu unit</x-ui.badge>
									@endif
								</p>
								<p class="truncate text-[11px] text-slate-500">
									{{-- Nama jabatan lebih jelas dari role (role sudah tampil di judul/kelompok); role bila jabatan kosong. --}}
									{{ collect([($row['position'] ?? null) ?: ($row['role_label'] ?? null), $row['unit'] ?? null])->filter()->implode(' · ') }}
								</p>
							</div>
							<x-ui.button :variant="$selected ? 'primary' : 'secondary'" class="shrink-0 px-2.5 py-1 text-xs"
								wire:click="pickSupervisor({{ $row['id'] }})">
								@if ($selected)<i class="fa-solid fa-check text-[10px]"></i> Dipilih @else Pilih @endif
							</x-ui.button>
						</li>
					@empty
						<li class="px-3 py-3 text-xs italic text-slate-500">
							{{ trim($supervisorSearch) !== '' ? 'Pejabat tidak ditemukan. Coba kata kunci lain.' : 'Belum ada pejabat di atas pegawai ini di instansinya.' }}
						</li>
					@endforelse
				</ul>

				@if ($supervisorModal['colleagues'] > 0)
					<x-form.checkbox wire:model="supervisorApplyColleagues"
						:label="'Terapkan juga ke ' . $supervisorModal['colleagues'] . ' rekan di ' . $supervisorModal['unit'] . ' yang belum punya atasan langsung'"
						wrapper-class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer" />
				@endif

				@if ($supervisorModal['replace'])
					<p class="text-xs text-slate-500">
						Pejabatnya sendiri berganti?
						<button type="button" wire:click="replaceOfficial('{{ $supervisorModal['replace']['role'] }}')"
							class="font-semibold text-primary-700 underline-offset-2 hover:underline">Ganti pejabat {{ $supervisorModal['replace']['name'] }}</button>
					</p>
				@endif
			</div>
		@endif

		<x-slot name="footer" class="flex items-center justify-between gap-2">
			<x-ui.button variant="secondary" wire:click="$set('showSupervisorModal', false)">Batal</x-ui.button>
			<x-ui.button variant="primary" wire:click="saveSupervisor" :disabled="! $supervisorChoiceId"
				wire:loading.attr="disabled" wire:target="saveSupervisor">
				<x-slot:icon><i class="fa-solid fa-check text-xs"></i></x-slot:icon>
				Simpan
			</x-ui.button>
		</x-slot>
	</x-ui.modal>

	{{-- Form Tambah Jabatan bersama (Data Jabatan & form Pegawai), dibuka dari langkah 2 wizard. --}}
	<livewire:position-form-modal />
</div>
