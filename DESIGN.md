---
name: SPPD Kota Kendari
description: Sistem digital pengelolaan Surat Perintah Perjalanan Dinas Pemerintah Kota Kendari — tepercaya, jernih, rapi.
colors:
  primary: "#1e80c6"
  primary-hover: "#1a6ba6"
  primary-strong: "#175987"
  accent: "#3a9ddf"
  accent-bright: "#4aa6e1"
  primary-tint: "#edf6fc"
  surface: "#f4f9fe"
  surface-card: "#ffffff"
  app-bg: "#f8fafc"
  nav-bg: "#0f172a"
  nav-border: "#1e293b"
  ink: "#0f172a"
  ink-secondary: "#334155"
  muted: "#64748b"
  border: "#e2e8f0"
  divider: "#f1f5f9"
  success: "#059669"
  danger: "#dc2626"
  warning: "#f59e0b"
typography:
  page-title:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "normal"
  title:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 700
    lineHeight: 1.3
    letterSpacing: "normal"
  body:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
  label:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.3
    letterSpacing: "0.05em"
rounded:
  control: "0.5rem"
  card: "0.75rem"
  modal: "1rem"
  pill: "9999px"
spacing:
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1rem"
  lg: "1.5rem"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface-card}"
    rounded: "{rounded.control}"
    padding: "0.5rem 1rem"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "{colors.surface-card}"
  button-secondary:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-secondary}"
    rounded: "{rounded.control}"
    padding: "0.5rem 1rem"
  button-danger:
    backgroundColor: "{colors.danger}"
    textColor: "{colors.surface-card}"
    rounded: "{rounded.control}"
    padding: "0.5rem 1rem"
  input:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-secondary}"
    rounded: "{rounded.control}"
    padding: "0.5rem 0.75rem"
  badge-pill:
    rounded: "{rounded.pill}"
    padding: "0.125rem 0.625rem"
  card:
    backgroundColor: "{colors.surface-card}"
    rounded: "{rounded.card}"
    padding: "1rem"
  modal:
    backgroundColor: "{colors.surface-card}"
    rounded: "{rounded.modal}"
  nav-item-active:
    backgroundColor: "{colors.primary-strong}"
    textColor: "{colors.surface-card}"
    rounded: "{rounded.control}"
    padding: "0.5rem 0.75rem"
---

# Design System: SPPD Kota Kendari

## 1. Overview

**Creative North Star: "Meja Kerja yang Rapi" (The Clear Desk)**

Sistem ini adalah meja kerja digital seorang petugas yang teliti: tenang, teratur, setiap dokumen punya tempatnya. Aksi utama selalu tahu di mana harus berdiri, status selalu terbaca sekilas, dan tak ada satu piksel pun yang berteriak minta perhatian tanpa alasan. Ini alat kerja pemerintahan yang dipakai berjam-jam untuk urusan administratif bertaruh tinggi — dokumen resmi bertanda tangan digital yang harus benar — jadi kredibilitas dibangun dari keteraturan, bukan dari dekorasi.

Chrome aplikasi memakai kerangka **slate** yang netral dan diam: sidebar gelap (`#0f172a`) sebagai jangkar navigasi, kanvas terang (`#f8fafc`) sebagai bidang kerja. Di atasnya, satu keluarga **biru logo** menjadi satu-satunya suara aksen — tombol utama, item nav aktif, cincin fokus, indikator status. Biru itu langka dan bertujuan; ia menandai "di sinilah kamu bertindak", bukan menghiasi. Kartu flat berbatas tipis dengan bayangan halus memberi struktur tanpa berat. Padat saat data memang butuh (tabel bisa panjang dan rapat), lapang saat bisa (formulir dan header bernapas).

Sistem ini **menolak** kesan aplikasi pemerintah jadul (tabel abu polos tanpa hierarki, font default, tata letak kaku), penampilan SaaS startup yang berlebihan (gradien mencolok, ilustrasi lucu, animasi dekoratif), dan layar yang sesak tanpa hierarki. Yang paling dijaga: **konsistensi** — satu kosakata komponen dipakai identik di setiap layar, karena keseragaman itulah yang menumbuhkan rasa aman bahwa dokumen yang keluar sudah benar.

**Key Characteristics:**
- Kerangka slate netral + satu suara aksen biru logo yang hemat (≤10% per layar)
- Kartu flat: batas tipis + bayangan halus, bukan elevasi tebal
- Poppins tunggal di semua peran; hierarki lewat bobot & ukuran, bukan pergantian font
- Status selalu terbaca sekilas lewat pill semantik yang terstandardisasi
- Motion hanya untuk umpan balik & keadaan; tak ada koreografi

## 2. Colors

