<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>@yield('title', 'Dashboard') - {{ config('app.name', 'SPPD') }}</title>

	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

	<script src="{{ asset('js/jquery-4.0.0.min.js') }}"></script>

	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen">
	{{-- Sidebar --}}
	@include('components.sidebar')

	{{-- Mobile overlay --}}
	<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

	{{-- Main content --}}
	<div class="lg:ml-64 min-h-screen flex flex-col">
		{{-- Header --}}
		@include('components.header')

		{{-- Content --}}
		<main class="flex-1 p-4 sm:p-6">
			{{-- Flash messages --}}
			@if (session('success'))
				<div
					class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm flex items-center gap-2">
					<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd"
							d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
							clip-rule="evenodd" />
					</svg>
					{{ session('success') }}
				</div>
			@endif
			@if (session('error'))
				<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm flex items-center gap-2">
					<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd"
							d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-7V7a1 1 0 112 0v4a1 1 0 11-2 0zm0 4a1 1 0 112 0 1 1 0 01-2 0z"
							clip-rule="evenodd" />
					</svg>
					{{ session('error') }}
				</div>
			@endif

			@yield('content')
		</main>

		{{-- Footer --}}
		<footer class="px-6 py-4 text-center text-xs text-slate-400 border-t border-slate-200">
			&copy; {{ date('Y') }} SPPD — Sistem Perjalanan Dinas v2.0
		</footer>
	</div>

	<script>
		function toggleSidebar() {
			const sidebar = document.getElementById('sidebar');
			const overlay = document.getElementById('sidebar-overlay');
			sidebar.classList.toggle('-translate-x-full');
			overlay.classList.toggle('hidden');
		}
	</script>

	@stack('scripts')
</body>

</html>
