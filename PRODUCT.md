# Product

## Register

product

## Platform

web

## Users

Aplikasi internal Pemerintah Kota Kendari untuk pengelolaan **SPPD (Surat Perintah Perjalanan Dinas)**. Tiga peran utama:

- **Operator / admin bagian** — pengguna inti. Menginput dan mengelola SPPD, pegawai, DPA, kuitansi, dan realisasi sehari-hari. Bekerja lama di depan layar desktop, berhadapan dengan tabel dan formulir yang padat data. Butuh alur cepat, minim klik, dan status yang selalu jelas.
- **Pejabat penandatangan** — memverifikasi dan menandatangani dokumen secara elektronik (TTE via BSrE). Pemakaian singkat dan sesekali, kadang lewat HP. Butuh kejelasan "apa yang saya tanda tangani" dan umpan balik verifikasi yang tegas (berhasil/gagal).
- **Pegawai pelaksana tugas** — subjek perjalanan dinas. Umumnya hanya melihat atau mengunduh dokumen yang sudah jadi.

Konteks pemakaian: jam kerja kantor, jaringan yang tidak selalu cepat, taruhan administratif tinggi (dokumen resmi bertanda tangan digital yang harus benar).

## Product Purpose

Menggantikan alur SPPD manual/berbasis kertas dengan sistem digital yang menerbitkan, memverifikasi, dan mencetak dokumen perjalanan dinas resmi: SPPD, kuitansi, rincian biaya, dan berkas realisasi. Menandatangani dokumen secara sah lewat TTE/BSrE dan menyediakan halaman verifikasi keaslian. Sukses = staf menyelesaikan siklus SPPD (buat → tanda tangan → cetak → realisasi) tanpa ambiguitas status, tanpa dokumen salah cetak, dan tanpa harus keluar dari alur kerjanya.

## Brand Personality

**Tepercaya · Jernih · Rapi.** Suara sistem pemerintahan yang kredibel dan sah — tenang, faktual, tanpa basa-basi pemasaran. Ramah bagi staf non-teknis: label lugas dalam Bahasa Indonesia, friksi rendah, tidak menakut-nakuti. Terlihat modern dan bersih (kontemporer, bukan aplikasi pemerintah jadul), dengan konsistensi visual yang menumbuhkan rasa aman: pengguna percaya dokumen yang keluar sudah benar.

## Anti-references

- **Aplikasi pemerintah jadul** — tabel polos tanpa hierarki, palet abu-abu mati, font default, tata letak kaku. Kesan usang menurunkan kepercayaan.
- **Tampilan yang tidak konsisten** — tombol/komponen beda gaya antar halaman, terasa ditambal. Setiap "Simpan", badge status, dan modal harus memakai kosakata komponen yang sama.
- **Layar terlalu padat/ramai** — semua ditumpuk tanpa napas, aksi utama tenggelam. Kepadatan boleh saat data memang butuh, tapi hierarki dan ruang tetap wajib.
- **SaaS startup berlebihan** — gradien mencolok, ilustrasi lucu, animasi dekoratif. Ini alat kerja resmi, bukan halaman pemasaran.

## Design Principles

- **Status tidak boleh ambigu.** Setiap SPPD, verifikasi, dan realisasi punya keadaan yang jelas terbaca sekilas (mis. "perjalanan selesai", "belum realisasi", "terverifikasi/gagal"). Umpan balik aksi selalu tegas — terutama untuk TTE, di mana keberhasilan/kegagalan harus terlihat, bukan diam-diam.
- **Konsisten mengalahkan kejutan.** Satu kosakata komponen (button, badge, modal, input) dipakai identik di seluruh aplikasi. Aksen interaktif selalu `primary-*`, tidak pernah literal warna. Delight disimpan untuk momen kecil, bukan halaman.
- **Alat menghilang ke dalam tugas.** Padat saat perlu, lapang saat bisa; aksi utama selalu menonjol. Utamakan alur inline/progresif sebelum modal.
- **Jujur dan lugas dalam Bahasa Indonesia.** Label, error, dan kosong-state berbicara seperti petugas yang menolong — memberi tahu apa yang terjadi dan apa langkah berikutnya, bukan jargon teknis.
- **Andal di kondisi nyata.** Jaringan lambat dan layar kecil (verifikasi HP) adalah kondisi normal, bukan kasus tepi. Loading, empty, dan error state adalah bagian dari desain, bukan tambahan.

## Accessibility & Inclusion

Standar wajar setara WCAG 2.1 AA sebagai lantai: kontras teks tubuh ≥ 4.5:1 (badge/status termasuk), target sentuh memadai untuk pemakaian HP oleh pejabat penandatangan, dukungan navigasi keyboard penuh pada formulir, dan alternatif `prefers-reduced-motion` untuk setiap animasi (toast, modal-shake, transisi). Bahasa Indonesia yang lugas sebagai bentuk inklusi bagi staf non-teknis. Tidak ada target formal khusus di luar itu untuk saat ini.
