---
name: use-components
description: Pastikan view Blade/Livewire konsisten memakai komponen yang sudah ada (button, badge, modal, input, select, textarea, checkbox, radio, file) — bukan HTML mentah. Pakai saat membuat/mengubah file .blade.php, atau saat diminta mengaudit konsistensi komponen UI.
---

# Konsistensi Komponen UI

Project ini sudah punya komponen Blade reusable. **Jangan menulis `<button>`, `<input>`,
`<span class="badge">`, `<select>`, dll. mentah** kalau sudah ada komponennya. Selalu
pakai komponen di bawah ini supaya gaya konsisten dan styling terpusat di satu tempat.

## Aturan

1. Sebelum menulis elemen UI di file `.blade.php`, cek dulu apakah ada komponen yang
   cocok di daftar di bawah. Kalau ada, **wajib pakai komponen itu**.
2. Pakai **prop/variant yang sudah disediakan** untuk variasi (mis. warna tombol lewat
   `variant`, bukan menimpa dengan `class` Tailwind manual).
3. `class` hanya untuk penyesuaian tambahan (spacing/lebar), bukan untuk mengganti
   identitas visual komponen.
4. Kalau butuh varian baru yang berulang (mis. warna badge baru), **tambahkan ke
   komponennya**, jangan hardcode di pemakaian.
5. Tetap ikuti gaya project: indentasi 2 spasi, jangan jalankan Pint.

## Katalog Komponen

### `<x-ui.button>` — `resources/views/components/ui/button.blade.php`
Tombol / link. Render `<a wire:navigate>` jika ada `href`, selain itu `<button>`.
- `variant`: `primary` (default), `secondary`, `success`, `warning`, `danger`, `ghost`, `dark`
  - `dark` = tombol slate-800 (dipakai untuk tombol Filter/Cari di filter bar).
- `type`: default `button` (mis. `submit`, `reset`)
- `href`: bila diisi → jadi link navigasi
- slot `icon` opsional untuk ikon di kiri teks
- `wire:click`, `@click`, `disabled`, dll. diteruskan otomatis lewat atribut.

```blade
<x-ui.button variant="success" type="submit">Simpan</x-ui.button>
<x-ui.button variant="secondary" href="{{ route('sppd.index') }}">Batal</x-ui.button>
<x-ui.button variant="dark" type="submit">Filter</x-ui.button>
```
JANGAN: `<button class="bg-cyan-600 text-white ...">`

### `<x-ui.badge>` — `resources/views/components/ui/badge.blade.php`
Label status. Dua mode:
- **Mode status** — prop `status` memetakan ke kelas CSS `badge-{status}` di `app.css`
  (`draft`, `in_progress`, `approved`, `rejected`, `completed`, `pending`, `revision`,
  `signed`, `verified`, `returned`). Pakai ini untuk badge status SPPD/approval.
- **Mode warna** — prop `bg` + `text` (kelas Tailwind) untuk warna ad-hoc.

```blade
<x-ui.badge :status="$sppd->status->value" class="px-2.5 py-1 text-xs uppercase">{{ $sppd->status->label() }}</x-ui.badge>
<x-ui.badge bg="bg-emerald-100" text="text-emerald-700">Disetujui</x-ui.badge>
```
JANGAN: `<span class="badge-{{ $status }} ...">` atau `<span class="badge bg-... text-...">`

### `<x-ui.modal>` — `resources/views/components/ui/modal.blade.php`
Modal Alpine.js. Props: `show` (ekspresi Alpine), `title`, `description`, `icon`
(`fa-*` atau SVG), `maxWidth` (default `max-w-md`), `closeable` (default `true`).

```blade
<x-ui.modal show="openModal" title="Konfirmasi" icon="fa-solid fa-trash">
  ...isi...
</x-ui.modal>
```

## Komponen Form — `resources/views/components/form/*`

Semua field form **wajib** lewat komponen ini (sudah menangani label, `required`,
`hint`, `@error`, dan `old()`). Prop umum yang ada di hampir semua: `name`, `label`,
`id`, `required`, `hint`, `class`, `wrapperClass`.

**Mendukung dua mode binding:**
- Form POST biasa → pakai `name="..."` (otomatis isi `value` dari `old()` & key `@error`).
- Form Livewire → pakai `wire:model="prop"` (tanpa `name`). Komponen otomatis
  mendeteksi `wire:model*`, tidak menimpa `value`, dan memakai nama property untuk
  `id` & `@error`. Contoh: `<x-form.input wire:model="start_date" type="date" label="Tanggal" required />`.

- `<x-form.input>` — props tambahan: `type` (default `text`), `value`, `placeholder`, `labelClass`
- `<x-form.textarea>` — props tambahan: `rows` (default 3), `value`, `placeholder`
- `<x-form.select>` — props tambahan: `disabled`; `<option>` lewat slot
- `<x-form.checkbox>` — props tambahan: `value` (default `1`), `checked`
- `<x-form.radio>` — props tambahan: `value`, `checked`
- `<x-form.file>` — props tambahan: `accept` (default `.pdf,.docx,.jpg,.jpeg,.png`)

```blade
<x-form.input name="tujuan" label="Tujuan" required />
<x-form.select name="status" label="Status">
  <option value="draft">Draft</option>
</x-form.select>
```
JANGAN: `<input class="w-full rounded border ...">` atau bikin label/`@error` manual.

## Cara mengaudit (saat diminta cek konsistensi)

Cari elemen mentah yang seharusnya pakai komponen, lalu ganti:

```bash
# tombol & badge mentah
grep -rn '<button\|class="badge"\|class="[^"]*badge' resources/views --include=*.blade.php
# field form mentah
grep -rn '<input\|<select\|<textarea' resources/views --include=*.blade.php
```

Abaikan match yang ada di dalam file komponen itu sendiri
(`resources/views/components/ui/*` dan `resources/views/components/form/*`) — di situ
HTML mentah memang wajar. Untuk setiap match lain, ganti ke `<x-ui.*>` / `<x-form.*>`
yang sesuai dan laporkan perubahannya.

## Pengecualian yang WAJAR (jangan dipaksa migrasi)

Komponen memakai tema **cyan** + bentuk/label standar. Biarkan elemen mentah bila:
- **Tema warna berbeda** yang disengaja — mis. form laporan (emerald), role-form
  (violet), modal approval di `sppd/show` (biru). Memaksa ke komponen mengubah warna.
  (Untuk tombol, tema emerald/danger/dark sudah ada variant-nya; pakai itu.)
- **Ada adornment di dalam field** — ikon search di kiri input, chevron kustom di
  select, toggle mata pada passphrase, atau input yang menyatu inline dengan tombol.
- **Repeater inline compact tanpa label** — mis. baris tujuan (`destinations.*`),
  kartu checkbox pengikut.
- **Tombol icon-only di baris tabel** (edit/hapus) dan chrome UI (sidebar, header,
  tab, filter chip) — itu pola tersendiri, bukan tombol berlabel.

Setelah migrasi, jalankan `docker exec sppd_update_app php artisan view:cache` lalu
`view:clear` untuk memastikan semua blade tetap ter-compile.
