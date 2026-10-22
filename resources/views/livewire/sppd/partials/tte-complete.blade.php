{{-- Animasi TTE selesai: layar penuh di atas halaman yang diburamkan (tetap terlihat samar).
     Muncul sekali saat seluruh dokumen penanda tangan ini berhasil ditandatangani (event
     tte-completed dari SppdShow). Ditutup lewat tombol, Esc, atau klik di luar konten. --}}
<div class="contents" x-data="{
		open: false,
		alive: false,
		docs: [],
		final: false,
		time: '',
		show(detail) {
			this.docs = detail.docs || [];
			this.final = !! detail.final;
			this.time = detail.time || '';
			this.alive = true;
			this.open = true;
			document.body.style.overflow = 'hidden';
			setTimeout(() => document.getElementById('tte-done-button')?.focus({ preventScroll: true }), 1400);
		},
		close() {
			this.open = false;
			document.body.style.overflow = (window.__modalOpenCount || 0) > 0 ? 'hidden' : '';
			// Konten dilepas setelah transisi keluar agar animasinya diputar ulang saat muncul lagi.
			setTimeout(() => { if (! this.open) this.alive = false; }, 250);
		},
	}" @tte-completed.window="show($event.detail)">
	<template x-teleport="body">
		<div x-show="open" style="display: none;"
			x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
			x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
			class="tte-done fixed inset-0 z-70 flex items-center justify-center overflow-y-auto bg-slate-950/50 px-4 py-8 backdrop-blur-md"
			role="dialog" aria-modal="true" aria-labelledby="tte-done-title"
			@click.self="close()" @keydown.escape.window="open && close()">
			<template x-if="alive">
				<div class="flex w-full max-w-md flex-col items-center text-center" @click.self="close()">

					{{-- Segel tanda tangan: cincin tergambar, cakram muncul, centang tergambar,
					     lalu gelombang & percikan menyebar dari tengah. --}}
					<div class="relative size-44 sm:size-52" aria-hidden="true">
						<span class="tte-ripple absolute inset-[18%] rounded-full border-2 border-emerald-300/70" style="--delay: 0.95s"></span>
						<span class="tte-ripple absolute inset-[18%] rounded-full border-2 border-emerald-300/50" style="--delay: 1.35s"></span>

						@foreach (range(0, 11) as $i)
							<span class="tte-spark absolute left-1/2 top-1/2 -ml-[3px] -mt-[3px] size-1.5 rounded-full {{ $i % 2 ? 'bg-emerald-200' : 'bg-primary-200' }}"
								style="--a: {{ $i * 30 + 15 }}deg; --delay: {{ 0.9 + ($i % 3) * 0.05 }}s"></span>
						@endforeach

						<svg viewBox="0 0 160 160" class="relative size-full overflow-visible">
							<defs>
								<path id="tte-seal-arc" d="M80,80 m-67,0 a67,67 0 1,1 134,0 a67,67 0 1,1 -134,0" />
							</defs>
							{{-- Tulisan melingkar segel. --}}
							<text class="tte-fade fill-emerald-100/80 font-mono text-[8.5px] font-semibold uppercase" style="--delay: 1.05s">
								<textPath href="#tte-seal-arc" textLength="415" lengthAdjust="spacing">Tanda Tangan Elektronik · Pemerintah Kota Kendari ·</textPath>
							</text>
							<circle cx="80" cy="80" r="57" fill="none" class="tte-fade stroke-white/25" stroke-width="1" stroke-dasharray="2 4" style="--delay: 0.6s" />
							{{-- Alas gelap di bawah cincin agar segel tegas di atas latar yang buram. --}}
							<circle cx="80" cy="80" r="54" class="tte-fade fill-slate-950/60" style="--delay: 0.1s" />
							<circle cx="80" cy="80" r="51" fill="none" pathLength="1" transform="rotate(-90 80 80)"
								class="tte-draw stroke-emerald-300" stroke-width="3" stroke-linecap="round" style="--dur: 0.75s; --delay: 0.15s" />
							<circle cx="80" cy="80" r="44" class="tte-pop fill-emerald-500" style="--delay: 0.5s" />
							<path d="M61 81 l13 13 l26 -27" fill="none" pathLength="1"
								class="tte-draw stroke-white" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" style="--dur: 0.45s; --delay: 0.8s" />
						</svg>
					</div>

					<h2 id="tte-done-title" class="tte-rise mt-4 text-balance text-2xl font-bold tracking-tight text-white sm:text-3xl" style="--delay: 1.05s">
						Tanda Tangan Elektronik Selesai
					</h2>
					<p class="tte-rise mt-1.5 text-sm text-slate-200" style="--delay: 1.15s">
						<span class="font-mono font-semibold text-white" x-text="docs.length"></span> dokumen berhasil ditandatangani secara elektronik.
					</p>

					{{-- Dokumen yang ditandatangani, muncul beruntun. --}}
					<ul class="tte-rise mt-5 w-full divide-y divide-white/10 rounded-lg bg-slate-900/55 text-left ring-1 ring-white/10" style="--delay: 1.2s">
						<template x-for="(doc, i) in docs" :key="i">
							<li class="tte-rise flex items-center gap-3 px-4 py-2.5" :style="`--delay: ${1.25 + i * 0.09}s`">
								<span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/40">
									<i class="fa-solid fa-check text-[10px]"></i>
								</span>
								<span class="min-w-0 flex-1 leading-tight">
									<span class="block text-sm font-semibold text-white" x-text="doc.label"></span>
									<span class="block truncate text-xs text-slate-300" x-show="doc.name" x-text="doc.name"></span>
								</span>
							</li>
						</template>
					</ul>

					<p class="tte-rise mt-4 text-pretty text-xs text-slate-300" :style="`--delay: ${1.35 + docs.length * 0.09}s`">
						<span x-text="final ? 'Seluruh tahap persetujuan selesai. SPPD telah disetujui.' : 'SPPD diteruskan ke tahap persetujuan berikutnya.'"></span>
						<span class="mt-0.5 block font-mono text-slate-300" x-text="time"></span>
					</p>

					<x-ui.button variant="secondary" id="tte-done-button" @click="close()" class="tte-rise mt-5 min-w-40 justify-center"
						::style="`--delay: ${1.45 + docs.length * 0.09}s`">
						Lanjutkan
					</x-ui.button>
				</div>
			</template>
		</div>
	</template>
</div>
