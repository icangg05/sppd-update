// Alpine data `imageCrop`: memotong (crop) gambar di browser sebelum diunggah ke properti
// Livewire (dipakai komponen <x-form.image-crop>). Cropper.js baru dimuat saat pertama kali
// dibutuhkan agar halaman lain tidak ikut menanggung ukurannya.
let cropperLib;
const loadCropper = () =>
  (cropperLib ??= Promise.all([import("cropperjs"), import("cropperjs/dist/cropper.css")]).then(
    ([module]) => module.default
  ));

// Format yang dipertahankan apa adanya; selain itu (GIF/BMP) hasil crop disimpan sebagai PNG.
const EXTENSIONS = { "image/png": "png", "image/jpeg": "jpg", "image/webp": "webp" };

// Sudut dalam rentang -180..180, dibulatkan ke 0,1° agar langkah desimal tidak menumpuk galat pembulatan.
const normalizeAngle = (degree) => Math.round(((((degree + 180) % 360) + 360) % 360 - 180) * 10) / 10;

const formatMb = (bytes) => `${(bytes / 1048576).toLocaleString("id-ID", { maximumFractionDigits: 1 })} MB`;

// Batas luas kanvas gambar asal yang digambar Cropper (iOS Safari menolak kanvas > 16,7 MP).
// Foto HP biasa (12 MP) tetap dipakai dengan resolusi penuh.
const MAX_SOURCE_PIXELS = 16_777_216;

