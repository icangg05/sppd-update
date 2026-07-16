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

  // Fallback offline hanya relevan di PWA terpasang (standalone).
  if (isStandalone) {
    window.addEventListener("offline", () => {
      if (location.pathname === "/offline.html") return;
      try {
        sessionStorage.setItem("sppd:lastUrl", location.href);
      } catch (e) {}
      location.href = "/offline.html";
    });
  }

  // Paksa link target="_blank" (dokumen) selalu buka di browser eksternal — di
  // semua mode termasuk desktop. Di PWA standalone ini mencegah Custom Tab yang
  // membuat tombol "kembali" me-reset aplikasi. Ctrl/Cmd/middle-click dibiarkan
  // agar "buka di tab baru" manual tetap jalan.
  // ponytail: window.open bergantung platform (Android Chrome buka browser
  //           sistem); kalau ada device yang tetap in-app, upgrade ke intent://.
  document.addEventListener(
    "click",
    (e) => {
      if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      const a = e.target.closest && e.target.closest('a[target="_blank"]');
      if (!a || !a.href) return;
      e.preventDefault();
      window.open(a.href, "_blank", "noopener");
    },
    true
  );
})();
