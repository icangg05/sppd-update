# Panduan Membuat Bot Telegram (BotFather) — SPPD Kota Kendari

Bot Telegram dipakai untuk mengirim notifikasi pengajuan, persetujuan, penolakan, dan revisi SPPD.
Panduan ini membahas cara membuat bot, mengisi profilnya, dan menyambungkannya ke aplikasi.

Buat **dua bot terpisah**: satu untuk produksi dan satu untuk lokal/dev. Satu bot hanya punya
satu jalur penerimaan pesan (webhook *atau* polling), jadi memakai bot produksi di laptop akan
memutus verifikasi di server produksi.

---

## 1. Rekomendasi nama

**Nama tampilan** adalah nama yang muncul di daftar chat. Boleh berspasi dan bisa diubah kapan saja.

**Username** dipakai di tautan `t.me/<username>`. Aturannya: 5–32 karakter, hanya huruf, angka, dan
`_`, **wajib diakhiri `bot`**, dan unik di seluruh Telegram. Username sebaiknya tidak diganti setelah
dipakai, karena tautan dan nilai `.env` mengacu ke sana.

| | Produksi | Lokal / dev |
|---|---|---|
| Nama tampilan | **SPPD Kota Kendari** | **SPPD Kendari (DEV)** |
| Username pilihan 1 | `SppdKendariBot` | `SppdKendariDevBot` |
| Username pilihan 2 | `SppdKotaKendariBot` | `SppdKendariTestBot` |
| Username pilihan 3 | `ESppdKendariBot` | `SppdKendariLocalBot` |
| Username pilihan 4 | `NotifSppdKendariBot` | `SppdKendari_dev_bot` |

- Coba berurutan. Bila BotFather menjawab *"Sorry, this username is already taken"*, pakai pilihan berikutnya.
- Bila bot dev disimpan bersama bot produksi di akun Telegram yang sama, akhiran `(DEV)` mencegah salah pilih.
- Pilihan lain untuk nama tampilan produksi: *Notifikasi SPPD Kendari* atau *e-SPPD Kendari*.

---

## 2. Membuat bot

1. Buka Telegram, cari **@BotFather** (centang biru resmi), lalu tekan **Start**.
2. Kirim `/newbot`.
3. BotFather bertanya nama bot. Kirim nama tampilan, mis. `SPPD Kota Kendari`.
4. BotFather bertanya username. Kirim username, mis. `SppdKendariBot`.
5. BotFather membalas dengan **token**, bentuknya seperti `7123456789:AAH...xyz`.

> ⚠️ **Token = password bot.** Siapa pun yang memegangnya bisa mengirim pesan atas nama bot.
> Jangan di-commit ke git, jangan dikirim di grup, dan simpan hanya di `.env` server.

---

## 3. Melengkapi profil bot (disarankan)

Semua perintah dikirim ke @BotFather. Setelah perintah dikirim, pilih bot yang ingin diubah dari
daftar yang muncul.

### `/setdescription` — teks di layar chat kosong (maks. 512 karakter)

Teks ini dilihat pengguna sebelum menekan Start.

```text
Bot resmi notifikasi Sistem SPPD Elektronik Pemerintah Kota Kendari.

Bot ini mengirim pemberitahuan otomatis saat ada pengajuan SPPD baru, serta saat pengajuan disetujui, ditolak, atau perlu revisi.

Cara menghubungkan akun:
1. Buka aplikasi SPPD, lalu masuk ke Profil Saya.
2. Tekan Hubungkan Telegram.
3. Buka tautan yang muncul, lalu tekan Start.

Pesan dari bot ini tidak perlu dibalas.
```

### `/setabouttext` — bio singkat di profil bot (maks. 120 karakter)

```text
Notifikasi resmi SPPD Elektronik Kota Kendari. Hubungkan lewat menu Profil di aplikasi SPPD.
```

### `/setcommands` — menu perintah

Kirim teks berikut persis dalam satu pesan:

