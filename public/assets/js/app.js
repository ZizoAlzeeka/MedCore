/**
 * App.js — MedCore platform main JavaScript
 *
 * Features:
 *  - SPA navigation: sidebar links load only main-content via fetch, no full page reload
 *  - AG Grid helper: window.initAgGrid(selector, columns, rowData, options)
 *  - LOINC search helper for admin/tests modal
 *  - CSRF helper, AJAX form submit, toast notifications
 *  - Browser back/forward handling
 */

// ============================================================
// Toggle password field visibility
// ============================================================
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Force English digits in number-like text inputs
function forceEnglishDigits(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
}

// ============================================================
// SPA Navigation (single-page app behavior)
// ============================================================
const SPA = {
    cache: new Map(),
    isNavigating: false,
    lastUrl: null,
};

function getAppBasePath() {
    // App root is always '/' in this project (APP_URL is empty on Render)
    return '/';
}

function isSpaLink(el) {
    if (!el || el.tagName !== 'A') return false;
    if (el.getAttribute('data-spa') !== '1') return false;
    const href = el.getAttribute('href');
    if (!href || href === '#' || href.startsWith('javascript:')) return false;
    if (el.hasAttribute('download')) return false;
    if (el.target === '_blank') return false;
    // Same-origin check
    try {
        const u = new URL(href, window.location.href);
        if (u.origin !== window.location.origin) return false;
    } catch (e) { return false; }
    return true;
}

function showMainLoader() {
    const main = document.getElementById('main-content');
    if (!main) return;
    // ⚡ Don't replace content immediately — only show a subtle top bar.
    // Replacing content with skeleton on every nav causes visual flicker
    // and makes the app feel slow. Keep existing content visible until
    // new content arrives (feels instant when cached, smooth when not).
    main.classList.add('spa-loading');
    showPageLoaderBar();
}

function hideMainLoader() {
    const main = document.getElementById('main-content');
    if (main) main.classList.remove('spa-loading');
    // ⚡ Hide top loading bar
    hidePageLoaderBar();
}

// ⚡ Top loading progress bar
function showPageLoaderBar() {
    const bar = document.getElementById('page-loader-bar');
    if (!bar) return;
    bar.style.width = '0%';
    bar.classList.add('loading');
    // Animate to 80% then wait for completion
    requestAnimationFrame(() => { bar.style.width = '30%'; });
    setTimeout(() => { if (bar.classList.contains('loading')) bar.style.width = '60%'; }, 200);
    setTimeout(() => { if (bar.classList.contains('loading')) bar.style.width = '80%'; }, 500);
}

function hidePageLoaderBar() {
    const bar = document.getElementById('page-loader-bar');
    if (!bar) return;
    bar.style.width = '100%';
    setTimeout(() => {
        bar.classList.remove('loading');
        bar.style.opacity = '0';
        setTimeout(() => {
            bar.style.width = '0%';
            bar.style.opacity = '';
        }, 300);
    }, 150);
}

// ⚡ Connection status indicator
function showConnStatus(state, message) {
    const el = document.getElementById('conn-status');
    if (!el) return;
    const txt = el.querySelector('.text');
    if (txt) txt.textContent = message || (state === 'error' ? 'انقطع الاتصال' : 'متصل');
    el.classList.toggle('error', state === 'error');
    el.classList.add('show');
    clearTimeout(window.__connStatusTimer);
    window.__connStatusTimer = setTimeout(() => el.classList.remove('show'), 2500);
}

