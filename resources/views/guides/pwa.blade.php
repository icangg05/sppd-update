@extends('layouts.app')

@section('title', 'Panduan Install Aplikasi')
@section('page-title', 'Panduan Install Aplikasi')

@section('content')
  <div class="mx-auto max-w-4xl space-y-6"
    x-data="{
      prompt: null,
      installed: false,
      init() {
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) this.installed = true;
        window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); this.prompt = e; });
        window.addEventListener('appinstalled', () => { this.installed = true; this.prompt = null; });
      },
      async install() {
        if (!this.prompt) return;
        this.prompt.prompt();
        await this.prompt.userChoice;
        this.prompt = null;
      },
    }">

    {{-- ══ Hero ══ --}}
    <section
      class="dash-enter relative isolate overflow-hidden rounded-xl border border-primary-700/20 bg-linear-to-br from-primary-600 to-primary-800 text-white shadow-sm">

      {{-- Ornamen: pola titik + dua cincin konsentris (inline agar tak bergantung build) --}}
      <div class="pointer-events-none absolute inset-0 -z-10 opacity-70"
        style="background-image: radial-gradient(rgba(255,255,255,.14) 1px, transparent 1.4px); background-size: 16px 16px;"
        aria-hidden="true"></div>
      <span class="pointer-events-none absolute -right-16 -top-16 -z-10 size-56 rounded-full border border-white/10" aria-hidden="true"></span>
      <span class="pointer-events-none absolute -right-4 -bottom-24 -z-10 size-64 rounded-full border border-white/10" aria-hidden="true"></span>

      <div class="relative grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center lg:gap-10">
        {{-- Kiri: pesan --}}
        <div class="min-w-0">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold tracking-wide ring-1 ring-inset ring-white/25">
            <i class="fa-solid fa-download text-[10px]"></i> Progressive Web App
          </span>

          <h1 class="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
            Pasang SPPD di perangkat Anda
          </h1>
          <p class="mt-2 max-w-lg text-sm leading-relaxed text-primary-50/90">
            Tambahkan SPPD ke <strong class="font-semibold text-white">layar utama</strong> HP atau laptop.
            Sekali ketuk langsung terbuka — tanpa mengetik alamat, tampil layar penuh seperti aplikasi biasa.
          </p>

          {{-- Aksi utama --}}
          <div class="mt-5 flex flex-wrap items-center gap-3">
            <button type="button" x-show="prompt && !installed" x-cloak @click="install()"
              class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
              <i class="fa-solid fa-circle-down"></i> Install Sekarang
            </button>

            <span x-show="installed" x-cloak
              class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3.5 py-2 text-sm font-semibold ring-1 ring-inset ring-white/25">
              <i class="fa-solid fa-circle-check"></i> Sudah terpasang di perangkat ini
            </span>

            <span x-show="!prompt && !installed" x-cloak class="inline-flex items-center gap-1.5 text-xs text-primary-50/85">
              <i class="fa-solid fa-arrow-down"></i> Ikuti langkah sesuai perangkat Anda di bawah
            </span>
          </div>
        </div>

        {{-- Kanan: mockup HP (ukuran tetap, tidak bisa melebar/overflow) --}}
        <div class="mx-auto w-44 shrink-0 select-none" aria-hidden="true">
          <div class="rounded-[2rem] border-[6px] border-white/20 bg-primary-950/30 p-2 shadow-2xl shadow-primary-950/40">
            <div class="overflow-hidden rounded-3xl bg-linear-to-b from-primary-50 to-white">
              {{-- Status bar --}}
              <div class="flex items-center justify-between px-3.5 pt-2.5 text-[9px] font-medium text-slate-400">
                <span>09.41</span>
                <span class="flex gap-1"><i class="fa-solid fa-signal"></i><i class="fa-solid fa-wifi"></i><i class="fa-solid fa-battery-full"></i></span>
              </div>
              {{-- Home screen --}}
              <div class="px-4 pb-6 pt-5">
                <div class="flex flex-col items-center gap-2">
                  <img src="{{ asset('img/icon-192.png') }}" alt="Ikon SPPD"
                    class="size-16 rounded-2xl shadow-lg ring-1 ring-black/5">
                  <span class="text-[11px] font-semibold text-slate-600">SPPD</span>
                </div>
                {{-- Baris ikon palsu sebagai konteks layar --}}
                <div class="mt-5 grid grid-cols-4 gap-2.5">
                  @foreach (range(1, 8) as $i)
                    <span class="aspect-square rounded-lg bg-slate-200/70"></span>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- ══ Langkah per perangkat ══ --}}
    @php
      $platforms = [
        [
          'label' => 'HP Android',
          'hint' => 'lewat browser Chrome',
          'icon' => 'fa-brands fa-android',
          'tint' => 'bg-emerald-100 text-emerald-700',
          'ring' => 'ring-emerald-100',
          'steps' => [
            'Buka SPPD di browser <strong class="font-semibold text-slate-700">Chrome</strong>.',
            'Ketuk menu <strong class="font-semibold text-slate-700">⋮</strong> (tiga titik) di kanan atas.',
            'Pilih <strong class="font-semibold text-slate-700">Instal aplikasi</strong> / <strong class="font-semibold text-slate-700">Tambahkan ke layar utama</strong>.',
            'Ketuk <strong class="font-semibold text-slate-700">Instal</strong> — ikon SPPD muncul di layar utama.',
          ],
        ],
        [
          'label' => 'iPhone / iPad',
          'hint' => 'wajib lewat Safari',
          'icon' => 'fa-brands fa-apple',
          'tint' => 'bg-slate-200 text-slate-700',
          'ring' => 'ring-slate-200',
          'steps' => [
            'Buka SPPD di browser <strong class="font-semibold text-slate-700">Safari</strong> (bukan Chrome).',
            'Ketuk tombol <strong class="font-semibold text-slate-700">Bagikan</strong> <i class="fa-solid fa-arrow-up-from-bracket text-[11px]"></i> di bawah layar.',
            'Gulir, pilih <strong class="font-semibold text-slate-700">Tambah ke Layar Utama</strong>.',
            'Ketuk <strong class="font-semibold text-slate-700">Tambah</strong> di kanan atas.',
          ],
        ],
        [
          'label' => 'Laptop / PC',
          'hint' => 'Chrome atau Edge',
          'icon' => 'fa-solid fa-laptop',
          'tint' => 'bg-primary-100 text-primary-700',
          'ring' => 'ring-primary-100',
          'steps' => [
            'Buka SPPD di <strong class="font-semibold text-slate-700">Chrome</strong> atau <strong class="font-semibold text-slate-700">Edge</strong>.',
            'Klik ikon <strong class="font-semibold text-slate-700">Instal</strong> <i class="fa-solid fa-circle-down text-[11px]"></i> di ujung kanan kolom alamat.',
            'Klik <strong class="font-semibold text-slate-700">Instal</strong> pada kotak konfirmasi.',
            'SPPD terbuka di jendela sendiri & bisa disematkan ke taskbar.',
          ],
        ],
      ];
    @endphp

    <section>
      <div class="mb-4 flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center rounded-full bg-primary-50 text-primary-700">
          <i class="fa-solid fa-list-check text-sm"></i>
        </span>
        <div>
          <h2 class="text-base font-bold tracking-tight text-slate-800">Cara memasang</h2>
          <p class="text-xs text-slate-500">Pilih perangkat Anda, lalu ikuti langkahnya — cukup sekali saja.</p>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($platforms as $p)
          <div class="dash-enter group relative flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md">
            {{-- Ornamen sudut: ikon perangkat samar --}}
            <i class="{{ $p['icon'] }} pointer-events-none absolute -right-3 -top-2 text-6xl text-slate-900/[0.03] transition-transform duration-300 group-hover:scale-110" aria-hidden="true"></i>

            {{-- Header kartu --}}
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
              <span class="flex size-9 shrink-0 items-center justify-center rounded-lg text-base ring-4 ring-inset {{ $p['tint'] }} {{ $p['ring'] }}">
                <i class="{{ $p['icon'] }}"></i>
              </span>
              <div class="min-w-0">
                <h3 class="truncate text-sm font-bold text-slate-800">{{ $p['label'] }}</h3>
                <p class="truncate text-[11px] text-slate-500">{{ $p['hint'] }}</p>
              </div>
              <span class="ml-auto shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-400 ring-1 ring-slate-200">
                {{ count($p['steps']) }} langkah
              </span>
            </div>

            {{-- Langkah dengan rel penghubung --}}
            <ol class="relative flex-1 space-y-4 px-4 py-4">
              <span class="pointer-events-none absolute bottom-4 left-[27px] top-4 w-px bg-slate-200" aria-hidden="true"></span>
              @foreach ($p['steps'] as $i => $step)
                <li class="relative flex gap-3">
                  <span class="relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full bg-primary-600 text-[11px] font-bold text-white ring-4 ring-white">
                    {{ $i + 1 }}
                  </span>
                  <p class="pt-0.5 text-[13px] leading-relaxed text-slate-600">{!! $step !!}</p>
                </li>
              @endforeach
            </ol>
          </div>
        @endforeach
      </div>
    </section>

    {{-- ══ Manfaat ══ --}}
    <section class="dash-enter relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      {{-- Ornamen garis diagonal tipis di kanan --}}
      <div class="pointer-events-none absolute inset-y-0 right-0 w-32 opacity-[0.35]"
        style="background-image: repeating-linear-gradient(-45deg, #e2e8f0 0 1px, transparent 1px 11px);" aria-hidden="true"></div>

      <div class="relative mb-4 flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center rounded-full bg-primary-50 text-primary-700">
          <i class="fa-solid fa-bolt text-sm"></i>
        </span>
        <h2 class="text-base font-bold tracking-tight text-slate-800">Kenapa sebaiknya dipasang?</h2>
      </div>

      <div class="relative grid gap-3 sm:grid-cols-2">
        @foreach ([
          ['fa-solid fa-hand-pointer', 'Sekali ketuk dari layar utama', 'Tak perlu buka browser atau mengetik alamat lagi.'],
          ['fa-solid fa-expand', 'Tampil layar penuh', 'Bersih tanpa kolom alamat, fokus ke pekerjaan.'],
          ['fa-solid fa-gauge-high', 'Terasa lebih cepat', 'Sebagian tampilan tersimpan di perangkat.'],
          ['fa-solid fa-arrows-rotate', 'Selalu versi terbaru', 'Tidak makan memori & ikut pembaruan otomatis.'],
        ] as $b)
          <div class="flex items-start gap-3 rounded-lg border border-slate-100 bg-slate-50/60 p-3.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
              <i class="{{ $b[0] }} text-sm"></i>
            </span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-800">{{ $b[1] }}</p>
              <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ $b[2] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </section>

    {{-- ══ Pertanyaan umum ══ --}}
    <section class="dash-enter rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-4 flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-500">
          <i class="fa-regular fa-circle-question text-sm"></i>
        </span>
        <h2 class="text-base font-bold tracking-tight text-slate-800">Pertanyaan umum</h2>
      </div>
      <dl class="divide-y divide-slate-100">
        @foreach ([
          ['Tombol "Install Sekarang" tidak muncul?', 'Tombol hanya tersedia di Chrome/Edge (Android & laptop). Di iPhone gunakan Safari lewat <strong class="font-semibold text-slate-700">Bagikan → Tambah ke Layar Utama</strong>. Jika sudah pernah dipasang, tombol memang tidak muncul lagi.'],
          ['Perlu login ulang setelah dipasang?', 'Tidak. Aplikasi memakai sesi login yang sama seperti di browser — selama belum keluar, langsung masuk dashboard.'],
          ['Bisa dipakai tanpa internet?', 'Halaman yang pernah dibuka masih bisa tampil, tapi membuat & menyimpan SPPD tetap butuh koneksi. PWA mempercepat pemuatan, bukan pengganti internet.'],
          ['Cara menghapusnya?', 'Tekan lama ikon SPPD di layar utama, lalu pilih <strong class="font-semibold text-slate-700">Hapus / Uninstall</strong> seperti aplikasi lain. Data akun Anda tidak terpengaruh.'],
        ] as $faq)
          <div class="py-3.5 first:pt-0 last:pb-0">
            <dt class="flex items-start gap-2 text-sm font-semibold text-slate-800">
              <i class="fa-solid fa-angle-right mt-1 text-[11px] text-primary-500"></i>
              <span>{{ $faq[0] }}</span>
            </dt>
            <dd class="mt-1.5 pl-4.5 text-xs leading-relaxed text-slate-500">{!! $faq[1] !!}</dd>
          </div>
        @endforeach
      </dl>
    </section>

    {{-- ══ Aksi ══ --}}
    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
      <x-ui.button href="{{ route('dashboard') }}" variant="secondary">
        <x-slot:icon><i class="fa-solid fa-arrow-left text-xs"></i></x-slot:icon>
        Kembali
      </x-ui.button>
      <x-ui.button type="button" variant="primary" x-show="prompt && !installed" x-cloak @click="install()">
        <x-slot:icon><i class="fa-solid fa-circle-down"></i></x-slot:icon>
        Install Sekarang
      </x-ui.button>
    </div>
  </div>
@endsection
