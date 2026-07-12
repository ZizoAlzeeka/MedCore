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
    // Fade-out current content
    main.style.opacity = '0.55';
    // Inject skeleton overlay
    let skel = document.getElementById('spa-skeleton');
    if (!skel) {
        skel = document.createElement('div');
        skel.id = 'spa-skeleton';
        skel.className = 'spa-skeleton-loader';
        skel.innerHTML = `
            <div class="spa-skel-line" style="width:40%"></div>
            <div class="spa-skel-line" style="width:75%"></div>
            <div class="spa-skel-line" style="width:60%"></div>
            <div class="spa-skel-block"></div>
            <div class="spa-skel-block"></div>
        `;
        main.parentElement.insertBefore(skel, main);
    }
    skel.style.display = 'block';
}

function hideMainLoader() {
    const skel = document.getElementById('spa-skeleton');
    if (skel) skel.style.display = 'none';
    const main = document.getElementById('main-content');
    if (main) main.style.opacity = '1';
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
        // Update active nav link
        document.querySelectorAll('.sidebar-nav a').forEach(a => {
            const linkUrl = a.getAttribute('data-url') || a.getAttribute('href') || '';
            // Compare path portion only
            try {
                const u = new URL(linkUrl, window.location.origin);
                const aPath = u.pathname;
                const curPath = meta.currentUrl;
                a.classList.toggle('active', aPath === curPath);
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
            a.classList.toggle('active', u.pathname === urlPath);
        } catch (e) {
            a.classList.toggle('active', false);
        }
    });
}

/**
 * Navigate to a URL using AJAX — fetches only main-content.
 */
function loadPage(url, pushState = true) {
    if (!url || url === '#') return;
    if (SPA.isNavigating) return;
    SPA.isNavigating = true;
    showMainLoader();

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        // Detect redirect to login (session expired)
        const finalUrl = r.url || url;
        if (finalUrl.includes('/login') && !url.includes('/login')) {
            window.location.href = finalUrl;
            return '';
        }
        return r.text();
    })
    .then(html => {
        if (!html) { SPA.isNavigating = false; hideMainLoader(); return; }

        // Parse response — first <script data-ajax-meta> contains metadata
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        const metaScript = tmp.querySelector('script[data-ajax-meta]');
        let meta = null;
        if (metaScript) {
            try { meta = JSON.parse(metaScript.textContent); } catch (e) {}
            metaScript.remove();
        }
        // Remove any <script> tags from the response — they won't execute via innerHTML
        // We'll re-evaluate them manually below
        const scriptNodes = Array.from(tmp.querySelectorAll('script'));
        const scriptContents = [];
        scriptNodes.forEach(s => {
            if (s.hasAttribute('data-ajax-meta')) return;
            if (s.src) {
                // External scripts: append a fresh <script src=...> to body
                // (we'll handle this separately below)
                scriptContents.push({ src: s.src, async: s.async, defer: s.defer });
            } else {
                scriptContents.push({ code: s.textContent });
            }
            s.remove();
        });

        const main = document.getElementById('main-content');
        if (main) {
            main.innerHTML = tmp.innerHTML;
            // Re-attach any page-specific scripts
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

        // Scroll main content to top
        if (main) main.scrollTop = 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Close any open sidebar (mobile)
        const sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.remove('show');

        SPA.lastUrl = url;
        hideMainLoader();
        SPA.isNavigating = false;

        // Dispatch event for components that want to know about navigation
        document.dispatchEvent(new CustomEvent('spa:navigated', { detail: { url, meta } }));
    })
    .catch(err => {
        console.error('SPA nav error:', err);
        const main = document.getElementById('main-content');
        if (main) {
            main.innerHTML = '<div class="alert alert-danger m-3"><i class="bi bi-exclamation-triangle"></i> فشل تحميل الصفحة: ' + err.message + '<br><a href="' + url + '" class="alert-link">إعادة المحاولة</a></div>';
        }
        hideMainLoader();
        SPA.isNavigating = false;
    });
}