function updatePageMeta(meta) {
    if (!meta) return;
    if (meta.title) {
        document.title = (window.appName || 'منصة كشف التحاليل المكررة') + ' — ' + meta.title;
        const titleEl = document.querySelector('.topbar-title');
        if (titleEl) titleEl.textContent = meta.title;
        const subtitleEl = document.querySelector('.topbar-subtitle');
        if (subtitleEl) subtitleEl.textContent = meta.subtitle || '';
    }
    if (meta.currentUrl) {
        // Update active nav link — match parent links when on sub-pages
        document.querySelectorAll('.sidebar-nav a').forEach(a => {
            const linkUrl = a.getAttribute('data-url') || a.getAttribute('href') || '';
            try {
                const u = new URL(linkUrl, window.location.origin);
                const linkPath = u.pathname;
                const curPath = meta.currentUrl;
                const isActive = (curPath === linkPath)
                    || (curPath.startsWith(linkPath + '/') && linkPath !== '/admin' && linkPath !== '/doctor' && linkPath !== '/reception' && linkPath !== '/labtech' && linkPath !== '/patient');
                a.classList.toggle('active', isActive);
            } catch (e) {
                a.classList.toggle('active', linkUrl === meta.currentUrl);
            }
        });
    }
}

function setActiveNav(urlPath) {
    document.querySelectorAll('.sidebar-nav a').forEach(a => {
        const linkUrl = a.getAttribute('data-url') || a.getAttribute('href') || '';
        try {
            const u = new URL(linkUrl, window.location.origin);
            const linkPath = u.pathname;
            // Match if: exact match OR urlPath starts with linkPath + '/' (sub-pages)
            // e.g. link=/admin/users matches /admin/users/5/edit
            // BUT avoid matching /admin matching /admin/users (parent shouldn't highlight when on child)
            const isActive = (urlPath === linkPath)
                || (urlPath.startsWith(linkPath + '/') && linkPath !== '/admin' && linkPath !== '/doctor' && linkPath !== '/reception' && linkPath !== '/labtech' && linkPath !== '/patient');
            a.classList.toggle('active', isActive);
        } catch (e) {
            a.classList.toggle('active', false);
        }
    });
}

/**
 * Navigate to a URL using AJAX — fetches only main-content.
 * ⚡ Robust: no AbortController (was causing AbortError), no timeout that
 * kills legitimate slow requests. Uses a per-request token to ignore
 * stale responses if user navigates again while a request is in-flight.
 */
function loadPage(url, pushState = true) {
    if (!url || url === '#') return;

    // ⚡ If page is already cached, render immediately (no fetch needed)
    if (SPA.cache.has(url)) {
        const cached = SPA.cache.get(url);
        if (cached.expires > Date.now()) {
            renderPageHtml(cached.html, url, pushState, cached.meta);
            return;
        }
        SPA.cache.delete(url);
    }

    // ⚡ Cancel any in-flight request by bumping the token.
    // Old .then() handlers will see their token is stale and silently exit.
    SPA.navToken = (SPA.navToken || 0) + 1;
    const myToken = SPA.navToken;
    SPA.isNavigating = true;
    showMainLoader();

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    })
    .then(r => {
        // Stale response — another navigation happened, ignore silently
        if (myToken !== SPA.navToken) return null;
        if (!r.ok) throw new Error('HTTP ' + r.status);
        // Detect redirect to login (session expired)
        const finalUrl = r.url || url;
        if (finalUrl.includes('/login') && !url.includes('/login')) {
            window.location.href = finalUrl;
            return null;
        }
        return r.text();
    })
    .then(html => {
        // Stale or empty — do nothing
        if (html === null || html === undefined) return;
        if (myToken !== SPA.navToken) return;

        // Parse response — first <script data-ajax-meta> contains metadata
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        const metaScript = tmp.querySelector('script[data-ajax-meta]');
        let meta = null;
        if (metaScript) {
            try { meta = JSON.parse(metaScript.textContent); } catch (e) {}
        }

        // ⚡ Cache for 60 seconds — back-button / repeated clicks are instant
        SPA.cache.set(url, {
            html: html,
            meta: meta,
            expires: Date.now() + 60000,
        });

        renderPageHtml(html, url, pushState, meta);
    })
    .catch(err => {
        // Stale request — don't show error, another nav is in progress
        if (myToken !== SPA.navToken) return;

        // ⚡ Network errors only — don't spam console for every failed nav
        console.warn('SPA nav failed:', err.message);

        const main = document.getElementById('main-content');
        if (main) {
            // ⚡ Friendlier error with auto-retry
            main.innerHTML = '<div class="alert alert-warning m-3">' +
                '<i class="bi bi-exclamation-triangle"></i> ' +
                'تعذّر تحميل الصفحة. جاري إعادة المحاولة...' +
                '</div>';
            // Auto-retry once after 1 second
            setTimeout(() => {
                if (myToken === SPA.navToken) {
                    loadPage(url, pushState);
                }
            }, 1000);
        }
        hideMainLoader();
        SPA.isNavigating = false;
        showConnStatus('error');
    });
}

