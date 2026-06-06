<div>
    <!-- Header Page -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Kalender Perjalanan Dinas</h1>
            <p class="text-sm text-slate-500 font-medium mt-1">Jadwal monitoring perjalanan dinas pegawai yang telah disetujui secara resmi.</p>
        </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
        <!-- Stat Card 1 -->
        <div class="flex items-center gap-4 p-5 rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition duration-200">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                <i class="fa-solid fa-calendar-days text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total SPPD Bulan Ini</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalTravelsThisMonth }}</p>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="flex items-center gap-4 p-5 rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition duration-200">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-plane-departure text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Sedang Berlangsung Hari Ini</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $activeTravelsCount }}</p>
            </div>
        </div>

        <!-- Stat Card 3 (Legend & Info) -->
        <div class="flex items-center gap-4 p-5 rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition duration-200 md:col-span-2 lg:col-span-1">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-slate-600">
                <i class="fa-solid fa-info text-xl"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Warna Agenda</p>
                <div class="flex items-center gap-4 mt-2">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                        <span class="inline-block h-3 w-3 rounded-full bg-sky-500"></span>
                        Disetujui
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                        <span class="inline-block h-3 w-3 rounded-full bg-emerald-500"></span>
                        Selesai
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Calendar Area -->
        <div class="lg:col-span-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div id="calendar" wire:ignore class="min-h-[600px] text-slate-800"></div>
        </div>

        <!-- Right: Sidebar Info & Upcoming -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            
            <!-- Upcoming Travels Card -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-extrabold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-compass text-sky-500"></i>
                        Perjalanan Mendatang
                    </h3>
                    <span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-bold text-sky-600">Next 5</span>
                </div>

                <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-slate-100">
                        @forelse($upcomingSppds as $sppd)
                            <li class="py-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 text-center bg-slate-50 border border-slate-200 rounded-lg p-2 min-w-[3.5rem] shadow-sm">
                                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                            {{ $sppd->start_date->format('M') }}
                                        </p>
                                        <p class="text-lg font-black text-slate-800 leading-none mt-0.5">
                                            {{ $sppd->start_date->format('d') }}
                                        </p>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('sppd.show', $sppd) }}" wire:navigate class="block text-sm font-bold text-slate-900 hover:text-sky-600 transition truncate">
                                            {{ $sppd->user?->name }}
                                        </a>
                                        <p class="text-xs text-slate-500 truncate mt-0.5">
                                            {{ $sppd->purpose }}
                                        </p>
                                        
                                        <!-- Destination and duration info -->
                                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-400 font-semibold">
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fa-solid fa-location-dot"></i>
                                                {{ $sppd->destinations->first()?->regency?->name ?? 'Tujuan' }}
                                            </span>
                                            <span class="text-slate-300">•</span>
                                            <span>{{ $sppd->duration_days }} hari</span>
                                            <span class="text-slate-300">•</span>
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] uppercase font-bold {{ $sppd->status === \App\Enums\SppdStatus::COMPLETED ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-sky-50 text-sky-600 border border-sky-100' }}">
                                                {{ $sppd->status->label() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="py-8 text-center">
                                <div class="text-slate-300 mb-2">
                                    <i class="fa-solid fa-route text-3xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-500">Tidak ada agenda perjalanan dinas mendatang.</p>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Calendar Quick Info -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-3">Panduan Interaksi</h4>
                <ul class="text-xs text-slate-600 space-y-2.5 font-medium">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-sky-500 mt-0.5 shrink-0"></i>
                        <span>Klik pada judul agenda di kalender untuk langsung masuk ke halaman detail SPPD.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-sky-500 mt-0.5 shrink-0"></i>
                        <span>Gunakan tombol navigasi di kiri atas kalender untuk berpindah bulan atau melihat hari ini.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-sky-500 mt-0.5 shrink-0"></i>
                        <span>Gunakan tab di kanan atas untuk mengganti tampilan: Bulanan, Mingguan, atau Harian.</span>
                    </li>
                </ul>
            </div>

        </div>

    </div>

    <!-- FullCalendar Styles custom overrides -->
    <style>
        .fc {
            --fc-border-color: #f1f5f9 !important;
            --fc-button-text-color: #334155 !important;
            --fc-button-bg-color: #ffffff !important;
            --fc-button-border-color: #cbd5e1 !important;
            --fc-button-hover-bg-color: #f8fafc !important;
            --fc-button-hover-border-color: #94a3b8 !important;
            --fc-button-active-bg-color: #f1f5f9 !important;
            --fc-button-active-border-color: #64748b !important;
            --fc-today-bg-color: rgba(14, 165, 233, 0.04) !important;
            font-family: inherit !important;
        }

        .fc .fc-toolbar-title {
            font-size: 1.125rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
        }

        .fc .fc-button {
            padding: 0.4rem 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 700 !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            text-transform: capitalize !important;
        }

        .fc .fc-button-group > .fc-button {
            border-radius: 0.375rem !important;
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
            font-weight: 700 !important;
            color: #475569 !important;
            text-decoration: none !important;
            padding: 6px 8px !important;
        }

        .fc .fc-event {
            border: none !important;
            padding: 3px 6px !important;
            border-radius: 4px !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.15s ease-in-out !important;
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
        
        .fc-theme-standard td, .fc-theme-standard th {
            border: 1px solid #e2e8f0 !important;
        }
    </style>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script>
            document.addEventListener('livewire:navigated', function() {
                initCalendar();
            });

            // Ensure calendar loads on initial full page load if livewire:navigated hasn't fired
            document.addEventListener('DOMContentLoaded', function() {
                initCalendar();
            });

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
        </script>
    @endpush
</div>
