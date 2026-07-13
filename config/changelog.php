<?php

/*
|--------------------------------------------------------------------------
| Log Pembaruan Sistem (Changelog)
|--------------------------------------------------------------------------
|
| Ditampilkan di panel notifikasi (icon lonceng) pada header. Item paling
| atas dianggap paling baru. Untuk menambah pembaruan, cukup tambahkan entri
| baru di paling atas array 'items' dan naikkan 'version'.
|
| color: token warna (emerald, cyan, indigo, amber, sky, violet, slate, rose)
|        dipetakan ke kelas chip di resources/views/components/notifications.blade.php
|
*/

return [
  // Penanda versi changelog. Saat berubah, lonceng menampilkan titik "belum dibaca".
  'version' => '2026.07.13.1',

  'items' => [
    [
      'title' => 'Install sebagai Aplikasi (PWA)',
      'description' => 'Pasang SPPD ke layar utama ponsel atau desktop agar terbuka seperti aplikasi — lebih cepat, tanpa membuka browser dulu.',
      'icon' => 'fa-solid fa-mobile-screen-button',
      'color' => 'cyan',
      'date' => '2026-07-13',
      // Sorot sebagai fitur terbaru + tautan ke halaman panduan khusus.
      'featured' => true,
      'badge' => 'Baru',
      'guide' => 'guide.pwa',
      'guide_label' => 'Panduan Install',
    ],
    [
      'title' => 'Fitur Notifikasi WhatsApp',
      'description' => 'Verifikasi nomor & notifikasi persetujuan SPPD kini langsung lewat WhatsApp agar tidak terlewat.',
      'icon' => 'fa-brands fa-whatsapp',
      'color' => 'emerald',
      'date' => '2026-06-26',
      // Sorot sebagai fitur terbaru + tautan ke halaman panduan khusus.
      'featured' => true,
      'badge' => 'Terbaru',
      'guide' => 'guide.whatsapp',
      'guide_label' => 'Panduan WhatsApp',
    ],
    [
      'title' => 'Antarmuka Lebih Responsive',
      'description' => 'Tampilan kini menyesuaikan rapi di ponsel, tablet, maupun desktop.',
      'icon' => 'fa-solid fa-mobile-screen-button',
      'color' => 'indigo',
      'date' => '2026-06-26',
    ],
    [
      'title' => 'Tampilan Clean & Modern',
      'description' => 'Desain disegarkan dengan komponen yang lebih konsisten dan enak dilihat.',
      'icon' => 'fa-solid fa-wand-magic-sparkles',
      'color' => 'cyan',
      'date' => '2026-06-26',
    ],
    [
      'title' => 'Lebih User Friendly',
      'description' => 'Alur kerja dipermudah dengan petunjuk yang jelas dan minim langkah membingungkan.',
      'icon' => 'fa-regular fa-face-smile',
      'color' => 'amber',
      'date' => '2026-06-26',
    ],
    [
      'title' => 'Halaman Profil Saya',
      'description' => 'Kelola data akun, kontak, dan ganti password Anda sendiri dari menu profil.',
      'icon' => 'fa-regular fa-user',
      'color' => 'sky',
      'date' => '2026-06-26',
    ],
    [
      'title' => 'Username Otomatis',
      'description' => 'Tombol Generate membuat username ringkas & unik otomatis dari nama lengkap.',
      'icon' => 'fa-solid fa-at',
      'color' => 'violet',
      'date' => '2026-06-26',
    ],
  ],
];