// ⚡ Extracted: render already-fetched HTML into main-content
function renderPageHtml(html, url, pushState, meta) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    const metaScript = tmp.querySelector('script[data-ajax-meta]');
    if (metaScript) {
        if (!meta) { try { meta = JSON.parse(metaScript.textContent); } catch (e) {} }
        metaScript.remove();
    }
    const scriptNodes = Array.from(tmp.querySelectorAll('script'));
    const scriptContents = [];
    scriptNodes.forEach(s => {
        if (s.hasAttribute('data-ajax-meta')) return;
        if (s.src) {
            scriptContents.push({ src: s.src, async: s.async, defer: s.defer });
        } else {
            scriptContents.push({ code: s.textContent });
        }
        s.remove();
    });

    const main = document.getElementById('main-content');
    if (main) {
        main.innerHTML = tmp.innerHTML;
        scriptContents.forEach(s => {
            if (s.src) {
                const existing = document.querySelector('script[src="' + s.src + '"]');
                if (existing) existing.remove();
                const tag = document.createElement('script');
                tag.src = s.src;
                if (s.async) tag.async = true;
                if (s.defer) tag.defer = true;
                document.body.appendChild(tag);
            } else if (s.code) {
                try {
                    const tag = document.createElement('script');
                    tag.textContent = s.code;
                    document.body.appendChild(tag);
                    setTimeout(() => tag.remove(), 100);
                } catch (e) { console.error('Script eval error:', e); }
            }
        });
    }

    if (pushState) {
        history.pushState({ url }, '', url);
    }
    if (meta) updatePageMeta(meta);
    setActiveNav(new URL(url, window.location.origin).pathname);

    if (main) main.scrollTop = 0;
    window.scrollTo({ top: 0, behavior: 'smooth' });

    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.remove('show');

    SPA.lastUrl = url;
    hideMainLoader();
    SPA.isNavigating = false;

    // ⚡ Show "connected" status briefly
    showConnStatus('ok');

    document.dispatchEvent(new CustomEvent('spa:navigated', { detail: { url, meta } }));
}

// ============================================================
// LOINC NLM API search helper (for admin/tests modal)
// ============================================================
function searchLoincApi(query, callback) {
    if (!query || query.length < 2) {
        callback([]);
        return;
    }
    fetch('/ajax/loinc/search?q=' + encodeURIComponent(query), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => callback(data.results || []))
    .catch(err => {
        console.error('LOINC search error:', err);
        callback([]);
    });
}

// ============================================================
// Toast notifications
// ============================================================
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert-popup ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.style.opacity = '0', 2500);
    setTimeout(() => toast.remove(), 3000);
}

// CSRF token helper for fetch
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

// POST form via fetch (for AJAX forms)
function submitFormAjax(formId, url, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    const formData = new FormData(form);
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.message) showToast(data.message, 'success');
            if (successCallback) successCallback(data);
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    })
    .catch(err => showToast('خطأ في الشبكة: ' + err.message, 'error'));
}

// Test catalog live search (for doctor order-test page)
function liveSearchTests(query, callback) {
    if (!query || query.length < 2) {
        callback([]);
        return;
    }
    fetch('/ajax/tests/search?q=' + encodeURIComponent(query), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => callback(data.tests || []))
    .catch(() => callback([]));
}

// Check duplicate (AJAX) before submitting order
function checkDuplicate(patientId, testId, callback) {
    fetch('/ajax/check-duplicate?patient_id=' + patientId + '&test_id=' + testId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => callback(data))
    .catch(() => callback({ duplicate: false }));
}

