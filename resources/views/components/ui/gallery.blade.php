{{-- Galeri gambar global (lightbox) — dipasang sekali di layout.
     Dibuka lewat <x-ui.gallery-link>: gambar BARU dimuat saat tautan diklik (spinner
     selama memuat). Tautan dengan data-gallery yang sama menjadi satu rangkaian
     (sebelumnya/berikutnya). Ctrl/Cmd/klik tengah tetap membuka tab baru seperti biasa. --}}
<div x-data="galleryViewer()" x-show="open" x-cloak
	x-trap.inert.noscroll="open"
	@click.window="onLinkClick($event)"
	@keydown.window="onKey($event)"
	x-transition:enter="transition ease-out duration-150"
	x-transition:enter-start="opacity-0"
	x-transition:enter-end="opacity-100"
	x-transition:leave="transition ease-in duration-100"
	x-transition:leave-start="opacity-100"
	x-transition:leave-end="opacity-0"
	role="dialog" aria-modal="true" aria-label="Galeri gambar"
	class="fixed inset-0 z-9998 flex flex-col bg-slate-950/97 text-white">

	{{-- Bilah atas: urutan, keterangan, aksi --}}
	<div class="flex items-center gap-2 px-3 py-2 sm:px-4">
		<span x-show="items.length > 1" x-text="(index + 1) + ' / ' + items.length"
			class="shrink-0 rounded bg-white/10 px-2 py-0.5 text-xs font-semibold tabular-nums"></span>
		<p class="min-w-0 flex-1 truncate text-sm font-medium text-white/90" x-text="current.caption"></p>
		<button type="button" @click="toggleZoom()" x-show="state === 'loaded'"
			:aria-label="zoomed ? 'Perkecil gambar' : 'Perbesar gambar'" :title="zoomed ? 'Perkecil' : 'Perbesar'"
			class="flex size-9 shrink-0 items-center justify-center rounded text-white/80 transition hover:bg-white/10 hover:text-white">
			<i class="fa-solid" :class="zoomed ? 'fa-magnifying-glass-minus' : 'fa-magnifying-glass-plus'"></i>
		</button>
		<a :href="current.src" download x-show="current.src" aria-label="Unduh gambar" title="Unduh"
			class="flex size-9 shrink-0 items-center justify-center rounded text-white/80 transition hover:bg-white/10 hover:text-white">
			<i class="fa-solid fa-download"></i>
		</a>
		<button type="button" @click="close()" x-ref="close" aria-label="Tutup galeri" title="Tutup (Esc)"
			class="flex size-9 shrink-0 items-center justify-center rounded text-white/80 transition hover:bg-white/10 hover:text-white">
			<i class="fa-solid fa-xmark text-lg"></i>
		</button>
	</div>

	{{-- Panggung gambar: klik area kosong menutup galeri; geser (swipe) untuk pindah gambar.
	     Hanya lapisan dalam yang digulir saat diperbesar; status & navigasi tetap di tempat. --}}
	<div class="relative min-h-0 flex-1">
		<div x-ref="stage" class="absolute inset-0" :class="zoomed ? 'overflow-auto' : 'overflow-hidden'"
			@click.self="backdropClose()"
			@touchstart.passive="touchStart($event)" @touchend="touchEnd($event)"
			@pointerdown="panStart($event)" @pointermove="panMove($event)" @pointerup="panEnd()" @pointerleave="panEnd()">
			<div class="flex min-h-full p-4 sm:px-16" :class="zoomed ? 'w-max min-w-full' : 'h-full w-full'" @click.self="backdropClose()">
				<img x-ref="img" :src="src || null" :alt="current.caption" draggable="false"
					x-show="state === 'loaded'" x-on:load="state = 'loaded'" x-on:error="if (src) state = 'error'"
					@click="toggleZoom($event)"
					class="m-auto select-none rounded shadow-2xl"
					:class="zoomed ? (panning ? 'max-w-none cursor-grabbing' : 'max-w-none cursor-grab') : 'max-w-full object-contain cursor-zoom-in'"
					:style="zoomed ? `width: ${zoomWidth}px` : 'max-height: calc(100dvh - 5.5rem)'">
			</div>
		</div>

		{{-- Memuat --}}
		<div x-show="state === 'loading'" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-3 text-white/80">
			<i class="fa-solid fa-circle-notch fa-spin text-3xl"></i>
			<span class="text-sm">Memuat gambar…</span>
		</div>

		{{-- Gagal --}}
		<div x-show="state === 'error'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-center" @click.self="backdropClose()">
			<i class="fa-solid fa-image text-3xl text-white/40"></i>
			<p class="text-sm text-white/80">Gambar gagal dimuat. Periksa koneksi lalu coba lagi.</p>
			<button type="button" @click="load(true)"
				class="rounded border border-white/30 px-3 py-1.5 text-xs font-semibold transition hover:bg-white/10">
				<i class="fa-solid fa-rotate-right mr-1"></i> Coba lagi
			</button>
		</div>

		{{-- Navigasi --}}
		<template x-if="items.length > 1">
			<div>
				<button type="button" @click="go(index - 1)" aria-label="Gambar sebelumnya"
					class="absolute left-2 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:left-4">
					<i class="fa-solid fa-chevron-left"></i>
				</button>
				<button type="button" @click="go(index + 1)" aria-label="Gambar berikutnya"
					class="absolute right-2 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:right-4">
					<i class="fa-solid fa-chevron-right"></i>
				</button>
			</div>
		</template>
	</div>
