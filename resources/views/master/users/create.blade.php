@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page-title', 'Tambah Pegawai')

@section('content')
	<div class="p-1 space-y-6" x-data="waVerifyData()" @open-verify.window="openVerify($event.detail)">

		{{-- Header Halaman --}}
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
			<div>
				<h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-cyan-500 inline-block pb-1">
					<i class="fa-solid fa-user-plus mr-2 text-cyan-600"></i>Tambah Pegawai
				</h1>
				<p class="mt-1 text-xs text-slate-500 font-medium">Tambahkan entitas pegawai baru beserta hak akses sistemnya</p>
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
		<form method="POST" action="{{ route('master.users.store') }}" class="space-y-6">
			@csrf

			<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
				{{-- Sub-header Card --}}
				<div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
					<h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
						<i class="fa-solid fa-address-card text-cyan-500"></i>Formulir Data Kredensial & Kepegawaian
					</h3>
				</div>

				{{-- Form Fields --}}
				<div class="p-6">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-6">

						<div class="space-y-1">
							<x-form.input name="name" label="Nama Lengkap" :value="old('name')" required
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input name="username" label="Username" :value="old('username')" required
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input type="email" name="email" label="Email Resmi" :value="old('email')"
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input type="password" name="password" label="Password Akun" required
								class="focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input name="nip" label="NIP (Nomor Induk Pegawai)" :value="old('nip')" placeholder="18 digit angka"
								class="font-mono focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							<x-form.input name="nik" label="NIK (Nomor Induk Kependudukan)" :value="old('nik')" placeholder="16 digit angka"
								class="font-mono focus:border-cyan-500 focus:ring-cyan-500" />
						</div>

						<div class="space-y-1">
							{{-- Phone Field dengan tombol verifikasi --}}
							<label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wide">
								No. Telepon / WhatsApp
							</label>
							<div class="flex gap-2">
								<input
									type="tel"
									id="phone"
									name="phone"
									value="{{ old('phone') }}"
									placeholder="Contoh: 08123456789"
									inputmode="numeric"
									pattern="[0-9+]*"
									class="flex-1 block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:ring-cyan-500 @error('phone') border-red-400 @enderror" />
								<button
									type="button"
									id="btn-test-wa"
									onclick="openVerifyModal()"
									title="Verifikasi nomor WhatsApp ini"
									class="inline-flex items-center gap-1.5 rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-600 hover:text-white whitespace-nowrap">
									<i class="fa-brands fa-whatsapp text-sm"></i> Verifikasi
								</button>
							</div>
							<p class="text-xs text-slate-400 mt-1">
								<i class="fa-solid fa-circle-info mr-1 text-cyan-500"></i>
								Nomor ini akan digunakan untuk mengirim notifikasi WhatsApp terkait pengajuan SPPD.
								Gunakan tombol <strong>Verifikasi</strong> untuk mengkonfirmasi nomor sebelum menyimpan.
							</p>
							@error('phone')
								<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
							@enderror
							<div id="wa-test-result" class="hidden mt-1 text-xs font-medium"></div>
						</div>

						<div class="space-y-1">
							<x-form.select name="employee_type" label="Tipe Status Pegawai" required
								class="focus:border-cyan-500 focus:ring-cyan-500">
								@foreach (\App\Enums\EmployeeType::cases() as $type)
									<option value="{{ $type->value }}" {{ old('employee_type') === $type->value ? 'selected' : '' }}>
										{{ $type->label() }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="sm:col-span-2 my-1">
							<hr class="border-slate-100">
						</div>

						<div class="space-y-1">
							<x-form.select name="department_id" label="Instansi / Unit Kerja (OPD)"
								class="focus:border-cyan-500 focus:ring-cyan-500">
								<option value="">— Pilih Instansi —</option>
								@foreach ($departments as $d)
									<option value="{{ $d->id }}"
										{{ old('department_id') == $d->id ? 'selected' : '' }}>
										{{ $d->display_name }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="space-y-1">
							<x-form.select name="rank_id" label="Golongan / Pangkat"
								class="focus:border-cyan-500 focus:ring-cyan-500">
								<option value="">— Pilih Pangkat —</option>
								@foreach ($ranks as $r)
									<option value="{{ $r->id }}" {{ old('rank_id') == $r->id ? 'selected' : '' }}>
										{{ $r->group }} — {{ $r->name }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="space-y-1">
							<x-form.select name="position_id" label="Jabatan Struktural / Fungsional"
								class="focus:border-cyan-500 focus:ring-cyan-500">
								<option value="">— Pilih Jabatan —</option>
								@foreach ($positions as $p)
									<option value="{{ $p->id }}" {{ old('position_id') == $p->id ? 'selected' : '' }}>
										{{ $p->name }}
									</option>
								@endforeach
							</x-form.select>
						</div>

						<div class="space-y-1">
							<x-form.select name="role" label="Role Otentikasi Sistem" required
								class="focus:border-cyan-500 focus:ring-cyan-500">
								<option value="">— Pilih Role —</option>
								@foreach (\Spatie\Permission\Models\Role::orderBy('label')->get() as $role)
									<option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
										{{ $role->label }}
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
					Simpan Pegawai
				</x-ui.button>
			</div>
		</form>

		{{-- Modal Verifikasi WhatsApp --}}
		<x-ui.modal show="showVerifyModal" :closeable="false" title="Verifikasi Nomor WhatsApp"
			description="Kirim pesan ke operator untuk konfirmasi" icon="fa-brands fa-whatsapp text-emerald-600">
			<div class="space-y-4">
				{{-- Instruksi singkat --}}
				<p class="text-xs text-slate-500">Kirim pesan verifikasi di bawah ini melalui WhatsApp. Status akan diperbarui otomatis setelah pesan diterima.</p>

				{{-- Template pesan --}}
				<div>
					<p class="text-xs font-semibold text-slate-600 mb-1.5">Pesan Verifikasi:</p>
					<div id="modal-verify-template" x-text="verificationTemplate"
						class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700 leading-relaxed whitespace-pre-wrap font-mono">
					</div>
				</div>

				{{-- Status Polling: 3 states --}}
				<div class="rounded-lg p-3 text-center text-xs font-medium border"
					:class="{
						'bg-emerald-50 text-emerald-800 border-emerald-200': isVerified,
						'bg-red-50 text-red-800 border-red-200': isFailed,
						'bg-amber-50 text-amber-800 border-amber-200': !isVerified && !isFailed && !isTimedOut,
						'bg-slate-50 text-slate-600 border-slate-200': isTimedOut && !isFailed && !isVerified
					}">
					{{-- Pending --}}
					<template x-if="!isVerified && !isFailed && !isTimedOut">
						<span class="flex items-center justify-center gap-2">
							<i class="fa-solid fa-circle-notch fa-spin text-amber-600"></i>
							Menunggu pesan WhatsApp dikirim...
						</span>
					</template>
					{{-- Verified --}}
					<template x-if="isVerified">
						<span class="flex items-center justify-center gap-2">
							<i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
							Nomor WhatsApp Berhasil Diverifikasi.
						</span>
					</template>
					{{-- Failed --}}
					<template x-if="isFailed">
						<div class="space-y-2">
							<span class="flex items-center justify-center gap-2">
								<i class="fa-solid fa-circle-xmark text-red-600 text-base"></i>
								Verifikasi Gagal
							</span>
							<p class="text-[11px] text-red-600 leading-relaxed" x-text="failedMessage"></p>
						</div>
					</template>
					{{-- Timed out --}}
					<template x-if="isTimedOut && !isFailed && !isVerified">
						<div class="space-y-2">
							<span class="flex items-center justify-center gap-2">
								<i class="fa-solid fa-clock text-slate-500 text-base"></i>
								Waktu verifikasi habis (5 menit)
							</span>
							<p class="text-[11px] text-slate-500">Silakan coba lagi dengan menekan tombol di bawah.</p>
						</div>
					</template>
				</div>
			</div>

			<x-slot name="footer" class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
				<button type="button" @click="closeModal()"
					class="flex-1 rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
					Tutup
				</button>
				{{-- Kirim via WhatsApp (pending) --}}
				<template x-if="!isVerified && !isFailed && !isTimedOut">
					<a :href="deeplinkUrl" target="_blank" rel="noopener"
						class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 py-2.5 text-xs font-bold text-white shadow transition hover:bg-green-700 whitespace-nowrap">
						<i class="fa-brands fa-whatsapp shrink-0"></i>
						<span>Kirim via WhatsApp</span>
					</a>
				</template>
				{{-- Terverifikasi --}}
				<template x-if="isVerified">
					<button type="button" disabled
						class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 py-2.5 text-xs font-bold text-white shadow cursor-not-allowed whitespace-nowrap">
						<i class="fa-solid fa-circle-check text-sm shrink-0"></i>
						<span>Terverifikasi</span>
					</button>
				</template>
				{{-- Coba Lagi (failed / timed out) --}}
				<template x-if="isFailed || isTimedOut">
					<button type="button" @click="retryVerification()"
						class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 py-2.5 text-xs font-bold text-white shadow transition hover:bg-amber-600 whitespace-nowrap">
						<i class="fa-solid fa-rotate-right text-sm shrink-0"></i>
						<span>Coba Lagi</span>
					</button>
				</template>
			</x-slot>
		</x-ui.modal>

	</div>{{-- end x-data wrapper --}}

@endsection

@push('scripts')
	<script>
		const _waCheckUrl = '{{ route('master.users.check-verification', ['token' => '__TOKEN__'], false) }}';

		function waVerifyData() {
			return {
				showVerifyModal: false,
				verificationNumber: '',
				verificationTemplate: '',
				deeplinkUrl: '',
				token: '',
				isVerified: false,
				isFailed: false,
				isTimedOut: false,
				failedMessage: '',
				pollingInterval: null,
				timeoutTimer: null,
				openVerify(data) {
					this.verificationNumber = '+' + data.verification_number;
					this.verificationTemplate = data.template;
					this.token = data.token;
					this.deeplinkUrl = 'https://wa.me/' + data.verification_number + '?text=' + encodeURIComponent(data
						.template);
					this.isVerified = false;
					this.isFailed = false;
					this.isTimedOut = false;
					this.failedMessage = '';
					this.showVerifyModal = true;
					this.startPolling();
					this.startTimeout();
				},
				startPolling() {
					if (this.pollingInterval) {
						clearInterval(this.pollingInterval);
					}
					const self = this;
					this.pollingInterval = setInterval(function() {
						if (!self.showVerifyModal || self.isVerified || self.isTimedOut) {
							clearInterval(self.pollingInterval);
							return;
						}
						const url = _waCheckUrl.replace('__TOKEN__', self.token);
						fetch(url)
							.then(function(r) {
								return r.json();
							})
							.then(function(res) {
								if (res.verified) {
									self.isVerified = true;
									self.isFailed = false;
									self.isTimedOut = false;
									clearInterval(self.pollingInterval);
									self.clearTimeout();
									document.getElementById('phone').value = res.phone;
									const btn = document.getElementById('btn-test-wa');
									if (btn) {
										btn.className =
											'inline-flex items-center gap-1.5 rounded border border-emerald-500 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 cursor-not-allowed';
										btn.disabled = true;
										btn.innerHTML =
											'<i class="fa-solid fa-circle-check text-sm"></i> Terverifikasi';
									}
								} else if (res.failed) {
									self.isFailed = true;
									self.failedMessage = res.message || 'Verifikasi gagal.';
									clearInterval(self.pollingInterval);
									self.clearTimeout();
								}
							})
							.catch(function() {});
					}, 3000);
				},
				startTimeout() {
					this.clearTimeout();
					const self = this;
					this.timeoutTimer = setTimeout(function() {
						if (!self.isVerified && !self.isFailed) {
							self.isTimedOut = true;
							clearInterval(self.pollingInterval);
						}
					}, 5 * 60 * 1000); // 5 menit
				},
				clearTimeout() {
					if (this.timeoutTimer) {
						window.clearTimeout(this.timeoutTimer);
						this.timeoutTimer = null;
					}
				},
				closeModal() {
					this.showVerifyModal = false;
					clearInterval(this.pollingInterval);
					this.clearTimeout();
				},
				retryVerification() {
					this.closeModal();
					openVerifyModal();
				}
			};
		}

		function openVerifyModal() {
			const phone = document.getElementById('phone').value.trim();
			const nameEl = document.getElementById('name');
			const emailEl = document.getElementById('email');
			const name = nameEl ? nameEl.value.trim() : '';
			const email = emailEl ? emailEl.value.trim() : '';
			const btn = document.getElementById('btn-test-wa');

			if (!phone) {
				alert('⚠️ Masukkan nomor telepon terlebih dahulu.');
				return;
			}

			btn.disabled = true;
			btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i> Memuat...';

			fetch('{{ route('master.users.test-wa', [], false) }}', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': '{{ csrf_token() }}',
						'Accept': 'application/json',
					},
					body: JSON.stringify({
						phone: phone,
						name: name,
						email: email
					}),
				})
				.then(function(r) {
					return r.json();
				})
				.then(function(data) {
					btn.disabled = false;
					btn.innerHTML = '<i class="fa-brands fa-whatsapp text-sm"></i> Verifikasi';

					if (!data.success) {
						alert('❌ ' + (data.message || 'Terjadi kesalahan.'));
						return;
					}

					window.dispatchEvent(new CustomEvent('open-verify', {
						detail: data
					}));
				})
				.catch(function() {
					btn.disabled = false;
					btn.innerHTML = '<i class="fa-brands fa-whatsapp text-sm"></i> Verifikasi';
					alert('❌ Terjadi kesalahan jaringan. Coba lagi.');
				});
		}

		function copyTemplate() {
			const text = document.getElementById('modal-verify-template').textContent;
			navigator.clipboard.writeText(text).then(function() {
				const btn = document.getElementById('btn-copy-template');
				btn.innerHTML = '<i class="fa-solid fa-check"></i> Tersalin!';
				setTimeout(function() {
					btn.innerHTML = '<i class="fa-regular fa-copy"></i> Salin';
				}, 2000);
			});
		}
	</script>
@endpush

