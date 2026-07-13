# Changelog

## [2026-07-13] — Performance & UX overhaul

### ⚡ Performance (massive speedup on Coolify)
- **Database**: added 3-second connect timeout + 10s read/write timeout to MySQL
  PDO connection. Previously, when the remote MySQL was slow, every page waited
  30–60 seconds for the timeout. Now pages fail fast (and render the friendly
  maintenance page) instead of hanging.
- **Database**: enabled `PDO::ATTR_PERSISTENT` to reuse the MySQL connection
  across requests — avoids re-handshaking TLS/auth on every page load.
- **Database**: added in-process query cache for `fetch()` and `fetchColumn()`.
  Repeated lookups (e.g. notification counts, user info) within the same
  request now hit the cache instead of querying MySQL again. Writes
  automatically invalidate the cache.
- **AutoMigrator**: was running `SHOW TABLES` + `COUNT(*)` queries on EVERY
  request to check if migration was needed. Now writes a `database/.migrated`
  flag file on first successful migration and skips the check on subsequent
  requests. Flag is automatically invalidated when the container restarts
  (ephemeral filesystem on Coolify).
- **Logging**: per-request INFO logging was causing a `file_put_contents` disk
  write on every page load. Now only logged when `APP_DEBUG=true`.
- **OPcache**: enabled with 128MB memory + 20000 max files. PHP files are now
  compiled once and served from memory on subsequent requests.
- **APCu**: enabled for cross-request caching of small data (notification
  counts, etc.). Cached for 15 seconds per user.
- **Apache**: enabled `mod_deflate` (gzip) + `mod_expires` (static asset
  caching) + `mod_headers` (security headers) + `mod_cache`. Static assets
  now have `Cache-Control: public, max-age=604800, immutable`.
- **MPM tuning**: reduced `StartServers/MinSpareServers/MaxSpareServers` for
  small instance sizes (Coolify free tier). Added `MaxConnectionsPerChild=1000`
  to prevent memory leaks.

### 🎨 Frontend improvements
- **Defer JS**: all `<script>` tags now use `defer` — Bootstrap, SweetAlert2,
  AG Grid, and app.js no longer block first paint.
- **Preconnect**: added `<link rel="preconnect">` for jsdelivr and Google
  Fonts to warm up DNS/TLS connections.
- **AG Grid CSS**: loaded with `media="print" onload="this.media='all'"`
  pattern so it doesn't block initial render.
- **Top loading bar**: NProgress-style progress bar at the top of the screen
  during SPA navigation.
- **Skeleton shimmer**: smoother loading skeleton with shimmer animation.
- **Hover prefetch**: when user hovers a SPA link for >100ms, the page is
  fetched in the background so the click is instant.
- **SPA cache**: pages are cached for 30 seconds in memory — back-button
  and repeated clicks on the same nav item don't re-fetch.
- **Fetch timeout**: SPA navigation now times out after 8 seconds instead of
  hanging indefinitely on slow networks.
- **Connection status**: small indicator in the bottom-left corner shows
  "متصل" / "انقطع الاتصال" based on fetch results.
- **PWA**: added `manifest.json` for installable app + theme color.
- **Service Worker**: basic cache-first strategy for static assets + offline
  fallback for navigation requests.

### 🛠️ Reliability
- **DB-down page**: friendly maintenance page (503) with auto-retry every 15s
  instead of a generic 500 error when MySQL is unreachable.
- **Health check endpoint**: new `/health` route returns 200 + JSON
  immediately without touching the DB. Used by Coolify for health checks.
- **Performance endpoint**: new `/perf` route (admin-only) shows OPcache,
  APCu, and DB cache stats — useful for debugging slow page loads.

### 🔒 Security
- Added security headers: `X-Content-Type-Options`, `X-Frame-Options`,
  `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`.
- `APP_DEBUG=false` in production (was `true`).
- `display_errors=Off` in PHP config.

### 🐛 Bug fixes
- `render.yaml` health check path: changed from `/login` (which 302-redirects)
  to `/health` (lightweight JSON) — was causing false-positive health check
  failures on Coolify that triggered constant redeploys.
- `.dockerignore`: now excludes `install.php`, `test-db.php`, `router.php`
  from production image (smaller image, less attack surface).
- `.htaccess`: was missing gzip + caching headers. Now properly configured.