Palet netral-dingin (slate) sebagai kerangka, dengan satu keluarga biru logo sebagai satu-satunya aksen bermuatan.

### Primary
- **Biru Aksi** (`#1e80c6`, primary-600): Warna aksi dan otoritas. Dipakai untuk tombol utama, latar item nav aktif, dan penanda status aktif. Inilah "di sinilah kamu bertindak".
- **Biru Tekan** (`#1a6ba6`, primary-700): Keadaan hover tombol utama — satu langkah lebih dalam, memberi umpan balik taktil tanpa berubah warna.
- **Biru Jangkar** (`#175987`, primary-800): Latar item navigasi yang sedang aktif di sidebar gelap; cukup pekat untuk menonjol di atas slate-900.
- **Azure Kilau** (`#3a9ddf`, primary-500): Aksen sekunder untuk ikon aktif & sorotan kecil.
- **Kabut Biru** (`#edf6fc`, primary-50): Latar tint sangat lembut untuk badge "sedang diproses" dan sorotan area terpilih.

### Neutral
- **Tinta** (`#0f172a`, slate-900): Judul halaman & teks paling penting; juga latar sidebar navigasi.
- **Tinta Kedua** (`#334155`, slate-700): Teks tubuh utama di dalam tabel & isi kartu.
- **Redup** (`#64748b`, slate-500): Subjudul, label kolom tabel, teks pembantu. **Jangan pakai lebih terang dari ini untuk teks tubuh** — kontras di bawahnya melanggar keterbacaan.
- **Batas** (`#e2e8f0`, slate-200): Garis batas kartu, tabel, dan panel.
- **Pemisah** (`#f1f5f9`, slate-100): Garis pemisah antar-baris tabel yang lebih halus dari batas.
- **Kanvas Kerja** (`#f8fafc`, slate-50): Latar body aplikasi — bidang tempat kartu berdiri.
- **Kartu** (`#ffffff`): Permukaan kartu, panel, modal, dan input.
- **Permukaan Sejuk** (`#f4f9fe`): Latar alternatif bernuansa biru sangat tipis untuk area tertentu.

### Semantic (status & aksi)
- **Berhasil** (`#059669`, emerald-600): Tombol sukses; keluarga hijau untuk status *approved / verified*.
- **Bahaya** (`#dc2626`, red-600): Tombol destruktif & pesan error; keluarga merah untuk *rejected / returned*.
- **Peringatan** (`#f59e0b`, amber-500): Tombol peringatan; keluarga amber untuk status *pending / revision*.

### Named Rules
**The One Voice Rule.** Biru logo adalah satu-satunya aksen bermuatan, dipakai pada ≤10% permukaan layar mana pun: aksi utama, seleksi kini, dan indikator status. Kelangkaannya justru intinya. Semua elemen interaktif memakai util `primary-*`, **tidak pernah** literal warna, agar aksen bisa diganti dari satu sumber kebenaran di `app.css`.

**The Ink Floor Rule.** Teks tubuh tidak pernah lebih terang dari Redup (`#64748b`). "Elegansi" abu-abu pucat adalah alasan nomor satu antarmuka jadi sulit dibaca — dan bagi staf non-teknis itu bukan elegansi, itu penghalang.

## 3. Typography

**Body & Display Font:** Poppins (fallback: ui-sans-serif, system-ui, sans-serif)
**Label/Mono Font:** — (tidak ada; label memakai Poppins bobot tebal + tracking)

**Character:** Satu keluarga geometris-humanis yang bersih dan ramah untuk semua peran — judul, tombol, label, tabel, isi. Tidak ada pasangan font display/body; hierarki dibangun murni dari bobot (400/500/600/700) dan ukuran pada skala rem tetap. Poppins memberi kesan modern dan mudah didekati tanpa terasa main-main — pas untuk alat pemerintahan yang ingin terlihat kontemporer tapi tetap sah.

### Hierarchy
- **Page Title** (700, 1.5rem/24px, line-height 1.2, warna Tinta): Judul halaman di `.page-title`. Satu per layar.
- **Title** (700, 0.875rem/14px, warna Tinta): Judul kartu, header modal (`<h3>`), heading panel. Tebal membedakannya dari body pada ukuran yang sama.
- **Body** (400, 0.875rem/14px, line-height 1.5, warna Tinta Kedua): Isi utama — sel tabel, teks kartu, deskripsi. Prosa panjang dibatasi 65–75ch; data & UI padat boleh lebih rapat.
- **Label** (700, 0.75rem/12px, letter-spacing 0.05em, UPPERCASE, warna Redup): Label field formulir dan header kolom tabel. Tebal + tracking + kapital menjadikannya penanda struktural yang tenang.
- **Micro** (400–500, 0.6875rem/11px, warna Redup): Deskripsi modal, hint, catatan kaki. Selalu sekunder, tak pernah untuk aksi.