export default ({ model, maxDimension = 2480, maxKb = null } = {}) => ({
  open: false, // modal crop terbuka
  loading: false, // Cropper / gambar sedang disiapkan
  uploading: false,
  progress: 0,
  error: "",
  ratio: null, // null = bebas
  size: "", // ukuran area crop, mis. "2480 × 496 px"
  angle: 0, // sudut putar total saat ini (derajat, -180..180) = base + fine
  base: 0, // putaran kelipatan 90° (tombol putar 90°)
  fine: 0, // kemiringan halus -45..45° (tombol −/+ & slider)
  step: 0.1, // langkah putar presisi (derajat): 0,1 / 0,2 / 0,5 / 1 / 1,5
  candidate: null, // { file, url, state? } yang sedang di-crop
  // { file, url, state } asal hasil crop yang sudah terunggah. state = posisi crop, zoom, rasio,
  // & sudut saat Terapkan, dipulihkan oleh Crop Ulang.
  source: null,
  preview: null, // object URL hasil crop yang sudah terunggah
  cropper: null,
  imageEl: null,

  init() {
    // Setelah disimpan (server mengosongkan properti), pratinjau & pilihan ikut dibersihkan.
    this.$watch(`$wire.${model}`, (value) => {
      if (!value && !this.uploading) this.clear();
    });
    // Ditutup lewat Esc / klik di luar = batal.
    this.$watch("open", (open) => open || this.close());
  },

  bindImage(el) {
    this.imageEl = el;
  },

  pick(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    this.error = "";
    if (!file.type.startsWith("image/")) {
      this.error = "Berkas harus berupa gambar (PNG, JPG, atau WEBP).";
      this.restoreInput();
      return;
    }

    this.candidate = { file, url: URL.createObjectURL(file) };
    this.edit();
  },

  // Buka ulang gambar asli dengan posisi crop, zoom, rasio, & sudut terakhir (bukan dari awal).
  recrop() {
    if (!this.source) return;
    this.candidate = this.source;
    if (this.source.state) this.ratio = this.source.state.ratio;
    this.edit();
  },

  async edit() {
    this.open = true;
    this.loading = true;
    this.size = "";
    this.angle = this.base = this.fine = 0;

    try {
      const Cropper = await loadCropper();
      // Tunggu modal tampil agar ukuran wadah Cropper sudah terhitung.
      await this.$nextTick();
      await new Promise(requestAnimationFrame);
      if (!this.open || !this.candidate) return;

      this.destroyCropper();
      this.imageEl.src = this.candidate.url;
      await this.imageEl.decode();
      if (!this.open) return;

      const state = this.candidate.state;
      this.cropper = new Cropper(this.imageEl, {
        viewMode: 1,
        autoCropArea: 1,
        aspectRatio: this.ratio ?? NaN,
        checkCrossOrigin: false,
        toggleDragModeOnDblclick: false,
        ready: () => {
          if (state) this.restore(state);
          this.loading = false;
        },
        crop: ({ detail }) => {
          this.size = `${Math.round(detail.width)} × ${Math.round(detail.height)} px`;
          this.angle = normalizeAngle(detail.rotate ?? 0);
        },
      });
    } catch {
      this.open = false;
      this.error = "Gambar tidak dapat dibuka. Gunakan berkas PNG, JPG, atau WEBP.";
    }
  },

  setRatio(ratio) {
    this.ratio = ratio;
    this.cropper?.setAspectRatio(ratio ?? NaN);
  },

  zoom(step) {
    this.cropper?.zoom(step);
  },

  // Kondisi saat Terapkan: area crop (koordinat gambar asli, termasuk sudut) + posisi/zoom gambar.
  snapshot() {
    return { data: this.cropper.getData(), canvas: this.cropper.getCanvasData(), ratio: this.ratio };
  },

  // Urutan penting: putar dulu (mengubah ukuran kanvas), lalu zoom/posisi, lalu area crop persis
  // dalam koordinat gambar asli — tetap tepat walau ukuran jendela berubah sejak Terapkan.
  restore({ data, canvas }) {
    const rotate = normalizeAngle(data.rotate ?? 0);
    this.base = normalizeAngle(Math.round(rotate / 90) * 90);
    this.fine = normalizeAngle(rotate - this.base);
    this.cropper.rotateTo(rotate);
    this.cropper.setCanvasData(canvas);
    this.cropper.setData(data);
  },

  applyRotation() {
    this.cropper?.rotateTo(normalizeAngle(this.base + this.fine));
  },

  // Putar 90°: kemiringan halus dipertahankan.
  rotate(degree) {
    this.base = normalizeAngle(this.base + degree);
    this.applyRotation();
  },

  // Putar presisi sebesar langkah terpilih; arah -1 = kiri, 1 = kanan.
  rotateStep(direction) {
    this.setFine(this.fine + direction * this.step);
  },

  // Dari slider (bergeser langsung saat ditarik), kolom angka, maupun tombol −/+. Presisi 0,1°.
  setFine(value) {
    const degree = Number(String(value).replace(",", "."));
    this.fine = Number.isFinite(degree) ? Math.min(45, Math.max(-45, Math.round(degree * 10) / 10)) : this.fine;
    this.applyRotation();
  },

  straighten() {
    this.setFine(0);
  },

  angleLabel() {
    return `${this.angle.toLocaleString("id-ID", { maximumFractionDigits: 1 })}°`;
  },

  // Kembali ke kondisi awal (termasuk sudut 0°).
  resetCrop() {
    this.base = this.fine = 0;
    this.cropper?.reset();
  },

  // Ukuran kanvas hasil crop. Jangan memakai opsi maxWidth/maxHeight Cropper untuk membatasi hasil:
  // opsi itu lebih dulu mengecilkan SELURUH gambar asal, lalu area crop diperbesar lagi selebar
  // gambar asal, sehingga kop yang di-crop dari foto/pindaian satu halaman jadi buram. Di sini area
  // crop disalin 1:1 dengan piksel aslinya, dan hanya dikecilkan bila sisi terpanjangnya melewati
  // maxDimension (atau gambar asal terpaksa diperkecil karena batas kanvas).
  croppedCanvasOptions() {
    const { width, height } = this.cropper.getData(true);
    const { naturalWidth, naturalHeight } = this.cropper.getCanvasData();
    const sourceScale = Math.min(1, Math.sqrt(MAX_SOURCE_PIXELS / (naturalWidth * naturalHeight)));
    const scale = Math.min(1, maxDimension / Math.max(width, height), sourceScale);

    return {
      rounded: true,
      width: Math.max(1, Math.round(width * scale)),
      height: Math.max(1, Math.round(height * scale)),
      ...(sourceScale < 1 && {
        maxWidth: Math.floor(naturalWidth * sourceScale),
        maxHeight: Math.floor(naturalHeight * sourceScale),
      }),
    };
  },

  async apply() {
    if (!this.cropper || this.loading) return;

    const original = this.candidate.file;
    const type = EXTENSIONS[original.type] ? original.type : "image/png";
    const canvas = this.cropper.getCroppedCanvas({
      ...this.croppedCanvasOptions(),
      imageSmoothingEnabled: true,
      imageSmoothingQuality: "high",
      // JPEG tidak mengenal transparansi: area kosong (mis. setelah diputar) jadi putih, bukan hitam.
      fillColor: type === "image/jpeg" ? "#fff" : "transparent",
    });
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, type, 0.92));

    if (!blob) {
      this.error = "Gagal memproses hasil crop. Coba lagi.";
      return;
    }
    if (maxKb && blob.size > maxKb * 1024) {
      this.error = `Hasil crop ${formatMb(blob.size)} melebihi batas ${formatMb(maxKb * 1024)}. Perkecil area crop atau gunakan JPG.`;
      return;
    }

    // Safari belum bisa menulis WEBP lewat canvas: ekstensi mengikuti format yang benar-benar dihasilkan.
    const actualType = EXTENSIONS[blob.type] ? blob.type : type;
    const base = original.name.replace(/\.[^.]+$/, "") || "gambar";
    const file = new File([blob], `${base}.${EXTENSIONS[actualType]}`, { type: actualType });

    // Salinan baru: bila unggahan gagal, kondisi crop milik hasil sebelumnya tidak ikut tertimpa.
    const cropped = { ...this.candidate, state: this.snapshot() };
    this.candidate = null; // tandai bukan batal sebelum modal ditutup
    this.open = false;
    this.upload(file, cropped);
  },

  cancel() {
    this.open = false;
  },

  // Dipanggil setiap modal tertutup (Terapkan, Batal, Esc, klik di luar).
  close() {
    this.destroyCropper();
    if (this.candidate) {
      if (this.candidate.url !== this.source?.url) URL.revokeObjectURL(this.candidate.url);
      this.candidate = null;
      this.restoreInput();
    }
  },

  upload(file, cropped) {
    this.uploading = true;
    this.progress = 0;
    this.error = "";

    // Event yang sama dengan unggahan wire:model, agar wire:loading/wire:target="<properti>" ikut aktif
    // (mis. tombol Simpan dinonaktifkan selama unggah).
    const detail = { id: this.$wire.$id, property: model };
    const emit = (name) =>
      this.$root.dispatchEvent(new CustomEvent(`livewire-upload-${name}`, { bubbles: true, detail }));
    const url = URL.createObjectURL(file);

    emit("start");
    this.$wire.upload(
      model,
      file,
      () => {
        this.uploading = false;
        if (this.preview) URL.revokeObjectURL(this.preview);
        if (this.source && this.source.url !== cropped.url) URL.revokeObjectURL(this.source.url);
        this.preview = url;
        this.source = cropped;
        emit("finish");
      },
      () => {
        this.uploading = false;
        URL.revokeObjectURL(url);
        if (cropped.url !== this.source?.url) URL.revokeObjectURL(cropped.url);
        this.error = "Gagal mengunggah gambar. Periksa koneksi lalu coba lagi.";
        this.restoreInput();
        emit("error");
      },
      (event) => (this.progress = event.detail.progress)
    );
  },

  // Batalkan hasil crop yang belum disimpan.
  remove() {
    this.$wire.$set(model, null);
    this.clear();
  },

  clear() {
    if (this.preview) URL.revokeObjectURL(this.preview);
    if (this.source) URL.revokeObjectURL(this.source.url);
    this.preview = null;
    this.source = null;
    this.error = "";
    this.restoreInput();
  },

  // Input berkas menampilkan berkas asal hasil crop yang berlaku (atau kosong), bukan pilihan yang dibatalkan.
  restoreInput() {
    const input = this.$refs.input;
    if (!input) return;
    try {
      const transfer = new DataTransfer();
      if (this.source) transfer.items.add(this.source.file);
      input.files = transfer.files;
    } catch {
      input.value = "";
    }
  },

  destroyCropper() {
    this.cropper?.destroy();
    this.cropper = null;
  },
});
