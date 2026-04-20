# PHP PWA Demo

A minimal but complete Progressive Web App powered by PHP.

## File Structure

```
pwa-demo/
├── index.php            # Main app page (PHP renders notes server-side)
├── offline.php          # Offline fallback page (served by SW when offline)
├── manifest.json        # PWA manifest (icons, display mode, theme)
├── sw.js                # Service Worker (caching, background sync, push)
├── css/
│   └── app.css          # Styles
├── js/
│   └── app.js           # SW registration, install prompt, offline queue, push
├── api/
│   ├── data.php         # REST endpoint (GET returns data, POST echoes back)
│   └── subscribe.php    # Saves push subscriptions (endpoint + keys)
└── storage/             # Create this folder, writable by PHP
```

## Setup

1. **Serve with PHP** (requires HTTPS for full PWA features):
   ```bash
   php -S localhost:8080
   ```
   For HTTPS locally, use [mkcert](https://github.com/FiloSottile/mkcert) + a reverse proxy like Caddy.

2. **Create the storage folder:**
   ```bash
   mkdir storage && chmod 777 storage
   ```

3. **Add icons** — place 192×192 and 512×512 PNG icons in an `icons/` folder.

4. **Push Notifications (optional):**
   - Generate VAPID keys: `npx web-push generate-vapid-keys`
   - Replace `YOUR_VAPID_PUBLIC_KEY_HERE` in `js/app.js`
   - Add server-side push sending with [minishlink/web-push](https://github.com/web-push-libs/web-push-php):
     ```bash
     composer require minishlink/web-push
     ```

## PWA Features Demonstrated

| Feature | File |
|---|---|
| App manifest (installable) | `manifest.json` |
| Service worker registration | `js/app.js` |
| Pre-caching static assets | `sw.js` → `install` event |
| Network-first fetch strategy | `sw.js` → `fetch` event |
| Offline fallback page | `offline.php` + `sw.js` |
| Install prompt (A2HS) | `js/app.js` |
| Online/offline detection | `js/app.js` |
| Background Sync (offline queue) | `sw.js` + `js/app.js` + IndexedDB |
| Push notifications | `sw.js` + `api/subscribe.php` |
| JSON REST API | `api/data.php` |

## Notes

- The service worker only works over **HTTPS** (or `localhost`).
- The `beforeinstallprompt` install button appears only in Chrome/Edge.
- Background Sync is a Chrome-only API at the moment; other browsers fall back gracefully.
- For production notes, swap `$_SESSION` storage in `index.php` for a real database.
