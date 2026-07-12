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
// AG Grid: tests catalog
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
                const editBtn = `<button class="btn btn-sm btn-outline-primary me-1" onclick='editTest(${JSON.stringify(t)})' title="تعديل"><i class="bi bi-pencil"></i></button>`;
                const deleteForm = `<form method="post" action="<?= url('/admin/tests') ?>/${t.id}/delete" style="display:inline" onsubmit="return confirm('تأكيد الحذف؟')">` +
                    `<input type="hidden" name="csrf_token" value="${getCsrfToken()}">` +
                    `<button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>` +
                    `</form>`;
                return `<div class="text-nowrap">${editBtn}${deleteForm}</div>`;
            },
        },
    ];

    // Wait for AG Grid + helper to be available
    function tryInit() {
        if (typeof agGrid === 'undefined' || typeof initAgGrid !== 'function') {
            setTimeout(tryInit, 100);
            return;
        }
        initAgGrid('#testsGrid', columns, testsData, {
            paginationPageSize: 15,
            paginationPageSizeSelector: [10, 15, 25, 50, 100],
        });
    }
    tryInit();
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
// LOINC NLM API search
// ============================================================
let loincSearchTimer = null;
function performLoincSearch() {
    const q = document.getElementById('loincSearchInput').value.trim();
    const resultsEl = document.getElementById('loincResults');
    if (q.length < 2) {
        resultsEl.innerHTML = '<small class="text-muted">اكتب حرفين على الأقل للبحث...</small>';
        return;
    }
    resultsEl.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> جاري البحث في قاعدة LOINC...</div>';
    searchLoincApi(q, function(results) {
        if (!results || results.length === 0) {
            resultsEl.innerHTML = '<small class="text-muted">لا توجد نتائج. أدخل البيانات يدوياً بالأسفل.</small>';
            return;
        }
        let html = '<div class="list-group" style="font-size: 12px;">';
        results.forEach(r => {
            const sourceBadge = r.source === 'NLM'
                ? '<span class="badge bg-success ms-1">NLM</span>'
                : '<span class="badge bg-secondary ms-1">محلي</span>';
            html += `<a href="javascript:void(0)" class="list-group-item list-group-item-action" onclick='pickLoincResult(${JSON.stringify(r)})'>` +
                `<div class="d-flex justify-content-between">` +
                `<span class="fw-bold"><span class="loinc-code">${r.loinc_code || ''}</span> ${sourceBadge}</span>` +
                `<small class="text-muted">${r.category || ''}</small>` +
                `</div>` +
                `<div dir="ltr" class="small">${r.name_en || r.name_ar || ''}</div>` +
                (r.short_name ? `<small class="text-muted">short: ${r.short_name}</small>` : '') +
                (r.sample_type ? `<small class="text-muted ms-2">• sample: ${r.sample_type}</small>` : '') +
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
    // Hide results
    document.getElementById('loincResults').innerHTML =
        '<div class="alert alert-success mb-0 py-2"><i class="bi bi-check-circle"></i> تم تعبئة الحقول من قاعدة LOINC. راجع الاسم العربي وأكمله إذا لزم.</div>';
}

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('loincSearchInput');
    const btn = document.getElementById('loincSearchBtn');
    if (input) {
        input.addEventListener('input', function() {
            clearTimeout(loincSearchTimer);
            loincSearchTimer = setTimeout(performLoincSearch, 400);
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performLoincSearch();
            }
        });
    }
    if (btn) {
        btn.addEventListener('click', performLoincSearch);
    }
});
</script>
