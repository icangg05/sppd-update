@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page-title', 'Tambah Pegawai')

@section('content')
	<div class="p-1 space-y-6">

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
									class="flex-1 block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:ring-cyan-500 @error('phone') border-red-400 @enderror"
								/>
								<button
									type="button"
									id="btn-test-wa"
									onclick="testWhatsApp()"
									title="Kirim pesan percobaan ke nomor ini"
									class="inline-flex items-center gap-1.5 rounded border border-green-500 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-600 hover:text-white whitespace-nowrap"
								>
									<i class="fa-brands fa-whatsapp text-sm"></i> Uji Kirim
								</button>
							</div>
							<p class="text-xs text-slate-400 mt-1">
								<i class="fa-solid fa-circle-info mr-1 text-cyan-500"></i>
								Nomor ini akan digunakan untuk mengirim notifikasi WhatsApp terkait pengajuan SPPD.
								Gunakan tombol <strong>Uji Kirim</strong> untuk memverifikasi nomor sebelum menyimpan.
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
	</div>
@endsection

@push('scripts')
<script>
  const WA_COOLDOWN_KEY = 'wa_cooldown_end_{{ auth()->id() }}';
  const COOLDOWN_SECONDS = 60;
  let countdownInterval = null;

  function startCooldown(seconds) {
    const btn = document.getElementById('btn-test-wa');
    const endTime = Date.now() + seconds * 1000;
    localStorage.setItem(WA_COOLDOWN_KEY, endTime.toString());

    btn.disabled = true;

    clearInterval(countdownInterval);
    countdownInterval = setInterval(function () {
      const remaining = Math.ceil((parseInt(localStorage.getItem(WA_COOLDOWN_KEY)) - Date.now()) / 1000);
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        localStorage.removeItem(WA_COOLDOWN_KEY);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-brands fa-whatsapp text-sm"></i> Uji Kirim';
      } else {
        btn.innerHTML = '<i class="fa-solid fa-hourglass-half fa-spin text-sm"></i> Kirim Ulang (' + remaining + ' d)';
      }
    }, 1000);
  }

  function testWhatsApp() {
    const phone = document.getElementById('phone').value.trim();
    const resultEl = document.getElementById('wa-test-result');
    const btn = document.getElementById('btn-test-wa');

    if (!phone) {
      resultEl.textContent = '⚠️ Masukkan nomor telepon terlebih dahulu.';
      resultEl.className = 'mt-1 text-xs font-medium text-amber-600';
      resultEl.classList.remove('hidden');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i> Mengantri...';
    resultEl.classList.add('hidden');

    fetch('{{ route('master.users.test-wa') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ phone }),
    })
    .then(function (r) { return r.json().then(function (data) { return { status: r.status, data }; }); })
    .then(function ({ status, data }) {
      if (data.success) {
        resultEl.textContent = '📤 ' + data.message;
        resultEl.className = 'mt-1 text-xs font-medium text-green-600';
        startCooldown(COOLDOWN_SECONDS);
      } else if (status === 429) {
        resultEl.textContent = '⏳ ' + data.message;
        resultEl.className = 'mt-1 text-xs font-medium text-amber-600';
        startCooldown(data.remaining ?? COOLDOWN_SECONDS);
      } else {
        resultEl.textContent = '❌ ' + data.message;
        resultEl.className = 'mt-1 text-xs font-medium text-red-600';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-brands fa-whatsapp text-sm"></i> Uji Kirim';
      }
      resultEl.classList.remove('hidden');
    })
    .catch(function () {
      resultEl.textContent = '❌ Terjadi kesalahan jaringan. Coba lagi.';
      resultEl.className = 'mt-1 text-xs font-medium text-red-600';
      resultEl.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-brands fa-whatsapp text-sm"></i> Uji Kirim';
    });
  }

  // Restore cooldown on page load
  document.addEventListener('DOMContentLoaded', function () {
    const savedEnd = localStorage.getItem(WA_COOLDOWN_KEY);
    if (savedEnd) {
      const remaining = Math.ceil((parseInt(savedEnd) - Date.now()) / 1000);
      if (remaining > 0) {
        startCooldown(remaining);
      } else {
        localStorage.removeItem(WA_COOLDOWN_KEY);
      }
    }
  });
</script>
@endpush
