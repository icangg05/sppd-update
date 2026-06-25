<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Default Pegawai
    |--------------------------------------------------------------------------
    |
    | Dipakai saat membuat pegawai baru tanpa mengisi password. Sistem akan
    | otomatis memakai nilai ini sebagai password awal. Sebaiknya pegawai
    | mengganti password setelah login pertama.
    |
    */

    'default_password' => env('USER_DEFAULT_PASSWORD', 'pass1234'),
];
