{{-- Sidebar Navigation --}}
<aside id="sidebar" class="sidebar -translate-x-full lg:translate-x-0">
	{{-- Logo --}}
	<div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700/50">
		<div class="w-9 h-9 bg-accent-500 rounded-lg flex items-center justify-center">
			<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
			</svg>
		</div>
		<div>
			<span class="text-base font-bold text-white tracking-tight">SPPD</span>
			<span class="block text-[10px] text-slate-400 -mt-0.5">Perjalanan Dinas</span>
		</div>
	</div>

	{{-- Navigation --}}
	<nav class="flex-1 overflow-y-auto py-4">
		<div class="sidebar-section">Utama</div>

		<a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
			<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
			</svg>
			Dashboard
		</a>

		<div class="sidebar-section">SPPD</div>

		<a href="{{ route('sppd.index') }}" class="sidebar-link {{ request()->routeIs('sppd.*') ? 'active' : '' }}">
			<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
			</svg>
			Daftar SPPD
		</a>

		@can('sppd.create')
			<a href="{{ route('sppd.create') }}" class="sidebar-link {{ request()->routeIs('sppd.create') ? 'active' : '' }}">
				<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
				</svg>
				Buat SPPD
			</a>
		@endcan

		@can('sppd.approve')
			<a href="{{ route('sppd.index', ['filter' => 'approval']) }}" class="sidebar-link">
				<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
					<path stroke-linecap="round" stroke-linejoin="round"
						d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
				</svg>
				Persetujuan
			</a>
		@endcan

		<div class="sidebar-section">Referensi</div>

		<a href="{{ route('master.users.index') }}"
			class="sidebar-link {{ request()->routeIs('master.users.*') ? 'active' : '' }}">
			<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
			</svg>
			Pegawai
		</a>

		<a href="{{ route('master.departments.index') }}"
			class="sidebar-link {{ request()->routeIs('master.departments.*') ? 'active' : '' }}">
			<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
			</svg>
			{{ auth()->user()->hasRole('super_admin') ? 'OPD' : 'Unit Kerja' }}
		</a>

		<a href="{{ route('master.budgets.index') }}"
			class="sidebar-link {{ request()->routeIs('master.budgets.*') ? 'active' : '' }}">
			<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
			</svg>
			DPA
		</a>

		@role('super_admin')
			<a href="{{ route('master.workflows.index') }}"
				class="sidebar-link {{ request()->routeIs('master.workflows.*') ? 'active' : '' }}">
				<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
					<path stroke-linecap="round" stroke-linejoin="round"
						d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
				</svg>
				Workflow SPPD
			</a>
		@endrole
	</nav>

	{{-- User info at bottom --}}
	<div class="border-t border-slate-700/50 p-4">
		<div class="flex items-center gap-3">
			<div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-xs font-bold text-white">
				{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
			</div>
			<div class="flex-1 min-w-0">
				<p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? '-' }}</p>
				<p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->getRoleNames()->first() ?? '-' }}</p>
			</div>
		</div>
	</div>
</aside>
