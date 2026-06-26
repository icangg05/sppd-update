<header class="sticky top-0 z-30 w-full border-b border-cyan-600/50 bg-cyan-700 text-white shadow-sm">
	<div class="flex h-13 lg:h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

		<div class="flex items-center gap-4">
			<button onclick="toggleSidebar()"
				class="inline-flex size-9 items-center justify-center rounded-lg bg-cyan-600/50 text-white transition-all hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-white/50 lg:hidden"
				aria-label="Buka menu navigasi" title="Buka menu navigasi">
				<i class="fa-solid fa-bars-staggered"></i>
			</button>

			<div class="hidden flex-col sm:flex">
				<h1 class="text-sm font-bold tracking-wide sm:text-base">@yield('page-title', 'Dashboard')</h1>
				<p class="text-xs font-medium text-cyan-200">Sistem Perjalanan Dinas</p>
			</div>
		</div>

		<div class="flex items-center gap-3 sm:gap-5">
			{{-- Notifikasi: log pembaruan sistem (changelog) --}}
			<x-notifications />

			<div class="hidden h-6 w-px bg-cyan-500/50 sm:block"></div>

			<div class="relative">
				<button id="toggle-profile"
					class="flex items-center gap-3 rounded-lg p-1 transition-all hover:bg-cyan-600/30 focus:outline-none">
					<div class="hidden text-right sm:block">
						<p class="text-sm font-semibold leading-tight text-white">{{ auth()->user()->name ?? 'Administrator' }}</p>
						<p class="text-xs font-medium text-cyan-200">{{ auth()->user()->department?->name ?? 'Sistem SPPD' }}</p>
					</div>
					<div class="relative size-9 overflow-hidden rounded-full border-2 border-cyan-200/50 shadow-sm">
						<img
							src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=0D8ABC&color=fff"
							alt="Profil" class="h-full w-full object-cover">
					</div>
					<i id="profile-icon"
						class="fa-solid fa-chevron-down text-xs text-cyan-200 transition-transform duration-200"></i>
				</button>

				<div id="dropdown-menu"
					class="absolute right-0 mt-3 hidden w-56 origin-top-right rounded-xl border border-slate-100 bg-white/95 py-1.5 shadow-xl shadow-slate-200/50 backdrop-blur-sm ring-1 ring-slate-900/5 focus:outline-none transition-all duration-200">

					{{-- Mobile User Info --}}
					<div class="block px-4 py-3 sm:hidden bg-slate-50/50 mb-1.5 border-b border-slate-100">
						<p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name ?? 'Administrator' }}</p>
						<p class="text-[11px] font-medium text-slate-500 truncate mt-0.5">
							{{ auth()->user()->department?->name ?? 'SPPD System' }}
						</p>
					</div>

					{{-- Menu Links --}}
					<div class="px-1.5 space-y-0.5">
						<a href="{{ route('profile') }}" wire:navigate
							class="group flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900">
							<i
								class="fa-regular fa-user mr-3 w-4 text-center text-slate-400 transition-colors group-hover:text-cyan-600"></i>
							Profil Saya
						</a>
					</div>

					<hr class="my-1.5 border-slate-100">

					{{-- Logout Action --}}
					<div class="px-1.5 pb-0.5">
						<form action="{{ route('logout') }}" method="POST" class="block">
							@csrf
							<button type="submit"
								class="group flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-rose-50 hover:text-rose-700">
								<i
									class="fa-solid fa-arrow-right-from-bracket mr-3 w-4 text-center text-slate-400 transition-colors group-hover:text-rose-600"></i>
								Keluar
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>

<script>
	$(document).ready(function() {
		$('#toggle-profile').on('click', function(e) {
			e.stopPropagation();
			$('#dropdown-menu').fadeToggle(150);
			$('#profile-icon').toggleClass('rotate-180');
		});

		$(document).on('click', function(e) {
			if (!$(e.target).closest('#toggle-profile, #dropdown-menu').length) {
				if ($('#dropdown-menu').is(':visible')) {
					$('#dropdown-menu').fadeOut(150);
					$('#profile-icon').removeClass('rotate-180');
				}
			}
		});
	});
</script>
