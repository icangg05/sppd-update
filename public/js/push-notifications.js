/**
 * PWA Push Notifications integration script.
 * Handles Web Push registration, subscription status, and UI toggle updates.
 */

(function () {
    const VAPID_META_SELECTOR = 'meta[name="vapid-public-key"]';
    const CSRF_META_SELECTOR = 'meta[name="csrf-token"]';

    // Helper to convert VAPID public key
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Main initialization function
    async function initPushNotification() {
        const btn = document.getElementById('push-notification-btn');
        const icon = document.getElementById('push-notification-icon');

        if (!btn || !icon) return;

        // Check compatibility
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.log('Push notifications are not supported on this browser.');
            btn.classList.add('hidden');
            return;
        }

        // Show button since push is supported
        btn.classList.remove('hidden');

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            updateUIState(subscription);

            // Setup click handler using standard onclick property to prevent race conditions and duplicate listeners
            btn.onclick = async () => {
                const currentSubscription = await registration.pushManager.getSubscription();
                if (currentSubscription) {
                    await unsubscribeUser(currentSubscription);
                } else {
                    await subscribeUser(registration);
                }
            };

        } catch (err) {
            console.error('Error during push notification initialization:', err);
        }
    }

    // Subscribe User
    async function subscribeUser(registration) {
        const vapidMeta = document.querySelector(VAPID_META_SELECTOR);
        if (!vapidMeta || !vapidMeta.content) {
            console.error('VAPID public key is missing in layout.');
            return;
        }

        try {
            const applicationServerKey = urlBase64ToUint8Array(vapidMeta.content);
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            // Send subscription to server
            const response = await fetch('/push-subscription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(subscription)
            });

            if (response.ok) {
                updateUIState(subscription);
                showToast('Notifikasi Web Berhasil Diaktifkan', 'Berhasil', 'success');
            } else {
                console.error('Failed to store subscription on backend.');
                // Rollback subscription in browser
                await subscription.unsubscribe();
                showToast('Gagal mengaktifkan notifikasi web.', 'Error', 'error');
            }
        } catch (err) {
            if (Notification.permission === 'denied') {
                showToast('Izin notifikasi diblokir oleh browser. Harap aktifkan lewat pengaturan situs.', 'Akses Ditolak', 'warning');
            } else {
                console.error('Subscription error:', err);
                showToast('Gagal melakukan registrasi notifikasi.', 'Error', 'error');
            }
            updateUIState(null);
        }
    }

    // Unsubscribe User
    async function unsubscribeUser(subscription) {
        try {
            // Delete subscription from server
            const response = await fetch('/push-subscription', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint
                })
            });

            if (response.ok) {
                await subscription.unsubscribe();
                updateUIState(null);
                showToast('Notifikasi Web Berhasil Dinonaktifkan', 'Berhasil', 'success');
            } else {
                console.error('Failed to delete subscription on backend.');
                showToast('Gagal menonaktifkan notifikasi di server.', 'Error', 'error');
            }
        } catch (err) {
            console.error('Unsubscription error:', err);
            showToast('Gagal menonaktifkan notifikasi.', 'Error', 'error');
        }
    }

    // Update UI Status
    function updateUIState(subscription) {
        const btn = document.getElementById('push-notification-btn');
        const icon = document.getElementById('push-notification-icon');
        const badge = document.getElementById('push-notification-badge');

        if (!btn || !icon) return;

        if (Notification.permission === 'denied') {
            icon.className = 'fa-solid fa-bell-slash text-slate-400';
            btn.title = 'Akses notifikasi diblokir browser';
            if (badge) badge.classList.add('hidden');
            return;
        }

        if (subscription) {
            icon.className = 'fa-solid fa-bell text-amber-400';
            btn.title = 'Notifikasi Web Aktif (Klik untuk matikan)';
            if (badge) badge.classList.remove('hidden');
        } else {
            icon.className = 'fa-regular fa-bell text-primary-200';
            btn.title = 'Aktifkan Notifikasi Web';
            if (badge) badge.classList.add('hidden');
        }
    }

    // Helper to get CSRF token
    function getCsrfToken() {
        const csrfMeta = document.querySelector(CSRF_META_SELECTOR);
        return csrfMeta ? csrfMeta.content : '';
    }

    // Helper to show native Toast if Alpine / Toast available, fallback to console/alert
    function showToast(message, title = 'Info', type = 'info') {
        // Look for Livewire/Alpine toast event
        if (window.dispatchEvent) {
            window.dispatchEvent(new CustomEvent('toast-message', {
                detail: {
                    message: message,
                    title: title,
                    type: type
                }
            }));
        }
    }

    // Run initialization on load
    document.addEventListener('DOMContentLoaded', initPushNotification);

    // Re-initialize when Livewire finishes navigations (wire:navigate)
    document.addEventListener('livewire:navigated', initPushNotification);

    // Export globally
    window.PushNotification = {
        init: initPushNotification,
        subscribe: async () => {
            const reg = await navigator.serviceWorker.ready;
            await subscribeUser(reg);
        },
        unsubscribe: async () => {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) await unsubscribeUser(sub);
        }
    };
})();
