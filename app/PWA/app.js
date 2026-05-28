// js/app.js — Frontend PWA logic

// ── Service Worker Registration ───────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const reg = await navigator.serviceWorker.register('/sw.js');
      console.log('[SW] Registered:', reg.scope);
      setupPushNotifications(reg);
    } catch (err) {
      console.error('[SW] Registration failed:', err);
    }
  });
}

// ── Online / Offline badge ────────────────────────────────
const badge = document.getElementById('online-badge');

function updateBadge() {
  badge.textContent = navigator.onLine ? 'Online' : 'Offline';
  badge.className   = navigator.onLine ? 'badge online' : 'badge offline';
}
updateBadge();
window.addEventListener('online',  updateBadge);
window.addEventListener('offline', updateBadge);

// ── Install Prompt ────────────────────────────────────────
let deferredInstallPrompt = null;
const installCard = document.getElementById('install-card');
const installBtn  = document.getElementById('install-btn');

window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredInstallPrompt = e;
  installCard.style.display = 'block';
});

installBtn?.addEventListener('click', async () => {
  if (!deferredInstallPrompt) return;
  deferredInstallPrompt.prompt();
  const { outcome } = await deferredInstallPrompt.userChoice;
  console.log('[PWA] Install outcome:', outcome);
  deferredInstallPrompt = null;
  installCard.style.display = 'none';
});

window.addEventListener('appinstalled', () => {
  console.log('[PWA] App installed');
  installCard.style.display = 'none';
});

// ── Offline Queue (Background Sync) ──────────────────────
const noteForm  = document.getElementById('note-form');
const noteInput = document.getElementById('note-input');

noteForm?.addEventListener('submit', async e => {
  if (navigator.onLine) return; // Let normal PHP form submit happen

  e.preventDefault(); // Intercept when offline

  const note = noteInput.value.trim();
  if (!note) return;

  await saveToQueue({ url: '/api/data.php', data: { note } });

  // Register background sync
  if ('serviceWorker' in navigator && 'SyncManager' in window) {
    const reg = await navigator.serviceWorker.ready;
    await reg.sync.register('sync-queue');
    alert('Saved offline! Will sync when you reconnect.');
  } else {
    alert('Saved offline (no background sync support — will retry on reload).');
  }

  noteInput.value = '';
});

// IndexedDB queue helpers
async function saveToQueue(item) {
  const db = await openDB();
  return new Promise((res, rej) => {
    const tx    = db.transaction('queue', 'readwrite');
    const store = tx.objectStore('queue');
    const req   = store.add({ ...item, queued_at: Date.now() });
    req.onsuccess = () => res(req.result);
    req.onerror   = () => rej(req.error);
  });
}

function openDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('pwa-queue', 1);
    req.onupgradeneeded = e =>
      e.target.result.createObjectStore('queue', { keyPath: 'id', autoIncrement: true });
    req.onsuccess = e => resolve(e.target.result);
    req.onerror   = ()  => reject(req.error);
  });
}

// ── API Fetch Demo ────────────────────────────────────────
const fetchBtn   = document.getElementById('fetch-btn');
const apiOutput  = document.getElementById('api-output');

fetchBtn?.addEventListener('click', async () => {
  apiOutput.textContent = 'Loading…';
  try {
    const res  = await fetch('/api/data.php');
    const data = await res.json();
    apiOutput.textContent = JSON.stringify(data, null, 2);
  } catch {
    apiOutput.textContent = '⚠️ Offline — no cached API response available.';
  }
});

// ── Push Notifications ────────────────────────────────────
async function setupPushNotifications(reg) {
  if (!('PushManager' in window)) return;

  const permission = await Notification.requestPermission();
  if (permission !== 'granted') return;

  // Replace with your VAPID public key
  const VAPID_PUBLIC_KEY = 'YOUR_VAPID_PUBLIC_KEY_HERE';

  try {
    const sub = await reg.pushManager.subscribe({
      userVisibleOnly:      true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
    });

    await fetch('/api/subscribe.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(sub),
    });

    console.log('[Push] Subscribed:', sub.endpoint);
  } catch (err) {
    console.warn('[Push] Subscribe failed:', err);
  }
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw     = atob(base64);
  return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}
