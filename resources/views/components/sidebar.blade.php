{{-- Sidebar Navigation --}}
<aside id="sidebar" class="sidebar -translate-x-full lg:translate-x-0">
	<div class="sidebar-top">
		<div class="sidebar-logo">
			<div class="sidebar-logo-badge">SPPD</div>
			<div>
				<p class="sidebar-logo-title">SPPD</p>
				<p class="sidebar-logo-subtitle">Sistem Perjalanan Dinas</p>
			</div>
		</div>

		<div class="sidebar-profile">
			<div class="sidebar-avatar">
				<span>{{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}</span>
			</div>
			<div>
				<p class="sidebar-profile-name">{{ auth()->user()->username ?? '-' }}</p>
				<p class="sidebar-profile-role">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</p>
			</div>
		</div>
	</div>

	<nav class="sidebar-nav">
		<div class="sidebar-section">Menu utama</div>

		<a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
			<span class="icon-wrap">
				<i class="fa-solid fa-house fa-fw"></i>
			</span>
			<span>Beranda</span>
		</a>

		<div class="sidebar-group">
			<button type="button"
				class="sidebar-link sidebar-link-toggle {{ request()->routeIs('sppd.*') ? 'active' : '' }}"
				data-sidebar-toggle="sppd-menu"
				aria-expanded="{{ request()->routeIs('sppd.*') ? 'true' : 'false' }}">
				<span class="icon-wrap">
					<i class="fa-solid fa-file-lines fa-fw"></i>
				</span>
				<span class="flex-1 text-left">List Telaah</span>
				<i class="fa-solid fa-chevron-down sidebar-chevron"></i>
			</button>
			<div id="sppd-menu" class="sidebar-submenu {{ request()->routeIs('sppd.*') ? '' : 'hidden' }}">
				<a href="{{ route('sppd.index') }}" class="sidebar-sublink {{ request()->routeIs('sppd.index') && !request('filter') ? 'active' : '' }}">
					<span class="sidebar-subdot"></span>
					Kepala OPD
				</a>
				<a href="{{ route('sppd.index', ['filter' => 'staff']) }}" class="sidebar-sublink {{ request('filter') === 'staff' ? 'active' : '' }}">
					<span class="sidebar-subdot"></span>
					Eselon III, IV & Staf
				</a>
			</div>
		</div>

		@can('sppd.approve')
			<a href="{{ route('sppd.index', ['filter' => 'approval']) }}" class="sidebar-link {{ request('filter') === 'approval' ? 'active' : '' }}">
				<span class="icon-wrap">
					<i class="fa-solid fa-circle-check fa-fw"></i>
				</span>
				<span>Persetujuan</span>
			</a>
		@endcan

		<a href="#" class="sidebar-link">
			<span class="icon-wrap">
				<i class="fa-solid fa-calendar-days fa-fw"></i>
			</span>
			<span>Kalender</span>
		</a>

		<div class="sidebar-section">Pengaturan</div>
		<a href="{{ route('master.users.index') }}" class="sidebar-link {{ request()->routeIs('master.users.*') ? 'active' : '' }}">
			<span class="icon-wrap">
				<i class="fa-solid fa-gear fa-fw"></i>
			</span>
			<span>Setting</span>
		</a>
	</nav>

	<div class="sidebar-footer">
		<button onclick="toggleSidebar()" type="button" class="sidebar-toggle-btn">
			<i class="fa-solid fa-chevron-left fa-fw"></i>
			<span>Tutup Sidebar</span>
		</button>
	</div>
</aside>
