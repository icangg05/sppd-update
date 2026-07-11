// Service worker minimal: network-first, fallback ke cache saat offline.
// ponytail: no precache/versioning, cukup untuk installability + offline dasar.
const CACHE = "sppd-v1";

self.addEventListener("install", () => self.skipWaiting());
self.addEventListener("activate", (e) => e.waitUntil(self.clients.claim()));

self.addEventListener("fetch", (e) => {
  const req = e.request;
  if (req.method !== "GET") return;
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

self.addEventListener("push", (e) => {
  if (!e.data) return;

  try {
    const data = e.data.json();
    const title = data.title || "Notifikasi Baru";
    const options = {
      body: data.body || "",
      icon: data.icon || "/img/icon-192.png",
      badge: "/img/icon-192.png",
      data: {
        url: data.url || "/"
      }
    };

    e.waitUntil(
      self.registration.showNotification(title, options)
    );
  } catch (err) {
    const text = e.data.text();
    e.waitUntil(
      self.registration.showNotification("Notifikasi Baru", {
        body: text,
        icon: "/img/icon-192.png",
        data: {
          url: "/"
        }
      })
    );
  }
});

self.addEventListener("notificationclick", (e) => {
  e.notification.close();

  const urlToOpen = new URL(e.notification.data.url, self.location.origin).href;

  e.waitUntil(
    clients.matchAll({ type: "window", includeUncontrolled: true }).then((windowClients) => {
      for (let i = 0; i < windowClients.length; i++) {
        const client = windowClients[i];
        if (client.url === urlToOpen && "focus" in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});
