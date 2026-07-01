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
		//
		// Posisi hanya dipulihkan pada navigasi SPA. Saat full page load
		// (mis. setelah login/logout) event livewire:navigated tetap terpicu,
		// tapi livewire:navigating TIDAK, jadi flag di bawah membedakannya
		// supaya scroll direset ke atas alih-alih memakai posisi sesi lama.
		const SIDEBAR_SCROLL_KEY = 'sidebar-nav-scroll';
		let sidebarNavigating = false;

		document.addEventListener('livewire:navigating', function() {
			sidebarNavigating = true;
			const nav = document.getElementById('sidebar-nav');
			if (nav) sessionStorage.setItem(SIDEBAR_SCROLL_KEY, nav.scrollTop);
		});

		document.addEventListener('livewire:navigated', function() {
			const nav = document.getElementById('sidebar-nav');
			if (!nav) return;

			if (sidebarNavigating) {
				// Pindah halaman via SPA: pulihkan posisi scroll.
				const saved = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
				if (saved !== null) nav.scrollTop = parseInt(saved, 10) || 0;
				sidebarNavigating = false;
			} else {
				// Full page load (login/logout/refresh): reset ke atas.
				sessionStorage.removeItem(SIDEBAR_SCROLL_KEY);
				nav.scrollTop = 0;
			}
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

	<x-ui.toast />

	@stack('scripts')
</body>

</html>
