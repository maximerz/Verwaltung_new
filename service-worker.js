const CACHE_NAME = 'projekt1-erp-v1';
const APP_SHELL = [
  '/',
  '/web_oberflaeche.php',
  '/offline.html',
  '/manifest.webmanifest',
  '/assets/css/style.css',
  '/assets/css/global.css',
  '/assets/images/logo.webp'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => Promise.allSettled(
      APP_SHELL.map((url) => fetch(url).then((response) => {
        if (response.ok) {
          return cache.put(url, response);
        }
        return undefined;
      }))
    )).catch(() => undefined)
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response && response.status === 200 && request.url.startsWith(self.location.origin)) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        }
        return response;
      })
      .catch(async () => {
        const cached = await caches.match(request);
        if (cached) {
          return cached;
        }

        if (request.mode === 'navigate') {
          return caches.match('/offline.html');
        }

        return new Response('', { status: 503, statusText: 'Offline' });
      })
  );
});
