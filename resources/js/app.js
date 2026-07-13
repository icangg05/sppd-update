// Registrasi service worker PWA (hanya jalan di HTTPS/localhost).
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker.register("/sw.js").catch(() => {});
  });
}

// Deteksi jaringan untuk mode aplikasi (PWA terpasang / standalone).
// Saat koneksi putus, alihkan ke halaman offline penuh dengan tombol refresh.
// (Di tab browser biasa dibiarkan apa adanya; fallback offline tetap via SW.)
(function () {
  const isStandalone =
    window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
  if (!isStandalone) return;

  window.addEventListener("offline", () => {
    if (location.pathname === "/offline.html") return;
    try {
      sessionStorage.setItem("sppd:lastUrl", location.href);
    } catch (e) {}
    location.href = "/offline.html";
  });
})();
