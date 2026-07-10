<aside id="sidebar"
	class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-800 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:translate-x-0 -translate-x-full">
	<!-- Bagian Atas: Logo Aplikasi -->
	<div class="flex h-16 items-center gap-3 border-b border-slate-800 px-4">
		<img src="{{ asset('img/logo-sppd.png') }}" alt="logo" class="size-8">
		<div class="leading-tight">
			<p class="text-sm font-bold tracking-wide text-white">SPPD PEMERINTAH</p>
			<p class="text-xs text-slate-500">Kota Kendari</p>
		</div>
	</div>

	<!-- Bagian Profil Pengguna -->
	<div class="border-b border-slate-800 p-4">
		<div class="flex items-center gap-3 rounded border border-slate-800/60 bg-slate-950/40 p-3">
			<div
				class="flex size-9 shrink-0 items-center justify-center rounded border border-slate-700 bg-slate-800 text-xs font-bold tracking-wider text-primary-400">
				{{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
			</div>
			<div class="overflow-hidden leading-tight">
				<p class="truncate text-sm font-semibold text-slate-200" title="{{ auth()->user()->username ?? '-' }}">
					{{ auth()->user()->username ?? '-' }}</p>
				<p class="truncate text-xs text-slate-500">{{ auth()->user()->roles->first()?->label ?? 'Undefined role' }}</p>
			</div>
		</div>
	</div>

	<!-- Konten Navigasi Menu -->
	<nav id="sidebar-nav" class="flex-1 space-y-1 overflow-y-auto px-3 py-4 lg:pb-16">
		<div class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">Menu Utama</div>

		<a href="{{ route('dashboard') }}" wire:navigate.hover
			class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-primary-700 text-white' : 'text-slate-500 hover:bg-slate-800 hover:text-slate-100' }}">
			<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-house fa-fw"></i></span>
			<span>Beranda</span>
		</a>

		@php $sppdActive = request()->routeIs('sppd.*'); @endphp
		<div class="space-y-1" x-data="{ open: true }">
			<button type="button" @click="open = !open"
				class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ $sppdActive ? 'bg-slate-800/60 text-white' : 'text-slate-100 hover:bg-slate-800/60' }}">
				<div class="flex items-center gap-3">
					<span class="flex w-5 justify-center text-base {{ $sppdActive ? 'text-primary-400' : '' }}"><i class="fa-solid fa-file-lines fa-fw"></i></span>
					<span class="text-left">List Telaah</span>
				</div>
				<i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
					:class="{ 'rotate-180': open }"></i>
			</button>

			<div id="sppd-menu" x-show="open" x-collapse class="space-y-1 py-1 pl-4 pr-1">
				<a href="{{ route('sppd.index', array_filter(\App\Livewire\Sppd\SppdIndex::savedFilters())) }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('sppd.*') && !request()->routeIs('sppd.calendar') && request('filter') !== 'approval' && request('from') !== 'approval' ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-list fa-fw"></i></span>
					<span>Daftar SPPD</span>
				</a>

				{{-- Super admin tidak ikut alur persetujuan, sembunyikan menunya. --}}
				@if (auth()->user()->can('sppd.approve') && ! auth()->user()->hasRole('super_admin'))
					<a href="{{ route('sppd.index', ['filter' => 'approval']) }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request('filter') === 'approval' || request('from') === 'approval' ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-check-double fa-fw"></i></span>
						<span class="flex-1">Menunggu Persetujuan</span>
						<livewire:pending-approval-badge />
					</a>
				@endif

				<a href="{{ route('sppd.calendar') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('sppd.calendar') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-calendar-days fa-fw"></i></span>
					<span>Kalender</span>
				</a>
			</div>
		</div>

		{{-- Menu pejabat kepemimpinan (daftar role di config/menu_access.php) --}}
		@if (auth()->user()->hasAnyRole(config('menu_access.leadership')))
			<a href="{{ route('leadership.history') }}" wire:navigate.hover
				class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('leadership.history') ? 'bg-primary-700 text-white' : 'text-slate-500 hover:bg-slate-800 hover:text-slate-100' }}">
				<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-clock-rotate-left fa-fw"></i></span>
				<span>Riwayat Persetujuan</span>
			</a>

			<a href="{{ route('leadership.recap') }}" wire:navigate.hover
				class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('leadership.recap') ? 'bg-primary-700 text-white' : 'text-slate-500 hover:bg-slate-800 hover:text-slate-100' }}">
				<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-chart-pie fa-fw"></i></span>
				<span>Rekap &amp; Statistik</span>
			</a>
		@endif

		{{-- Menu sekretariat (daftar role di config/menu_access.php) --}}
		@if (auth()->user()->hasAnyRole(config('menu_access.secretariat')))
			<a href="{{ route('secretariat.monitoring') }}" wire:navigate.hover
				class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('secretariat.monitoring') ? 'bg-primary-700 text-white' : 'text-slate-500 hover:bg-slate-800 hover:text-slate-100' }}">
				<span class="flex w-5 justify-center text-base"><i class="fa-solid fa-clipboard-check fa-fw"></i></span>
				<span>Monitoring Tindak Lanjut</span>
			</a>
		@endif

		@if (auth()->user()->hasAnyRole(['super_admin', 'admin_opd']))
		<div class="px-3 py-1.5 pt-4 text-xs font-bold uppercase tracking-wider text-slate-500">Pengaturan</div>

		@if (auth()->user()->hasRole('super_admin'))
		{{-- Grup: Kepegawaian --}}
		@php
			$openKepegawaian = request()->routeIs('master.users.*', 'master.position-requests.*', 'master.positions.*', 'master.ranks.*');
		@endphp
		<div class="space-y-1" x-data="{ open: true }">
			<button type="button" @click="open = !open"
				class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ $openKepegawaian ? 'bg-slate-800/60 text-white' : 'text-slate-100 hover:bg-slate-800/60' }}">
				<div class="flex items-center gap-3">
					<span class="flex w-5 justify-center text-base {{ $openKepegawaian ? 'text-primary-400' : '' }}"><i class="fa-solid fa-user-group fa-fw"></i></span>
					<span class="text-left">Kepegawaian</span>
				</div>
				<i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
					:class="{ 'rotate-180': open }"></i>
			</button>

			<div x-show="open" x-collapse class="space-y-1 py-1 pl-4 pr-1">
				<a href="{{ route('master.users.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.users.*') && request('type') !== 'dprd' ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-users fa-fw"></i></span>
					<span>Pegawai</span>
				</a>

				@if (auth()->user()->hasRole('super_admin') ||
						auth()->user()->department?->type?->value === 'dprd' ||
						auth()->user()->department?->parent?->type?->value === 'dprd')
					<a wire:navigate.hover href="{{ route('master.users.index', ['type' => 'dprd']) }}"
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.users.*') && request('type') === 'dprd' ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-user-tie fa-fw"></i></span>
						<span>Anggota DPRD</span>
					</a>
				@endif

				<a href="{{ route('master.position-requests.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.position-requests.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-user-plus fa-fw"></i></span>
					<span class="flex-1">Pengajuan Jabatan</span>
					<livewire:pending-position-request-badge />
				</a>

				@if (auth()->user()->hasRole('super_admin'))
					<a href="{{ route('master.positions.index') }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.positions.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-id-badge fa-fw"></i></span>
						<span>Data Jabatan</span>
					</a>

					<a href="{{ route('master.ranks.index') }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.ranks.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-ranking-star fa-fw"></i></span>
						<span>Data Pangkat</span>
					</a>
				@endif
			</div>
		</div>

		{{-- Grup: Data Master --}}
		@php
			$openDataMaster = request()->routeIs('master.budgets.*', 'master.departments.*', 'master.provinces.*', 'master.regencies.*');
		@endphp
		<div class="space-y-1" x-data="{ open: true }">
			<button type="button" @click="open = !open"
				class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ $openDataMaster ? 'bg-slate-800/60 text-white' : 'text-slate-100 hover:bg-slate-800/60' }}">
				<div class="flex items-center gap-3">
					<span class="flex w-5 justify-center text-base {{ $openDataMaster ? 'text-primary-400' : '' }}"><i class="fa-solid fa-table-list fa-fw"></i></span>
					<span class="text-left">Data Master</span>
				</div>
				<i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
					:class="{ 'rotate-180': open }"></i>
			</button>

			<div x-show="open" x-collapse class="space-y-1 py-1 pl-4 pr-1">
				<a href="{{ route('master.budgets.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.budgets.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-coins fa-fw"></i></span>
					<span>DPA</span>
				</a>

				<a href="{{ route('master.departments.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.departments.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-building fa-fw"></i></span>
					<span>Unit Kerja</span>
				</a>

				@if (auth()->user()->hasRole('super_admin'))
					<a href="{{ route('master.provinces.index') }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.provinces.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-map fa-fw"></i></span>
						<span>Data Provinsi</span>
					</a>

					<a href="{{ route('master.regencies.index') }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.regencies.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-city fa-fw"></i></span>
						<span>Data Kabupaten/Kota</span>
					</a>
				@endif
			</div>
		</div>
		@else
		{{-- Grup gabungan untuk admin_opd: Kepegawaian + Data Master --}}
		@php
			$openDataMaster = request()->routeIs('master.users.*', 'master.position-requests.*', 'master.budgets.*', 'master.departments.*');
		@endphp
		<div class="space-y-1" x-data="{ open: true }">
			<button type="button" @click="open = !open"
				class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ $openDataMaster ? 'bg-slate-800/60 text-white' : 'text-slate-100 hover:bg-slate-800/60' }}">
				<div class="flex items-center gap-3">
					<span class="flex w-5 justify-center text-base {{ $openDataMaster ? 'text-primary-400' : '' }}"><i class="fa-solid fa-table-list fa-fw"></i></span>
					<span class="text-left">Data Master</span>
				</div>
				<i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
					:class="{ 'rotate-180': open }"></i>
			</button>

			<div x-show="open" x-collapse class="space-y-1 py-1 pl-4 pr-1">
				<a href="{{ route('master.users.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.users.*') && request('type') !== 'dprd' ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-users fa-fw"></i></span>
					<span>Pegawai</span>
				</a>

				@if (auth()->user()->department?->type?->value === 'dprd' ||
						auth()->user()->department?->parent?->type?->value === 'dprd')
					<a wire:navigate.hover href="{{ route('master.users.index', ['type' => 'dprd']) }}"
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.users.*') && request('type') === 'dprd' ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-user-tie fa-fw"></i></span>
						<span>Anggota DPRD</span>
					</a>
				@endif

				<a href="{{ route('master.position-requests.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.position-requests.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-user-plus fa-fw"></i></span>
					<span class="flex-1">Pengajuan Jabatan</span>
					<livewire:pending-position-request-badge />
				</a>

				<a href="{{ route('master.budgets.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.budgets.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-coins fa-fw"></i></span>
					<span>DPA</span>
				</a>

				<a href="{{ route('master.departments.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.departments.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-building fa-fw"></i></span>
					<span>Unit Kerja</span>
				</a>
			</div>
		</div>
		@endif

		{{-- Grup: Sistem (super admin) --}}
		@if (auth()->user()->hasRole('super_admin'))
			@php
				$openSistem = request()->routeIs('master.workflows.*', 'master.roles.*', 'master.database.backup');
			@endphp
			<div class="space-y-1" x-data="{ open: true }">
				<button type="button" @click="open = !open"
					class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ $openSistem ? 'bg-slate-800/60 text-white' : 'text-slate-100 hover:bg-slate-800/60' }}">
					<div class="flex items-center gap-3">
						<span class="flex w-5 justify-center text-base {{ $openSistem ? 'text-primary-400' : '' }}"><i class="fa-solid fa-gear fa-fw"></i></span>
						<span class="text-left">Sistem</span>
					</div>
					<i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
						:class="{ 'rotate-180': open }"></i>
				</button>

				<div x-show="open" x-collapse class="space-y-1 py-1 pl-4 pr-1">
					<a href="{{ route('master.workflows.index') }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.workflows.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-diagram-project fa-fw"></i></span>
						<span>Workflow</span>
					</a>

					<a href="{{ route('master.roles.index') }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.roles.*') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-shield-halved fa-fw"></i></span>
						<span>Role & Permission</span>
					</a>

					<a href="{{ route('master.database.backup') }}" wire:navigate.hover
						class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.database.backup') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
						<span class="flex w-4 justify-center"><i class="fa-solid fa-database fa-fw"></i></span>
						<span>Backup Database</span>
					</a>
				</div>
			</div>
		@endif

		{{-- Grup: Dokumen & Log --}}
		@php
			$openDokumen = request()->routeIs('verify.index', 'verify.document', 'master.logs.index', 'master.logs.tte');
		@endphp
		<div class="space-y-1" x-data="{ open: true }">
			<button type="button" @click="open = !open"
				class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ $openDokumen ? 'bg-slate-800/60 text-white' : 'text-slate-100 hover:bg-slate-800/60' }}">
				<div class="flex items-center gap-3">
					<span class="flex w-5 justify-center text-base {{ $openDokumen ? 'text-primary-400' : '' }}"><i class="fa-solid fa-folder-open fa-fw"></i></span>
					<span class="text-left">Dokumen &amp; Log</span>
				</div>
				<i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
					:class="{ 'rotate-180': open }"></i>
			</button>

			<div x-show="open" x-collapse class="space-y-1 py-1 pl-4 pr-1">
				{{-- Cek keaslian & status TTE dokumen (target QR code) --}}
				<a href="{{ route('verify.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('verify.index', 'verify.document') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-file-shield fa-fw"></i></span>
					<span>Cek Dokumen TTE</span>
				</a>

				{{-- Logs: super_admin (semua data) & admin_opd (data unit kerjanya) --}}
				<a href="{{ route('master.logs.index') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.logs.index') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-clock-rotate-left fa-fw"></i></span>
					<span>Logs Aktivitas</span>
				</a>

				<a href="{{ route('master.logs.tte') }}" wire:navigate.hover
					class="flex items-center gap-2.5 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('master.logs.tte') ? 'text-primary-400 bg-slate-800/40' : 'text-slate-500 hover:text-slate-100 hover:bg-slate-800/20' }}">
					<span class="flex w-4 justify-center"><i class="fa-solid fa-pen-nib fa-fw"></i></span>
					<span>Logs TTE</span>
				</a>
			</div>
		</div>
		@endif
	</nav>

	<div class="border-t border-slate-800 p-3 lg:hidden">
		<button type="button" onclick="toggleSidebar()"
			class="flex w-full items-center justify-center gap-2 rounded border border-slate-800 bg-slate-950/20 py-2 text-xs font-medium text-slate-500 transition-colors hover:bg-slate-800 hover:text-slate-200">
			<i class="fa-solid fa-chevron-left text-[10px]"></i>
			<span>Tutup Sidebar</span>
		</button>
	</div>
</aside>
