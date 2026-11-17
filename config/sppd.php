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

    // Imbuhan wilayah untuk jabatan pimpinan DPRD pada dokumen, mis. jadi
    // "Ketua DPRD Kota Kendari". Kosongkan ('') untuk menonaktifkan.
    'dprd_region' => env('SPPD_DPRD_REGION', 'Kota Kendari'),

    // Domain aplikasi SPPD lama (CodeIgniter). Dokumen lama ber-TTE memuat QR ke
    // https://sppd.kendarikota.go.id/telaah/... (mis. telaah/history/detail?telaah_id=...),
    // padahal aplikasi lama sudah pindah domain. Semua permintaan /telaah/* dialihkan
    // ke domain ini dengan path & query yang sama. Kosongkan ('') untuk menonaktifkan.
    'legacy_url' => env('SPPD_LEGACY_URL', 'https://sppd-old.kendarikota.go.id'),
];
