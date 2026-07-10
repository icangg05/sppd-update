{{-- Notifikasi Toast global — dipakai layout app maupun guest.
     Kirim dari mana pun: $dispatch('toast', { type, title, message })
     type: success | error | info | warning (default: success). --}}

{{-- Dispatcher flash session (tersembunyi).
     setTimeout memastikan event 'toast' dikirim setelah container di bawah
     selesai mendaftarkan listener @toast.window. --}}
<div x-data x-init="
	@if (session('success')) setTimeout(() => $dispatch('toast', { type: 'success', message: @js(session('success')) }), 60); @endif
	@if (session('status')) setTimeout(() => $dispatch('toast', { type: 'success', message: @js(session('status')) }), 60); @endif
	@if (session('error')) setTimeout(() => $dispatch('toast', { type: 'error', message: @js(session('error')) }), 60); @endif
" class="hidden"></div>

{{-- Toast Notifications container --}}
<div x-data="toastManager()"
	@toast.window="add($event.detail)"
	class="fixed top-3 right-3 left-3 sm:top-5 sm:right-auto sm:left-1/2 sm:-translate-x-1/2 z-9999 flex flex-col items-center gap-2 sm:gap-3.5 sm:w-full sm:max-w-sm pointer-events-none">
	<template x-for="toast in toasts" :key="toast.id">
		<div x-show="toast.show"
			@mouseenter="pause(toast.id)" @mouseleave="resume(toast.id)"
			@touchstart.passive="dragStart(toast.id, $event)"
			@touchmove="dragMove(toast.id, $event)"
			@touchend="dragEnd(toast.id)" @touchcancel="dragEnd(toast.id)"
			x-transition:enter="transition ease-out duration-300 transform"
			x-transition:enter-start="-translate-y-16 opacity-0 scale-90"
			x-transition:enter-end="translate-y-0 opacity-100 scale-100"
			x-transition:leave="transition ease-in duration-300 transform"
			x-transition:leave-start="translate-y-0 opacity-100 scale-100"
			x-transition:leave-end="-translate-y-16 opacity-0 scale-90"
			:style="toast.dragging
				? `transform: translateY(${toast.dragY}px); opacity: ${Math.max(0, 1 + toast.dragY / 80)}`
				: (toast.springing ? 'transform: translateY(0); opacity: 1; transition: transform 0.2s ease, opacity 0.2s ease' : '')"
			class="toast-card relative overflow-hidden pointer-events-auto w-full flex items-center gap-2.5 sm:gap-3 rounded sm:rounded border border-slate-200/80 bg-white px-3 py-2 sm:px-3.5 sm:py-3 shadow-lg shadow-slate-900/5 ring-1 ring-black/5"
			style="touch-action: pan-x;">

			<!-- Icon (lingkaran berisi) -->
			<div class="shrink-0 flex size-6 sm:size-7 items-center justify-center rounded-full text-white shadow-sm"
				:class="{
				    'bg-emerald-500': toast.type === 'success',
				    'bg-rose-500': toast.type === 'error',
				    'bg-primary-500': toast.type === 'info',
				    'bg-amber-500': toast.type === 'warning'
				}">
				<i class="fa-solid text-xs"
					:class="{
					    'fa-check': toast.type === 'success',
					    'fa-xmark': toast.type === 'error',
					    'fa-info': toast.type === 'info',
					    'fa-exclamation': toast.type === 'warning'
					}"></i>
			</div>

			<!-- Title + Message -->
			<div class="flex-1 min-w-0 leading-tight select-none">
				<p class="text-xs sm:text-sm font-bold text-slate-800 truncate" x-text="toast.title"></p>
				<p class="text-[11px] sm:text-xs text-slate-500 mt-0.5 line-clamp-2 sm:line-clamp-none" x-show="toast.message" x-text="toast.message"></p>
			</div>

			<!-- Close button -->
			<button type="button" @click="remove(toast.id)"
				class="shrink-0 self-start -mr-0.5 -mt-0.5 rounded p-1 text-slate-300 transition hover:bg-slate-100 hover:text-slate-500 cursor-pointer">
				<i class="fa-solid fa-xmark text-xs"></i>
			</button>

			<!-- Bilah progres durasi -->
			<div class="toast-progress absolute bottom-0 left-0 h-1 w-full"
				:class="{
				    'bg-emerald-500': toast.type === 'success',
				    'bg-rose-500': toast.type === 'error',
				    'bg-primary-500': toast.type === 'info',
				    'bg-amber-500': toast.type === 'warning'
				}"
				:style="`animation-duration: ${duration}ms`"></div>
		</div>
	</template>
