Rundown Laporan Pembangunan SPPD Baru (26 Juni – 31 Juli 2026)
Tahap 1 — Penyiapan Fondasi Sistem (26 – 30 Juni)
Inisialisasi proyek Laravel + Livewire sesuai rancangan arsitektur; lingkungan pengembangan dan produksi dikontainerisasi dengan Docker.
Implementasi struktur basis data hasil perancangan (migrasi berversi): tabel pegawai, unit kerja, perjalanan_dinas, surat_perintah, rincian_biaya, riwayat_persetujuan, log_tte.
Penyiapan autentikasi dan pembagian hak akses peran (pengaju, verifikator, penandatangan, admin).
Penyiapan pipeline deploy otomatis (CI/CD) ke server VPS, termasuk pembersihan artefak Docker agar disk server tidak penuh.
Backup basis data otomatis dan pembersihan log terjadwal.
Uji koneksi awal ke layanan Tanda Tangan Elektronik (BSrE).
Tahap 2 — Modul Inti Alur Perjalanan Dinas (1 – 5 Juli)
Pembangunan modul data induk: pegawai dan unit kerja.
Pembangunan alur pengajuan perjalanan dinas berbasis status (draft → diajukan → verifikasi → SPD terbit), lengkap dengan riwayat persetujuan.
Modul verifikasi/telaah dengan mekanisme setuju/tolak (revisi) beserta catatan.
Uji coba integrasi API eksternal pendukung.
Tahap 3 — Cetak Dokumen (6 – 8 Juli)
Implementasi cetak dokumen dengan pustaka PDF modern (pengganti FPDF): SPD, surat tugas, kuitansi, dan rincian biaya.
Penanganan kop/gambar template yang sebelumnya menjadi sumber galat pada sistem lama.
Penyesuaian penandatangan pada cetakan kuitansi dan dokumen terkait.
Tahap 4 — TTE Proses Latar & Notifikasi (9 – 11 Juli)
Implementasi antrean proses latar (queue/worker) untuk TTE sesuai rancangan: status antre → diproses → berhasil/gagal, dengan retry otomatis.
Pembangunan halaman verifikasi keaslian dokumen bertandatangan elektronik (upload PDF → verifikasi ke layanan BSrE).
Implementasi notifikasi otomatis pada titik-titik alur (pengajuan masuk, hasil telaah, SPD terbit, status TTE).
Tahap 5 — Antarmuka & Aksesibilitas Perangkat (12 – 13 Juli)
Penerapan Progressive Web App (PWA): aplikasi dapat dipasang di perangkat pegawai layaknya aplikasi mobile.
Optimasi tampilan mobile (tabel responsif berbentuk kartu) dan penyempurnaan antarmuka.
Perbaikan detail data (urutan jabatan, penataan unit kerja).
Tahap 6 — Migrasi Data & Stabilisasi (14 – 20 Juli)
Migrasi data pegawai dan data anggaran (DPA) dari sistem lama ke sistem baru.
Verifikasi kecocokan data hasil migrasi dan pembersihan data staging.
Perbaikan bug hasil temuan penggunaan internal.
Tahap 7 — Uji Coba & Persiapan Rilis (21 – 31 Juli)
Uji coba menyeluruh alur end-to-end: pengajuan → verifikasi → penerbitan SPD → TTE → cetak.
Uji coba TTE massal (banyak dokumen paralel) untuk memvalidasi keandalan antrean.
Sosialisasi/pendampingan singkat ke operator OPD percontohan.
Penyiapan rilis produksi dan rencana penonaktifan bertahap sistem lama.
Tahap 1–5 sudah selesai (sesuai riwayat commit), Tahap 6 sedang berjalan, Tahap 7 adalah rencana sampai akhir Juli. Kalau rundown ini sudah cocok, bilang saja — saya lanjutkan jadi format laporan lengkap seperti dokumen sebelumnya (tabel Nama/Lokasi/Tanggal, narasi per tahapan, Hasil Kegiatan, Tindak Lanjut).
