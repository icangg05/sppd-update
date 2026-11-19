---
name: use-components
description: Katalog komponen Blade project (x-ui.button/badge/alert/modal, x-form.input/select/textarea/checkbox/radio/file/image-crop). Pakai saat menambah atau mengganti tombol, field form, badge, alert, atau modal di file .blade.php, atau saat diminta mengaudit konsistensi komponen. Tidak perlu untuk edit teks, tata letak, atau logika Blade saja.
---

# Komponen UI

Pakai komponen di bawah, bukan `<button>`, `<input>`, `<select>`, `<textarea>`, atau badge mentah. Variasi lewat prop/variant; `class` hanya untuk spacing/lebar. Varian baru yang berulang ditambahkan ke komponennya, bukan di-hardcode di pemakaian. Indentasi 2 spasi, jangan jalankan Pint.

## UI — `resources/views/components/ui/`
- `<x-ui.button>`: `variant` primary (default) | secondary (Reset/Batal/Kembali: border slate + bg putih) | success | warning | danger | ghost | danger-ghost (hapus/reset sekunder) | dark (Filter/Cari) | violet. `type` default `button`. `href` → link `wire:navigate`; URL dengan lebih dari satu query param wajib `:href="route(...)"`. Slot `icon`. Disabled: satu komponen dengan `:disabled="..."`, bukan dua tombol lewat `@if/@else`.
- `<x-ui.badge>`: `status` (draft, in_progress, approved, rejected, completed, pending, revision, signed, verified, returned) atau `bg` + `text` (kelas Tailwind).
- `<x-ui.alert>`: `variant` danger (default, memblokir) | warning (sementara) | info | success; `title` = kalimat masalahnya, bukan "PERINGATAN"; `icon` fa-*; slot `actions`. Teks tebal: `<strong class="font-semibold">` tanpa warna.
- `<x-ui.modal>`: `show` (ekspresi Alpine), `title`, `description`, `icon`, `maxWidth` (default `max-w-md`), `closeable` (default true).

## Form — `resources/views/components/form/`
Semua sudah menangani label, `required`, `hint`, `@error`, dan `old()`. Prop umum: `name`, `label`, `id`, `required`, `hint`, `class`, `wrapperClass`. Di Livewire pakai `wire:model="prop"` tanpa `name`.
- `input`: `type`, `value`, `placeholder`, `labelClass`, `icon` (fa-* di kiri), `loadingTarget` (spinner di kanan). Field search juga pakai ini.
- `textarea`: `rows`, `value`, `placeholder` · `select`: `disabled`, opsi lewat slot · `checkbox`: `value`, `checked`, `size` (`md` | `sm` ringkas) · `radio`: `value`, `checked` · `file`: `accept` · `image-crop`: `ratios`, `title`, `tipTitle`, `tip`, `maxDimension`, `maxKb`.

## Boleh tetap HTML mentah
Tema warna lain yang disengaja (form laporan emerald, role-form violet, modal approval di sppd/show biru); adornment di dalam field (chevron kustom, toggle mata passphrase, input yang menyatu dengan tombol); repeater inline tanpa label (baris tujuan `destinations.*`, kartu pengikut); tombol icon-only di baris tabel dan chrome UI (sidebar, header, tab, filter chip). Tombol berlabel di filter bar tetap `<x-ui.button>`.

Audit konsistensi seluruh view: baca `audit.md` di folder ini.
