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
        <p class="truncate text-sm font-semibold text-slate-200" title="{{ auth()->user()->username ?? '-' }}">{{ auth()->user()->username ?? '-' }}</p>
        <p class="truncate text-xs text-slate-400">{{ auth()->user()->roles->first()?->label ?? 'Undefined role' }}</p>
      </div>
    </div>
  </div>

  <!-- Konten Navigasi Menu -->
  <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
    <div class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">Menu Utama</div>

    <a href="{{ route('dashboard') }}"
      class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
      <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-house fa-fw"></i></span>
      <span>Beranda</span>
    </a>

    <div class="space-y-1">
      <button type="button"
        class="sidebar-toggle-btn flex w-full items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('sppd.*') ? 'text-slate-100' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}"
        data-target="sppd-menu">
        <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-file-lines fa-fw"></i></span>
        <span class="flex-1 text-left">List Telaah</span>
        <i
          class="fa-solid fa-chevron-down text-xs transition-transform duration-200 {{ request()->routeIs('sppd.*') ? 'rotate-180' : '' }}"></i>
      </button>

      <div id="sppd-menu" class="space-y-1 py-1 pl-8 pr-1 {{ request()->routeIs('sppd.*') ? '' : 'hidden' }}">
        <a href="{{ route('sppd.index') }}"
          class="flex items-center gap-2 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('sppd.index') && !request('filter') ? 'text-cyan-400 bg-slate-800/40' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/20' }}">
          <span class="size-1 rounded-sm bg-current"></span>
          Kepala OPD
        </a>
        <a href="{{ route('sppd.index', ['filter' => 'staff']) }}"
          class="flex items-center gap-2 rounded px-3 py-1.5 text-xs font-medium transition-colors {{ request('filter') === 'staff' ? 'text-cyan-400 bg-slate-800/40' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/20' }}">
          <span class="size-1 rounded-sm bg-current"></span>
          Eselon III, IV & Staf
        </a>
      </div>
    </div>

    @can('sppd.approve')
      <a href="{{ route('sppd.index', ['filter' => 'approval']) }}"
        class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request('filter') === 'approval' ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
        <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-circle-check fa-fw"></i></span>
        <span>Persetujuan</span>
      </a>
    @endcan

    <a href="#"
      class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-100">
      <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-calendar-days fa-fw"></i></span>
      <span>Kalender</span>
    </a>

    <div class="px-3 py-1.5 pt-4 text-xs font-bold uppercase tracking-wider text-slate-500">Pengaturan</div>

    <a href="{{ route('master.users.index') }}"
      class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.users.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
      <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-users fa-fw"></i></span>
      <span>Pegawai</span>
    </a>

    <a href="{{ route('master.budgets.index') }}"
      class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.budgets.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
      <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-coins fa-fw"></i></span>
      <span>DPA</span>
    </a>

    <a href="{{ route('master.departments.index') }}"
      class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.departments.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
      <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-building fa-fw"></i></span>
      <span>Unit Kerja</span>
    </a>

    <a href="{{ route('master.workflows.index') }}"
      class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('master.workflows.*') ? 'bg-cyan-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
      <span class="flex w-5 justify-center text-base"><i class="fa-solid fa-diagram-project fa-fw"></i></span>
      <span>Workflow</span>
    </a>
  </nav>

  <div class="border-t border-slate-800 p-3 lg:hidden">
    <button type="button" onclick="toggleSidebar()"
      class="flex w-full items-center justify-center gap-2 rounded border border-slate-800 bg-slate-950/20 py-2 text-xs font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200">
      <i class="fa-solid fa-chevron-left text-[10px]"></i>
      <span>Tutup Sidebar</span>
    </button>
  </div>
</aside>

<script>
  $(document).ready(function () {
    $('.sidebar-toggle-btn').on('click', function () {
      const targetId = $(this).data('target');
      const $submenu = $('#' + targetId);
      const $chevron = $(this).find('.fa-chevron-down');

      $submenu.slideToggle(200);
      $chevron.toggleClass('rotate-180');
    });
  });
</script>