### Named Rules
**The One Family Rule.** Poppins mengerjakan semuanya. Font display terlarang di label, tombol, dan data. Butuh penekanan? Naikkan bobot atau ukuran — jangan ganti keluarga font. Skala tetap dalam rem (bukan `clamp` cair) karena UI produk dilihat pada DPI konsisten.

## 4. Elevation

Sistem ini **flat secara default**. Kedalaman disampaikan lewat batas tipis dan bayangan yang sangat halus, bukan elevasi tebal atau glassmorphism dekoratif. Kartu berdiri di atas kanvas dengan kombinasi `border border-slate-200` + bayangan lembut; bukan bayangan gelap yang mengambang. Bayangan lebih terasa hanya sebagai respons keadaan (hover kartu), bukan sebagai hiasan permanen. Satu-satunya blur yang dipakai adalah `backdrop-blur` tipis di backdrop modal — fungsional, memisahkan modal dari isi di baliknya, bukan efek kaca gaya-gayaan.

### Shadow Vocabulary
- **Kartu diam** (`box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05)`; util `shadow-card` / `shadow-sm`): Bayangan default kartu, tabel, panel — nyaris tak terlihat, hanya memberi pijakan.
- **Kartu hover** (`box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08)`; util `shadow-card-hover`): Muncul saat kartu interaktif disorot — umpan balik, bukan dekorasi.
- **Modal** (`shadow-2xl`): Satu-satunya elevasi tinggi, sah karena modal memang harus melayang jelas di atas segalanya.

### Named Rules
**The Flat-By-Default Rule.** Permukaan flat saat diam. Bayangan muncul hanya sebagai respons keadaan (hover, modal). Jika sebuah kartu punya bayangan gelap mengambang tanpa interaksi, itu salah — kembalikan ke `border + shadow-sm`.

## 5. Components

### Buttons
- **Shape:** Sudut lembut (`rounded-lg`, 0.5rem) di semua ukuran; bayangan mikro (`shadow-2xs`) sebagai pijakan halus.
- **Primary:** Latar Biru Aksi (`#1e80c6`), teks putih, padding `0.5rem 1rem` (ukuran md). Untuk aksi utama tunggal per konteks.
- **Hover / Focus:** Hover menggelapkan ke Biru Tekan (`#1a6ba6`) via `transition-colors`; fokus keyboard memunculkan cincin `ring-2 ring-primary-500` dengan offset. Saat aksi Livewire berjalan, spinner otomatis muncul dan tombol dinonaktifkan.
- **Secondary:** Batas slate + latar putih + teks Tinta Kedua; untuk aksi sekunder.
- **Danger / Success / Warning:** Memakai warna semantik solid + putih; disimpan untuk aksi yang benar-benar destruktif/afirmatif.
- **Ghost:** Tanpa latar, teks Redup → menggelap saat hover; untuk aksi tersier & ikon.
- **Ukuran:** sm (`px-3 py-1.5 text-xs`), md (`px-4 py-2 text-sm`), lg (`px-5 py-2.5 text-sm`).

### Badges (pill status)
- **Style:** Pill penuh (`rounded-full`), padding `0.125rem 0.625rem`, teks 0.75rem bobot 500. Latar tint lembut + teks pekat sewarna, bukan warna penuh.
- **State semantik:** Tiap status punya pasangan tetap — *draft* (slate), *in_progress* (biru lembut), *approved/verified* (emerald), *rejected* (red), *completed* (violet), *pending* (amber), *revision* (orange), *signed* (teal), *returned* (rose). Peta ini adalah kosakata status tunggal; jangan buat warna status baru ad-hoc.
- **Mode token warna:** Varian dengan `ring-1 ring-inset` untuk badge non-status (label kategori).

### Cards / Containers
- **Corner Style:** `rounded-xl` (0.75rem) untuk kartu & panel; `rounded-2xl` (1rem) untuk modal.
- **Background:** Putih (`#ffffff`) di atas kanvas slate-50.
- **Shadow Strategy:** `border border-slate-200` + `shadow-sm` saat diam (lihat Elevation). Tanpa elevasi tebal.
- **Internal Padding:** 1rem (`p-4`) standar; header/footer panel `px-4 py-3`.
- **Aturan:** Kartu adalah wadah struktural, bukan default malas. Kartu bersarang di dalam kartu **selalu salah**.