</div>

@once
	<script>
		function galleryViewer() {
			return {
				open: false,
				items: [],
				index: 0,
				src: '',
				state: 'idle', // idle | loading | loaded | error
				zoomed: false,
				zoomWidth: 0,
				panning: false,
				pan: null,
				touch: null,
				init() {
					// Pindah halaman (wire:navigate) saat galeri terbuka: tutup dulu.
					document.addEventListener('livewire:navigating', () => this.close());
				},
				get current() {
					return this.items[this.index] || { src: '', caption: '' };
				},
				onLinkClick(e) {
					const link = e.target.closest && e.target.closest('a[data-gallery]');
					if (!link || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
					e.preventDefault();
					// Kumpulkan rangkaian saat dibuka (DOM Livewire bisa berubah); satu gambar
					// yang ditautkan dua kali (mis. di halaman & modal) cukup tampil sekali.
					const group = link.dataset.gallery;
					const seen = new Set();
					this.items = [...document.querySelectorAll('a[data-gallery]')]
						.filter((a) => a.dataset.gallery === group && !seen.has(a.href) && seen.add(a.href))
						.map((a) => ({ src: a.href, caption: a.dataset.caption || decodeURIComponent(a.href.split('/').pop()) }));
					this.index = Math.max(0, this.items.findIndex((it) => it.src === link.href));
					this.open = true;
					this.load();
				},
				// Kosongkan src dulu agar gambar yang sama tetap memicu event load.
				load(retry = false) {
					this.state = 'loading';
					this.zoomed = false;
					this.src = '';
					const src = this.current.src;
					this.$nextTick(() => {
						this.src = retry ? src + (src.includes('?') ? '&' : '?') + 'r=' + Date.now() : src;
					});
				},
				go(i) {
					if (this.items.length < 2) return;
					this.index = (i + this.items.length) % this.items.length;
					this.load();
				},
				close() {
					if (!this.open) return;
					this.open = false;
					this.zoomed = false;
					this.src = ''; // batalkan unduhan yang belum selesai
					this.state = 'idle';
				},
				// Klik area kosong menutup galeri, kecuali akhir dari menggeser gambar.
				backdropClose() {
					if (!(this.pan && this.pan.moved)) this.close();
				},
				onKey(e) {
					if (!this.open) return;
					if (e.key === 'Escape') { e.preventDefault(); this.close(); }
					if (e.key === 'ArrowLeft') { e.preventDefault(); this.go(this.index - 1); }
					if (e.key === 'ArrowRight') { e.preventDefault(); this.go(this.index + 1); }
				},
				// Perbesar ke ukuran asli (minimal 2x tampilan), berpusat di titik yang diklik.
				toggleZoom(e) {
					if (this.state !== 'loaded') return;
					if (this.pan && this.pan.moved) return; // akhir dari geser, bukan klik
					const img = this.$refs.img;
					const stage = this.$refs.stage;
					if (this.zoomed) {
						this.zoomed = false;
						return;
					}
					const fx = e ? e.offsetX / img.clientWidth : 0.5;
					const fy = e ? e.offsetY / img.clientHeight : 0.5;
					this.zoomWidth = Math.min(Math.max(img.naturalWidth, img.clientWidth * 2), 6000);
					this.zoomed = true;
					this.$nextTick(() => {
						stage.scrollLeft = img.offsetLeft + fx * img.clientWidth - stage.clientWidth / 2;
						stage.scrollTop = img.offsetTop + fy * img.clientHeight - stage.clientHeight / 2;
					});
				},
				// Geser gambar yang diperbesar dengan mouse (sentuh memakai gulir bawaan).
				panStart(e) {
					if (!this.zoomed || e.pointerType !== 'mouse' || e.button !== 0) return;
					const stage = this.$refs.stage;
					this.pan = { x: e.clientX, y: e.clientY, left: stage.scrollLeft, top: stage.scrollTop, moved: false };
					this.panning = true;
				},
				panMove(e) {
					if (!this.panning) return;
					const dx = e.clientX - this.pan.x;
					const dy = e.clientY - this.pan.y;
					if (Math.abs(dx) + Math.abs(dy) > 4) this.pan.moved = true;
					this.$refs.stage.scrollLeft = this.pan.left - dx;
					this.$refs.stage.scrollTop = this.pan.top - dy;
				},
				panEnd() {
					this.panning = false;
					// Biarkan event click (toggleZoom) membaca status moved dulu.
					setTimeout(() => { this.pan = null; }, 0);
				},
				touchStart(e) {
					this.touch = e.touches.length === 1 ? { x: e.touches[0].clientX, y: e.touches[0].clientY } : null;
				},
				touchEnd(e) {
					if (!this.touch || this.zoomed) return;
					const dx = e.changedTouches[0].clientX - this.touch.x;
					const dy = e.changedTouches[0].clientY - this.touch.y;
					this.touch = null;
					if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) this.go(this.index + (dx < 0 ? 1 : -1));
				},
			};
		}
	</script>
@endonce
