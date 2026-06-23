# Yang belum dikerjakan:

1. Anggota DPRD - create / edit data perlu disesuaikan => ✅
2. Tambahkan enum => Partai DPRD dan jabatan => ✅
2. Halaman dashboard role lain
3. Toast CRUD data => ✅
4. Reusable komponen => alert, button, badge, table, form input => ✅
5. Searchable di select input => ✅
6. Tampilkan anggaran tersedia di modal konfirmasi create perjalanan => ✅
7. Perbaiki halaman show department di DPRD => menampilkan 2 kop surat => ✅
8. Validasi hanya boleh ada satu jabatan => walikota, sekda, kepala opd, dan lain sebagainya => ✅
9. Filter data yang ditampilkan di halaman index SPPD.
10. Akses melalui url masih bisa padahal bukan permission dari rolenya. contoh role kepala_opd masih bisa crud data users padahal hanya admin_opd dan super_admin yang bisa. => ✅

================================================================

Optional:

1. Redesign menggunakan claude SKILLS
2. Hapus database yang tidak terpakai: => ✅ (sebagian)
   - document_signatories       => dihapus
   - notifications              => dihapus
   - sessions                   => dihapus (SESSION_DRIVER=file)
   - settings                   => dihapus
   - signature_settings         => dihapus
   - user_department_assignments => dihapus
   - bank_accounts              => dihapus (model+relasi belum tersambung ke fitur apa pun)
   - sppd_advance_receipts      => DIPERTAHANKAN (fitur panjar masih dipakai: controller, route, cetak kuitansi; tabel kosong krn belum ada data)
   - model_has_permissions      => DIPERTAHANKAN (inti Spatie, dipakai tiap cek permission)
