@extends('layouts.app')

@section('title', 'Panduan Notifikasi Telegram')
@section('page-title', 'Panduan Notifikasi Telegram')

@section('content')
  @php
    $botUsername = config('telegram.bot_username');
    // Info teknis layanan antrian hanya relevan bagi super admin yang bisa menanganinya.
    $isSuperAdmin = auth()->user()->hasRole('super_admin');
  @endphp

  <div class="mx-auto max-w-3xl space-y-5">

    {{-- Hero: dua kolom — pesan brand di kiri, mockup percakapan di kanan --}}
    <section
      class="dash-enter relative overflow-hidden rounded border border-sky-700/20 bg-linear-to-br from-sky-500 to-sky-700 text-white shadow-sm">
      {{-- Watermark institusional (karakter tipis, bukan aksen baru) --}}
      <i class="fa-brands fa-telegram pointer-events-none absolute -right-6 -top-8 text-[10rem] text-white/8"
        aria-hidden="true"></i>

      <div class="relative flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center">
        <div class="min-w-0 flex-1">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-[11px] font-semibold tracking-wide ring-1 ring-inset ring-white/20">
            <i class="fa-solid fa-bolt text-[10px]"></i> Terintegrasi
          </span>
          <h1 class="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
            Notifikasi Telegram
          </h1>
          <p class="mt-2 max-w-md text-sm leading-relaxed text-sky-50/90">
            Sistem memakai bot Telegram untuk <strong class="font-semibold text-white">mengirim notifikasi
              pengajuan &amp; persetujuan SPPD</strong> ke pegawai dan pejabat, sehingga tidak ada pengajuan yang
            terlewat. Cukup hubungkan akun Telegram Anda satu kali.
          </p>
        </div>

        {{-- Mockup percakapan: menegaskan alur "tekan Start" --}}
        <div class="w-full max-w-[15rem] shrink-0 overflow-hidden rounded-lg bg-[#cfe3ee] shadow-lg ring-1 ring-black/10 lg:w-60"
          aria-hidden="true">
          <div class="flex items-center gap-2 bg-sky-800 px-3 py-2">
            <span class="flex size-7 items-center justify-center rounded-full bg-white/20 text-xs">
              <i class="fa-solid fa-plane-departure"></i>
            </span>
            <div class="min-w-0 leading-tight">
              <p class="truncate text-xs font-semibold">Bot SPPD Kendari</p>
              <p class="text-[10px] text-sky-200">bot</p>
            </div>
          </div>
          <div class="space-y-1.5 px-2.5 py-3">
            <div class="ml-auto max-w-[60%] rounded-lg rounded-tr-sm bg-[#e1fec6] px-2.5 py-1.5 text-[11px] leading-snug text-slate-800 shadow-sm">
              <span class="font-mono font-medium">/start</span>
              <span class="mt-0.5 flex items-center justify-end gap-1 text-[9px] text-emerald-600">
                09.41 <i class="fa-solid fa-check-double"></i>
              </span>
            </div>
            <div class="max-w-[88%] rounded-lg rounded-tl-sm bg-white px-2.5 py-1.5 text-[11px] leading-snug text-slate-700 shadow-sm">
              ✅ <strong>Akun terhubung!</strong> Anda akan menerima notifikasi SPPD di sini.
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- Cara menghubungkan akun — alur bernomor dengan rel penghubung --}}
    <section class="table-container p-5 sm:p-6">
      <div class="mb-5 flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center rounded-full bg-sky-100 text-sky-700">
          <i class="fa-solid fa-link text-sm"></i>
        </span>
        <h2 class="text-sm font-bold tracking-wide text-slate-800">Cara menghubungkan akun Telegram</h2>
      </div>

      <ol class="relative space-y-5">
        {{-- Rel vertikal di belakang lingkaran nomor --}}
        <span class="pointer-events-none absolute bottom-3 left-[13.5px] top-3 w-px bg-sky-200" aria-hidden="true"></span>

        @foreach ([
          ['Pasang & masuk ke Telegram', 'Unduh aplikasi <strong class="font-semibold text-slate-700">Telegram</strong> di ponsel (atau Telegram Desktop) dan masuk dengan akun Anda.'],
          ['Buka halaman profil', 'Masuk ke <strong class="font-semibold text-slate-700">Profil Saya</strong> (menu kanan atas), lalu tekan <strong class="font-semibold text-slate-700">Hubungkan Telegram</strong>.'],
          ['Buka bot & tekan Start', 'Tekan <strong class="font-semibold text-slate-700">Buka Telegram</strong> atau pindai QR dengan kamera ponsel. Di chat bot, tekan tombol <strong class="font-semibold text-slate-700">Start</strong> / <strong class="font-semibold text-slate-700">Mulai</strong>.'],
          ['Tunggu konfirmasi otomatis', 'Status berubah menjadi <strong class="font-semibold text-emerald-700">Terhubung</strong> dalam beberapa detik, dan bot mengirim pesan konfirmasi.'],
        ] as $i => $step)
          <li class="relative flex gap-4">
            <span class="relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white ring-4 ring-white">
              {{ $i + 1 }}
            </span>
            <div class="pt-0.5">
              <p class="text-sm font-semibold text-slate-800">{{ $step[0] }}</p>
              <p class="mt-1 text-xs leading-relaxed text-slate-500">{!! $step[1] !!}</p>
            </div>
          </li>
        @endforeach
      </ol>

      <div class="mt-6 flex items-start gap-2.5 rounded border border-sky-200 bg-sky-50 px-3.5 py-3 text-xs leading-relaxed text-sky-800">
        <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
        <p>
          @if ($botUsername)
            Bot resmi sistem: <strong class="font-mono font-semibold">{{ '@' . $botUsername }}</strong>.
          @endif
          Tautan verifikasi berlaku 15 menit dan hanya bisa dipakai sekali. Admin yang menghubungkan untuk pegawai
          lain dapat menyalin tautan atau menunjukkan QR-nya ke pegawai tersebut.
        </p>
      </div>
    </section>

    {{-- Super admin: dua panel berdampingan (manfaat notifikasi + syarat layanan aktif).
         Role lain: panel manfaat saja, selebar penuh. --}}
    <section class="dash-enter grid gap-5 {{ $isSuperAdmin ? 'sm:grid-cols-2' : '' }}">
      <div class="table-container flex flex-col p-5 sm:p-6">
        <div class="mb-3 flex items-center gap-2.5">
          <span class="flex size-8 items-center justify-center rounded-full bg-primary-50 text-primary-700">
            <i class="fa-solid fa-paper-plane text-sm"></i>
          </span>
          <h2 class="text-sm font-bold tracking-wide text-slate-800">Notifikasi persetujuan</h2>
        </div>
        <p class="text-sm leading-relaxed text-slate-600">
          Setiap ada SPPD baru atau revisi yang butuh persetujuan, pejabat terkait menerima
          <strong class="font-semibold text-slate-700">pesan Telegram otomatis</strong>. Pemohon juga diberi tahu
          saat SPPD disetujui, ditolak, atau perlu revisi. Syaratnya, akun Telegram sudah terhubung.
        </p>
        <ul class="mt-4 text-sm text-slate-600 {{ $isSuperAdmin ? 'space-y-2.5' : 'grid gap-2.5 sm:grid-cols-3' }}">
          <li class="flex gap-2.5"><i class="fa-solid fa-check mt-1 shrink-0 text-emerald-500"></i> Pemberitahuan langsung tanpa membuka aplikasi.</li>
          <li class="flex gap-2.5"><i class="fa-solid fa-check mt-1 shrink-0 text-emerald-500"></i> Berisi ringkasan pengajuan dan tautan untuk meninjau.</li>
          <li class="flex gap-2.5"><i class="fa-solid fa-check mt-1 shrink-0 text-emerald-500"></i> Gratis dan bisa dimatikan sendiri di Profil.</li>
        </ul>
      </div>

      @if ($isSuperAdmin)
        <div class="rounded border border-amber-200 bg-amber-50/60 p-5 sm:p-6">
          <div class="mb-3 flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
              <i class="fa-solid fa-triangle-exclamation text-sm"></i>
            </span>
            <h2 class="text-sm font-bold tracking-wide text-amber-900">Layanan antrian harus aktif</h2>
          </div>
          <p class="text-sm leading-relaxed text-amber-900/80">
            Pengiriman notifikasi Telegram diproses oleh <strong class="font-semibold">layanan antrian
              (queue worker)</strong>. Bila layanan mati, sistem <strong class="font-semibold">menolak</strong>
            penghubungan akun maupun pembuatan SPPD agar tidak ada notifikasi yang gagal terkirim. Jika pengguna
            melaporkan pesan itu, periksa dan jalankan ulang container <code class="font-mono">queue</code>, lalu
            minta pengguna mencoba lagi.
          </p>
        </div>
      @endif
    </section>

    {{-- Pertanyaan umum --}}
    <section class="dash-enter table-container p-5 sm:p-6">
      <div class="mb-5 flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-500">
          <i class="fa-regular fa-circle-question text-sm"></i>
        </span>
        <h2 class="text-sm font-bold tracking-wide text-slate-800">Pertanyaan umum</h2>
      </div>
      <dl class="divide-y divide-slate-100">
        @foreach ([
          ['Apakah akun Telegram harus memakai nomor HP tertentu?', 'Tidak. Sistem mengenali Anda dari tautan yang dibuat aplikasi, bukan dari nomor HP. Akun Telegram yang dipakai untuk menekan <strong class="font-semibold text-slate-700">Start</strong> itulah yang akan menerima notifikasi.'],
          ['Status tidak berubah setelah menekan Start?', 'Pastikan Anda membuka tautan dari chat pribadi dengan bot (bukan grup)' . ($isSuperAdmin ? ' dan layanan antrian aktif' : '') . '. Tekan <strong class="font-semibold text-slate-700">Coba Lagi</strong> bila waktu habis (5 menit)' . ($isSuperAdmin ? '' : ', atau hubungi administrator bila masih gagal') . '.'],
          ['Ingin memakai akun Telegram lain?', 'Tekan <strong class="font-semibold text-slate-700">Putuskan</strong> di halaman Profil, lalu hubungkan kembali dengan akun Telegram yang baru.'],
          ['Tidak menerima notifikasi lagi?', 'Bila bot diblokir atau chat dihapus, sistem otomatis memutus tautan. Cek status di halaman Profil dan hubungkan ulang bila perlu.'],
        ] as $faq)
          <div class="py-3.5 first:pt-0 last:pb-0">
            <dt class="flex items-start gap-2 text-sm font-semibold text-slate-800">
              <i class="fa-solid fa-angle-right mt-1 text-[11px] text-sky-500"></i>
              <span>{{ $faq[0] }}</span>
            </dt>
            <dd class="mt-1.5 pl-4.5 text-xs leading-relaxed text-slate-500">{!! $faq[1] !!}</dd>
          </div>
        @endforeach
      </dl>
    </section>

    {{-- Aksi --}}
    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
      <x-ui.button href="{{ route('dashboard') }}" variant="secondary">
        <x-slot:icon><i class="fa-solid fa-arrow-left text-xs"></i></x-slot:icon>
        Kembali
      </x-ui.button>
      <x-ui.button href="{{ route('profile') }}">
        <x-slot:icon><i class="fa-brands fa-telegram"></i></x-slot:icon>
        Hubungkan Telegram saya
      </x-ui.button>
    </div>
  </div>
@endsection
