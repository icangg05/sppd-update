<div x-data="{ showResetModal: false }">
	<div class="mx-auto max-w-3xl space-y-6">

		{{-- ── Hero identitas ── --}}
		<div class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
			{{-- Pita gradien institusional --}}
			<div class="relative h-14 bg-linear-to-br from-primary-800 via-primary-700 to-primary-600">
				<div class="absolute inset-0 opacity-20"
					style="background-image: radial-gradient(circle at 1px 1px, #ffffff 1px, transparent 0); background-size: 18px 18px;">
				</div>
				<span
					class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white/90 backdrop-blur-sm ring-1 ring-white/25">
					<i class="fa-regular fa-id-badge text-[10px]"></i>
					Profil Saya
				</span>
			</div>

			<div class="px-5 pb-5 sm:px-7 sm:pb-6 mt-14 sm:mt-16">
				<div class="-mt-12 flex flex-col gap-4 sm:flex-row sm:items-end">
					<div
						class="size-24 shrink-0 overflow-hidden rounded-full border-4 border-white bg-white shadow-md ring-1 ring-slate-900/5 sm:size-28">
						<img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Admin') }}&background=337AB7&color=fff&bold=true&size=160"
							alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover">
					</div>

					{{-- Nama & meta di sebelah avatar: dijaga pendek agar tidak masuk ke pita gradien --}}
					<div class="min-w-0 flex-1 sm:pb-1">
						<h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
							{{ $user->name ?? 'Administrator' }}
						</h1>
						<p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm text-slate-500">
							<span class="font-medium text-slate-600">{{ $user->position?->name ?? 'Tanpa Jabatan' }}</span>
							<span class="text-slate-300">•</span>
							<span class="truncate">{{ $user->department?->name ?? 'Sistem SPPD' }}</span>
						</p>
					</div>
				</div>

				{{-- Chip status di baris penuh sendiri, di bawah avatar --}}
				<div class="mt-4 flex flex-wrap items-center gap-2">
					<x-ui.badge color="blue">
						<i class="fa-solid fa-shield-halved mr-1.5 text-[10px]"></i>
						{{ $user->roles->first()?->label ?? 'Tanpa Role' }}
					</x-ui.badge>

					@if ($phoneVerified)
						<span
							class="inline-flex items-center rounded px-2 py-1 text-[11px] font-medium text-green-700 ring-1 ring-inset ring-green-600/20 bg-green-50">
							<i class="fa-brands fa-whatsapp mr-1.5 text-[11px]"></i>
							WhatsApp Terverifikasi
						</span>
					@else
						<span
							class="inline-flex items-center rounded px-2 py-1 text-[11px] font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20 bg-amber-50">
							<i class="fa-solid fa-triangle-exclamation mr-1.5 text-[11px]"></i>
							Belum Verifikasi
						</span>
					@endif

					@if ($user->nip)
						<span class="inline-flex items-center rounded px-2 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-inset ring-slate-600/15 bg-slate-50">
							<i class="fa-regular fa-address-card mr-1.5 text-[11px]"></i>
							<span class="font-mono">{{ $user->nip }}</span>
						</span>
					@endif
				</div>
			</div>
		</div>

		<form wire:submit.prevent="save" class="space-y-6">

			{{-- ── Identitas Dasar ── --}}
			<div class="dash-enter rounded border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
				<div class="mb-5 flex items-center gap-3">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
						<i class="fa-regular fa-user"></i>
					</div>
					<div>
						<h2 class="text-sm font-bold text-slate-800">Identitas</h2>
						<p class="text-xs text-slate-500">Data diri utama yang tampil di dokumen.</p>
					</div>
				</div>

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<x-form.input wire:model="name" label="Nama Lengkap" required placeholder="Nama lengkap" />

					{{-- Username dengan tombol generate --}}
					<div>
						<label for="username" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
							Username <span class="text-rose-500">*</span>
						</label>
						<div class="flex gap-2">
							<input wire:model="username" type="text" id="username" name="username" required
								placeholder="Username"
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

					<x-form.input wire:model="email" type="email" label="Email" placeholder="email@contoh.go.id" />
					<div class="hidden sm:block"></div>
					<x-form.input wire:model="nip" label="NIP" placeholder="Nomor Induk Pegawai" class="font-mono" />
					<x-form.input wire:model="nik" label="NIK" placeholder="16 digit angka" class="font-mono" />
				</div>
			</div>

			{{-- ── Kontak / WhatsApp ── --}}
			<div class="dash-enter rounded border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
				<div class="mb-5 flex items-center gap-3">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
						<i class="fa-brands fa-whatsapp"></i>
					</div>
					<div>
						<h2 class="text-sm font-bold text-slate-800">Nomor WhatsApp</h2>
						<p class="text-xs text-slate-500">Dipakai untuk notifikasi & verifikasi sistem.</p>
					</div>
				</div>

				<div class="space-y-1">
					<label for="phone" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
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
							class="block w-full flex-1 rounded border px-3 py-2 text-sm text-slate-800 shadow-2xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 @error('phone') border-red-400 @else border-slate-300 @enderror @if ($phoneVerified) bg-slate-50 cursor-not-allowed @endif" />

						@if ($phoneVerified)
							<button type="button" disabled
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 cursor-default">
								<i class="fa-solid fa-check-circle text-sm"></i> <span class="hidden sm:inline">Terverifikasi</span>
							</button>
							<button type="button" wire:click="confirmTestMessage" title="Tes kirim pesan ke nomor ini"
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-primary-500 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-600 hover:text-white active:scale-95">
								<i class="fa-solid fa-paper-plane text-sm"></i> <span class="hidden sm:inline">Tes Pesan</span>
							</button>
							<button type="button" @click="showResetModal = true" title="Ganti nomor WhatsApp"
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-amber-500 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-600 hover:text-white active:scale-95">
								<i class="fa-solid fa-rotate text-sm"></i> <span class="hidden sm:inline">Ganti</span>
							</button>
						@else
							<button type="button" wire:click="openVerifyModal" wire:loading.attr="disabled"
								wire:target="openVerifyModal" title="Verifikasi nomor WhatsApp ini"
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-600 hover:text-white active:scale-95 disabled:opacity-50">
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
							<span>Wajib verifikasi nomor dengan menekan tombol <strong>Verifikasi</strong> sebelum dapat menyimpan.</span>
						</p>
					@endif

					@error('phone')
						<p class="mt-0.5 text-xs text-red-500">{{ $message }}</p>
					@enderror
				</div>
			</div>

			{{-- ── Ganti Password ── --}}
			<div class="dash-enter rounded border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
				<div class="mb-5 flex items-center gap-3">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
						<i class="fa-solid fa-lock"></i>
					</div>
					<div>
						<h2 class="text-sm font-bold text-slate-800">Ganti Password</h2>
						<p class="text-xs text-slate-500">Kosongkan jika tidak ingin mengubah password.</p>
					</div>
				</div>

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<x-form.input wire:model="current_password" type="password" label="Password Saat Ini"
						placeholder="••••••" wrapperClass="sm:col-span-2" />
					<x-form.input wire:model="password" type="password" label="Password Baru" placeholder="Minimal 6 karakter" />
					<x-form.input wire:model="password_confirmation" type="password" label="Konfirmasi Password Baru"
						placeholder="Ulangi password baru" />
				</div>
			</div>

			{{-- ── Informasi Organisasi (read-only) ── --}}
			<div class="dash-enter rounded border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
				<div class="mb-5 flex items-center gap-3">
					<div class="flex size-9 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500 ring-1 ring-slate-200">
						<i class="fa-solid fa-building"></i>
					</div>
					<div>
						<h2 class="text-sm font-bold text-slate-800">Informasi Organisasi</h2>
						<p class="text-xs text-slate-500">Dikelola administrator — tidak dapat diubah sendiri.</p>
					</div>
				</div>

				<dl class="grid grid-cols-1 gap-px overflow-hidden rounded border border-slate-200 bg-slate-200 sm:grid-cols-2">
					<div class="bg-white p-4">
						<dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Unit Kerja</dt>
						<dd class="mt-1 text-sm font-medium text-slate-800">{{ $user->department?->name ?? '—' }}</dd>
					</div>
					<div class="bg-white p-4">
						<dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Jabatan</dt>
						<dd class="mt-1 text-sm font-medium text-slate-800">{{ $user->position?->name ?? '—' }}</dd>
					</div>
					<div class="bg-white p-4">
						<dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Pangkat / Golongan</dt>
						<dd class="mt-1 text-sm font-medium text-slate-800">{{ $user->rank?->name ?? '—' }}</dd>
					</div>
					<div class="bg-white p-4">
						<dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Tipe Pegawai</dt>
						<dd class="mt-1 text-sm font-medium text-slate-800">{{ $user->employee_type?->label() ?? '—' }}</dd>
					</div>
					<div class="bg-white p-4 sm:col-span-2">
						<dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Role</dt>
						<dd class="mt-1.5">
							<x-ui.badge>{{ $user->roles->first()?->label ?? 'Tanpa Role' }}</x-ui.badge>
						</dd>
					</div>
				</dl>
			</div>

			{{-- ── Aksi ── --}}
			<div class="sticky bottom-4 z-10 flex items-center justify-end gap-3 rounded border border-slate-200 bg-white/85 p-3 shadow-lg shadow-slate-900/5 backdrop-blur-sm">
				<x-ui.button href="{{ route('dashboard') }}" variant="secondary">
					<i class="fa-solid fa-arrow-left text-xs"></i> Kembali
				</x-ui.button>
				<x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
					<span wire:loading.remove wire:target="save"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan
						Perubahan</span>
					<span wire:loading wire:target="save"><i class="fa-solid fa-spinner fa-spin text-xs"></i>
						Menyimpan...</span>
				</x-ui.button>
			</div>
		</form>

		@include('livewire.partials.phone-verification')

	</div>
</div>
