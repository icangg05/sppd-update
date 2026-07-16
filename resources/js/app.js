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

  // Buka link target="_blank" (dokumen) tanpa tab baru.
  // - Mobile: navigasi di tab yang sama, jadi tombol "kembali" balik ke halaman
  //   SPPD dan tidak menutup aplikasi (Custom Tab / tab baru menutup app saat back).
  // - Desktop: tetap buka di browser eksternal.
  // Ctrl/Cmd/middle-click dibiarkan agar "buka di tab baru" manual tetap jalan.
  const isMobile = /Android|iPhone|iPad|iPod|Mobi/i.test(navigator.userAgent);
  document.addEventListener(
    "click",
    (e) => {
      if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      const a = e.target.closest && e.target.closest('a[target="_blank"]');
      if (!a || !a.href) return;
      e.preventDefault();
      if (isMobile) {
        location.href = a.href;
      } else {
        window.open(a.href, "_blank", "noopener");
      }
    },
    true
  );
})();
