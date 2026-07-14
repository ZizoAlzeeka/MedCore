<?php /** Admin: Tests Catalog — native HTML table + LOINC search + live search + numbered pagination */
$csrf = Auth::csrfToken();
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-clipboard2-pulse-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">قاعدة بيانات التحاليل بمعيار LOINC — <?= count($tests) ?> تحليل</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#testModal" onclick="resetTestForm()">
        <i class="bi bi-plus-circle"></i> إضافة تحليل
    </button>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label small">بحث لحظي</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="testSearchInput" class="form-control" placeholder="ابحث بالاسم، الكود، الفئة، نوع العينة..." oninput="renderTestTable()">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small">عدد الصفوف</label>
                <select id="testPageSizeSelect" class="form-select form-select-sm" onchange="renderTestTable()">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> قاعدة بيانات التحاليل</span>
        <small class="text-muted" id="testResultCount"><?= count($tests) ?> سجل</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 medcore-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>كود LOINC</th>
                        <th>الاسم (عربي)</th>
                        <th>الاسم (إنجليزي)</th>
                        <th>الفئة</th>
                        <th>نوع العينة</th>
                        <th style="width: 110px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="testsTableBody"></tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" id="testPaginationBar"></div>
</div>

<!-- Modal: Add/Edit Test -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="testForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="id" id="test_id">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="testModalTitle">إضافة تحليل</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                            <label class="form-label small">كود LOINC <span class="text-danger">*</span></label>
                            <input type="text" name="loinc_code" id="test_loinc" class="form-control form-control-sm" required placeholder="مثال: 6690-2" dir="ltr">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small">الاسم بالعربية <span class="text-danger">*</span></label>
                            <input type="text" name="name_ar" id="test_name_ar" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">الاسم بالإنجليزية</label>
                            <input type="text" name="name_en" id="test_name_en" class="form-control form-control-sm" dir="ltr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">الفئة</label>
                            <input type="text" name="category" id="test_cat" class="form-control form-control-sm" placeholder="أمراض دم / كيمياء حيوية / ...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">نوع العينة</label>
                            <input type="text" name="sample_type" id="test_sample" class="form-control form-control-sm" placeholder="دم / مصل / بلازما / بول">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var allTests = <?= json_encode($tests, JSON_UNESCAPED_UNICODE) ?>;
var testCurrentPage = 1;

function renderTestTable() {
    var search = (document.getElementById('testSearchInput').value || '').toLowerCase().trim();
    var pageSize = parseInt(document.getElementById('testPageSizeSelect').value);

    var filtered = allTests.filter(function(t) {
        if (!search) return true;
        return (t.loinc_code||'').toLowerCase().includes(search) ||
               (t.name_ar||'').toLowerCase().includes(search) ||
               (t.name_en||'').toLowerCase().includes(search) ||
               (t.category||'').toLowerCase().includes(search) ||
               (t.sample_type||'').toLowerCase().includes(search);
    });

    var totalPages = Math.ceil(filtered.length / pageSize) || 1;
    if (testCurrentPage > totalPages) testCurrentPage = totalPages;
    if (testCurrentPage < 1) testCurrentPage = 1;
    var start = (testCurrentPage - 1) * pageSize;
    var pageData = filtered.slice(start, start + pageSize);

    var tbody = document.getElementById('testsTableBody');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:24px;opacity:0.4;"></i><p class="mt-2">لا نتائج مطابقة</p></td></tr>';
    } else {
        tbody.innerHTML = pageData.map(function(t, i) {
            var num = start + i + 1;
            var deleteUrl = '<?= url('/admin/tests') ?>/' + t.id + '/delete';
            return '<tr>' +
                '<td class="text-muted">' + num + '</td>' +
                '<td><span class="loinc-code">' + (t.loinc_code||'') + '</span></td>' +
                '<td class="fw-bold">' + (t.name_ar||'') + '</td>' +
                '<td dir="ltr" class="text-end small">' + (t.name_en||'-') + '</td>' +
                '<td>' + (t.category ? '<span class="badge bg-info">' + t.category + '</span>' : '-') + '</td>' +
                '<td class="small">' + (t.sample_type||'-') + '</td>' +
                '<td><div class="d-flex gap-1">' +
                '<button class="btn btn-sm btn-outline-primary" onclick=\'editTest(' + JSON.stringify(t) + ')\' title="تعديل"><i class="bi bi-pencil"></i></button>' +
                '<form method="post" action="' + deleteUrl + '" style="display:inline" onsubmit="return confirm(\'تأكيد الحذف؟\')">' +
                '<input type="hidden" name="csrf_token" value="' + csrf + '">' +
                '<button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>' +
                '</form></div></td>' +
                '</tr>';
        }).join('');
    }

    document.getElementById('testResultCount').textContent = filtered.length + ' سجل';
    renderTestPagination(totalPages, filtered.length, start, pageData.length);
}

