// sw.js — Service Worker for PHP PWA Demo
const CACHE_NAME = 'php-pwa-v1';
const OFFLINE_URL = '/offline.php';

// Assets to pre-cache on install
const PRE_CACHE = [
  '/',
  '/index.php',
  '/offline.php',
  '/manifest.json',
  '/css/app.css',
  '/js/app.js',
];

// ── Install ──────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(PRE_CACHE))
  );
  self.skipWaiting(); // Activate immediately
});

// ── Activate ─────────────────────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(key => key !== CACHE_NAME)
          .map(key => caches.delete(key))
      )
    )
  );
  self.clients.claim(); // Take control of all open tabs
});

// ── Fetch (Network-first with offline fallback) ───────────
self.addEventListener('fetch', event => {
  const { request } = event;

  // Only handle GET requests
  if (request.method !== 'GET') return;

  // API calls: network-only (don't cache)
  if (request.url.includes('/api/')) {
    event.respondWith(
      fetch(request).catch(() =>
        new Response(JSON.stringify({ error: 'Offline', offline: true }), {
          headers: { 'Content-Type': 'application/json' },
        })
      )
    );
    return;
  }

  // Everything else: network-first, fall back to cache, then offline page
  event.respondWith(
    fetch(request)
      .then(response => {
        // Clone and store fresh response in cache
        const clone = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
        return response;
      })
      .catch(async () => {
        const cached = await caches.match(request);
        return cached || caches.match(OFFLINE_URL);
      })
  );
});

// ── Background Sync (queue offline form submissions) ─────
self.addEventListener('sync', event => {
  if (event.tag === 'sync-queue') {
    event.waitUntil(flushQueue());
  }
});

async function flushQueue() {
  const db = await openDB();
  const tx = db.transaction('queue', 'readwrite');
  const store = tx.objectStore('queue');
  const items = await store.getAll();

  for (const item of items) {
    try {
      await fetch(item.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item.data),
      });
      await store.delete(item.id);
    } catch {
      // Will retry on next sync
    }
  }
}

// ── Push Notifications ────────────────────────────────────
self.addEventListener('push', event => {
  const data = event.data?.json() ?? { title: 'New update', body: '' };
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: '/icons/icon-192.png',
      badge: '/icons/badge-72.png',
      data: { url: data.url || '/' },
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data.url));
});

// ── Tiny IndexedDB helper ─────────────────────────────────
function openDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('pwa-queue', 1);
    req.onupgradeneeded = e => e.target.result.createObjectStore('queue', { keyPath: 'id', autoIncrement: true });
    req.onsuccess = e => resolve(e.target.result);
    req.onerror = () => reject(req.error);
  });
}
