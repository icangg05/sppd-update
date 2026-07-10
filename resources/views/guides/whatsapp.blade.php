@extends('layouts.app')

@section('title', 'Panduan Notifikasi WhatsApp')
@section('page-title', 'Panduan Notifikasi WhatsApp')

@section('content')
  @php
    $waNumber = config('kirimchat.verification_number', '6281376111919');
  @endphp

  <div class="mx-auto max-w-3xl space-y-6">

    {{-- Hero --}}
    <div class="relative overflow-hidden rounded bg-gradient-to-br from-emerald-600 to-green-700 p-6 text-white shadow-lg sm:p-8">
      <i class="fa-brands fa-whatsapp pointer-events-none absolute -right-4 -top-4 text-[8rem] text-white/10"></i>
      <div class="relative">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">
          <i class="fa-solid fa-star text-[10px]"></i> Fitur Terbaru
        </span>
        <h1 class="mt-3 text-2xl font-extrabold sm:text-3xl">Notifikasi WhatsApp</h1>
        <p class="mt-2 max-w-xl text-sm leading-relaxed text-emerald-50">
          Sistem menggunakan WhatsApp untuk dua hal: <strong>memverifikasi nomor</strong> pengguna dan
          <strong>mengirim notifikasi persetujuan SPPD</strong> ke pejabat — agar tidak ada pengajuan yang terlewat.
        </p>
      </div>
    </div>

    {{-- Cara verifikasi nomor --}}
    <div class="table-container p-5 sm:p-6">
      <div class="mb-4 flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-emerald-600"></i>
        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Cara Verifikasi Nomor WhatsApp</h2>
      </div>

      <ol class="space-y-4">
        @foreach ([
          ['Buka halaman profil / data pegawai', 'Masuk ke <strong>Profil Saya</strong> (menu kanan atas) atau halaman tambah/ubah pegawai.'],
          ['Masukkan nomor WhatsApp aktif', 'Isi kolom <strong>No. Telepon / WhatsApp</strong>, lalu tekan tombol <strong>Verifikasi</strong>.'],
          ['Kirim pesan otomatis', 'Sistem membuka WhatsApp dengan pesan yang sudah terisi. Cukup tekan <strong>kirim</strong> tanpa mengubah isinya.'],
          ['Tunggu konfirmasi otomatis', 'Status verifikasi akan berubah menjadi <strong>Terverifikasi</strong> secara otomatis dalam beberapa detik.'],
        ] as $i => $step)
          <li class="flex gap-3.5">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
              {{ $i + 1 }}
            </span>
            <div class="pt-0.5">
              <p class="text-sm font-semibold text-slate-800">{{ $step[0] }}</p>
              <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{!! $step[1] !!}</p>
            </div>
          </li>
        @endforeach
      </ol>

      <div class="mt-5 flex items-start gap-2 rounded border border-emerald-200 bg-emerald-50 px-3.5 py-3 text-xs text-emerald-800">
        <i class="fa-solid fa-circle-info mt-0.5"></i>
        <p>Nomor operator verifikasi: <strong class="font-mono">{{ $waNumber }}</strong>.
          Sistem mencocokkan nomor pengirim secara otomatis, jadi pastikan mengirim dari nomor yang didaftarkan.</p>
      </div>
    </div>

    {{-- Notifikasi persetujuan --}}
    <div class="table-container p-5 sm:p-6">
      <div class="mb-4 flex items-center gap-2">
        <i class="fa-solid fa-paper-plane text-primary-600"></i>
        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Notifikasi Persetujuan SPPD</h2>
      </div>
      <p class="text-sm leading-relaxed text-slate-600">
        Setiap kali ada SPPD baru atau revisi yang membutuhkan persetujuan, pejabat terkait akan menerima
        <strong>pesan WhatsApp otomatis</strong>. Syaratnya, nomor pejabat tersebut sudah <strong>terverifikasi</strong>.
      </p>
      <ul class="mt-3 space-y-2 text-sm text-slate-600">
        <li class="flex gap-2"><i class="fa-solid fa-check mt-1 text-emerald-500"></i> Pemberitahuan langsung tanpa perlu membuka aplikasi.</li>
        <li class="flex gap-2"><i class="fa-solid fa-check mt-1 text-emerald-500"></i> Berisi ringkasan pengajuan dan tautan untuk meninjau.</li>
        <li class="flex gap-2"><i class="fa-solid fa-check mt-1 text-emerald-500"></i> Mengurangi keterlambatan persetujuan.</li>
      </ul>
    </div>

    {{-- Syarat layanan aktif --}}
    <div class="table-container p-5 sm:p-6">
      <div class="mb-3 flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Penting: Layanan Antrian Harus Aktif</h2>
      </div>
      <p class="text-sm leading-relaxed text-slate-600">
        Pengiriman & verifikasi WhatsApp diproses oleh <strong>layanan antrian (queue worker)</strong>. Bila layanan
        sedang mati, sistem akan <strong>menolak</strong> verifikasi maupun pembuatan SPPD untuk mencegah notifikasi gagal terkirim.
        Jika menemui pesan tersebut, hubungi administrator sistem dan coba lagi setelah layanan aktif.
      </p>
    </div>

    {{-- Troubleshooting --}}
    <div class="table-container p-5 sm:p-6">
      <div class="mb-4 flex items-center gap-2">
        <i class="fa-regular fa-circle-question text-slate-500"></i>
        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Pertanyaan Umum</h2>
      </div>
      <div class="space-y-4">
        @foreach ([
          ['Status verifikasi tidak berubah?', 'Pastikan pesan benar-benar terkirim dari nomor yang didaftarkan dan layanan antrian aktif. Tekan <strong>Coba Lagi</strong> bila waktu verifikasi habis (5 menit).'],
          ['Ingin mengganti nomor yang sudah terverifikasi?', 'Tekan tombol <strong>Ganti</strong> di samping nomor, lalu lakukan verifikasi ulang untuk nomor baru.'],
          ['Tidak menerima notifikasi persetujuan?', 'Cek apakah nomor Anda sudah terverifikasi di halaman Profil. Notifikasi hanya dikirim ke nomor terverifikasi.'],
        ] as $faq)
          <div>
            <p class="text-sm font-semibold text-slate-800"><i class="fa-solid fa-angle-right mr-1.5 text-slate-500"></i>{{ $faq[0] }}</p>
            <p class="mt-1 pl-5 text-xs leading-relaxed text-slate-500">{!! $faq[1] !!}</p>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Aksi --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
      <x-ui.button href="{{ route('dashboard') }}" variant="secondary">
        <i class="fa-solid fa-arrow-left text-xs"></i> Kembali
      </x-ui.button>
      <x-ui.button href="{{ route('profile') }}" variant="success">
        <i class="fa-brands fa-whatsapp"></i> Verifikasi Nomor Saya
      </x-ui.button>
    </div>
  </div>
@endsection
