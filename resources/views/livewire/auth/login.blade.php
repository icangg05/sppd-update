<div
	x-data="{
		remaining: @entangle('lockoutSeconds'),
		timer: null,
		captcha: @js($turnstileSiteKey ? 'idle' : 'verified'),
		startTimer() {
			if (this.timer) clearInterval(this.timer);
			if (this.remaining <= 0) return;
			this.timer = setInterval(() => {
				this.remaining--;
				if (this.remaining <= 0) { clearInterval(this.timer); this.timer = null; }
			}, 1000);
		},
	}"
	x-init="startTimer(); $watch('remaining', (val, old) => { if (val > (old ?? 0)) startTimer(); })">

	{{-- Kesalahan login ditampilkan lewat toast (lihat komponen Login), bukan alert. --}}

	{{-- Banner akun diblokir: hanya super_admin yang dapat membuka blokir --}}
	@if ($blocked)
		<div class="mb-4 flex items-start gap-3 rounded border border-rose-200 bg-rose-50 p-3.5 text-sm shadow-sm" role="alert">
			<i class="fa-solid fa-user-lock mt-0.5 shrink-0 text-base text-rose-600"></i>
			<div class="leading-snug">
				<p class="font-bold text-rose-800">Akun diblokir</p>
				<p class="mt-0.5 text-rose-700">
					Akun ini diblokir karena berulang kali salah memasukkan password.
					Harap hubungi <strong>Super Admin</strong> untuk membuka blokir.
				</p>
			</div>
		</div>
	@endif

	{{-- Alert akun dikunci sementara: hilang sendiri saat hitung mundur selesai --}}
	<div x-show="remaining > 0" x-cloak
		class="mb-4 flex items-start gap-3 rounded border border-amber-200 bg-amber-50 p-3.5 text-sm shadow-sm" role="alert">
		<i class="fa-solid fa-clock mt-0.5 shrink-0 text-base text-amber-600"></i>
		<div class="leading-snug">
			<p class="font-bold text-amber-800">Akun Dikunci Sementara</p>
			<p class="mt-0.5 text-amber-700">
				Terlalu banyak percobaan gagal.
				Silakan tunggu <span class="font-mono font-bold text-amber-900" x-text="remaining"></span> detik.
			</p>
		</div>
	</div>

	{{-- Pesan login lainnya (salah password, field kosong, akun nonaktif) lewat toast. --}}

	{{-- Autofill browser mengisi DOM tanpa memicu event, sehingga state Livewire tetap kosong
	     dan login gagal. Nilai hasil autofill dipancing sekali saat load agar ikut ter-sync.
	     Sengaja TIDAK memakai autocomplete="off" supaya password manager tetap menawarkan simpan. --}}
	<form wire:submit="login" class="space-y-4 short:space-y-3"
		x-init="window.addEventListener('load', () => setTimeout(() =>
			$el.querySelectorAll('input:not([type=checkbox])').forEach(
				i => i.value && i.dispatchEvent(new Event('input', { bubbles: true }))
			), 100))">
		<div>
			<label for="username" class="block text-sm font-semibold text-sky-50">Username</label>
			<input
				wire:model="username"
				type="text"
				id="username"
				name="username"
				required
				autofocus
				autocomplete="username"
				class="mt-2 w-full rounded border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm short:mt-1.5 short:py-2.5 text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
				placeholder="Masukkan username">
		</div>

		<div x-data="{ show: false }">
			<label for="password" class="block text-sm font-semibold text-sky-50">Password</label>
			<div class="relative mt-2 short:mt-1.5">
				<input
					wire:model="password"
					{{-- type statis agar password manager mengenali form ini sebelum Alpine init;
					     :type mengambil alih setelahnya untuk tombol lihat/sembunyikan. --}}
					type="password"
					:type="show ? 'text' : 'password'"
					id="password"
					name="password"
					required
					autocomplete="current-password"
					class="w-full rounded border border-sky-100/30 bg-sky-100/15 px-4 py-3 pr-12 text-sm short:py-2.5 text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
					placeholder="Masukkan password">
				<button type="button" @click="show = !show" tabindex="-1"
					:aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
					class="absolute inset-y-0 right-0 flex items-center pr-4 text-sky-100/70 transition hover:text-white focus:outline-none">
					<i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
				</button>
			</div>
		</div>

		{{-- Ingat saya: toggle switch bertema kaca (komponen checkbox default bertema terang, tak cocok di sini).
		     Sengaja membulat (track pil + knob lingkaran), pengecualian dari radius 2px — lihat DESIGN.md. --}}
		<label class="flex w-fit cursor-pointer items-center gap-3 text-xs font-medium text-sky-50/90 select-none">
			<span class="relative inline-flex shrink-0">
				<input wire:model="remember" type="checkbox" class="peer sr-only">
				<span
					class="h-5 w-9 rounded-full bg-sky-100/20 ring-1 ring-inset ring-sky-100/30 transition-colors peer-checked:bg-sky-300/80 peer-focus-visible:ring-2 peer-focus-visible:ring-sky-200"></span>
				<span
					class="absolute left-0.5 top-0.5 size-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
			</span>
			Ingat saya
		</label>

		{{-- Cloudflare Turnstile. Managed widget tidak bisa dipaksa menampilkan checkbox, jadi kotak centang
		     ini yang memulai tantangan (execution: execute); widget Cloudflare hanya muncul bila meminta
		     interaksi. wire:ignore menjaga iframe dan status centang dari morph Livewire.
		     Lebar minimum widget normal 300px; kartu di layar HP lebih sempit, jadi pakai ukuran compact. --}}
		@if ($turnstileSiteKey)
			@assets
				<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" defer></script>
			@endassets

			<div wire:ignore
				x-data="{
					widgetId: null,
					challenge: false,
					mount(tries = 0) {
						if (! window.turnstile) {
							if (tries >= 100) { this.captcha = 'failed'; return; }
							setTimeout(() => this.mount(tries + 1), 100);
							return;
						}
						this.widgetId = turnstile.render(this.$refs.widget, {
							sitekey: @js($turnstileSiteKey),
							theme: 'dark',
							language: 'id',
							size: this.$refs.widget.clientWidth < 300 ? 'compact' : 'flexible',
							execution: 'execute',
							appearance: 'interaction-only',
							retry: 'never',
							'refresh-expired': 'never',
							'refresh-timeout': 'never',
							callback: (token) => this.setToken(token),
							'before-interactive-callback': () => { this.challenge = true; },
							'expired-callback': () => this.reset(),
							'timeout-callback': () => this.reset(),
							'error-callback': () => { this.reset('failed'); return true; },
						});
						if (this.captcha === 'verifying') this.tick();
					},
					tick() {
						this.captcha = 'verifying';
						if (this.widgetId !== null) turnstile.execute(this.$refs.widget);
					},
					setToken(token) {
						this.$wire.turnstileToken = token;
						if (token) this.captcha = 'verified';
					},
					reset(state = 'idle') {
						this.setToken('');
						this.captcha = state;
						this.challenge = false;
						if (this.widgetId !== null) turnstile.reset(this.widgetId);
					},
				}"
				x-init="mount()"
				@turnstile-reset.window="reset()">
				<label
					class="flex items-center gap-3 rounded border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm font-medium text-sky-50 backdrop-blur-md transition select-none short:py-2.5"
					:class="captcha === 'idle' || (captcha === 'failed' && widgetId !== null) ? 'cursor-pointer hover:bg-sky-100/20' : 'cursor-default'">
					<span class="relative inline-flex shrink-0">
						<input type="checkbox" class="peer sr-only" @change="tick()"
							:checked="captcha === 'verifying' || captcha === 'verified'"
							:disabled="captcha === 'verifying' || captcha === 'verified' || (captcha === 'failed' && widgetId === null)">
						<span class="flex size-5 items-center justify-center rounded border transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-sky-200"
							:class="captcha === 'verified' ? 'border-emerald-300 bg-emerald-300 text-slate-950' : 'border-sky-100/70 bg-slate-950/20 text-sky-100'">
							<i class="fa-solid fa-spinner fa-spin text-[11px]" x-show="captcha === 'verifying'" x-cloak></i>
							<i class="fa-solid fa-check text-xs" x-show="captcha === 'verified'" x-cloak></i>
						</span>
					</span>
					<span x-text="captcha === 'verified' ? 'Terverifikasi' : (captcha !== 'verifying' ? 'Saya bukan robot' : (challenge ? 'Selesaikan di bawah' : 'Memverifikasi…'))">Saya bukan robot</span>
					<span class="ml-auto inline-flex items-center gap-1.5 text-xs font-normal text-sky-100/80">
						<i class="fa-brands fa-cloudflare"></i>
						Cloudflare
					</span>
				</label>
				<div x-ref="widget" class="leading-[0]" :class="challenge && 'mt-2'"></div>
				<p x-show="captcha === 'failed'" x-cloak class="mt-2 text-xs leading-5 text-rose-200" role="alert"
					x-text="widgetId === null ? 'Verifikasi keamanan gagal dimuat. Periksa koneksi internet, lalu muat ulang halaman.' : 'Verifikasi keamanan gagal. Silakan centang lagi.'"></p>
			</div>
		@endif

		<button
			type="submit"
			:disabled="remaining > 0 || captcha !== 'verified'"
			wire:loading.attr="disabled"
			wire:target="login"
			class="w-full rounded bg-sky-300 px-4 py-3 text-sm font-bold short:py-2.5 uppercase tracking-[0.2em] text-slate-950 shadow-[0_18px_40px_rgba(125,211,252,0.25)] transition hover:-translate-y-0.5 hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0">
			{{-- Tiga keadaan tombol: terkunci (anti-spam), sedang proses, dan normal (nonaktif sampai kotak verifikasi dicentang). --}}
			<span wire:loading.remove wire:target="login" x-show="remaining > 0" x-cloak
				class="inline-flex items-center justify-center gap-2">
				<i class="fa-solid fa-spinner fa-spin"></i>
				Tunggu <span x-text="remaining"></span> detik
			</span>
			<span wire:loading.remove wire:target="login" x-show="remaining <= 0" x-cloak>Login</span>
			<span wire:loading wire:target="login" x-cloak class="inline-flex items-center justify-center gap-2">
				<i class="fa-solid fa-spinner fa-spin"></i>
				Memproses…
			</span>
		</button>
	</form>
</div>
