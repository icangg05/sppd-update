## Plan: Implementasi TTE ke SPPD-Update (Laravel)

**TL;DR:** Implementasikan Tanda Tangan Elektronik dengan menggabungkan API provider e-sign lokal, generate PDF menggunakan dompdf, queue job untuk async signing, dan service layer yang clean untuk fleksibilitas provider.

---

### **Fase 1: Setup Infra & Database** (1-2 jam)

**Extend migrations** untuk `sppd_digital_signatures` tambah fields:
- `document_type`, `provider_id`, `error_message`, `signed_file_path`, `qr_code`

**Extend `document_signatories`** tambah fields:
- `nik` (untuk mapping e-sign), ensure `signature_image_path`

**Create migration** untuk `signature_settings` (centralize config provider)

**Setup .env** dan `config/tte.php` untuk:
- `E_SIGN_API_ENDPOINT`, `E_SIGN_AUTH_BASIC`, `QUEUE_CONNECTION=database`

**Setup storage dirs** di public:
- `doc_dummy/` (draft PDF), `doc_tte/` (signed result), `tanda_tangan/` (gambar), `qr_codes/`

---

### **Fase 2: Models & Enums** (1 jam)

**Create enums**: `SignatureStatus` (pending/signed/rejected/error), `SignatureDocumentType` (sppd/spt/kuitansi), `SignatureProvider`

**Extend models**:
- `SppdDigitalSignature`: Add fields + methods `requestSign()`, `markSigned()`, `markError()`
- `DocumentSignatory`: Add `getSignatureImage()`, `getJobTitle()` methods
- `SppdRequest`: Add `getPendingSignatures()`, `getSignedDocument($type)`
- **Create** `SignatureSettings` model untuk manage provider config

---

### **Fase 3: Services Layer** (2-3 jam)

**SignatureService** (abstraksi/interface):
- `requestSign(file_path, nik, passphrase, page, coords)` → return `id_dokumen`
- `verifyAndDownload(id_dokumen, output_path)` → download PDF signed
- `getSignerStatus(nik)`

**LocalProxySignService** (implementasi ke API lokal `http://103.85.5.99`):
- Multipart POST untuk send PDF + params
- GET untuk download signed
- Error handling & retry

**PdfGeneratorService**:
- Generate SPPD/SPT/Kuitansi PDF (pakai dompdf)
- Embed QR code dengan koordinat sesuai template
- Header/footer dengan logo kop surat

---

### **Fase 4: Database Seeding** (1 jam)

**Seeders**:
- `SignatureSettingsSeeder`: Insert default provider config
- `TandaTanganSeeder`: Copy/import gambar dari folder lama (optional)

---

### **Fase 5: Queue Jobs** (1-2 jam)

**SendSignRequestJob**: 
- Generate PDF → call `SignatureService::requestSign()` → update DB dengan `provider_id`

**DownloadSignedDocumentJob**:
- Call `SignatureService::verifyAndDownload()` → save file → update `status = signed` & `signed_file_path`

**Event Listeners** (optional): Auto-trigger polling atau move to next workflow step

---

### **Fase 6: Controllers & Routes** (2-3 jam)

**SppdDigitalSignatureController**:
- `POST /sppd/{sppd}/sign/{docType}` → initiate (dispatch job)
- `GET /sppd/{sppd}/sign/status/{sig}` → polling status
- `POST /sppd/{sppd}/sign/verify/{sig}` → trigger download

**DocumentSignatoryController**:
- CRUD signers + upload gambar signature ke storage

**Update SppdController**:
- Extend `approve()` untuk block workflow step sampai signature done
- Add `resetTte()` untuk clear attempts

---

### **Fase 7: Frontend** (2 jam)

**Blade templates**:
- Modal TTE dengan form input NIK, passphrase, document type
- List signers dengan upload button gambar

**JavaScript**:
- Form submit → POST `/sppd/{sppd}/sign/{type}`
- Polling `/sppd/{sppd}/sign/status/{sig}` setiap 3 detik
- Display progress & error messages

---

### **Fase 8: Testing** (2 jam)

- Unit tests untuk Services + Models
- Integration test: sign flow end-to-end
- Manual testing: upload → generate → sign → download → verify

---

### **Relevant Files to Create/Modify**

**Create (Baru)**:
- Migrations: `signature_settings` table
- Enums: `SignatureStatus`, `SignatureDocumentType`, `SignatureProvider`
- Models: `SignatureSettings`
- Services: `SignatureService`, `LocalProxySignService`, `PdfGeneratorService`
- Jobs: `SendSignRequestJob`, `DownloadSignedDocumentJob`
- Controllers: `SppdDigitalSignatureController`, `DocumentSignatoryController`
- Config: `config/tte.php`
- Views: Modal TTE, Master signatory list
- Seeders: `SignatureSettingsSeeder`

**Modify (Existing)**:
- Migrations: `sppd_digital_signatures`, `document_signatories`
- Models: `SppdDigitalSignature`, `DocumentSignatory`, `SppdRequest`
- Controller: `SppdController`, routes
- .env dan .env.example

---

### **Verification Checklist**

✅ SPPD generate PDF sesuai format template  
✅ PDF sent to API dengan koordinat & param benar  
✅ Response parsed → `provider_id` disimpan  
✅ Signed PDF downloaded → saved ke `doc_tte/`  
✅ Database updated: `status=signed`, `signed_at`, `signed_file_path`  
✅ Workflow lanjut ke step berikutnya  
✅ Gambar tanda tangan visible di UI Master  
✅ Error handling + retry logic + user notification  

---

### **Further Considerations**

1. **Environment**: Backup .env sebelum jalankan migrate. Confirm API endpoint `http://103.85.5.99` accessible.
2. **Storage Migration**: Ada gambar tanda tangan dari `sppd-2026/upload/tanda_tangan/` - perlu copy ke Laravel storage atau reference path lama.
3. **Provider URL**: Jika endpoint tidak tersedia, perlu setup proxy atau endpoint baru.
