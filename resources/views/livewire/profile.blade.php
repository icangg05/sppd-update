<div x-data="{ showResetModal: false }">
	<div class="mx-auto max-w-3xl space-y-6">

		{{-- Header --}}
		<div class="page-header">
			<div>
				<h1 class="page-title">Profil Saya</h1>
				<p class="page-subtitle">Kelola data akun dan informasi kontak Anda.</p>
			</div>
		</div>

		<form wire:submit.prevent="save" class="space-y-6">

			{{-- Identitas Dasar --}}
			<div class="table-container p-5 sm:p-6">
				<div class="mb-4 flex items-center gap-2">
					<i class="fa-regular fa-user text-cyan-600"></i>
					<h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Identitas</h2>
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

					<x-form.input wire:model="email" type="email" label="Email" placeholder="email@contoh.go.id" />
					<div class="hidden sm:block"></div>
					<x-form.input wire:model="nip" label="NIP" placeholder="Nomor Induk Pegawai" class="font-mono" />
					<x-form.input wire:model="nik" label="NIK" placeholder="16 digit angka" class="font-mono" />
				</div>
			</div>

			{{-- Kontak / WhatsApp --}}
			<div class="table-container p-5 sm:p-6">
				<div class="mb-4 flex items-center gap-2">
					<i class="fa-brands fa-whatsapp text-emerald-600"></i>
					<h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Nomor WhatsApp</h2>
				</div>

				<div class="space-y-1">
					<label for="phone" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">
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
							class="block w-full flex-1 rounded border px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:ring-cyan-500 @error('phone') border-red-400 @else border-slate-300 @enderror @if ($phoneVerified) bg-slate-50 cursor-not-allowed @endif" />

						@if ($phoneVerified)
							<button type="button" disabled
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 cursor-default">
								<i class="fa-solid fa-check-circle text-sm"></i> <span class="hidden sm:inline">Terverifikasi</span>
							</button>
							<button type="button" wire:click="sendTestMessage" wire:loading.attr="disabled"
								wire:target="sendTestMessage" title="Tes kirim pesan ke nomor ini"
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-cyan-500 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-600 hover:text-white disabled:opacity-50">
								<span wire:loading.remove wire:target="sendTestMessage" class="inline-flex items-center gap-1.5">
									<i class="fa-solid fa-paper-plane text-sm"></i> <span class="hidden sm:inline">Tes Pesan</span>
								</span>
								<span wire:loading wire:target="sendTestMessage" class="inline-flex items-center gap-1.5">
									<i class="fa-solid fa-spinner fa-spin text-sm"></i> <span class="hidden sm:inline">Mengirim...</span>
								</span>
							</button>
							<button type="button" @click="showResetModal = true" title="Ganti nomor WhatsApp"
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-amber-500 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-600 hover:text-white">
								<i class="fa-solid fa-rotate text-sm"></i> <span class="hidden sm:inline">Ganti</span>
							</button>
						@else
							<button type="button" wire:click="openVerifyModal" wire:loading.attr="disabled"
								wire:target="openVerifyModal" title="Verifikasi nomor WhatsApp ini"
								class="inline-flex items-center gap-1.5 whitespace-nowrap rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-600 hover:text-white disabled:opacity-50">
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
						<p class="mt-1 text-xs font-medium text-green-600">
							<i class="fa-solid fa-circle-check mr-1"></i>
							Nomor telah diverifikasi dan terkunci. Gunakan tombol Ganti jika ingin mengubahnya.
						</p>
					@else
						<p class="mt-1 text-xs font-medium text-amber-600">
							<i class="fa-solid fa-triangle-exclamation mr-1"></i>
							Wajib verifikasi nomor dengan menekan tombol <strong>Verifikasi</strong> sebelum dapat menyimpan.
						</p>
					@endif

					@error('phone')
						<p class="mt-0.5 text-xs text-red-500">{{ $message }}</p>
					@enderror
				</div>
			</div>

			{{-- Ganti Password --}}
			<div class="table-container p-5 sm:p-6">
				<div class="mb-4 flex items-center gap-2">
					<i class="fa-solid fa-lock text-cyan-600"></i>
					<h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Ganti Password</h2>
				</div>
				<p class="mb-4 text-xs text-slate-400">Kosongkan jika tidak ingin mengubah password.</p>

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<x-form.input wire:model="current_password" type="password" label="Password Saat Ini"
						placeholder="••••••" wrapperClass="sm:col-span-2" />
					<x-form.input wire:model="password" type="password" label="Password Baru" placeholder="Minimal 6 karakter" />
					<x-form.input wire:model="password_confirmation" type="password" label="Konfirmasi Password Baru"
						placeholder="Ulangi password baru" />
				</div>
			</div>

			{{-- Informasi Organisasi (read-only) --}}
			<div class="table-container p-5 sm:p-6">
				<div class="mb-1 flex items-center gap-2">
					<i class="fa-solid fa-building text-slate-500"></i>
					<h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Informasi Organisasi</h2>
				</div>
				<p class="mb-4 text-xs text-slate-400">Data ini dikelola oleh administrator dan tidak dapat diubah sendiri.</p>

				<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<div>
						<dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Kerja</dt>
						<dd class="mt-0.5 text-sm text-slate-800">{{ $user->department?->name ?? '—' }}</dd>
					</div>
					<div>
						<dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jabatan</dt>
						<dd class="mt-0.5 text-sm text-slate-800">{{ $user->position?->name ?? '—' }}</dd>
					</div>
					<div>
						<dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pangkat / Golongan</dt>
						<dd class="mt-0.5 text-sm text-slate-800">{{ $user->rank?->name ?? '—' }}</dd>
					</div>
					<div>
						<dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipe Pegawai</dt>
						<dd class="mt-0.5 text-sm text-slate-800">{{ $user->employee_type?->label() ?? '—' }}</dd>
					</div>
					<div>
						<dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</dt>
						<dd class="mt-0.5">
							<x-ui.badge>{{ $user->roles->first()?->label ?? 'Tanpa Role' }}</x-ui.badge>
						</dd>
					</div>
				</dl>
			</div>

			{{-- Aksi --}}
			<div class="flex items-center justify-end gap-3">
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
