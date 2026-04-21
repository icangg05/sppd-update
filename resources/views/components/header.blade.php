{{-- Top Header Bar --}}
<header class="sticky top-0 z-20 bg-white/80 backdrop-blur-lg border-b border-slate-200">
  <div class="flex items-center justify-between px-4 sm:px-6 h-16">
    {{-- Left: mobile menu + breadcrumb --}}
    <div class="flex items-center gap-3">
      <button onclick="toggleSidebar()" class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>
      <div>
        <h2 class="text-lg font-semibold text-slate-900">@yield('page-title', 'Dashboard')</h2>
      </div>
    </div>

    {{-- Right: notifications + user --}}
    <div class="flex items-center gap-2">
      {{-- Notification bell --}}
      <button class="relative p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
      </button>

      {{-- User dropdown --}}
      <div class="relative" x-data="{ open: false }">
        <form action="{{ route('logout') }}" method="POST" class="inline">
          @csrf
          <button type="submit" class="btn-ghost text-xs">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            Logout
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
