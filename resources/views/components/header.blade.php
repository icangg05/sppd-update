{{-- Top Header Bar --}}
<header class="app-header">
  <div class="header-inner">
    <div class="header-left">
      <button onclick="toggleSidebar()" class="header-menu-btn lg:hidden" aria-label="Buka menu navigasi" title="Buka menu navigasi">
        <i class="fa-solid fa-bars-staggered"></i>
      </button>

      <div>
        <p class="header-page-label">@yield('page-title', 'Dashboard')</p>
        <p class="header-page-subtitle">Sistem Perjalanan Dinas</p>
      </div>
    </div>

    <div class="header-right">
      <button class="header-icon-btn" aria-label="Notifikasi" title="Notifikasi">
        <i class="fa-solid fa-bell"></i>
        <span class="header-notification-dot"></span>
      </button>

      <div class="header-separator"></div>

      <div class="header-user">
        <div class="header-user-info">
          <p class="header-user-role">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</p>
          <p class="header-user-dept">{{ auth()->user()->department?->name ?? 'SPPD System' }}</p>
        </div>
        <div class="header-user-avatar" aria-hidden="true">
          {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
        </div>
      </div>

      <form action="{{ route('logout') }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="header-icon-btn" title="Logout" aria-label="Logout">
          <i class="fa-solid fa-right-from-bracket"></i>
        </button>
      </form>
    </div>
  </div>
</header>
