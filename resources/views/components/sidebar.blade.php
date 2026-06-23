<aside id="sidebar"
	class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-800 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:translate-x-0 -translate-x-full">
	<!-- Bagian Atas: Logo Aplikasi -->
	<div class="flex h-16 items-center gap-3 border-b border-slate-800 px-4">
		<img src="{{ asset('img/logo-sppd.png') }}" alt="logo" class="size-8">
		<div class="leading-tight">
			<p class="text-sm font-bold tracking-wide text-white">SPPD PEMERINTAH</p>
			<p class="text-xs text-slate-400">Kota Kendari</p>
		</div>
	</div>

	<!-- Bagian Profil Pengguna -->
	<div class="border-b border-slate-800 p-4">
		<div class="flex items-center gap-3 rounded border border-slate-800/60 bg-slate-950/40 p-3">
			<div
				class="flex size-9 shrink-0 items-center justify-center rounded border border-slate-700 bg-slate-800 text-xs font-bold tracking-wider text-cyan-400">
				{{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
			</div>
			<div class="overflow-hidden leading-tight">
				<p class="truncate text-sm font-semibold text-slate-200" title="{{ auth()->user()->username ?? '-' }}">
					{{ auth()->user()->username ?? '-' }}</p>
				<p class="truncate text-xs text-slate-400">{{ auth()->user()->roles->first()?->label ?? 'Undefined role' }}</p>
			</div>
		</div>
	</div>

	<!-- Konten Navigasi Menu -->
	<nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
		<div class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">Menu Utama</div>

		<a href="{{ route('dashboard') }}" wire:navigate
			class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
			<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-house fa-fw"></i></span>
			<span>Beranda</span>
		</a>

		<div class="space-y-1" x-data="{ open: true }">
			<button type="button" @click="open = !open"
				class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-sm font-medium transition-colors text-slate-100 hover:bg-slate-800/60">
				<div class="flex items-center gap-3">
					<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-file-lines fa-fw"></i></span>
					<span class="text-left">List Telaah</span>
				</div>
				<i class="fa-solid fa-chevron-down text-xs transition-transform duration-200 rotate-180"
					:class="{ 'rotate-180': open }"></i>
			</button>

			<div id="sppd-menu" x-show="open" x-collapse class="space-y-1 py-1 pl-8 pr-1">
				<a href="{{ route('sppd.index', array_filter(\App\Livewire\Sppd\SppdIndex::savedFilters())) }}" wire:navigate
					class="flex items-center gap-2 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('sppd.*') && !request()->routeIs('sppd.calendar') && request('filter') !== 'approval' && request('from') !== 'approval' ? 'text-cyan-400 bg-slate-800/40' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="size-1 rounded-sm bg-current"></span>
					Daftar SPPD
				</a>

				@can('sppd.approve')
					<a href="{{ route('sppd.index', ['filter' => 'approval']) }}" wire:navigate
						class="flex items-center gap-2 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request('filter') === 'approval' || request('from') === 'approval' ? 'text-cyan-400 bg-slate-800/40' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="size-1 rounded-sm bg-current"></span>
						<span class="flex-1">Persetujuan</span>
						<livewire:pending-approval-badge />
					</a>
				@endcan

				<a href="{{ route('sppd.calendar') }}" wire:navigate
					class="flex items-center gap-2 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('sppd.calendar') ? 'text-cyan-400 bg-slate-800/40' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="size-1 rounded-sm bg-current"></span>
					Kalender
				</a>
			</div>
		</div>

		@if (auth()->user()->hasAnyRole(['super_admin', 'admin_opd']))
		<div class="px-3 py-1.5 pt-4 text-xs font-bold uppercase tracking-wider text-slate-500">Pengaturan</div>

		<a href="{{ route('master.users.index') }}" wire:navigate.hover
			class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.users.*') && request('type') !== 'dprd' ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
			<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-users fa-fw"></i></span>
			<span>Pegawai</span>
		</a>

		@if (auth()->user()->department?->type?->value === 'dprd' ||
				auth()->user()->department?->parent?->type?->value === 'dprd')
			<a wire:navigate href="{{ route('master.users.index', ['type' => 'dprd']) }}" wire:navigate
				class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.users.*') && request('type') === 'dprd' ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
				<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-user-tie fa-fw"></i></span>
				<span>Anggota DPRD</span>
			</a>
		@endif

		<a href="{{ route('master.budgets.index') }}" wire:navigate
			class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.budgets.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
			<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-coins fa-fw"></i></span>
			<span>DPA</span>
		</a>

		<a href="{{ route('master.departments.index') }}" wire:navigate
			class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.departments.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
			<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-building fa-fw"></i></span>
			<span>Unit Kerja</span>
		</a>

		@if (auth()->user()->hasRole('super_admin'))
			<a href="{{ route('master.workflows.index') }}" wire:navigate
				class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.workflows.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
				<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-diagram-project fa-fw"></i></span>
				<span>Workflow</span>
			</a>

			<a href="{{ route('master.roles.index') }}" wire:navigate
				class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.roles.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
				<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-shield-halved fa-fw"></i></span>
				<span>Role & Permission</span>
			</a>
		@endif
		@endif
	</nav>

	<div class="border-t border-slate-800 p-3 lg:hidden">
		<button type="button" onclick="toggleSidebar()"
			class="flex w-full items-center justify-center gap-2 rounded border border-slate-800 bg-slate-950/20 py-2 text-xs font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200">
			<i class="fa-solid fa-chevron-left text-[10px]"></i>
			<span>Tutup Sidebar</span>
		</button>
	</div>
</aside>
