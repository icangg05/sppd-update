<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Wilayah SPPD
    |--------------------------------------------------------------------------
    |
    | Nilai-nilai ini sebelumnya di-hardcode di komponen. Dipindah ke config agar
    | mudah disesuaikan bila instansi berada di provinsi/kota induk yang berbeda.
    |
    */

    // Nama provinsi induk (untuk default tujuan/asal perjalanan dinas).
    'default_province_name' => env('SPPD_DEFAULT_PROVINCE', 'Sulawesi Tenggara'),

    // Kata kunci nama kota/kabupaten tempat kedudukan (dipakai pada query LIKE).
    'home_base_regency_keyword' => env('SPPD_HOME_BASE_REGENCY', 'Kendari'),
];
