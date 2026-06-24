/* =====================================================================
   Ev Muhasebe · Service Worker (PWA / çevrimdışı destek)
   ---------------------------------------------------------------------
   Strateji:
   - Statik varlıklar (CSS/JS/font/görsel, CDN dahil): önce önbellek,
     arkada güncelle (stale-while-revalidate).
   - Sayfa gezinmeleri: önce ağ; başarılıysa kopyasını önbelleğe al,
     çevrimdışıysa son önbelleği ya da offline.html'i göster.
   - POST istekleri ve /actions/ uçları asla önbelleğe alınmaz.
   - /auth/ sayfaları (giriş/çıkış) önbelleğe alınmaz.
   Sürüm yükseltmek için CACHE adını değiştirin.
   ===================================================================== */
const CACHE = 'evmuhasebe-v1';
const OFFLINE_URL = 'offline.html'; // SW kapsamına göreli

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE)
            .then((c) => c.add(new Request(OFFLINE_URL, { cache: 'reload' })))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

function staleWhileRevalidate(req) {
    return caches.open(CACHE).then((c) =>
        c.match(req).then((hit) => {
            const net = fetch(req).then((res) => {
                if (res && res.status === 200 && (res.type === 'basic' || res.type === 'cors')) {
                    c.put(req, res.clone());
                }
                return res;
            }).catch(() => hit);
            return hit || net;
        })
    );
}

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return; // POST/PUT vb. dokunma

    let url;
    try { url = new URL(req.url); } catch (_) { return; }

    // Dinamik / hassas uçlar: tamamen ağdan
    if (url.pathname.indexOf('/actions/') !== -1 ||
        url.pathname.indexOf('/auth/') !== -1 ||
        url.searchParams.has('ajax')) {
        return; // tarayıcı varsayılan davranışı (ağ)
    }

    // Farklı köken (CDN: fontlar, chart.js, pdf.js): swr
    if (url.origin !== self.location.origin) {
        e.respondWith(staleWhileRevalidate(req));
        return;
    }

    // Sayfa gezinmeleri: ağ öncelikli, çevrimdışı yedek
    if (req.mode === 'navigate') {
        e.respondWith(
            fetch(req)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
                    return res;
                })
                .catch(() =>
                    caches.match(req).then((r) => r || caches.match(OFFLINE_URL))
                )
        );
        return;
    }

    // Statik varlıklar
    e.respondWith(staleWhileRevalidate(req));
});

/* ---------------------------------------------------------------
   Web Push: bildirim alımı ve tıklama
   --------------------------------------------------------------- */
self.addEventListener('push', (e) => {
    let data = { title: 'Ev Muhasebe', body: '', url: '' };
    try {
        if (e.data) {
            const j = e.data.json();
            data.title = j.title || data.title;
            data.body  = j.body || '';
            data.url   = j.url || '';
        }
    } catch (_) {
        if (e.data) data.body = e.data.text();
    }
    e.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: 'assets/icons/icon-192.png',
            badge: 'assets/icons/icon-192.png',
            data: { url: data.url },
            vibrate: [80, 40, 80],
        })
    );
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const target = (e.notification.data && e.notification.data.url) || '';
    e.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const c of list) {
                if ('focus' in c) {
                    if (target) { try { c.navigate(target); } catch (_) {} }
                    return c.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(target || './');
            }
        })
    );
});

/* Sayfadan gelen "hemen güncelle" mesajı */
self.addEventListener('message', (e) => {
    if (e.data === 'skipWaiting') self.skipWaiting();
});
