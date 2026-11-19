# Audit konsistensi komponen

Dipakai hanya saat diminta mengaudit seluruh view. Untuk edit biasa, SKILL.md sudah cukup.

1. Cari elemen mentah:
   ```bash
   grep -rn '<button\|class="badge"\|class="[^"]*badge' resources/views --include=*.blade.php
   grep -rn '<input\|<select\|<textarea' resources/views --include=*.blade.php
   ```
   Abaikan hasil di `resources/views/components/ui/*` dan `resources/views/components/form/*`.
2. Ganti tiap temuan ke `<x-ui.*>` / `<x-form.*>` yang sesuai, kecuali yang termasuk "Boleh tetap HTML mentah" di SKILL.md. Petunjuk pengenalan:
   - `<button>` dengan `border-slate-300 bg-white text-slate-700` = `variant="secondary"`.
   - `<span class="badge-{{ $status }}">` = `<x-ui.badge :status="...">`.
   - Alert dengan gradien (`bg-linear-to-br from-red-50 ...`) = `<x-ui.alert>`.
3. Setelah migrasi, sekali saja: `docker exec sppd_update_app php artisan view:cache` lalu `view:clear`, untuk memastikan semua Blade ter-compile.
4. Laporkan file yang diubah.
