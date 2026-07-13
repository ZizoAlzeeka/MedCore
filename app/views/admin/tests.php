<?php /** Admin: Tests Catalog with AG Grid + LOINC NLM API search */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-clipboard2-pulse-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">قاعدة بيانات التحاليل بمعيار LOINC — يستخدمها الأطباء عند الطلب</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#testModal" onclick="resetTestForm()">
        <i class="bi bi-plus-circle"></i> إضافة تحليل
    </button>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> قاعدة بيانات التحاليل (<?= count($tests) ?>)</span>
        <small class="text-muted">بحث + ترتيب + تصفية + ترقيم صفحات تلقائي عبر AG Grid</small>
    </div>
    <div class="card-body p-2">
        <div id="testsGrid" style="width:100%; height: 560px;"></div>
    </div>
</div>

<!-- Modal: Add/Edit Test -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="testForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="test_id">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="testModalTitle">إضافة تحليل</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- LOINC NLM API Search -->
                    <div class="card mb-3" style="background:#f8f9ff;border:1px solid #e0e6ff;">
                        <div class="card-body p-3">
                            <label class="form-label fw-bold text-purple">
                                <i class="bi bi-cloud-download"></i> بحث في قاعدة بيانات LOINC العالمية (NLM)
                            </label>
                            <div class="input-group">
                                <input type="text" id="loincSearchInput" class="form-control"
                                       placeholder="ابحث بالاسم أو الكود (مثال: glucose, CBC, 6690-2, hemoglobin)..."
                                       dir="ltr">
                                <button class="btn btn-info" type="button" id="loincSearchBtn">
                                    <i class="bi bi-search"></i> بحث
                                </button>
                            </div>
                            <small class="text-muted">يعمل البحث مباشرة على خادم LOINC التابع للمكتبة الوطنية الأمريكية للطب (NLM). اختر نتيجة لتعبئة الحقول تلقائياً.</small>
                            <div id="loincResults" class="mt-2" style="max-height: 240px; overflow-y: auto;"></div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">كود LOINC <span class="text-danger">*</span></label>
                            <input type="text" name="loinc_code" id="test_loinc" class="form-control" required placeholder="مثال: 6690-2" dir="ltr">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                            <input type="text" name="name_ar" id="test_name_ar" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">الاسم بالإنجليزية</label>
                            <input type="text" name="name_en" id="test_name_en" class="form-control" dir="ltr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الفئة</label>
                            <input type="text" name="category" id="test_cat" class="form-control" placeholder="أمراض دم / كيمياء حيوية / ...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نوع العينة</label>
                            <input type="text" name="sample_type" id="test_sample" class="form-control" placeholder="دم / مصل / بلازما / بول">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============================================================
// AG Grid: tests catalog — uses whenAgGridReady (defined in <head>)
// ============================================================
(function() {
    const testsData = <?= json_encode($tests, JSON_UNESCAPED_UNICODE) ?>;

    const roleBadgeClass = (status) => {
        return 'bg-info';
    };

    const columns = [
        {
            headerName: '#',
            valueGetter: params => params.node.rowIndex + 1,
            width: 60,
            maxWidth: 80,
            sortable: false,
            filter: false,
            pinned: 'right',
            cellClass: 'text-center text-muted',
        },
        {
            headerName: 'كود LOINC',
            field: 'loinc_code',
            width: 130,
            cellRenderer: params => `<span class="loinc-code">${params.value || ''}</span>`,
        },
        {
            headerName: 'الاسم (عربي)',
            field: 'name_ar',
            minWidth: 200,
            cellClass: 'fw-bold',
        },
        {
            headerName: 'الاسم (إنجليزي)',
            field: 'name_en',
            minWidth: 180,
            cellClass: 'small',
            cellRenderer: params => `<span dir="ltr">${params.value || ''}</span>`,
        },
        {
            headerName: 'الفئة',
            field: 'category',
            width: 150,
            cellRenderer: params => params.value ? `<span class="badge bg-info">${params.value}</span>` : '',
        },
        {
            headerName: 'نوع العينة',
            field: 'sample_type',
            width: 130,
        },
        {
            headerName: 'إجراءات',
            width: 150,
            sortable: false,
            filter: false,
            pinned: 'left',
            cellRenderer: function(params) {
                const t = params.data;
                const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                const editBtn = `<button class="btn btn-sm btn-outline-primary me-1" onclick='editTest(${JSON.stringify(t)})' title="تعديل"><i class="bi bi-pencil"></i></button>`;
                const deleteForm = `<form method="post" action="<?= url('/admin/tests') ?>/${t.id}/delete" style="display:inline" onsubmit="return confirm('تأكيد الحذف؟')">` +
                    `<input type="hidden" name="csrf_token" value="${csrf}">` +
                    `<button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>` +
                    `</form>`;
                return `<div class="text-nowrap">${editBtn}${deleteForm}</div>`;
            },
        },
    ];

    // ⚡ Wait for AG Grid library to be available, then init
    window.whenAgGridReady(function() {
        window.initAgGrid('#testsGrid', columns, testsData, {
            paginationPageSize: 15,
            paginationPageSizeSelector: [10, 15, 25, 50, 100],
        });
    });
})();

