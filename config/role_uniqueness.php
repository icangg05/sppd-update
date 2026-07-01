<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Keunikan Role / Jabatan Otentikasi
  |--------------------------------------------------------------------------
  |
  | Menentukan role yang hanya boleh dipegang satu pegawai aktif. Berbeda
  | dengan jabatan struktural (position), role di sini adalah kewenangan
  | sistem (mis. kepala_opd yang menyetujui SPPD).
  |
  |   system     => hanya satu pemegang di seluruh sistem.
  |   department => hanya satu pemegang per OPD / unit kerja (department_id).
  |
  | Role yang tidak terdaftar di sini boleh dipegang banyak pegawai
  | (mis. staf, admin_opd, kabid_irban_kabag, pimpinan_dprd, anggota_dprd).
  |
  */

  'system' => [
    'walikota',
    'wakil_walikota',
    'sekda',
    'sekwan',
  ],

  'department' => [
    'kepala_opd',
    'sekretaris_opd',
    'camat',
    'sekcam',
    'lurah',
    'kapus',
  ],
];