```text
start - Hubungkan akun SPPD / tampilkan petunjuk
```

Bot hanya memproses `/start`. Pesan lain dibalas dengan petunjuk singkat.

### `/setuserpic` — foto profil

Kirim logo Pemerintah Kota Kendari atau logo aplikasi SPPD. Gunakan gambar persegi (mis. 512×512 px)
dengan logo di tengah, karena foto dipotong menjadi lingkaran.

### `/setjoingroups` → **Disable**

Bot ini hanya untuk chat pribadi, dan tautan verifikasi yang dibuka dari grup ditolak sistem.
Dengan opsi ini dimatikan, bot tidak bisa ditambahkan ke grup.

### Untuk bot dev

Cukup isi `/setdescription` singkat, mis. *"Bot uji coba SPPD Kendari, bukan untuk pengguna."*

---

## 4. Menyambungkan ke aplikasi

### Produksi (`.env` server)

```env
APP_URL=https://domain-produksi          # wajib HTTPS publik
TELEGRAM_BOT_TOKEN=7123456789:AAH...xyz
TELEGRAM_BOT_USERNAME=SppdKendariBot     # tanpa "@", sama persis dengan username bot
TELEGRAM_ENABLED=true
TELEGRAM_UPDATE_MODE=webhook
TELEGRAM_WEBHOOK_SECRET=                 # isi dengan hasil: openssl rand -hex 32
```

Lalu restart container dan daftarkan webhook sekali:

```bash
docker compose restart
docker exec sppd_update_app php artisan telegram:webhook
docker exec sppd_update_app php artisan telegram:webhook --info   # baris "Bot: @..." = token valid
```

### Lokal / dev (`.env` laptop)

```env
TELEGRAM_BOT_TOKEN=<token bot dev>
TELEGRAM_BOT_USERNAME=SppdKendariDevBot
TELEGRAM_ENABLED=true
TELEGRAM_UPDATE_MODE=polling
TELEGRAM_WEBHOOK_SECRET=
```

```bash
docker compose restart
docker exec sppd_update_app php artisan telegram:webhook --info   # pastikan "Bot: @SppdKendariDevBot" dan url kosong
```

Pada mode polling, container `scheduler` mengambil pesan masuk tiap menit tanpa perlu tunnel.

---

## 5. Uji coba

1. Buka `t.me/<username-bot>` lalu tekan **Start**. Bot membalas pesan petunjuk. Di mode polling,
   balasan bisa butuh hingga ±1 menit pada percobaan pertama.
2. Login ke aplikasi, buka **Profil Saya**, tekan **Hubungkan Telegram**, buka tautan atau pindai QR,
   lalu tekan **Start**. Status berubah menjadi **Terhubung** dan bot mengirim pesan konfirmasi.
3. Tekan **Tes Pesan** di Profil. Pesan uji masuk ke chat bot.

Bila tidak ada balasan, periksa hal berikut:
- container `queue` berjalan (`docker ps`)
- token dan username di `.env` sudah benar, lalu container di-restart
- untuk produksi, `telegram:webhook --info` tidak menampilkan `last_error_message`

---

## 6. Perawatan

| Kejadian | Tindakan |
|---|---|
| Token bocor | Di @BotFather: `/revoke` → pilih bot, lalu ganti `TELEGRAM_BOT_TOKEN`, `docker compose restart`, dan jalankan ulang `telegram:webhook` (produksi). |
| Lupa token | `/token` → pilih bot. |
| Domain produksi berubah | Perbarui `APP_URL`, lalu jalankan ulang `telegram:webhook`. |
| Ganti nama tampilan / foto | `/setname`, `/setuserpic`. Tidak perlu mengubah `.env`. |
| Pindah pengelola | `/mybots` → pilih bot → *Transfer Ownership*, agar bot tidak terikat akun pribadi satu orang. |

Disarankan membuat bot produksi dari **akun Telegram dinas atau milik tim**, bukan akun pribadi,
supaya akses BotFather tidak hilang saat pengelola berganti.
