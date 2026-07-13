// Service worker PWA.
// - Navigasi (buka halaman): selalu coba jaringan; bila gagal -> halaman offline penuh.
//   Halaman HTML tidak di-cache agar tidak menampilkan data lama / sesi kedaluwarsa.
// - Aset statis (css/js/img): network-first, fallback ke cache saat offline.
// ponytail: tanpa precache versi aset; cukup offline fallback + cache dasar.
const CACHE = "sppd-v2";
const OFFLINE_URL = "/offline.html";

self.addEventListener("install", (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.add(OFFLINE_URL)));
  self.skipWaiting();
});

self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (e) => {
  const req = e.request;
  if (req.method !== "GET") return;

  // Permintaan halaman: utamakan jaringan; offline -> tampilkan halaman offline.
  if (req.mode === "navigate") {
    e.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  // Aset statis: network-first, simpan salinan, fallback ke cache saat offline.
  e.respondWith(
    fetch(req)
      .then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
        return res;
      })
      .catch(() => caches.match(req))
  );
});
