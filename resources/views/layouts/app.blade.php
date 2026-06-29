<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>@yield('title', 'Dashboard') - {{ config('app.name', 'SPPD') }}</title>

	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
		crossorigin="anonymous" referrerpolicy="no-referrer" />

	<script src="{{ asset('js/jquery-4.0.0.min.js') }}"></script>

	@vite(['resources/css/app.css', 'resources/js/app.js'])

	@livewireStyles
</head>

<body class="h-full text-slate-900 antialiased bg-slate-50">

	{{-- Sidebar Component --}}
	@include('components.sidebar')

	{{-- Sidebar Overlay untuk Mobile --}}
	<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-950/40 lg:hidden" onclick="toggleSidebar()"></div>

	{{-- Main Content Wrapper (Geser kanan otomatis saat desktop lewat lg:ml-64) --}}
	<div class="flex min-h-screen flex-col lg:ml-64">

		{{-- Header Component --}}
		@include('components.header')

		{{-- Main Page Content --}}
		<main class="flex-1 p-4 sm:p-5 lg:p-6">

			{{-- Hidden Session Dispatcher inside dynamic area.
			     setTimeout memastikan event 'toast' dikirim setelah container toast
			     (di akhir body) selesai mendaftarkan listener @toast.window. --}}
			<div x-data x-init="
				@if (session('success')) setTimeout(() => $dispatch('toast', { type: 'success', message: @js(session('success')) }), 60); @endif
				@if (session('error')) setTimeout(() => $dispatch('toast', { type: 'error', message: @js(session('error')) }), 60); @endif
			" class="hidden"></div>

			@if (session('error_details'))
				<script>
					console.log('TTE error details:', @json(session('error_details')));
				</script>
			@endif

			{{ $slot ?? '' }}
			@yield('content')
		</main>

		{{-- Footer dengan Font Sedang & Blur Tipis --}}
		<footer
			class="border-t border-slate-200 bg-white/80 px-6 py-4 text-center text-xs font-normal text-slate-500 backdrop-blur-sm">
			&copy; {{ date('Y') }} {{ config('app.name', 'SPPD') }} — Sistem Perjalanan Dinas
			v{{ config('app.sppd_version') }}
		</footer>
	</div>

	{{-- Global Navigation Script --}}
	<script>
		function toggleSidebar() {
			const sidebar = document.getElementById('sidebar');
			const overlay = document.getElementById('sidebar-overlay');
			if (!sidebar || !overlay) return;
			sidebar.classList.toggle('-translate-x-full');
			overlay.classList.toggle('hidden');
		}

		// Jaga posisi scroll sidebar agar tidak balik ke atas saat pindah halaman
		// lewat wire:navigate (terasa seperti SPA). wire:navigate menukar seluruh
		// DOM termasuk <nav>, jadi scrollTop disimpan sebelum navigasi lalu
		// dipulihkan setelah DOM baru terpasang.
		const SIDEBAR_SCROLL_KEY = 'sidebar-nav-scroll';

		document.addEventListener('livewire:navigating', function() {
			const nav = document.getElementById('sidebar-nav');
			if (nav) sessionStorage.setItem(SIDEBAR_SCROLL_KEY, nav.scrollTop);
		});

		document.addEventListener('livewire:navigated', function() {
			const nav = document.getElementById('sidebar-nav');
			const saved = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
			if (nav && saved !== null) nav.scrollTop = parseInt(saved, 10) || 0;
		});

		document.addEventListener('livewire:navigated', function() {
			const submenuToggles = document.querySelectorAll('[data-sidebar-toggle]');
			submenuToggles.forEach((toggle) => {
				const submenuId = toggle.getAttribute('data-sidebar-toggle');
				const submenu = document.getElementById(submenuId);
				if (!submenu) return;

				// Reset listener by cloning to prevent duplicates on wire:navigate
				const newToggle = toggle.cloneNode(true);
				toggle.parentNode.replaceChild(newToggle, toggle);

				newToggle.addEventListener('click', function() {
					const isExpanded = newToggle.getAttribute('aria-expanded') === 'true';
					newToggle.setAttribute('aria-expanded', String(!isExpanded));
					submenu.classList.toggle('hidden', isExpanded);
				});
			});
		});
	</script>

	@livewireScripts

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
				x-transition:enter-start="-translate-y-3 opacity-0 scale-95"
				x-transition:enter-end="translate-y-0 opacity-100 scale-100"
				x-transition:leave="transition ease-in duration-200 transform"
				x-transition:leave-start="translate-y-0 opacity-100 scale-100"
				x-transition:leave-end="-translate-y-3 opacity-0 scale-95"
				:style="toast.dragY ? `transform: translateY(${toast.dragY}px); opacity: ${Math.max(0, 1 + toast.dragY / 80)}` : ''"
				:class="!toast.dragging && 'transition-transform duration-200'"
				class="toast-card relative overflow-hidden pointer-events-auto w-full flex items-center gap-2.5 sm:gap-3 rounded-lg sm:rounded-xl border border-slate-200/80 bg-white px-3 py-2 sm:px-3.5 sm:py-3 shadow-lg shadow-slate-900/5 ring-1 ring-black/5"
				style="touch-action: pan-x;">

				<!-- Icon (lingkaran berisi) -->
				<div class="shrink-0 flex size-6 sm:size-7 items-center justify-center rounded-full text-white shadow-sm"
					:class="{
					    'bg-emerald-500': toast.type === 'success',
					    'bg-rose-500': toast.type === 'error',
					    'bg-cyan-500': toast.type === 'info',
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
					    'bg-cyan-500': toast.type === 'info',
					    'bg-amber-500': toast.type === 'warning'
					}"
					:style="`animation-duration: ${duration}ms`"></div>
			</div>
		</template>
	</div>

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
						dragging: false
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
						toast.dragY = 0;
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

	@stack('scripts')
</body>

</html>
