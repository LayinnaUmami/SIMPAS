/*
 * SIMPAS Service Worker
 * - Cache-first untuk aset statis (build, ikon, favicon, manifest)
 * - Network-first untuk navigasi (fallback halaman offline sederhana)
 */

const CACHE_NAME = 'simpas-v1';

// Prefiks path yang di-cache secara cache-first.
const ASSET_PREFIXES = [
    '/build/',
    '/icons/',
    '/favicon.svg',
    '/favicon.ico',
    '/manifest.webmanifest'
];

const OFFLINE_HTML = [
    '<!doctype html>',
    '<html lang="id">',
    '<head><meta charset="utf-8">',
    '<meta name="viewport" content="width=device-width, initial-scale=1">',
    '<title>SIMPAS - Offline</title>',
    '<style>',
    'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;',
    'background:#f8fafc;color:#0f172a;font-family:Segoe UI,Roboto,Arial,sans-serif;text-align:center}',
    '.box{padding:32px;max-width:420px}',
    '.logo{width:72px;height:72px;border-radius:18px;background:linear-gradient(#059669,#047857);',
    'color:#fff;font-size:44px;font-weight:700;line-height:72px;margin:0 auto 16px}',
    'h1{font-size:20px;margin:0 0 8px}',
    'p{font-size:15px;line-height:1.6;color:#475569;margin:0}',
    '</style></head>',
    '<body><div class="box">',
    '<div class="logo">S</div>',
    '<h1>SIMPAS</h1>',
    '<p>Anda offline. Sambungkan internet untuk membuka SIMPAS.</p>',
    '</div></body></html>'
].join('');

self.addEventListener('install', (event) => {
    // Langsung mengambil alih kontrol tanpa menunggu tab lama tertutup.
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(ASSET_PREFIXES.map((url) => new Request(url, { cache: 'reload' }))))
            .catch(() => undefined) // Prefetch bersifat opsional, kegagalan tidak menggagalkan install.
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        // Buang cache lama milik versi sebelumnya.
        const keys = await caches.keys();
        await Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        );
        await self.clients.claim();
    })());
});

function isAssetRequest(url) {
    return ASSET_PREFIXES.some((prefix) => url.pathname === prefix || url.pathname.startsWith(prefix));
}

// Cache-first untuk aset statis.
async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request, { ignoreSearch: false });
    if (cached) {
        return cached;
    }
    try {
        const response = await fetch(request);
        if (response && response.ok && response.type === 'basic') {
            cache.put(request, response.clone());
        }
        return response;
    } catch (err) {
        const fallback = await caches.match(request);
        if (fallback) {
            return fallback;
        }
        throw err;
    }
}

// Network-first untuk navigasi halaman, fallback ke shell offline.
async function networkFirstNavigation(request) {
    try {
        const response = await fetch(request);
        if (response && response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (err) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return new Response(OFFLINE_HTML, {
            status: 200,
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
        });
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Hanya tangani same-origin; biarkan permintaan lain (mis. font eksternal) berjalan normal.
    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (isAssetRequest(url)) {
        event.respondWith(cacheFirst(request));
    }
});
