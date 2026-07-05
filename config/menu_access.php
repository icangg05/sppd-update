<?php

/*
|--------------------------------------------------------------------------
| Akses Menu per Grup Role
|--------------------------------------------------------------------------
| Satu sumber kebenaran daftar role untuk menu tambahan pada sidebar &
| gating route. Dipakai di routes/web.php (middleware role:) dan di
| resources/views/components/sidebar.blade.php (hasAnyRole).
|
| - leadership  : pejabat penyetuju/pemimpin — melihat Riwayat Persetujuan
|                 & Rekap/Statistik SPPD.
| - secretariat : sekretaris/pendamping — melihat Monitoring SPPD OPD.
*/

return [
  'leadership' => [
    'kepala_opd',
    'sekda',
    'walikota',
    'wakil_walikota',
    'kabid_irban_kabag',
    'camat',
    'lurah',
    'kapus',
  ],

  'secretariat' => [
    'sekretaris_opd',
    'sekcam',
    'sekwan',
  ],
];
