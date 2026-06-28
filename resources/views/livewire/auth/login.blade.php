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

	{{-- Peringatan kesalahan --}}
	@if ($errors->any())
		<div class="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-3.5 text-sm shadow-sm" role="alert">
			<svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
				stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
			</svg>
			<div>
				<h3 class="mb-1 text-xs font-bold text-red-900 md:text-sm">Gagal Melakukan Autentikasi</h3>
				<ul class="list-none space-y-1 text-xs font-medium text-red-700 md:text-sm">
					@foreach ($errors->all() as $error)
						<li class="flex items-center gap-1.5">
							<span class="inline-block h-1 w-1 rounded-full bg-red-600"></span>
							{{ $error }}
						</li>
					@endforeach
				</ul>
			</div>
		</div>
	@endif

	{{-- Banner penguncian dengan hitung mundur yang terlihat --}}
	<div x-show="remaining > 0" x-cloak
		class="mt-3 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3.5 text-sm shadow-sm" role="status">
		<svg class="h-5 w-5 shrink-0 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
			stroke-width="2" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
		</svg>
		<p class="font-medium text-amber-800">
			Terlalu banyak percobaan gagal. Coba lagi dalam
			<span class="font-mono text-base font-bold text-amber-900" x-text="remaining"></span> detik.
		</p>
	</div>

	<form wire:submit="login" class="mt-6 space-y-4">
		<div>
			<label for="username" class="block text-sm font-semibold text-sky-50">Username</label>
			<input
				wire:model="username"
				type="text"
				id="username"
				required
				autofocus
				autocomplete="username"
				class="mt-2 w-full rounded-2xl border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
				placeholder="Masukkan username">
		</div>

		<div>
			<label for="password" class="block text-sm font-semibold text-sky-50">Password</label>
			<input
				wire:model="password"
				type="password"
				id="password"
				required
				autocomplete="current-password"
				class="mt-2 w-full rounded-2xl border border-sky-100/30 bg-sky-100/15 px-4 py-3 text-sm text-white placeholder:text-sky-100/70 outline-none backdrop-blur-md transition focus:border-sky-200/70 focus:bg-sky-100/20"
				placeholder="Masukkan password">
		</div>

		<label class="flex items-center gap-2 text-xs font-medium text-sky-50/90 select-none">
			<input wire:model="remember" type="checkbox"
				class="size-4 rounded border-sky-100/40 bg-sky-100/15 text-sky-400 focus:ring-sky-300">
			Ingat saya
		</label>

		<button
			type="submit"
			:disabled="remaining > 0"
			wire:loading.attr="disabled"
			wire:target="login"
			class="w-full rounded-2xl bg-sky-300 px-4 py-3 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 shadow-[0_18px_40px_rgba(125,211,252,0.25)] transition hover:-translate-y-0.5 hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0">
			<span wire:loading.remove wire:target="login">Login</span>
			<span wire:loading wire:target="login" x-cloak>Memproses…</span>
		</button>
	</form>
</div>