// Print element to PDF using html2pdf
function printToPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    if (!element || typeof html2pdf === 'undefined') {
        alert('عذراً، مكتبة الطباعة غير متاحة');
        return;
    }
    const opt = {
        margin: 10,
        filename: filename,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
    };
    html2pdf().set(opt).from(element).save();
}

// Init Quill editor
function initQuill(containerId = 'editor-container', hiddenInputId = 'description_html') {
    if (typeof Quill === 'undefined') {
        console.error('Quill not loaded');
        return null;
    }
    var container = document.getElementById(containerId);
    if (!container) return null;

    var quill = new Quill('#' + containerId, {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'align': [] }],
                [{ 'direction': [] }],
                ['blockquote'],
                ['clean']
            ]
        }
    });

    var hidden = document.getElementById(hiddenInputId);
    if (hidden) {
        quill.on('text-change', function() {
            hidden.value = quill.root.innerHTML;
        });
        if (hidden.value) {
            quill.root.innerHTML = hidden.value;
        }
    }
    return quill;
}

// ============================================================
// Bootstrap — wire up everything on DOMContentLoaded
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Set global appName from title
    const titleMatch = document.title.match(/^(.+?) —/);
    if (titleMatch) window.appName = titleMatch[1];

    // Number inputs: force English digits
    document.querySelectorAll('input[inputmode="numeric"]').forEach(input => {
        input.addEventListener('input', () => forceEnglishDigits(input));
    });

    // SPA navigation: intercept clicks on [data-spa="1"] links
    document.body.addEventListener('click', function(e) {
        // Walk up to find anchor
        let target = e.target;
        while (target && target !== document.body) {
            if (target.tagName === 'A' && isSpaLink(target)) {
                e.preventDefault();
                const href = target.getAttribute('href');
                if (href) loadPage(href, true);
                return;
            }
            target = target.parentElement;
        }
    });

    // Live update notification count + show SweetAlert2 toast on new notif (every 15s)
    (function() {
        var bellBtn = document.getElementById('notifBellBtn');
        if (!bellBtn) return;

        // Track the last seen notification id (so we only toast NEW ones, not backlog)
        var lastSeenId = parseInt(bellBtn.dataset.lastNotifId || '0', 10) || 0;

        function updateBadge(count) {
            var badge = bellBtn.querySelector('.badge-count');
            if (!badge) {
                if (count > 0) {
                    badge = document.createElement('span');
                    badge.className = 'badge-count';
                    badge.style.display = 'flex';
                    bellBtn.appendChild(badge);
                }
                return;
            }
            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        function showToast(latest) {
            if (typeof Swal === 'undefined' || !latest) return;
            var icon = 'info';
            if (latest.type === 'result_ready') icon = 'success';
            else if (latest.type === 'treatment_added') icon = 'success';
            else if (latest.type === 'duplicate_alert') icon = 'warning';
            else if (latest.type === 'referral') icon = 'info';
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: latest.title || 'إشعار جديد',
                text: latest.message || '',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: function(toast) {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        }

        setInterval(function() {
            fetch('/notifications/unread-count', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data || !data.success) return;
                    updateBadge(data.count || 0);
                    // Show toast if a brand new notification arrived
                    if (data.latest && data.latest.id) {
                        var newId = parseInt(data.latest.id, 10) || 0;
                        if (newId > lastSeenId) {
                            lastSeenId = newId;
                            showToast(data.latest);
                        }
                    }
                })
                .catch(function() {});
        }, 15000);
    })();
});

// Handle browser back/forward
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.url) {
        loadPage(e.state.url, false);
    } else {
        // No state — load current URL
        loadPage(window.location.pathname, false);
    }
});

// Save initial state
history.replaceState({ url: window.location.pathname + window.location.search }, '', window.location.href);
SPA.lastUrl = window.location.pathname;

// ⚡ Register service worker for static asset caching + offline support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/public/assets/sw.js').catch(() => {
            // SW registration failed — silently ignore (dev environment, etc.)
        });
    });
}
