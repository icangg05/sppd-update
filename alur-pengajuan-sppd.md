Listed directory Models
Viewed User.php:1-113
Viewed SppdRequest.php:1-167
Listed directory Enums
Listed directory Services
Viewed SppdStatus.php:1-35
Viewed SppdDomain.php:1-20
Listed directory seeders
Viewed RoleAndPermissionSeeder.php:1-249

Tentu, ini adalah panduan lengkap alur pengajuan SPPD (Surat Perintah Perjalanan Dinas) berdasarkan standar operasional prosedur (SOP) Pemerintahan Daerah yang disesuaikan dengan _role_ yang sudah Anda definisikan di `RoleAndPermissionSeeder`.

### ❓ Apakah alur pengajuan Dalam Daerah dan Luar Daerah berbeda?

**Ya, sangat berbeda.**

- **Dalam Daerah:** Alurnya lebih pendek. Biasanya persetujuan cukup diselesaikan di tingkat internal instansi (maksimal di Kepala OPD/Camat).
- **Luar Daerah:** Alurnya lebih berjenjang dan ketat. Untuk pejabat setingkat Kepala OPD/Camat, perjalanan luar daerah wajib mendapat izin dari pimpinan daerah (Sekda/Walikota) karena menyangkut representasi daerah dan plot anggaran yang lebih besar.

---

### 🛣️ Detail Alur Pengajuan Berdasarkan Role & Instansi

Berikut adalah rincian alur persetujuan (_approval routing_) yang bisa Anda implementasikan:

#### 1. Instansi Reguler (Dinas / Badan / Inspektorat)

_(Melibatkan Role: `staff`, `kasubag`, `kabag`, `sekretaris`, `kepala_opd`, `pptk`)_

- **Pengajuan oleh Staff / Pejabat Fungsional**
  - **Dalam Daerah:** `Staff` ➔ `Kasubag / Kabag` (Verifikasi) ➔ `PPTK` (Verifikasi Anggaran) ➔ `Kepala OPD` (Approval Akhir & TTD)
  - **Luar Daerah:** `Staff` ➔ `Kasubag / Kabag` ➔ `Sekretaris Dinas` ➔ `PPTK` ➔ `Kepala OPD` (Approval Akhir)
- **Pengajuan oleh Pejabat Struktural (Kasubag / Kabag / Sekretaris)**
  - **Dalam & Luar Daerah:** `Pemohon` ➔ `PPTK` ➔ `Kepala OPD` (Approval Akhir)
- **Pengajuan oleh Kepala OPD (Kadis / Kaban)**
  - _Kepala OPD tidak bisa menyetujui perjalanannya sendiri._
  - **Dalam Daerah:** `Kepala OPD` ➔ `Asisten` ➔ `Sekda` (Approval Akhir)
  - **Luar Daerah:** `Kepala OPD` ➔ `Asisten` ➔ `Sekda` ➔ `Walikota` (Approval Akhir)

#### 2. Sekretariat Daerah (Setda)

_(Melibatkan Role: `asisten`, `sekda`, `walikota`)_

- **Pengajuan oleh Asisten**
  - **Dalam & Luar Daerah:** `Asisten` ➔ `Sekda` ➔ `Walikota` (Approval Akhir)
- **Pengajuan oleh Sekda**
  - **Dalam & Luar Daerah:** `Sekda` ➔ `Walikota` (Approval Akhir)

#### 3. Kecamatan & Kelurahan

_(Melibatkan Role: `staff`, `lurah`, `camat`)_

- **Pengajuan oleh Staff Kecamatan/Kelurahan**
  - **Dalam Daerah:** `Staff` ➔ `Lurah` (Khusus staff kelurahan) ➔ `Camat` (Approval Akhir)
  - **Luar Daerah:** `Staff` ➔ `Sekcam` (Jika ada) ➔ `Camat` (Approval Akhir)
- **Pengajuan oleh Lurah**
  - **Dalam & Luar Daerah:** `Lurah` ➔ `Camat` (Approval Akhir)
- **Pengajuan oleh Camat**
  - **Dalam Daerah:** `Camat` ➔ `Asisten` ➔ `Sekda` (Approval Akhir)
  - **Luar Daerah:** `Camat` ➔ `Asisten` ➔ `Sekda` ➔ `Walikota` (Approval Akhir)

#### 4. UPTD / Puskesmas

_(Melibatkan Role: `staff`, `kapus` / Kepala Puskesmas)_

- **Pengajuan oleh Staff Puskesmas**
  - **Dalam Daerah:** `Staff` ➔ `PPTK` ➔ `Kepala Puskesmas` (Approval Akhir)
  - **Luar Daerah:** `Staff` ➔ `Kepala Puskesmas` ➔ `Kepala Dinas Kesehatan` (Approval Akhir)
- **Pengajuan oleh Kepala Puskesmas**
  - **Dalam & Luar Daerah:** `Kepala Puskesmas` ➔ `Kepala Dinas Kesehatan` (Approval Akhir)

#### 5. Sekretariat DPRD (Setwan)

_(Melibatkan Role: `staff_dprd`, `anggota_dprd`, `pimpinan_dprd`, `sekwan`)_

- **Pengajuan oleh Staff DPRD**
  - **Dalam & Luar Daerah:** `Staff DPRD` ➔ `Kabag Setwan` ➔ `Sekwan` (Approval Akhir)
- **Pengajuan oleh Anggota DPRD**
  - **Dalam & Luar Daerah:** `Anggota DPRD` ➔ `Sekwan` (Verifikasi Administrasi & Anggaran) ➔ `Pimpinan DPRD` (Ketua/Wakil) (Approval Akhir)
- **Pengajuan oleh Pimpinan DPRD**
  - **Dalam & Luar Daerah:** `Pimpinan DPRD` ➔ `Sekwan` (Administrasi) ➔ Ditetapkan sendiri/Kolektif.

---

### 💡 Saran Implementasi di Laravel

Berdasarkan struktur database yang Anda miliki (seperti model `SppdApproval` dan fungsi `currentApproval()`), Anda dapat mengimplementasikannya dengan metode **Dynamic Approval Chain**:

1. **Buat Class/Service Khusus** (misal: `SppdWorkflowService.php`) yang bertugas men-_generate_ baris di tabel `sppd_approvals` saat `SppdRequest` baru dibuat.
2. **Logic Berdasarkan Kondisi:**
   - Cek role pemohon (`$user->roles->first()->name`).
   - Cek domain perjalanan (`$sppd->domain === SppdDomain::LUAR_DAERAH`).
3. **Generate `step_order`:** Insert data ke `sppd_approvals` sesuai urutannya.
   - _Contoh jika Staff Luar Daerah:_
     - `step_order: 1` ➔ `approver_id` = ID Kasubag / Atasan Langsung
     - `step_order: 2` ➔ `approver_id` = ID PPTK (Verifikasi Dana)
     - `step_order: 3` ➔ `approver_id` = ID Kepala OPD (Persetujuan & TTD)
4. Saat approver ke-1 melakukan _Approve_, status di `sppd_approvals` berubah jadi `approved`, dan sistem otomatis menunggu `step_order` selanjutnya (seperti fungsi `currentApproval()` Anda yang mengecek status `pending` terendah).
5. Jika step terakhir sudah menyetujui, maka tabel `sppd_requests` kolom `status` berubah menjadi `SppdStatus::APPROVED`.

Pendekatan ini akan membuat aplikasi Anda sangat fleksibel jika sewaktu-waktu ada perubahan peraturan dari instansi daerah setempat tanpa perlu mengubah banyak _logic_ di Controller.