</div>

@once
	<script>
		function toastManager() {
			const DURATION = 4000;
			const defaultTitles = {
				success: 'Berhasil!',
				error: 'Terjadi Kesalahan',
				info: 'Informasi',
				warning: 'Peringatan',
			};
			return {
				toasts: [],
				duration: DURATION,
				add(detail) {
					const id = Date.now() + Math.random().toString(36).substr(2, 9);
					const type = detail.type || 'success';
					this.toasts.push({
						id: id,
						type: type,
						title: detail.title || defaultTitles[type] || defaultTitles.success,
						message: detail.message || '',
						show: false,
						timer: null,
						startedAt: null,
						remaining: DURATION,
						dragY: 0,
						dragStartY: 0,
						dragging: false,
						springing: false
					});

					this.$nextTick(() => {
						const toast = this.toasts.find(t => t.id === id);
						if (toast) {
							toast.show = true;
							this.startTimer(id);
						}
					});
				},
				startTimer(id) {
					const toast = this.toasts.find(t => t.id === id);
					if (!toast) return;
					toast.startedAt = Date.now();
					toast.timer = setTimeout(() => this.remove(id), toast.remaining);
				},
				// Saat di-hover: bekukan hitung mundur penghilangan toast.
				pause(id) {
					const toast = this.toasts.find(t => t.id === id);
					if (!toast || !toast.timer) return;
					clearTimeout(toast.timer);
					toast.timer = null;
					toast.remaining -= Date.now() - toast.startedAt;
				},
				// Saat mouse keluar: lanjutkan dari sisa waktu yang tersimpan.
				resume(id) {
					const toast = this.toasts.find(t => t.id === id);
					if (!toast || toast.timer) return;
					if (toast.remaining <= 0) {
						this.remove(id);
						return;
					}
					this.startTimer(id);
				},
				// Geser ke atas untuk menutup: mulai membekukan timer & catat titik sentuh.
				dragStart(id, event) {
					const toast = this.toasts.find(t => t.id === id);
					if (!toast) return;
					this.pause(id);
					toast.dragging = true;
					toast.dragStartY = event.touches[0].clientY;
				},
				dragMove(id, event) {
					const toast = this.toasts.find(t => t.id === id);
					if (!toast || !toast.dragging) return;
					// Hanya izinkan gerak ke atas (nilai negatif).
					const offset = Math.min(0, event.touches[0].clientY - toast.dragStartY);
					// Cegah halaman ikut ter-scroll saat menggeser toast ke atas.
					if (offset < 0 && event.cancelable) event.preventDefault();
					toast.dragY = offset;
				},
				dragEnd(id) {
					const toast = this.toasts.find(t => t.id === id);
					if (!toast || !toast.dragging) return;
					toast.dragging = false;
					// Tutup jika sudah digeser melewati ambang batas.
					if (toast.dragY < -40) {
						this.remove(id);
					} else {
						// Pantul balik ke posisi semula dengan transisi halus.
						toast.springing = true;
						toast.dragY = 0;
						setTimeout(() => { toast.springing = false; }, 200);
						this.resume(id);
					}
				},
				remove(id) {
					const index = this.toasts.findIndex(t => t.id === id);
					if (index !== -1) {
						if (this.toasts[index].timer) {
							clearTimeout(this.toasts[index].timer);
							this.toasts[index].timer = null;
						}
						this.toasts[index].show = false;
						setTimeout(() => {
							this.toasts = this.toasts.filter(t => t.id !== id);
						}, 300);
					}
				}
			};
		}
	</script>
@endonce