// ============================================================
// Test modal: form helpers + LOINC search
// ============================================================
function resetTestForm() {
    document.getElementById('testForm').action = '<?= url("/admin/tests/store") ?>';
    document.getElementById('test_id').value = '';
    document.getElementById('test_loinc').value = '';
    document.getElementById('test_name_ar').value = '';
    document.getElementById('test_name_en').value = '';
    document.getElementById('test_cat').value = '';
    document.getElementById('test_sample').value = '';
    document.getElementById('testModalTitle').textContent = 'إضافة تحليل';
    document.getElementById('loincSearchInput').value = '';
    document.getElementById('loincResults').innerHTML = '';
}
function editTest(t) {
    document.getElementById('testForm').action = '<?= url("/admin/tests") ?>/' + t.id + '/update';
    document.getElementById('test_id').value = t.id;
    document.getElementById('test_loinc').value = t.loinc_code;
    document.getElementById('test_name_ar').value = t.name_ar;
    document.getElementById('test_name_en').value = t.name_en || '';
    document.getElementById('test_cat').value = t.category || '';
    document.getElementById('test_sample').value = t.sample_type || '';
    document.getElementById('testModalTitle').textContent = 'تعديل تحليل';
    document.getElementById('loincResults').innerHTML = '';
    new bootstrap.Modal(document.getElementById('testModal')).show();
}

// ============================================================
// LOINC NLM API search — LIVE as user types (250ms debounce)
// ============================================================
let loincSearchTimer = null;
let loincSearchReqId = 0; // race-condition guard: only render latest response

function performLoincSearch() {
    const input = document.getElementById('loincSearchInput');
    const resultsEl = document.getElementById('loincResults');
    if (!input || !resultsEl) return;

    const q = input.value.trim();
    if (q.length < 2) {
        resultsEl.innerHTML = '<small class="text-muted">✍️ اكتب حرفين على الأقل للبحث اللحظي في قاعدة LOINC العالمية...</small>';
        return;
    }

    // Show inline spinner immediately (don't clear previous results entirely —
    // keep them visible while new ones load, just show spinner on top)
    resultsEl.innerHTML = '<div class="text-center py-2 text-primary"><div class="spinner-border spinner-border-sm"></div> <small>جاري البحث في قاعدة LOINC عن "' + q.replace(/[<>]/g, '') + '"...</small></div>';

    const reqId = ++loincSearchReqId;
    searchLoincApi(q, function(results) {
        // Only render if this is still the latest request
        if (reqId !== loincSearchReqId) return;

        if (!results || results.length === 0) {
            resultsEl.innerHTML = '<small class="text-muted">❌ لا توجد نتائج. أدخل البيانات يدوياً بالأسفل.</small>';
            return;
        }
        let html = '<div class="list-group loinc-results-list" style="font-size: 12px;">';
        results.forEach((r, idx) => {
            const sourceBadge = r.source === 'NLM'
                ? '<span class="badge bg-success ms-1">NLM</span>'
                : '<span class="badge bg-secondary ms-1">محلي</span>';
            const safeJson = JSON.stringify(r).replace(/'/g, "\\'");
            html += `<a href="javascript:void(0)" class="list-group-item list-group-item-action" onclick='pickLoincResult(${safeJson})'>` +
                `<div class="d-flex justify-content-between align-items-center">` +
                `<span class="fw-bold"><span class="loinc-code">${r.loinc_code || ''}</span> ${sourceBadge}</span>` +
                `<small class="text-muted">${r.category || ''}</small>` +
                `</div>` +
                `<div dir="ltr" class="small text-truncate" style="max-width:100%">${r.name_en || r.name_ar || ''}</div>` +
                (r.sample_type ? `<small class="text-muted">sample: ${r.sample_type}</small>` : '') +
                `</a>`;
        });
        html += '</div>';
        resultsEl.innerHTML = html;
    });
}

function pickLoincResult(r) {
    if (r.loinc_code) document.getElementById('test_loinc').value = r.loinc_code;
    if (r.name_en) document.getElementById('test_name_en').value = r.name_en;
    if (r.name_ar) document.getElementById('test_name_ar').value = r.name_ar;
    if (r.category) document.getElementById('test_cat').value = r.category;
    if (r.sample_type) document.getElementById('test_sample').value = r.sample_type;
    document.getElementById('loincResults').innerHTML =
        '<div class="alert alert-success mb-0 py-2"><i class="bi bi-check-circle"></i> تم تعبئة الحقول من قاعدة LOINC. راجع الاسم العربي وأكمله إذا لزم.</div>';
}

// Wire up input listeners — works on both initial load AND after SPA navigation.
// We re-attach on every page navigation because the input element is recreated.
function initLoincSearchInput() {
    const input = document.getElementById('loincSearchInput');
    const btn = document.getElementById('loincSearchBtn');
    if (!input) return;
    // Avoid double-binding: mark the input
    if (input.dataset.loincBound === '1') return;
    input.dataset.loincBound = '1';

    // Live search as user types (250ms debounce for snappy UX)
    input.addEventListener('input', function() {
        clearTimeout(loincSearchTimer);
        loincSearchTimer = setTimeout(performLoincSearch, 250);
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(loincSearchTimer);
            performLoincSearch();
        }
    });
    // Trigger initial search when modal opens with focus
    input.addEventListener('focus', function() {
        if (input.value.trim().length >= 2 && !document.getElementById('loincResults').innerHTML.trim()) {
            performLoincSearch();
        }
    });
    if (btn && !btn.dataset.loincBound) {
        btn.dataset.loincBound = '1';
        btn.addEventListener('click', function() {
            clearTimeout(loincSearchTimer);
            performLoincSearch();
        });
    }
}

// Initial page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoincSearchInput);
} else {
    // We're already loaded (e.g. SPA navigation just swapped content)
    initLoincSearchInput();
}
// SPA navigation: re-bind when new content arrives
document.addEventListener('spa:navigated', initLoincSearchInput);
</script>
