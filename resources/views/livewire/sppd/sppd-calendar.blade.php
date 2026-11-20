<div x-data="{
  dayOpen: false,
  dayLabel: '',
  dayItems: [],
  dayQuery: '',
  get dayFiltered() {
    const q = this.dayQuery.trim().toLowerCase();
    if (!q) return this.dayItems;
    return this.dayItems.filter((i) => [i.name, i.department, i.purpose, i.destination].join(' ').toLowerCase().includes(q));
  },
  get dayCompleted() { return this.dayItems.filter((i) => i.status === 'completed').length; },
}"
  @sppd-calendar-day.window="dayLabel = $event.detail.label; dayItems = $event.detail.items; dayQuery = ''; dayOpen = true">
  {{-- ── Header halaman (title card) ── --}}
  <div
    class="dash-enter relative mb-4 overflow-hidden rounded border border-slate-200 bg-linear-to-br from-white via-white to-primary-50/50 px-5 py-4 shadow-sm">
    {{-- Watermark institusional (tipis, hanya karakter). --}}
    <i class="fa-regular fa-calendar-days pointer-events-none absolute -right-3 -top-4 text-8xl text-primary-500/6"
      aria-hidden="true"></i>

    <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="min-w-0 leading-tight">
        <span
          class="mb-1.5 inline-flex items-center gap-1.5 rounded bg-primary-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.15em] text-primary-700 ring-1 ring-inset ring-primary-600/15">
          <i class="fa-solid fa-calendar-check text-[9px]"></i> Monitoring Agenda
        </span>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Kalender Perjalanan Dinas</h1>
        <p class="mt-1 text-xs text-slate-500">Jadwal monitoring perjalanan dinas pegawai yang telah disetujui resmi</p>
      </div>

      {{-- Penanda tanggal hari ini --}}
      <div
        class="inline-flex shrink-0 items-center gap-2 self-start rounded border border-slate-200 bg-white px-3 py-2 shadow-2xs sm:self-auto">
        <i class="fa-regular fa-clock text-primary-500"></i>
        <span class="text-sm font-semibold text-slate-700">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
      </div>
    </div>
  </div>

  {{-- ── Ringkasan cepat ──
       Kartu: label + angka di atas, ikon di kanan, keterangan periode di pita bawah. --}}
  @php
    $nextWeekLabel = $nextWeekStart->isSameMonth($nextWeekEnd)
      ? $nextWeekStart->format('d') . '–' . $nextWeekEnd->translatedFormat('d F Y')
      : $nextWeekStart->translatedFormat('d F') . ' – ' . $nextWeekEnd->translatedFormat('d F Y');
    $stats = [
      [
        'label' => 'Total SPPD Bulan Ini',
        'value' => $totalTravelsThisMonth,
        'icon' => 'fa-calendar-days',
        'tone' => 'bg-primary-50 text-primary-600 ring-primary-100',
        'note' => 'Keberangkatan ' . now()->translatedFormat('F Y'),
      ],
      [
        'label' => 'Sedang Berlangsung Hari Ini',
        'value' => $activeTravelsCount,
        'icon' => 'fa-plane-departure',
        'tone' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        'note' => 'Masa perjalanannya mencakup ' . now()->translatedFormat('d F'),
      ],
      [
        'label' => 'Berangkat 7 Hari ke Depan',
        'value' => $departingNextWeekCount,
        'icon' => 'fa-suitcase-rolling',
        'tone' => 'bg-amber-50 text-amber-600 ring-amber-100',
        'note' => 'Keberangkatan ' . $nextWeekLabel,
      ],
    ];
  @endphp
  <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
    @foreach ($stats as $stat)
      <div class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
        <div class="flex items-start justify-between gap-3 px-5 pb-4 pt-4">
          <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $stat['label'] }}</p>
            <p class="mt-2 flex items-baseline gap-1.5">
              <span class="text-3xl font-bold leading-none tracking-tight text-slate-900 tabular-nums">{{ $stat['value'] }}</span>
              <span class="text-xs font-semibold text-slate-400">SPPD</span>
            </p>
          </div>
          <span class="flex size-10 shrink-0 items-center justify-center rounded ring-1 ring-inset {{ $stat['tone'] }}">
            <i class="fa-solid {{ $stat['icon'] }}"></i>
          </span>
        </div>
        <p class="flex items-center gap-1.5 border-t border-slate-100 bg-slate-50/70 px-5 py-2 text-xs text-slate-500">
          <i class="fa-regular fa-calendar text-[11px] text-slate-400"></i>
          {{ $stat['note'] }}
        </p>
      </div>
    @endforeach
  </div>

  {{-- ── Grid utama ── --}}
  <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-12">

    {{-- Kiri: area kalender --}}
    <div class="dash-enter overflow-hidden rounded border border-slate-200 bg-white shadow-sm lg:col-span-8">
      <div class="p-3 sm:p-5">
        <div id="calendar" wire:ignore class="text-slate-800"></div>
      </div>

      {{-- Legenda + cara melihat semua nama --}}
      <div
        class="flex flex-col gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-3 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div class="flex items-center gap-4 font-semibold text-slate-600">
          <span class="inline-flex items-center gap-1.5">
            <span class="inline-block size-3 rounded-sm bg-primary-100 ring-1 ring-inset ring-primary-300"></span>
            Disetujui
          </span>
          <span class="inline-flex items-center gap-1.5">
            <span class="inline-block size-3 rounded-sm bg-emerald-100 ring-1 ring-inset ring-emerald-300"></span>
            Selesai
          </span>
        </div>
        <p class="flex items-center gap-1.5 text-slate-500">
          <i class="fa-regular fa-hand-pointer text-slate-400"></i>
          Klik tanggal atau <span class="font-semibold text-primary-700">+N lainnya</span> untuk melihat semua nama
        </p>
      </div>
    </div>

    {{-- Kanan: info & agenda mendatang --}}
    <div class="flex flex-col gap-4 lg:col-span-4">

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
                      {{ $sppd->destinations->first()?->place_name ?? 'Tujuan' }}
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
      <div class="dash-enter rounded border border-slate-200 bg-slate-50 p-5">
        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Panduan Interaksi</h4>
        <ul class="space-y-2.5 text-xs font-medium text-slate-600">
          <li class="flex items-start gap-2">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-primary-500"></i>
            <span>Klik nama di kalender untuk langsung membuka detail SPPD.</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-primary-500"></i>
            <span>Tiap tanggal menampilkan satu nama. Klik tanggalnya atau tautan <strong class="font-semibold">+N lainnya</strong> untuk melihat semua pegawai yang bertugas, lengkap dengan pencarian.</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-primary-500"></i>
            <span>Panah di kiri atas berpindah periode; tombol <strong class="font-semibold">Bulan</strong>/<strong class="font-semibold">Minggu</strong> di kanan atas mengganti tampilan.</span>
          </li>
        </ul>
      </div>

    </div>
  </div>

  {{-- ── Modal: semua perjalanan pada satu tanggal ── --}}
  <x-ui.modal show="dayOpen" title="Perjalanan Dinas per Tanggal" icon="fa-solid fa-calendar-day text-primary-600"
    maxWidth="max-w-xl">
    <div class="space-y-3">
      <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
          <p class="text-base font-bold text-slate-900" x-text="dayLabel"></p>
          <p class="mt-0.5 text-xs text-slate-500"><span class="font-semibold tabular-nums text-slate-700" x-text="dayItems.length"></span> perjalanan dinas pada tanggal ini</p>
        </div>
        <div class="flex items-center gap-1.5" x-show="dayItems.length">
          {{-- Warna mengikuti legenda kalender (biru = disetujui, hijau = selesai). --}}
          <x-ui.badge color="cyan" class="gap-1 font-semibold" x-show="dayItems.length - dayCompleted > 0"><span class="tabular-nums" x-text="dayItems.length - dayCompleted"></span> Disetujui</x-ui.badge>
          <x-ui.badge color="emerald" class="gap-1 font-semibold" x-show="dayCompleted > 0"><span class="tabular-nums" x-text="dayCompleted"></span> Selesai</x-ui.badge>
        </div>
      </div>

      <div x-show="dayItems.length > 5">
        <x-form.input type="search" id="calendar-day-search" x-model="dayQuery" data-autofocus
          icon="fa-solid fa-magnifying-glass" placeholder="Cari nama, instansi, maksud, atau tujuan…" />
      </div>

      <ul class="divide-y divide-slate-100 overflow-hidden rounded border border-slate-200" x-show="dayFiltered.length">
        <template x-for="item in dayFiltered" :key="item.id">
          <li>
            <a :href="item.url" wire:navigate @click="dayOpen = false"
              class="group flex items-start gap-3 px-3.5 py-3 transition-colors hover:bg-slate-50">
              <span class="mt-1.5 size-2 shrink-0 rounded-full"
                :class="item.status === 'completed' ? 'bg-emerald-500' : 'bg-primary-500'"></span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-slate-900 transition-colors group-hover:text-primary-700" x-text="item.name"></p>
                <p class="text-xs text-slate-500" x-show="item.department" x-text="item.department"></p>
                <p class="mt-1 text-xs leading-relaxed text-slate-600" x-show="item.purpose" x-text="item.purpose"></p>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold text-slate-500">
                  <span class="inline-flex items-center gap-1" x-show="item.destination">
                    <i class="fa-solid fa-location-dot text-slate-400"></i>
                    <span x-text="item.destination"></span>
                  </span>
                  <span class="inline-flex items-center gap-1 tabular-nums">
                    <i class="fa-regular fa-calendar text-slate-400"></i>
                    <span x-text="item.period + ' · ' + item.days + ' hari'"></span>
                  </span>
                  <span class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase ring-1 ring-inset"
                    :class="item.status === 'completed' ? 'bg-emerald-50 text-emerald-600 ring-emerald-100' : 'bg-primary-50 text-primary-600 ring-primary-100'"
                    x-text="item.status === 'completed' ? 'Selesai' : 'Disetujui'"></span>
                </div>
              </div>
              <i class="fa-solid fa-chevron-right mt-1 shrink-0 text-xs text-slate-300 transition-all group-hover:translate-x-0.5 group-hover:text-primary-500"></i>
            </a>
          </li>
        </template>
      </ul>

      {{-- Kosong: tanggal tanpa perjalanan, atau pencarian tak cocok --}}
      <div class="rounded border border-dashed border-slate-200 px-4 py-8 text-center" x-show="!dayFiltered.length">
        <i class="fa-solid fa-route mb-2 text-2xl text-slate-300"></i>
        <p class="text-sm font-semibold text-slate-500"
          x-text="dayItems.length ? 'Tidak ada yang cocok dengan pencarian.' : 'Tidak ada perjalanan dinas pada tanggal ini.'"></p>
      </div>
    </div>
  </x-ui.modal>

  {{-- Override tampilan FullCalendar agar selaras dengan identitas biru SPPD --}}
  <style>
    .fc {
      --fc-border-color: #e2e8f0 !important;
      --fc-button-text-color: #334155 !important;
      --fc-button-bg-color: #ffffff !important;
      --fc-button-border-color: #cbd5e1 !important;
      --fc-button-hover-bg-color: #f8fafc !important;
      --fc-button-hover-border-color: #94a3b8 !important;
      --fc-button-active-bg-color: #edf6fc !important;
      --fc-button-active-border-color: #1e80c6 !important;
      --fc-today-bg-color: rgba(30, 128, 198, 0.06) !important;
      font-family: inherit !important;
    }

    .fc .fc-toolbar.fc-header-toolbar {
      margin-bottom: 1rem !important;
    }

    .fc .fc-toolbar-title {
      font-size: 1.125rem !important;
      font-weight: 700 !important;
      color: #0f172a !important;
    }

    .fc .fc-button {
      padding: 0.375rem 0.75rem !important;
      font-size: 0.8125rem !important;
      font-weight: 600 !important;
      border-radius: var(--radius) !important;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
      text-transform: none !important;
    }

    .fc .fc-button:focus {
      box-shadow: 0 0 0 2px rgba(30, 128, 198, 0.25) !important;
    }

    .fc .fc-button:disabled {
      opacity: 0.5 !important;
    }

    .fc .fc-button-active {
      color: #1e80c6 !important;
    }

    .fc .fc-button-group > .fc-button {
      border-radius: var(--radius) !important;
      margin-left: 2px !important;
      margin-right: 2px !important;
    }

    .fc .fc-col-header-cell {
      padding: 6px 0 !important;
      background-color: #f8fafc !important;
    }

    .fc .fc-col-header-cell-cushion {
      font-size: 0.6875rem !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.06em !important;
      color: #64748b !important;
      text-decoration: none !important;
    }

    /* Sel hari: tinggi seragam (satu nama + tautan "+N lainnya"), bisa diklik untuk modal. */
    .fc .fc-daygrid-day {
      cursor: pointer;
      transition: background-color 0.15s ease;
    }

    .fc .fc-daygrid-day:hover {
      background-color: #f8fafc;
    }

    .fc .fc-dayGridMonth-view .fc-daygrid-day-frame {
      min-height: 5.25rem;
    }

    .fc .fc-daygrid-body-natural .fc-daygrid-day-events {
      margin-bottom: 4px !important;
    }

    .fc .fc-day-other {
      background-color: #fbfcfd;
    }

    .fc .fc-daygrid-day-number {
      display: inline-flex;
      min-width: 1.625rem;
      height: 1.625rem;
      align-items: center;
      justify-content: center;
      margin: 4px 4px 0;
      padding: 0 6px !important;
      border-radius: 999px;
      font-size: 0.8125rem !important;
      font-weight: 600 !important;
      color: #475569 !important;
      text-decoration: none !important;
    }

    /* Minggu (tanggal merah). */
    .fc .fc-day-sun .fc-daygrid-day-number {
      color: #e11d48 !important;
    }

    .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
      background-color: #1e80c6;
      color: #ffffff !important;
      font-weight: 700 !important;
    }

    /* Agenda: pita lembut sesuai status (lihat legenda di bawah kalender). */
    .fc .sppd-ev--approved {
      --fc-event-bg-color: #d0e9f8;
      --fc-event-border-color: #a6d5f1;
      --fc-event-text-color: #143f5c;
    }

    .fc .sppd-ev--done {
      --fc-event-bg-color: #d1fae5;
      --fc-event-border-color: #a7f3d0;
      --fc-event-text-color: #064e3b;
    }

    .fc .fc-daygrid-event {
      margin-top: 2px !important;
      padding: 2px 6px !important;
      border-radius: var(--radius) !important;
      font-size: 0.75rem !important;
      font-weight: 600 !important;
      cursor: pointer !important;
      transition: filter 0.15s ease !important;
    }

    .fc .fc-daygrid-event:hover {
      filter: brightness(0.96) saturate(1.2);
    }

    /* Tautan "+N lainnya" → modal semua nama di tanggal itu. Jangan timpa margin
       .fc-daygrid-day-bottom: margin-top inline dari FullCalendar menaruh tautan
       di bawah pita agenda multi-hari. */
    .fc .fc-daygrid-more-link {
      margin-top: 2px !important;
      padding: 2px 6px !important;
      border-radius: var(--radius) !important;
      font-size: 0.75rem !important;
      font-weight: 700 !important;
      color: #1e80c6 !important;
      text-decoration: none !important;
    }

    .fc .fc-daygrid-more-link:hover {
      background-color: #edf6fc !important;
      color: #175987 !important;
    }

    @media (max-width: 639px) {
      .fc .fc-toolbar.fc-header-toolbar {
        flex-wrap: wrap;
        gap: 0.5rem;
      }

      .fc .fc-toolbar-chunk:nth-child(2) {
        order: -1;
        width: 100%;
        text-align: center;
      }

      .fc .fc-toolbar-title {
        font-size: 1rem !important;
      }

      .fc .fc-dayGridMonth-view .fc-daygrid-day-frame {
        min-height: 4.25rem;
      }

      .fc .fc-daygrid-day-number {
        min-width: 1.375rem;
        height: 1.375rem;
        margin: 2px 2px 0;
        padding: 0 4px !important;
        font-size: 0.75rem !important;
      }

      .fc .fc-daygrid-event {
        padding: 1px 3px !important;
        font-size: 0.625rem !important;
      }

      .fc .fc-daygrid-more-link {
        padding: 1px 3px !important;
        font-size: 0.6875rem !important;
      }

      .fc .sppd-more-label {
        display: none;
      }
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  <script>
    (function() {
      function parseDate(str) {
        var p = str.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
      }

      // Semua agenda yang berlangsung pada tanggal itu (end FullCalendar eksklusif), urut nama.
      function eventsOn(calendar, date) {
        var t = date.getTime();
        return calendar.getEvents().filter(function(ev) {
          var start = ev.start.getTime();
          var end = ev.end ? ev.end.getTime() : start + 86400000;
          return start <= t && t < end;
        }).map(function(ev) {
          var p = ev.extendedProps;
          return {
            id: ev.id,
            url: ev.url,
            name: p.name,
            purpose: p.purpose,
            department: p.department,
            destination: p.destination,
            period: p.period,
            days: p.days,
            status: p.status,
          };
        }).sort(function(a, b) {
          return a.name.localeCompare(b.name, 'id');
        });
      }

      function openDay(calendar, date) {
        window.dispatchEvent(new CustomEvent('sppd-calendar-day', {
          detail: {
            label: date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
            items: eventsOn(calendar, date),
          },
        }));
      }

      function initCalendar() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        // If already initialized, avoid re-initializing
        if (calendarEl.innerHTML !== '') return;

        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          locale: 'id',
          themeSystem: 'standard',
          height: 'auto',
          fixedWeekCount: false,
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek'
          },
          buttonText: {
            today: 'Hari ini',
            month: 'Bulan',
            week: 'Minggu'
          },
          buttonHints: {
            prev: 'Sebelumnya',
            next: 'Berikutnya',
            today: 'Hari ini'
          },
          // Bulan: satu nama per tanggal + "+N lainnya" agar baris tidak memanjang.
          views: {
            dayGridMonth: { dayMaxEvents: 1 },
            dayGridWeek: {
              dayMaxEvents: 8,
              dayHeaderFormat: { weekday: 'short', day: 'numeric', month: 'short' }
            }
          },
          moreLinkContent: function(arg) {
            return { html: '+' + arg.num + '<span class="sppd-more-label"> lainnya</span>' };
          },
          moreLinkHint: function(num) {
            return 'Lihat ' + num + ' perjalanan lainnya';
          },
          // Perjalanan singkat didahulukan agar satu perjalanan panjang (mis. diklat
          // berminggu-minggu) tidak menguasai slot satu-nama di setiap tanggal.
          eventOrder: 'duration,title',
          events: @json($events),
          eventDidMount: function(info) {
            var p = info.event.extendedProps;
            info.el.title = p.name + (p.purpose ? ' — ' + p.purpose : '');
          },
          eventClick: function(info) {
            if (info.event.url) {
              info.jsEvent.preventDefault();
              Livewire.navigate(info.event.url);
            }
          },
          dateClick: function(info) {
            openDay(calendar, info.date);
          }
        });

        // "+N lainnya" membuka modal daftar lengkap, bukan popover bawaan FullCalendar.
        // Dicegat di fase capture karena moreLinkClick bawaan selalu membuka popover
        // atau pindah tanggal (bisa ke bulan lain bila tanggalnya di luar bulan aktif).
        function onMoreLink(e) {
          var link = e.target.closest && e.target.closest('.fc-more-link');
          if (!link) return;
          if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
          e.stopPropagation();
          var cell = link.closest('[data-date]');
          if (cell) openDay(calendar, parseDate(cell.getAttribute('data-date')));
        }
        calendarEl.addEventListener('click', onMoreLink, true);
        calendarEl.addEventListener('keydown', onMoreLink, true);

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