function renderTestPagination(totalPages, totalRows, start, count) {
    var bar = document.getElementById('testPaginationBar');
    if (totalPages <= 1) { bar.innerHTML = ''; return; }

    var info = '<div class="medcore-pagination-info">عرض ' + (start+1) + ' إلى ' + (start+count) + ' من ' + totalRows + ' سجل</div>';
    var html = '<div class="medcore-pagination">';
    html += '<button class="page-btn' + (testCurrentPage <= 1 ? ' disabled' : '') + '" ' + (testCurrentPage <= 1 ? '' : 'onclick="testGoPage('+(testCurrentPage-1)+')"') + '><i class="bi bi-chevron-right"></i></button>';

    var maxButtons = 7;
    var pages = [];
    if (totalPages <= maxButtons) {
        for (var i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (testCurrentPage > 3) pages.push('...');
        var s = Math.max(2, testCurrentPage - 1);
        var e = Math.min(totalPages - 1, testCurrentPage + 1);
        for (var i = s; i <= e; i++) pages.push(i);
        if (testCurrentPage < totalPages - 2) pages.push('...');
        pages.push(totalPages);
    }
    pages.forEach(function(p) {
        if (p === '...') { html += '<span class="page-dots">…</span>'; }
        else { html += '<button class="page-btn' + (p === testCurrentPage ? ' active' : '') + '" onclick="testGoPage('+p+')">' + p + '</button>'; }
    });

    html += '<button class="page-btn' + (testCurrentPage >= totalPages ? ' disabled' : '') + '" ' + (testCurrentPage >= totalPages ? '' : 'onclick="testGoPage('+(testCurrentPage+1)+')"') + '><i class="bi bi-chevron-left"></i></button>';
    html += '</div>';
    bar.innerHTML = info + html;
}

function testGoPage(p) { testCurrentPage = p; renderTestTable(); }

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

// LOINC search
let loincSearchTimer = null;
let loincSearchReqId = 0;
function performLoincSearch() {
    const input = document.getElementById('loincSearchInput');
    const resultsEl = document.getElementById('loincResults');
    if (!input || !resultsEl) return;
    const q = input.value.trim();
    if (q.length < 2) { resultsEl.innerHTML = '<small class="text-muted">✍️ اكتب حرفين على الأقل...</small>'; return; }
    resultsEl.innerHTML = '<div class="text-center py-2 text-primary"><div class="spinner-border spinner-border-sm"></div> <small>جاري البحث...</small></div>';
    const reqId = ++loincSearchReqId;
    searchLoincApi(q, function(results) {
        if (reqId !== loincSearchReqId) return;
        if (!results || results.length === 0) { resultsEl.innerHTML = '<small class="text-muted">❌ لا توجد نتائج.</small>'; return; }
        let html = '<div class="list-group" style="font-size: 12px;">';
        results.forEach((r) => {
            const safeJson = JSON.stringify(r).replace(/'/g, "\\'");
            const sourceBadge = r.source === 'NLM' ? '<span class="badge bg-success ms-1">NLM</span>' : '<span class="badge bg-secondary ms-1">محلي</span>';
            html += `<a href="javascript:void(0)" class="list-group-item list-group-item-action" onclick='pickLoincResult(${safeJson})'><div class="d-flex justify-content-between"><span class="fw-bold"><span class="loinc-code">${r.loinc_code||''}</span> ${sourceBadge}</span><small class="text-muted">${r.category||''}</small></div><div dir="ltr" class="small text-truncate">${r.name_en||r.name_ar||''}</div></a>`;
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
    document.getElementById('loincResults').innerHTML = '<div class="alert alert-success mb-0 py-2"><i class="bi bi-check-circle"></i> تم تعبئة الحقول.</div>';
}
function initLoincSearchInput() {
    const input = document.getElementById('loincSearchInput');
    const btn = document.getElementById('loincSearchBtn');
    if (!input || input.dataset.loincBound === '1') return;
    input.dataset.loincBound = '1';
    input.addEventListener('input', function() { clearTimeout(loincSearchTimer); loincSearchTimer = setTimeout(performLoincSearch, 250); });
    input.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(loincSearchTimer); performLoincSearch(); } });
    if (btn && !btn.dataset.loincBound) { btn.dataset.loincBound = '1'; btn.addEventListener('click', function() { clearTimeout(loincSearchTimer); performLoincSearch(); }); }
}

// Init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { renderTestTable(); initLoincSearchInput(); });
} else {
    renderTestTable();
    initLoincSearchInput();
}
document.addEventListener('spa:navigated', function() { renderTestTable(); initLoincSearchInput(); });
</script>
