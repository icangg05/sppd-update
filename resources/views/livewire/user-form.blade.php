<div x-data="{ showResetModal: false }">
	<div class="p-1 space-y-6">

		{{-- Header Halaman --}}
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
					<i class="fa-solid {{ $isEdit ? 'fa-user-pen' : 'fa-user-plus' }} mr-2 text-cyan-600"></i>{{ $isEdit ? 'Edit' : 'Tambah' }} Pegawai
				</h1>
				<p class="mt-1 text-xs text-slate-500 font-medium">
					{{ $isEdit ? 'Ubah informasi profile, instansi, atau kredensial pengguna sistem' : 'Tambahkan pegawai baru ke dalam sistem' }}
				</p>
			</div>
			<x-ui.button href="{{ route('master.users.index') }}" variant="secondary"
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
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input wire:model="username" name="username" label="Username" required
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input wire:model="email" type="email" name="email" label="Email Resmi"
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input wire:model="password" type="password" name="password" label="{{ $isEdit ? 'Password Baru' : 'Password' }}"
								placeholder="{{ $isEdit ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 6 karakter' }}"
								:required="!$isEdit"
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input wire:model="nip" name="nip" label="NIP (Nomor Induk Pegawai)"
								placeholder="18 digit angka" class="font-mono focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

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
									@if($phoneVerified) readonly @endif
									class="flex-1 block w-full rounded border px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:ring-cyan-500 @error('phone') border-red-400 @else border-slate-300 @enderror @if($phoneVerified) bg-slate-50 cursor-not-allowed @endif" />

								@if($phoneVerified)
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
							
							@if($phoneVerified)
								<p class="text-xs text-green-600 mt-1 font-medium">
									<i class="fa-solid fa-shield-check mr-1"></i> Nomor telah diverifikasi dan terkunci. Gunakan tombol Ganti jika ingin mengubahnya.
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
							<x-form.select wire:model="employee_type" name="employee_type" label="Tipe Status Pegawai" required
								class="focus:border-cyan-500 focus:ring-cyan-500">
								@foreach ($employeeTypes as $type)
									<option value="{{ $type->value }}">
										{{ $type->label() }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="sm:col-span-2 my-1">
							<hr class="border-slate-100">
						</div>

						<div class="space-y-1">
							<x-form.select wire:model="department_id" name="department_id" label="Instansi / Unit Kerja (OPD)"
								class="focus:border-cyan-500 focus:ring-cyan-500">
								<option value="">— Pilih Instansi —</option>
								@foreach ($departments as $d)
									<option value="{{ $d->id }}">
										{{ $d->display_name }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="space-y-1">
							<x-form.select wire:model="rank_id" name="rank_id" label="Golongan / Pangkat" class="focus:border-cyan-500 focus:ring-cyan-500">
								<option value="">— Pilih Pangkat —</option>
								@foreach ($ranks as $r)
									<option value="{{ $r->id }}">
										{{ $r->group }} — {{ $r->name }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="space-y-1">
							<x-form.select wire:model="position_id" name="position_id" label="Jabatan Struktural / Fungsional"
								class="focus:border-cyan-500 focus:ring-cyan-500">
								<option value="">— Pilih Jabatan —</option>
								@foreach ($positions as $p)
									<option value="{{ $p->id }}">
										{{ $p->name }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="space-y-1">
							<x-form.select wire:model="role" name="role" label="Role Otentikasi Sistem" required
								class="focus:border-cyan-500 focus:ring-cyan-500">
                                <option value="">— Pilih Role —</option>
								@foreach ($roles as $r)
									<option value="{{ $r->name }}">
										{{ $r->label }}
									</option>
								@endforeach
							</x-form.select>
						</div>

					</div>
				</div>
			</div>

			{{-- Form Actions --}}
			<div class="flex justify-end gap-3">
				<x-ui.button href="{{ route('master.users.index') }}" variant="secondary"
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

		{{-- Polling component for verification status --}}
		@if($isPolling)
			<div wire:poll.1s.keep-alive="checkVerification"></div>
		@endif

		{{-- Modal Verifikasi WhatsApp --}}
		<x-ui.modal show="$wire.showVerifyModal" :closeable="false" title="Verifikasi Nomor WhatsApp"
			description="Kirim pesan ke operator untuk konfirmasi" icon="fa-brands fa-whatsapp text-emerald-600">
			<div class="space-y-4">
				{{-- Instruksi singkat --}}
				<p class="text-xs text-slate-500">Kirim pesan verifikasi di bawah ini melalui WhatsApp. Status akan diperbarui otomatis setelah pesan diterima.</p>

				{{-- Template pesan --}}
				<div>
					<p class="text-xs font-semibold text-slate-600 mb-1.5">Pesan Verifikasi:</p>
					<div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700 leading-relaxed whitespace-pre-wrap font-mono">{{ $verificationTemplate }}</div>
				</div>

				{{-- Status Polling: 3 states --}}
				<div class="rounded-lg p-3 text-center text-xs font-medium border
					{{ $isVerified ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : '' }}
					{{ $isFailed ? 'bg-red-50 text-red-800 border-red-200' : '' }}
					{{ !$isVerified && !$isFailed && !$isTimedOut ? 'bg-amber-50 text-amber-800 border-amber-200' : '' }}
					{{ $isTimedOut && !$isFailed && !$isVerified ? 'bg-slate-50 text-slate-600 border-slate-200' : '' }}">
					
					{{-- Pending --}}
					@if(!$isVerified && !$isFailed && !$isTimedOut)
						<span class="flex items-center justify-center gap-2">
							<i class="fa-solid fa-circle-notch fa-spin text-amber-600"></i>
							Menunggu pesan WhatsApp dikirim...
						</span>
					@endif
					
					{{-- Verified --}}
					@if($isVerified)
						<span class="flex items-center justify-center gap-2">
							<i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
							Nomor WhatsApp Berhasil Diverifikasi.
						</span>
					@endif
					
					{{-- Failed --}}
					@if($isFailed)
						<div class="space-y-2">
							<span class="flex items-center justify-center gap-2">
								<i class="fa-solid fa-circle-xmark text-red-600 text-base"></i>
								Verifikasi Gagal
							</span>
							<p class="text-[11px] text-red-600 leading-relaxed">{{ $failedMessage }}</p>
						</div>
					@endif
					
					{{-- Timed out --}}
					@if($isTimedOut && !$isFailed && !$isVerified)
						<div class="space-y-2">
							<span class="flex items-center justify-center gap-2">
								<i class="fa-solid fa-clock text-slate-500 text-base"></i>
								Waktu verifikasi habis (5 menit)
							</span>
							<p class="text-[11px] text-slate-500">Silakan coba lagi dengan menekan tombol di bawah.</p>
						</div>
					@endif
				</div>
			</div>

			<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
				<button type="button" wire:click="closeVerifyModal"
					class="flex-1 rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
					Tutup
				</button>
				
				{{-- Kirim via WhatsApp (pending) --}}
				@if(!$isVerified && !$isFailed && !$isTimedOut)
					<a href="{{ $deeplinkUrl }}" target="_blank" rel="noopener"
						class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 py-2.5 text-xs font-bold text-white shadow transition hover:bg-green-700 whitespace-nowrap">
						<i class="fa-brands fa-whatsapp shrink-0"></i>
						<span>Kirim via WhatsApp</span>
					</a>
				@endif
				
				{{-- Terverifikasi --}}
				@if($isVerified)
					<button type="button" disabled
						class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 py-2.5 text-xs font-bold text-white shadow cursor-not-allowed whitespace-nowrap">
						<i class="fa-solid fa-circle-check text-sm shrink-0"></i>
						<span>Terverifikasi</span>
					</button>
				@endif
				
				{{-- Coba Lagi (failed / timed out) --}}
				@if($isFailed || $isTimedOut)
					<button type="button" wire:click="retryVerification"
						class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 py-2.5 text-xs font-bold text-white shadow transition hover:bg-amber-600 whitespace-nowrap">
						<i class="fa-solid fa-rotate-right text-sm shrink-0"></i>
						<span>Coba Lagi</span>
					</button>
				@endif
			</x-slot>
		</x-ui.modal>

		{{-- Modal Konfirmasi Ganti Nomor --}}
		<x-ui.modal show="showResetModal" :closeable="false" title="Konfirmasi Ganti Nomor"
			description="Tindakan ini membutuhkan verifikasi ulang" icon="fa-solid fa-triangle-exclamation text-amber-600">
			<div class="space-y-4">
				<p class="text-sm text-slate-600">
					Apakah Anda yakin ingin mengganti nomor WhatsApp? 
					<br><br>
					Status verifikasi pada nomor sebelumnya akan <strong>dihapus</strong> dan Anda harus melakukan proses verifikasi ulang untuk nomor yang baru.
				</p>
			</div>

			<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
				<button type="button" @click="showResetModal = false"
					class="flex-1 rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
					Batal
				</button>
				<button type="button" wire:click="resetPhoneVerification" @click="showResetModal = false"
					class="flex-1 rounded-lg bg-amber-500 py-2.5 text-xs font-bold text-white shadow transition hover:bg-amber-600">
					Ya, Ganti Nomor
				</button>
			</x-slot>
		</x-ui.modal>

	</div>
</div>
