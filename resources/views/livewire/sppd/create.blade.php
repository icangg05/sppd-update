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
					class="mb-1.5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
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
				$domainOptions = [
					['value' => 'dalam_daerah', 'label' => 'Dalam Daerah'],
					['value' => 'lddp', 'label' => 'Luar Daerah Dalam Provinsi (LDDP)'],
					['value' => 'ldlp', 'label' => 'Luar Daerah Luar Provinsi (LDLP)'],
				];
				$selectedPrefix = $selectedUser
					? ($selectedUser->employee_type === \App\Enums\EmployeeType::DPRD ? 'Anggota DPRD' : $selectedUser->nip)
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
											@php $prefix = $u->employee_type === \App\Enums\EmployeeType::DPRD ? 'Anggota DPRD' : $u->nip; @endphp
											{{ trim(($prefix ? $prefix . ' - ' : '') . $u->name) }}
										</button>
									</li>
								@empty
									<li class="px-3 py-2 text-xs text-slate-500">
										{{ trim($searchUser) !== '' ? 'Pegawai tidak ditemukan.' : 'Belum ada pegawai.' }}
									</li>
								@endforelse
								@if ($usersHasMore)
									<li class="border-t border-slate-100 px-3 py-2 text-[11px] italic text-slate-500">
										Menampilkan 25 hasil teratas — persempit pencarian untuk yang lain.
									</li>
								@endif
							</ul>
						</div>
					</div>
					@error('user_id')
						<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
					@enderror
				</div>

				<x-form.searchable-select wire:model.live="domain" name="domain" label="Domain Perjalanan" required
					:options="$domainOptions" placeholder="— Pilih Domain —" searchPlaceholder="Cari domain..." />
			</div>

			{{-- Container Pratinjau Alur Alur Dokumen --}}
			@if ($user_id)
				<div id="workflow-preview" class="border-t border-slate-100 bg-slate-50/50 p-5 animate-fadeIn">
					<div class="flex items-center justify-between mb-4">
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
								$nodeClass = $isFound ? 'bg-emerald-600 ring-emerald-100' : 'bg-red-600 ring-red-100';
								$roleClass = $isFound ? 'text-emerald-700' : 'text-red-600';
								$nameClass = $isFound ? 'text-slate-800' : 'text-red-700 italic';
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
						@empty
							@if ($errorMessage === '')
								<div class="w-full py-6 text-center text-sm font-medium text-slate-500 italic">
									<i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary-600"></i>Memvalidasi alur instansi...
								</div>
							@else
								@php $isBusy = $errorMessageName !== ''; @endphp
								{{-- Empty state kompak: rantai persetujuan putus (berornamen + animasi) --}}
								<div class="w-full">
									<div class="relative flex flex-col items-center gap-2.5 overflow-hidden rounded-lg border border-dashed border-amber-300 bg-linear-to-br from-amber-50/70 via-white to-white px-4 py-4">
										{{-- Ornamen watermark --}}
										<i class="fa-solid fa-diagram-project pointer-events-none absolute -bottom-3 -right-2 text-6xl text-amber-500/10" aria-hidden="true"></i>

										{{-- Rantai node: pelaksana → simpul putus → tak diketahui --}}
										<div class="relative flex items-center gap-1.5" aria-hidden="true">
											<span class="flex size-6 items-center justify-center rounded-full bg-slate-200 text-[10px] text-slate-400"><i class="fa-solid fa-user"></i></span>
											<span class="h-0.5 w-5 rounded-full bg-slate-300"></span>
											<span class="flex size-8 items-center justify-center rounded-full bg-amber-100 text-amber-600 ring-4 ring-amber-50 animate-pulse"><i class="fa-solid {{ $isBusy ? 'fa-user-clock' : 'fa-link-slash' }} text-sm"></i></span>
											<span class="w-5 border-t-2 border-dashed border-slate-300"></span>
											<span class="flex size-6 items-center justify-center rounded-full border border-dashed border-slate-300 text-[10px] text-slate-300"><i class="fa-solid fa-question"></i></span>
										</div>

										<div class="relative text-center">
											<p class="text-sm font-bold text-slate-700">{{ $isBusy ? 'Belum dapat membuat SPPD' : 'Alur persetujuan belum tersedia' }}</p>
											<p class="mt-0.5 text-xs leading-relaxed text-slate-500">
												@if ($isBusy)
													Pegawai <strong class="font-semibold text-slate-700">{{ $errorMessageName }}</strong> {{ $errorMessage }}
												@elseif ($errorMessageRole !== '')
													{{ $errorMessage }} <span class="whitespace-nowrap">(Role: <strong class="font-semibold text-slate-700">{{ $errorMessageRole }}</strong>)</span>
												@else
													{{ $errorMessage }}
												@endif
											</p>
										</div>
									</div>
								</div>
							@endif
						@endforelse
					</div>

					{{-- Pesan Kesalahan / Validasi Alur Instansi — hanya bila ada langkah yang
					     tampil (kasus tanpa langkah pesannya sudah menyatu di kartu empty-state). --}}
					@if ($errorMessage !== '' && count($steps) > 0)
						<div id="workflow-error-msg" class="mt-4 rounded border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
							<div class="flex gap-2">
								<i class="fa-solid fa-circle-exclamation shrink-0 mt-0.5"></i>
								<div>
									<span class="font-bold">Peringatan Validasi:</span>
									<span>
										@if ($errorMessageName !== '')
											Pegawai <strong class="font-bold">{{ $errorMessageName }}</strong> {{ $errorMessage }}
										@elseif ($errorMessageRole !== '')
											{{ $errorMessage }} (Role Pelaksana: <strong class="font-bold">{{ $errorMessageRole }}</strong>).
										@else
											{{ $errorMessage }}
										@endif
									</span>
								</div>
							</div>
						</div>
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
</div>
