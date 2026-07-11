@extends('layouts.app')

@section('title', 'Panduan Notifikasi WhatsApp')
@section('page-title', 'Panduan Notifikasi WhatsApp')

@section('content')
  @php
    $waNumber = config('kirimchat.verification_number', '6281376111919');
  @endphp

  <div class="mx-auto max-w-3xl space-y-5">

    {{-- Hero: dua kolom — pesan brand di kiri, mockup percakapan di kanan --}}
    <section
      class="dash-enter relative overflow-hidden rounded border border-emerald-700/20 bg-linear-to-br from-emerald-600 to-green-700 text-white shadow-sm">
      {{-- Watermark institusional (karakter tipis, bukan aksen baru) --}}
      <i class="fa-brands fa-whatsapp pointer-events-none absolute -right-6 -top-8 text-[10rem] text-white/8"
        aria-hidden="true"></i>

      <div class="relative flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center">
        <div class="min-w-0 flex-1">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-[11px] font-semibold tracking-wide ring-1 ring-inset ring-white/20">
            <i class="fa-solid fa-bolt text-[10px]"></i> Terintegrasi
          </span>
          <h1 class="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
            Notifikasi WhatsApp
          </h1>
          <p class="mt-2 max-w-md text-sm leading-relaxed text-emerald-50/90">
            Sistem memakai WhatsApp untuk dua hal: <strong class="font-semibold text-white">memverifikasi nomor</strong>
            pengguna dan <strong class="font-semibold text-white">mengirim notifikasi persetujuan SPPD</strong> ke
            pejabat — agar tidak ada pengajuan yang terlewat.
          </p>
        </div>

        {{-- Mockup percakapan: menegaskan alur "kirim pesan yang sudah terisi" --}}
        <div class="w-full max-w-[15rem] shrink-0 overflow-hidden rounded-lg bg-[#e7ddd3] shadow-lg ring-1 ring-black/10 lg:w-60"
          aria-hidden="true">
          <div class="flex items-center gap-2 bg-emerald-800 px-3 py-2">
            <span class="flex size-7 items-center justify-center rounded-full bg-white/20 text-xs">
              <i class="fa-solid fa-plane-departure"></i>
            </span>
            <div class="min-w-0 leading-tight">
              <p class="truncate text-xs font-semibold">Operator SPPD</p>
              <p class="text-[10px] text-emerald-200">online</p>
            </div>
          </div>
          <div class="space-y-1.5 px-2.5 py-3">
            <div class="max-w-[85%] rounded-lg rounded-tl-sm bg-white px-2.5 py-1.5 text-[11px] leading-snug text-slate-700 shadow-sm">
              Kirim pesan ini untuk memverifikasi nomor Anda.
            </div>
            <div class="ml-auto max-w-[88%] rounded-lg rounded-tr-sm bg-[#d9fdd3] px-2.5 py-1.5 text-[11px] leading-snug text-slate-800 shadow-sm">
              <span class="font-mono font-medium">VERIFIKASI-8F2A</span>
              <span class="mt-0.5 flex items-center justify-end gap-1 text-[9px] text-emerald-600">
                09.41 <i class="fa-solid fa-check-double"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- Cara verifikasi nomor — alur bernomor dengan rel penghubung --}}
    <section class="table-container p-5 sm:p-6">
      <div class="mb-5 flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
          <i class="fa-solid fa-circle-check text-sm"></i>
        </span>
        <h2 class="text-sm font-bold tracking-wide text-slate-800">Cara verifikasi nomor WhatsApp</h2>
      </div>

      <ol class="relative space-y-5">
        {{-- Rel vertikal di belakang lingkaran nomor --}}
        <span class="pointer-events-none absolute bottom-3 left-[13.5px] top-3 w-px bg-emerald-200" aria-hidden="true"></span>

        @foreach ([
          ['Buka halaman profil / data pegawai', 'Masuk ke <strong class="font-semibold text-slate-700">Profil Saya</strong> (menu kanan atas) atau halaman tambah / ubah pegawai.'],
          ['Masukkan nomor WhatsApp aktif', 'Isi kolom <strong class="font-semibold text-slate-700">No. Telepon / WhatsApp</strong>, lalu tekan tombol <strong class="font-semibold text-slate-700">Verifikasi</strong>.'],
          ['Kirim pesan otomatis', 'Sistem membuka WhatsApp dengan pesan yang sudah terisi. Cukup tekan <strong class="font-semibold text-slate-700">kirim</strong> tanpa mengubah isinya.'],
          ['Tunggu konfirmasi otomatis', 'Status berubah menjadi <strong class="font-semibold text-emerald-700">Terverifikasi</strong> secara otomatis dalam beberapa detik.'],
        ] as $i => $step)
          <li class="relative flex gap-4">
            <span class="relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white ring-4 ring-white">
              {{ $i + 1 }}
            </span>
            <div class="pt-0.5">
              <p class="text-sm font-semibold text-slate-800">{{ $step[0] }}</p>
              <p class="mt-1 text-xs leading-relaxed text-slate-500">{!! $step[1] !!}</p>
            </div>
          </li>
        @endforeach
      </ol>

      <div class="mt-6 flex items-start gap-2.5 rounded border border-emerald-200 bg-emerald-50 px-3.5 py-3 text-xs leading-relaxed text-emerald-800">
        <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
        <p>Nomor operator verifikasi: <strong class="font-mono font-semibold">{{ $waNumber }}</strong>. Sistem
          mencocokkan nomor pengirim secara otomatis, jadi pastikan mengirim dari nomor yang didaftarkan.</p>
      </div>
    </section>

    {{-- Dua panel berdampingan: manfaat notifikasi + syarat layanan aktif --}}
    <section class="dash-enter grid gap-5 sm:grid-cols-2">
      <div class="table-container flex flex-col p-5 sm:p-6">
        <div class="mb-3 flex items-center gap-2.5">
          <span class="flex size-8 items-center justify-center rounded-full bg-primary-50 text-primary-700">
            <i class="fa-solid fa-paper-plane text-sm"></i>
          </span>
          <h2 class="text-sm font-bold tracking-wide text-slate-800">Notifikasi persetujuan</h2>
        </div>
        <p class="text-sm leading-relaxed text-slate-600">
          Setiap ada SPPD baru atau revisi yang butuh persetujuan, pejabat terkait menerima
          <strong class="font-semibold text-slate-700">pesan WhatsApp otomatis</strong> — asalkan nomornya sudah
          terverifikasi.
        </p>
        <ul class="mt-4 space-y-2.5 text-sm text-slate-600">
          <li class="flex gap-2.5"><i class="fa-solid fa-check mt-1 shrink-0 text-emerald-500"></i> Pemberitahuan langsung tanpa membuka aplikasi.</li>
          <li class="flex gap-2.5"><i class="fa-solid fa-check mt-1 shrink-0 text-emerald-500"></i> Berisi ringkasan pengajuan dan tautan untuk meninjau.</li>
          <li class="flex gap-2.5"><i class="fa-solid fa-check mt-1 shrink-0 text-emerald-500"></i> Mengurangi keterlambatan persetujuan.</li>
        </ul>
      </div>

      <div class="rounded border border-amber-200 bg-amber-50/60 p-5 sm:p-6">
        <div class="mb-3 flex items-center gap-2.5">
          <span class="flex size-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
            <i class="fa-solid fa-triangle-exclamation text-sm"></i>
          </span>
          <h2 class="text-sm font-bold tracking-wide text-amber-900">Layanan antrian harus aktif</h2>
        </div>
        <p class="text-sm leading-relaxed text-amber-900/80">
          Pengiriman &amp; verifikasi WhatsApp diproses oleh <strong class="font-semibold">layanan antrian
            (queue worker)</strong>. Bila layanan mati, sistem <strong class="font-semibold">menolak</strong>
          verifikasi maupun pembuatan SPPD agar tidak ada notifikasi yang gagal terkirim. Jika menemui pesan itu,
          hubungi administrator dan coba lagi setelah layanan aktif.
        </p>
      </div>
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
          ['Status verifikasi tidak berubah?', 'Pastikan pesan benar-benar terkirim dari nomor yang didaftarkan dan layanan antrian aktif. Tekan <strong class="font-semibold text-slate-700">Coba Lagi</strong> bila waktu verifikasi habis (5 menit).'],
          ['Ingin mengganti nomor yang sudah terverifikasi?', 'Tekan tombol <strong class="font-semibold text-slate-700">Ganti</strong> di samping nomor, lalu lakukan verifikasi ulang untuk nomor baru.'],
          ['Tidak menerima notifikasi persetujuan?', 'Cek apakah nomor Anda sudah terverifikasi di halaman Profil. Notifikasi hanya dikirim ke nomor terverifikasi.'],
        ] as $faq)
          <div class="py-3.5 first:pt-0 last:pb-0">
            <dt class="flex items-start gap-2 text-sm font-semibold text-slate-800">
              <i class="fa-solid fa-angle-right mt-1 text-[11px] text-emerald-500"></i>
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
      <x-ui.button href="{{ route('profile') }}" variant="success">
        <x-slot:icon><i class="fa-brands fa-whatsapp"></i></x-slot:icon>
        Verifikasi nomor saya
      </x-ui.button>
    </div>
  </div>
@endsection