// ============================================================
// AG Grid helper
// ============================================================
/**
 * Initialize an AG Grid on a given element.
 *
 * @param {string|HTMLElement} el - selector or DOM element
 * @param {Array} columnDefs - AG Grid column defs
 * @param {Array} rowData - data array
 * @param {Object} opts - extra options (gridOptions)
 * @returns {agGrid.Grid} AG Grid API instance
 */
function initAgGrid(el, columnDefs, rowData, opts = {}) {
    if (typeof agGrid === 'undefined') {
        console.error('AG Grid library not loaded');
        return null;
    }
    const target = (typeof el === 'string') ? document.querySelector(el) : el;
    if (!target) {
        console.error('AG Grid target not found:', el);
        return null;
    }

    // Default options — RTL friendly + Arabic UI strings
    const defaults = {
        columnDefs: columnDefs,
        rowData: rowData || [],
        direction: 'rtl',
        animateRows: true,
        defaultColDef: {
            flex: 1,
            minWidth: 100,
            resizable: true,
            sortable: true,
            filter: true,
            floatingFilter: true,
            menuTabs: ['filterMenuTab', 'generalMenuTab'],
        },
        pagination: true,
        paginationPageSize: 25,
        paginationPageSizeSelector: [10, 25, 50, 100],
        enableCellTextSelection: true,
        ensureDomOrder: true,
        suppressCellFocus: true,
        localeText: AG_GRID_AR_LOCALE,
        theme: 'ag-theme-quartz',
    };

    const gridOptions = Object.assign({}, defaults, opts);
    // Ensure class is on the element
    target.className = (target.className || '') + ' ag-theme-quartz medcore-ag-grid';

    return agGrid.createGrid(target, gridOptions);
}

// Arabic locale strings for AG Grid (subset — covers common UI)
const AG_GRID_AR_LOCALE = {
    // pagination
    page: 'صفحة',
    more: 'المزيد',
    to: 'إلى',
    of: 'من',
    next: 'التالي',
    last: 'الأخير',
    first: 'الأول',
    previous: 'السابق',
    loadingOoo: 'جاري التحميل...',
    noRowsToShow: 'لا توجد بيانات',
    // pinning
    pinColumn: 'تثبيت العمود',
    pinLeft: 'تثبيت يسار',
    pinRight: 'تثبيت يمين',
    noPin: 'إلغاء التثبيت',
    // menu
    columnMenu: 'قائمة الأعمدة',
    // filter
    filterOoo: 'تصفية...',
    equals: 'يساوي',
    notEqual: 'لا يساوي',
    contains: 'يحتوي',
    notContains: 'لا يحتوي',
    startsWith: 'يبدأ بـ',
    endsWith: 'ينتهي بـ',
    blank: 'فارغ',
    notBlank: 'غير فارغ',
    andCondition: 'و',
    orCondition: 'أو',
    applyFilter: 'تطبيق',
    resetFilter: 'إعادة تعيين',
    clearFilter: 'مسح',
    // sort
    sortAscending: 'ترتيب تصاعدي',
    sortDescending: 'ترتيب تنازلي',
    sortUnSort: 'إلغاء الترتيب',
    // columns
    columns: 'الأعمدة',
    resetColumns: 'إعادة تعيين الأعمدة',
    toolPanel: 'لوحة الأدوات',
    // menu general
    copy: 'نسخ',
    ctrlC: 'Ctrl+C',
    paste: 'لصق',
    ctrlV: 'Ctrl+V',
    export: 'تصدير',
};

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
    const quill = new Quill('#' + containerId, {
        theme: 'snow',
        rtl: true,
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['blockquote'],
                ['clean']
            ]
        }
    });
    const hidden = document.getElementById(hiddenInputId);
    if (hidden) {
        quill.on('text-change', () => {
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

    // Live update notification count every 60s
    if (document.querySelector('.topbar-icon-btn .badge-count')) {
        setInterval(() => {
            fetch('/notifications/unread-count', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const badge = document.querySelector('.topbar-icon-btn .badge-count');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(() => {});
        }, 60000);
    }
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
