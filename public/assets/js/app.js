/**
 * App.js — main JavaScript for platform
 */

// Toggle password field visibility
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

// AJAX navigation: load main content only
function loadPage(url, pushState = true) {
    if (!url || url === '#') return;
    // Show loader
    const main = document.getElementById('main-content');
    if (main) {
        main.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">جاري التحميل...</p></div>';
    }
    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => {
        if (main) main.innerHTML = html;
        if (pushState) history.pushState({ url }, '', url);
        // Update active nav
        document.querySelectorAll('.sidebar-nav a').forEach(a => {
            a.classList.toggle('active', a.getAttribute('href') === url);
        });
    })
    .catch(err => {
        if (main) main.innerHTML = '<div class="alert alert-danger">فشل التحميل: ' + err.message + '</div>';
    });
}

// Show toast notification
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
    fetch(`/ajax/tests/search?q=${encodeURIComponent(query)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => callback(data.tests || []))
    .catch(() => callback([]));
}

// Check duplicate (AJAX) before submitting order
function checkDuplicate(patientId, testId, callback) {
    fetch(`/ajax/check-duplicate?patient_id=${patientId}&test_id=${testId}`, {
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
    // Sync hidden input on text change
    const hidden = document.getElementById(hiddenInputId);
    if (hidden) {
        quill.on('text-change', () => {
            hidden.value = quill.root.innerHTML;
        });
        // If hidden input has value, load it
        if (hidden.value) {
            quill.root.innerHTML = hidden.value;
        }
    }
    return quill;
}

// Auto-attach: handle AJAX nav links
document.addEventListener('DOMContentLoaded', function() {
    // Number inputs: force English digits
    document.querySelectorAll('input[inputmode="numeric"]').forEach(input => {
        input.addEventListener('input', () => forceEnglishDigits(input));
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
    }
});
