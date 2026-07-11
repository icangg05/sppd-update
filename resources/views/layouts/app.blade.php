<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>@yield('title', 'Dashboard') - {{ config('app.name', 'SPPD') }}</title>

	<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
	<link rel="apple-touch-icon" href="{{ asset('img/logo-sppd.png') }}">
	<link rel="manifest" href="{{ asset('manifest.json') }}">
	<meta name="theme-color" content="#0c4a6e">
	<meta name="mobile-web-app-capable" content="yes">

	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=geist:400,500,600,700,800|geist-mono:400,500,600" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
		crossorigin="anonymous" referrerpolicy="no-referrer" />

	<script src="{{ asset('js/jquery-4.0.0.min.js') }}"></script>

	@vite(['resources/css/app.css', 'resources/js/app.js'])

	@livewireStyles
</head>

<body class="text-slate-900 antialiased bg-blue-50">

	{{-- Sidebar Component --}}
	@include('components.sidebar')

	{{-- Sidebar Overlay untuk Mobile --}}
	<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-950/40 lg:hidden" onclick="toggleSidebar()"></div>

	{{-- Main Content Wrapper (Geser kanan otomatis saat desktop lewat lg:ml-64) --}}
	<div class="flex min-h-dvh flex-col lg:ml-64">

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

		{{-- Footer institusional: atribusi di kiri, versi di kanan --}}
		<footer class="shadow border-t border-slate-200 bg-white/80 backdrop-blur-sm">
			<div
				class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-6 py-4 text-xs text-slate-500 sm:flex-row">
				<div class="flex items-center gap-2">
					<img src="{{ asset('img/logo-sppd.png') }}" alt="Logo SPPD Kota Kendari" class="size-4 opacity-80">
          <div>
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'SPPD') }} &mdash; Sistem Perjalanan Dinas Elektronik.</span>
            <span class="inline sm:hidden">v{{ config('app.sppd_version') }}</span>
          </div>
				</div>
				<div class="flex items-center gap-2.5">
					<span class="hidden text-slate-400 sm:inline">Diskominfo Kota Kendari</span>
					<span class="hidden text-slate-300 sm:inline">&bull;</span>
					<span
						class="hidden sm:inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-medium text-slate-500">v{{ config('app.sppd_version') }}</span>
				</div>
			</div>
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
