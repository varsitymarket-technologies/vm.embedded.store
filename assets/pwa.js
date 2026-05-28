// pwa.js — PWA client-side logic for Varsity Market

// ── Service Worker Registration ───────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
      console.log('[SW] Registered:', reg.scope);
    } catch (err) {
      console.error('[SW] Registration failed:', err);
    }
  });
}

// ── Online / Offline indicator ────────────────────────────
function updateOnlineStatus() {
  const badge = document.getElementById('vm-online-badge');
  if (!badge) return;
  if (navigator.onLine) {
    badge.textContent = 'Online';
    badge.className = 'vm-badge vm-online';
  } else {
    badge.textContent = 'Offline';
    badge.className = 'vm-badge vm-offline';
  }
}
document.addEventListener('DOMContentLoaded', updateOnlineStatus);
window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);

// ── Install Prompt (Add to Home Screen) ───────────────────
let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredInstallPrompt = e;
  const installCard = document.getElementById('vm-install-card');
  if (installCard) installCard.style.display = 'block';
});

document.addEventListener('click', async e => {
  if (e.target.id !== 'vm-install-btn') return;
  if (!deferredInstallPrompt) return;
  deferredInstallPrompt.prompt();
  const { outcome } = await deferredInstallPrompt.userChoice;
  console.log('[PWA] Install outcome:', outcome);
  deferredInstallPrompt = null;
  const installCard = document.getElementById('vm-install-card');
  if (installCard) installCard.style.display = 'none';
});

window.addEventListener('appinstalled', () => {
  console.log('[PWA] App installed');
  const installCard = document.getElementById('vm-install-card');
  if (installCard) installCard.style.display = 'none';
});

// ── Offline Queue (Background Sync) ──────────────────────
async function saveToOfflineQueue(url, data) {
  const db = await openPWADB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction('queue', 'readwrite');
    const store = tx.objectStore('queue');
    const req = store.add({ url, data, queued_at: Date.now() });
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

function openPWADB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('vm-pwa-queue', 1);
    req.onupgradeneeded = e =>
      e.target.result.createObjectStore('queue', { keyPath: 'id', autoIncrement: true });
    req.onsuccess = e => resolve(e.target.result);
    req.onerror = () => reject(req.error);
  });
}
