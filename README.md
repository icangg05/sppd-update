SPPD Update Kota Kendari => Laravel 13

koreksi yg belum:

1. pada detail halaman perjalanan, detail spt dan detail sppd tidak perlu tampilkan tombol download
2. pada file spt yang di tte masih muncul qrcode kecil yang posisi bukan diatas nama si penandatangan. hilangkan qrcode bawaan tersebut. spt harusnya hanya satu qrcode saja sedangkan saat ini hasilnya dua.

untuk tipe instansi dprd akan menggunakan format spt_dprd.blade.php. format sppd tetap sama. lalu tipe dprd memiliki dua kop surat (letterhead dan letterhead_second). letterhead_second di
implementasikan khusus untuk anggota dprd. pada sistem ini saya bingung data anggota dprd karena datanya memiliki nama, partai, dan jabatan (contoh: Anggota Komisi III). di instansi dprd
untuk filter menu di index sppd yaitu: Anggota DPRD, Staff DPRD, dan Sekwan. nah, staff dprd dan sekwan normal seperti pada sistem yang berjalan sedangkan untuk anggota dprd seperti yang
saya jelaskan saat ini.
