<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>@yield('title', 'Dashboard') - {{ config('app.name', 'SPPD') }}</title>

	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<script src="{{ asset('js/jquery-4.0.0.min.js') }}"></script>

	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
	@include('components.sidebar')
	<div id="sidebar-overlay" class="fixed inset-0 bg-slate-950/45 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

	<div class="lg:ml-64 min-h-screen flex flex-col">
		@include('components.header')

		<main class="flex-1 p-4 sm:p-6 lg:p-8">
			@if (session('success'))
				<div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
					<i class="fa-solid fa-circle-check fa-fw"></i>
					{{ session('success') }}
				</div>
			@endif
			@if (session('error'))
				<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm flex items-center gap-2">
					<i class="fa-solid fa-circle-exclamation fa-fw"></i>
					{{ session('error') }}
				</div>
			@endif
			@if (session('error_details'))
				<script>console.log('TTE error details:', @json(session('error_details')));</script>
			@endif
			@yield('content')
		</main>

		<footer class="px-6 py-4 text-center text-xs text-slate-400 border-t border-slate-200 bg-white/70 backdrop-blur">
			&copy; {{ date('Y') }} SPPD — Sistem Perjalanan Dinas v2.0
		</footer>
	</div>

	<script>
		function toggleSidebar() {
			const sidebar = document.getElementById('sidebar');
			const overlay = document.getElementById('sidebar-overlay');
			if (!sidebar || !overlay) return;
			sidebar.classList.toggle('-translate-x-full');
			overlay.classList.toggle('hidden');
		}

		document.addEventListener('DOMContentLoaded', function() {
			const submenuToggles = document.querySelectorAll('[data-sidebar-toggle]');
			submenuToggles.forEach((toggle) => {
				const submenuId = toggle.getAttribute('data-sidebar-toggle');
				const submenu = document.getElementById(submenuId);
				if (!submenu) return;

				toggle.addEventListener('click', function() {
					const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
					toggle.setAttribute('aria-expanded', String(!isExpanded));
					submenu.classList.toggle('hidden', isExpanded);
				});
			});
		});
	</script>

	@stack('scripts')
</body>

</html>
