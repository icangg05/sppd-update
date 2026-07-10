<div>
  {{-- ── Header halaman ── --}}
  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
      <div class="flex size-11 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
        <i class="fa-regular fa-calendar-days text-lg"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Kalender Perjalanan Dinas</h1>
        <p class="mt-0.5 text-sm text-slate-500">Jadwal monitoring perjalanan dinas pegawai yang telah disetujui resmi.</p>
      </div>
    </div>

    {{-- Penanda tanggal hari ini --}}
    <div class="inline-flex items-center gap-2 self-start rounded border border-slate-200 bg-white px-3 py-2 shadow-2xs sm:self-auto">
      <i class="fa-regular fa-clock text-primary-500"></i>
      <span class="text-sm font-semibold text-slate-700">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
    </div>
  </div>

  {{-- ── Ringkasan cepat ── --}}
  <div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
    {{-- Total bulan ini --}}
    <div class="dash-enter flex items-center gap-4 rounded border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex size-12 shrink-0 items-center justify-center rounded bg-primary-50 text-primary-600 ring-1 ring-primary-100">
        <i class="fa-solid fa-calendar-days text-lg"></i>
      </div>
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total SPPD Bulan Ini</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 tabular-nums">{{ $totalTravelsThisMonth }}</p>
      </div>
    </div>

    {{-- Sedang berlangsung --}}
    <div class="dash-enter flex items-center gap-4 rounded border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex size-12 shrink-0 items-center justify-center rounded bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
        <i class="fa-solid fa-plane-departure text-lg"></i>
      </div>
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Sedang Berlangsung Hari Ini</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 tabular-nums">{{ $activeTravelsCount }}</p>
      </div>
    </div>

    {{-- Legenda warna --}}
    <div class="dash-enter flex items-center gap-4 rounded border border-slate-200 bg-white p-5 shadow-sm md:col-span-2 lg:col-span-1">
      <div class="flex size-12 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500 ring-1 ring-slate-200">
        <i class="fa-solid fa-palette text-lg"></i>
      </div>
      <div class="flex-1">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Status Warna Agenda</p>
        <div class="mt-2 flex items-center gap-4">
          <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
            <span class="inline-block size-2.5 rounded-full bg-primary-500 ring-2 ring-primary-100"></span>
            Disetujui
          </div>
          <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
            <span class="inline-block size-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-100"></span>
            Selesai
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Grid utama ── --}}
  <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">

    {{-- Kiri: area kalender --}}
    <div class="dash-enter rounded border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:col-span-8">
      <div id="calendar" wire:ignore class="min-h-150 text-slate-800"></div>
    </div>

    {{-- Kanan: info & agenda mendatang --}}
    <div class="flex flex-col gap-6 lg:col-span-4">

      {{-- Perjalanan mendatang --}}
      <div class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900">
            <i class="fa-solid fa-compass text-primary-500"></i>
            Perjalanan Mendatang
          </h3>
          <span class="rounded bg-primary-50 px-2 py-0.5 text-[11px] font-bold text-primary-600 ring-1 ring-inset ring-primary-100">5 Terdekat</span>
        </div>

        <ul role="list" class="divide-y divide-slate-100">
          @forelse($upcomingSppds as $sppd)
            <li>
              <a href="{{ route('sppd.show', $sppd) }}" wire:navigate
                class="group flex items-start gap-3 px-5 py-4 transition-colors hover:bg-slate-50/70">
                <div class="min-w-13 shrink-0 rounded border border-slate-200 bg-slate-50 p-2 text-center shadow-2xs transition-colors group-hover:border-primary-200 group-hover:bg-primary-50">
                  <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 group-hover:text-primary-600">
                    {{ $sppd->start_date->locale('id')->translatedFormat('M') }}
                  </p>
                  <p class="mt-0.5 text-lg font-bold leading-none text-slate-800 tabular-nums group-hover:text-primary-700">
                    {{ $sppd->start_date->format('d') }}
                  </p>
                </div>

                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-bold text-slate-900 transition-colors group-hover:text-primary-600">
                    {{ $sppd->user?->name }}
                  </p>
                  <p class="mt-0.5 truncate text-xs text-slate-500">
                    {{ $sppd->purpose }}
                  </p>

                  <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold text-slate-500">
                    <span class="inline-flex items-center gap-1">
                      <i class="fa-solid fa-location-dot text-slate-400"></i>
                      {{ $sppd->destinations->first()?->regency?->name ?? 'Tujuan' }}
                    </span>
                    <span class="text-slate-300">•</span>
                    <span>{{ $sppd->duration_days }} hari</span>
                    <span class="text-slate-300">•</span>
                    <span class="inline-block rounded px-1.5 py-0.5 text-[9px] font-bold uppercase ring-1 ring-inset {{ $sppd->status === \App\Enums\SppdStatus::COMPLETED ? 'bg-emerald-50 text-emerald-600 ring-emerald-100' : 'bg-primary-50 text-primary-600 ring-primary-100' }}">
                      {{ $sppd->status->label() }}
                    </span>
                  </div>
                </div>

                <i class="fa-solid fa-chevron-right mt-1 text-xs text-slate-300 transition-all group-hover:translate-x-0.5 group-hover:text-primary-500"></i>
              </a>
            </li>
          @empty
            <li class="px-5 py-10 text-center">
              <div class="mb-2 text-slate-300">
                <i class="fa-solid fa-route text-3xl"></i>
              </div>
              <p class="text-sm font-semibold text-slate-500">Tidak ada agenda perjalanan dinas mendatang.</p>
            </li>
          @endforelse
        </ul>
      </div>

      {{-- Panduan interaksi --}}
      <div class="rounded border border-slate-200 bg-slate-50 p-5">
        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Panduan Interaksi</h4>
        <ul class="space-y-2.5 text-xs font-medium text-slate-600">
          <li class="flex items-start gap-2">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-primary-500"></i>
            <span>Klik pada judul agenda di kalender untuk langsung masuk ke halaman detail SPPD.</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-primary-500"></i>
            <span>Gunakan tombol navigasi di kiri atas kalender untuk berpindah bulan atau melihat hari ini.</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-primary-500"></i>
            <span>Gunakan tab di kanan atas untuk mengganti tampilan: Bulanan, Mingguan, atau Harian.</span>
          </li>
        </ul>
      </div>

    </div>
  </div>

  {{-- Override tampilan FullCalendar agar selaras dengan identitas biru SPPD --}}
  <style>
    .fc {
      --fc-border-color: #f1f5f9 !important;
      --fc-button-text-color: #334155 !important;
      --fc-button-bg-color: #ffffff !important;
      --fc-button-border-color: #cbd5e1 !important;
      --fc-button-hover-bg-color: #f8fafc !important;
      --fc-button-hover-border-color: #94a3b8 !important;
      --fc-button-active-bg-color: #eff6fc !important;
      --fc-button-active-border-color: #1e80c6 !important;
      --fc-today-bg-color: rgba(30, 128, 198, 0.05) !important;
      font-family: inherit !important;
    }

    .fc .fc-toolbar-title {
      font-size: 1.125rem !important;
      font-weight: 700 !important;
      color: #0f172a !important;
    }

    .fc .fc-button {
      padding: 0.4rem 0.75rem !important;
      font-size: 0.8125rem !important;
      font-weight: 600 !important;
      border-radius: 0.25rem !important;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
      text-transform: capitalize !important;
    }

    .fc .fc-button:focus {
      box-shadow: 0 0 0 2px rgba(30, 128, 198, 0.25) !important;
    }

    .fc .fc-button-active {
      color: #1e80c6 !important;
    }

    .fc .fc-button-group > .fc-button {
      border-radius: 0.25rem !important;
      margin-left: 2px !important;
      margin-right: 2px !important;
    }

    .fc .fc-col-header-cell {
      padding: 8px 0 !important;
      background-color: #f8fafc !important;
    }

    .fc .fc-col-header-cell-cushion {
      font-size: 0.75rem !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.05em !important;
      color: #64748b !important;
      text-decoration: none !important;
    }

    .fc .fc-daygrid-day-number {
      font-size: 0.8125rem !important;
      font-weight: 600 !important;
      color: #475569 !important;
      text-decoration: none !important;
      padding: 6px 8px !important;
    }

    .fc .fc-day-today .fc-daygrid-day-number {
      color: #1e80c6 !important;
      font-weight: 800 !important;
    }

    .fc .fc-event {
      border: none !important;
      padding: 3px 6px !important;
      border-radius: 4px !important;
      font-size: 0.75rem !important;
      font-weight: 600 !important;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
      transition: all 0.15s ease-in-out !important;
      cursor: pointer !important;
    }

    .fc .fc-event:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
      filter: brightness(0.95) !important;
    }

    .fc .fc-daygrid-day.fc-day-today {
      background-color: var(--fc-today-bg-color) !important;
    }

    .fc .fc-daygrid-event-harness {
      margin-top: 2px !important;
      margin-bottom: 2px !important;
    }

    .fc-theme-standard td,
    .fc-theme-standard th {
      border: 1px solid #e2e8f0 !important;
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  <script>
    (function() {
      function initCalendar() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        // If already initialized, avoid re-initializing
        if (calendarEl.innerHTML !== '') return;

        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          locale: 'id',
          themeSystem: 'standard',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
          },
          events: @json($events),
          eventClick: function(info) {
            if (info.event.url) {
              info.jsEvent.preventDefault();
              Livewire.navigate(info.event.url);
            }
          }
        });
        calendar.render();
      }

      if (typeof FullCalendar !== 'undefined') {
        initCalendar();
      } else {
        var checkInterval = setInterval(function() {
          if (typeof FullCalendar !== 'undefined') {
            clearInterval(checkInterval);
            initCalendar();
          }
        }, 50);
      }
    })();
  </script>
</div>