### Inputs / Fields
- **Style:** Latar putih, batas `slate-300`, `rounded-lg`, padding `0.5rem 0.75rem`, teks 0.875rem, placeholder `slate-400`, bayangan mikro `shadow-2xs`.
- **Label:** Di atas field — 0.75rem, bold, UPPERCASE, tracking, warna Redup; wajib `*` merah untuk field wajib.
- **Focus:** Batas bergeser ke `primary-500` + cincin `ring-2 ring-primary-500/30` — glow biru lembut yang menandai fokus tanpa berisik.
- **Disabled:** Latar `slate-100`, teks `slate-500`, kursor `not-allowed` — otomatis dari satu tempat (mis. field telepon terkunci setelah verifikasi berhasil).
- **Error:** Pesan `text-xs font-semibold text-red-600` tepat di bawah field, terhubung ke `@error` Livewire.

### Navigation (Sidebar)
- **Style:** Panel gelap tetap selebar 16rem (`bg-slate-900`, batas kanan `slate-800`), teks `slate-300/400`. Off-canvas di bawah `lg`, tetap terlihat di desktop.
- **Item default:** `rounded`, `px-3 py-2`, 0.875rem medium, teks `slate-400` → hover `bg-slate-800 text-slate-100`.
- **Item aktif:** Latar Biru Tekan (`bg-primary-700`) + teks putih, atau grup induk `bg-slate-800/60` dengan ikon `text-primary-400`.
- **Section label:** 0.75rem bold UPPERCASE tracking `text-slate-500` sebagai pemisah grup menu.

### Modal
- **Style:** Kartu putih `rounded-2xl` + `shadow-2xl` + batas tipis, di atas backdrop `bg-slate-900/60 backdrop-blur-xs`. Header/isi/footer terbagi garis `slate-100`.
- **Behavior:** Transisi masuk 300ms (naik + skala 95→100), keluar 200ms. Modal non-closeable **menggoyang** kartu (`animate-modal-shake`) saat diklik di luar — isyarat "tutup lewat tombol", bukan sekadar mengabaikan. Scroll body terkunci selama modal terbuka. Modal adalah pilihan terakhir; utamakan alur inline/progresif.

### Toast
- **Style:** Kartu mengambang dengan bilah progres durasi (`toast-progress`) yang menyusut penuh→kosong dan **jeda saat hover**. Untuk umpan balik hasil aksi (mis. verifikasi TTE berhasil/gagal) yang harus terlihat, bukan diam.

## 6. Do's and Don'ts

### Do:
- **Do** pakai util `primary-*` untuk setiap elemen interaktif (tombol utama, nav aktif, fokus, status) — **jangan pernah** literal warna. Satu sumber kebenaran di `app.css`.
- **Do** batasi biru logo pada ≤10% permukaan tiap layar (The One Voice Rule). Selebihnya kerangka slate netral.
- **Do** jaga teks tubuh pada Tinta Kedua (`#334155`) dan label/pembantu tidak lebih terang dari Redup (`#64748b`); kontras minimal 4.5:1.
- **Do** pakai skala radius yang disepakati: kontrol `rounded-lg`, kartu/panel/dropdown `rounded-xl`, modal `rounded-2xl`, pill & avatar `rounded-full`.
- **Do** beri tiap SPPD/verifikasi/realisasi status pill semantik dari peta yang ada; buat umpan balik aksi (terutama TTE) selalu tegas dan terlihat lewat toast.
- **Do** pakai satu kosakata komponen (`x-ui.button`, `x-ui.badge`, `x-ui.modal`, `x-form.*`) identik di semua layar. Setiap "Simpan" harus terlihat sama.
- **Do** tampung kepadatan pada tabel saat data memang butuh, tapi sisakan napas pada header & formulir.

### Don't:
- **Don't** terlihat seperti **aplikasi pemerintah jadul** — tabel abu polos tanpa hierarki, font default, tata letak kaku. Hierarki & keteraturan yang membangun kepercayaan.
- **Don't** tampil seperti **SaaS startup berlebihan** — gradien mencolok, teks bergradien (`background-clip: text` terlarang), ilustrasi lucu, animasi dekoratif. Ini alat kerja resmi.
- **Don't** menjejalkan layar hingga **terlalu padat/ramai** tanpa hierarki sampai aksi utama tenggelam.
- **Don't** biarkan komponen **tidak konsisten** antar halaman — tombol/badge/modal bergaya beda adalah cacat, salah satunya keliru.
- **Don't** pakai border-left/right >1px sebagai stripe warna pada kartu/alert; pakai batas penuh, tint latar, atau ikon.
- **Don't** pakai bayangan gelap mengambang pada kartu diam (The Flat-By-Default Rule) atau glassmorphism dekoratif; blur hanya sah di backdrop modal.
- **Don't** ganti keluarga font untuk penekanan; naikkan bobot/ukuran Poppins.
- **Don't** jadikan modal pilihan pertama; habiskan dulu alternatif inline/progresif.
