<div
	x-data="{
		remaining: @entangle('lockoutSeconds'),
		timer: null,
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

	{{-- Banner penguncian dengan hitung mundur yang terlihat --}}
	<div x-show="remaining > 0" x-cloak
		class="mt-3 flex items-center gap-3 rounded border border-amber-200 bg-amber-50 p-3.5 text-sm shadow-sm" role="status">
		<svg class="h-5 w-5 shrink-0 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
			stroke-width="2" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
		</svg>
		<p class="font-medium text-amber-800">
			Terlalu banyak percobaan gagal. Coba lagi dalam
			<span class="font-mono text-base font-bold text-amber-900" x-text="remaining"></span> detik.
		</p>
	</div>

	{{-- Autofill browser mengisi DOM tanpa memicu event, sehingga state Livewire tetap kosong
	     dan login gagal. Nilai hasil autofill dipancing sekali saat load agar ikut ter-sync.
	     Sengaja TIDAK memakai autocomplete="off" supaya password manager tetap menawarkan simpan. --}}
	<form wire:submit="login" class="mt-6 space-y-4"
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
				class="mt-2 w-full rounded border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
				placeholder="Masukkan username">
		</div>

		<div x-data="{ show: false }">
			<label for="password" class="block text-sm font-semibold text-sky-50">Password</label>
			<div class="relative mt-2">
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
					class="w-full rounded border border-sky-100/30 bg-sky-100/15 px-4 py-3 pr-12 text-sm text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
					placeholder="Masukkan password">
				<button type="button" @click="show = !show" tabindex="-1"
					:aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
					class="absolute inset-y-0 right-0 flex items-center pr-4 text-sky-100/70 transition hover:text-white focus:outline-none">
					<i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
				</button>
			</div>
		</div>

		{{-- Ingat saya: toggle switch bertema kaca (komponen checkbox default bertema terang, tak cocok di sini) --}}
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

		<button
			type="submit"
			:disabled="remaining > 0"
			wire:loading.attr="disabled"
			wire:target="login"
			class="w-full rounded bg-sky-300 px-4 py-3 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 shadow-[0_18px_40px_rgba(125,211,252,0.25)] transition hover:-translate-y-0.5 hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0">
			{{-- Tiga keadaan tombol: terkunci (anti-spam), sedang proses, dan normal. --}}
			<span wire:loading.remove wire:target="login" x-show="remaining > 0" x-cloak
				class="inline-flex items-center justify-center gap-2">
				<i class="fa-solid fa-spinner fa-spin"></i>
				Tunggu <span x-text="remaining"></span> detik
			</span>
			<span wire:loading.remove wire:target="login" x-show="remaining <= 0">Login</span>
			<span wire:loading wire:target="login" x-cloak class="inline-flex items-center justify-center gap-2">
				<i class="fa-solid fa-spinner fa-spin"></i>
				Memproses…
			</span>
		</button>
	</form>
</div>
