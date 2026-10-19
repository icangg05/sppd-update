SPPD Kota Kendari => Laravel 13

===================================================
cloudflared tunnel --url http://localhost:8004
===================================================

# ⚠️ PENTING: Notifikasi Telegram (pengganti WhatsApp)

Notifikasi SPPD dan verifikasi akun berjalan lewat bot Telegram. Tanpa langkah di bawah,
tombol "Hubungkan Telegram" ditolak dan tidak ada notifikasi yang terkirim.

## Produksi

1. Buat bot **khusus produksi** di @BotFather (`/newbot`), lalu catat token dan username-nya.
   Langkah lengkap, rekomendasi nama, dan teks profil bot ada di [panduan-bot-telegram.md](panduan-bot-telegram.md).
2. Isi `.env`:

   ```env
   APP_URL=https://domain-produksi          # WAJIB HTTPS publik, Telegram menolak http
   TELEGRAM_BOT_TOKEN=123456:ABC...
   TELEGRAM_BOT_USERNAME=NamaBotProduksi    # tanpa "@"
   TELEGRAM_ENABLED=true
   TELEGRAM_UPDATE_MODE=webhook
   TELEGRAM_WEBHOOK_SECRET=                 # string acak, mis. hasil: openssl rand -hex 32
   ```

3. Deploy (`docker compose up -d --build`), lalu jalankan `docker exec sppd_update_app php artisan migrate --force`.
4. Daftarkan webhook **sekali**. Ulangi setiap kali domain (`APP_URL`) atau `TELEGRAM_WEBHOOK_SECRET` berubah.

   ```bash
   docker exec sppd_update_app php artisan telegram:webhook          # daftarkan
   docker exec sppd_update_app php artisan telegram:webhook --info   # cek: url benar & last_error_message kosong
   ```

## Lokal / dev

- **Pakai bot terpisah dari produksi.** Satu bot hanya punya satu jalur penerimaan pesan.
  Mode polling di lokal butuh webhook nonaktif (`telegram:webhook --delete`), jadi memakai bot
  produksi di lokal akan memutus verifikasi di produksi.
- Isi `.env` dengan token/username bot dev dan `TELEGRAM_UPDATE_MODE=polling`. Container
  `scheduler` mengambil pesan masuk tiap menit, jadi tidak perlu tunnel atau HTTPS.
- Setelah mengubah `.env`, jalankan `docker compose restart` agar queue dan scheduler membaca nilai baru.

## Catatan

- Container `queue` harus berjalan karena semua pesan dikirim lewat antrian. Bila worker mati,
  penghubungan akun dan pembuatan SPPD ditolak.
- Pengguna lama (yang sebelumnya verifikasi WhatsApp) harus menekan **Hubungkan Telegram** sekali
  di halaman Profil. Panduan untuk pengguna ada di `/panduan/telegram`.
- Bila pengguna memblokir bot, tautannya otomatis diputus dan ia perlu menghubungkan ulang.

# command redesign pakai skills:

/brandkit /high-end-visual-design /design-taste-frontend /redesign-existing-projects /impeccable /use-components redesign halaman kalender. ikuti model style di halaman index bagian title card lalu element pakai dash-enter. jelaskan hasil pakai bahasa

/brandkit /high-end-visual-design /design-taste-frontend /redesign-existing-projects /impeccable /use-components redesign laman pengeluaran rill. ikuti model style di halaman index bagian title card lalu element pakai dash-enter. tambahkan design ornamen, garis, bentuk, icon, atau lainnya agar tidak terlalu polos. font gunakan ukuran normal serta buat agar lebih user experience (mudah dipahami pengguna awam), ganti warna tombol warna hitam dengan warna lai

pada index sppd, untuk mobile minimalizir scroll horizontal sehingga untuk tabel kamu design selayaknya ukuran mobile, kreativitaskan
