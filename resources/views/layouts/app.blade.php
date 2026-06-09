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

			{{-- Hidden Session Dispatcher inside dynamic area --}}
			<div x-data x-init="@if (session('success')) $dispatch('toast', { type: 'success', message: '{{ addslashes(session('success')) }}' }); @endif
@if (session('error')) $dispatch('toast', { type: 'error', message: '{{ addslashes(session('error')) }}' }); @endif" class="hidden"></div>

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
		class="fixed top-5 right-5 z-9999 flex flex-col gap-3.5 max-w-sm w-full pointer-events-none px-4 sm:px-0">
		<template x-for="toast in toasts" :key="toast.id">
			<div x-show="toast.show"
				x-transition:enter="transition ease-out duration-300 transform"
				x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
				x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
				x-transition:leave="transition ease-in duration-200 transform"
				x-transition:leave-start="opacity-100 translate-x-0"
				x-transition:leave-end="opacity-0 translate-x-2"
				class="pointer-events-auto flex items-start gap-3 rounded-lg border p-4 shadow-md bg-white border-l-4"
				:class="{
				    'border-emerald-500 bg-emerald-50 text-emerald-950 border-l-emerald-600': toast.type === 'success',
				    'border-rose-200 bg-rose-50 text-rose-950 border-l-rose-600': toast.type === 'error',
				    'border-cyan-200 bg-cyan-50 text-cyan-950 border-l-cyan-600': toast.type === 'info',
				    'border-amber-200 bg-amber-50 text-amber-950 border-l-amber-600': toast.type === 'warning'
				}">

				<!-- Icon -->
				<div class="shrink-0 mt-0.5">
					<template x-if="toast.type === 'success'">
						<i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
					</template>
					<template x-if="toast.type === 'error'">
						<i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
					</template>
					<template x-if="toast.type === 'info'">
						<i class="fa-solid fa-circle-info text-cyan-600 text-base"></i>
					</template>
					<template x-if="toast.type === 'warning'">
						<i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
					</template>
				</div>

				<!-- Message -->
				<div class="flex-1 text-xs font-semibold leading-normal" x-text="toast.message"></div>

				<!-- Close button -->
				<button type="button" @click="remove(toast.id)"
					class="text-slate-400 hover:text-slate-600 shrink-0 cursor-pointer">
					<i class="fa-solid fa-xmark text-xs"></i>
				</button>
			</div>
		</template>
	</div>

	<script>
		function toastManager() {
			return {
				toasts: [],
				add(detail) {
					const id = Date.now() + Math.random().toString(36).substr(2, 9);
					this.toasts.push({
						id: id,
						type: detail.type || 'success',
						message: detail.message,
						show: false
					});

					this.$nextTick(() => {
						const toast = this.toasts.find(t => t.id === id);
						if (toast) {
							toast.show = true;
						}
					});

					setTimeout(() => {
						this.remove(id);
					}, 4000);
				},
				remove(id) {
					const index = this.toasts.findIndex(t => t.id === id);
					if (index !== -1) {
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
