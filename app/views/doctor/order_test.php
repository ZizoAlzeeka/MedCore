<?php /** Doctor: order new test with duplicate detection + ICD-10 suggestions */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-clipboard2-plus"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">المريض: <strong><?= e($patient['full_name']) ?></strong> — <span class="uid-code"><?= e($patient['unique_id']) ?></span></div>
    </div>
    <a href="<?= url('/doctor/patients/' . $patient['id']) ?>" class="btn btn-secondary btn-sm spa-link" data-spa="1" data-url="<?= url('/doctor/patients/' . $patient['id']) ?>"><i class="bi bi-arrow-right"></i> رجوع</a>
</div>

<!-- Search catalog -->
<div class="card mb-3">
    <div class="card-header gradient"><i class="bi bi-search"></i> ابحث في كتالوج التحاليل (LOINC)</div>
    <div class="card-body">
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="testSearch" class="form-control" placeholder="اكتب اسم التحليل أو كود LOINC (حرفان على الأقل)..." autofocus>
        </div>
        <div id="testResults" style="max-height: 300px; overflow-y: auto;"></div>
    </div>
</div>

<!-- Selected test form (hidden until test selected) -->
<div class="card" id="orderForm" style="display:none;">
    <div class="card-header"><i class="bi bi-clipboard2-check text-purple"></i> تأكيد الطلب</div>
    <div class="card-body">
        <div id="duplicateAlert"></div>

        <form method="post" action="<?= url('/doctor/patients/' . $patient['id'] . '/order-test') ?>" id="orderFormEl">
            <?= csrf_field() ?>
            <input type="hidden" name="test_id" id="test_id" value="">
            <input type="hidden" name="decision" id="decision" value="proceed">

            <div class="mb-3">
                <label class="form-label">التحليل المختار</label>
                <div class="form-control bg-light" id="testInfo">—</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Diagnosis (ICD-10) <span class="text-muted">(optional)</span></label>
                    <div style="position:relative;">
                        <input type="text" name="diagnosis_icd" id="icdInput" class="form-control" dir="ltr" placeholder="Search: diabetes, headache, R10..." autocomplete="off">
                        <input type="hidden" id="icdSelectedCode" value="">
                        <div id="icdSuggestions" style="display:none;position:absolute;top:100%;right:0;left:0;z-index:1050;max-height:280px;overflow-y:auto;background:#fff;border:1px solid #e0e0e8;border-radius:0 0 10px 10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);"></div>
                    </div>
                    <small class="text-muted" id="icdDesc" style="margin-top:4px;display:block;"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ملاحظات</label>
                    <input type="text" name="notes" class="form-control" placeholder="ملاحظات للطبيب/المختبر">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="proceedBtn"><i class="bi bi-check-lg"></i> تأكيد الطلب</button>
                <button type="submit" class="btn btn-warning" id="usePrevBtn" style="display:none;" onclick="document.getElementById('decision').value='use_previous'">
                    <i class="bi bi-check2-circle"></i> الاكتفاء بالنتيجة السابقة
                </button>
                <a href="<?= url('/doctor/patients/' . $patient['id']) ?>" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<!-- Recent orders -->
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-clock-history text-purple"></i> آخر تحاليل المريض</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 medcore-table">
                <thead><tr><th>التحليل</th><th>الحالة</th><th>النتيجة</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><span class="loinc-code" dir="ltr"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                            <td><?= statusBadge($o['status']) ?></td>
                            <td class="small" dir="ltr" style="text-align:right;"><?= $o['result_value'] ? e($o['result_value']) . ' ' . e($o['unit']) : '-' ?></td>
                            <td class="small text-muted"><?= formatDate($o['ordered_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">لا تحاليل سابقة</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const patientId = <?= $patient['id'] ?>;
let searchTimer = null;

// ⚡ Live search for tests — reduced debounce from 300ms to 200ms
document.getElementById('testSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) {
        document.getElementById('testResults').innerHTML = '';
        return;
    }
    searchTimer = setTimeout(() => {
        fetch(`/ajax/tests/search?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('testResults');
                if (!data.tests || data.tests.length === 0) {
                    container.innerHTML = '<div class="text-muted text-center py-3"><i class="bi bi-info-circle"></i> لا نتائج</div>';
                    return;
                }
                container.innerHTML = data.tests.map(function(t, idx) {
                    // ⚡ Use data attribute + index-based lookup instead of inline JSON
                    // (avoids SyntaxError from unescaped characters in onclick)
                    return '<div class="border rounded p-2 mb-1 cursor-pointer test-item" data-test-idx="' + idx + '" onclick="selectTestByIdx(' + idx + ')">' +
                        '<span class="loinc-code">' + (t.loinc_code||'') + '</span> ' +
                        '<strong>' + (t.name_ar||'') + '</strong> ' +
                        '<span class="text-muted small">' + (t.name_en||'') + '</span> ' +
                        (t.category ? '<span class="badge bg-info">' + t.category + '</span>' : '') +
                        '</div>';
                }).join('');
                // Store tests data globally for lookup
                window._testSearchResults = data.tests;
            });
    }, 200);
});

function selectTestByIdx(idx) {
    var t = window._testSearchResults && window._testSearchResults[idx];
    if (!t) return;
    selectTest(t);
}

function selectTest(t) {
    document.getElementById('test_id').value = t.id;
    document.getElementById('testInfo').innerHTML = '<span class="loinc-code">' + (t.loinc_code||'') + '</span> <strong>' + (t.name_ar||'') + '</strong> (' + (t.name_en||'') + ') — عينة: ' + (t.sample_type||'-');
    document.getElementById('orderForm').style.display = 'block';
    document.getElementById('duplicateAlert').innerHTML = '';
    document.getElementById('usePrevBtn').style.display = 'none';
    document.getElementById('decision').value = 'proceed';

    // Check duplicate
    fetch('/ajax/check-duplicate?patient_id=' + patientId + '&test_id=' + t.id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.duplicate) {
                var prev = data.prev;
                var alertHtml = '<div class="dup-alert">' +
                    '<div class="dup-title"><i class="bi bi-exclamation-triangle-fill"></i> تنبيه: هذا التحليل تم إجراؤه مؤخراً!</div>' +
                    '<div class="dup-msg">تم إجراء نفس التحليل قبل <strong>' + prev.days_diff + ' يوم</strong> بتاريخ <strong>' + prev.ordered_at + '</strong>.</div>' +
                    '<div class="prev-result">' +
                    '<strong>النتيجة السابقة:</strong> <span dir="ltr">' + prev.result_value + ' ' + (prev.unit||'') + '</span><br>' +
                    '<strong>العلم:</strong> ' + prev.flag + '<br>' +
                    '<strong>الطبيب:</strong> ' + (prev.doctor_name || '-') +
                    '</div></div>';
                document.getElementById('duplicateAlert').innerHTML = alertHtml;
                document.getElementById('usePrevBtn').style.display = 'inline-block';
            }
        })
        .catch(function(err) { console.error('Duplicate check error:', err); });
}

// ⚡ ICD-10 live search via NLM Clinical Tables API (English only, LTR)
var icdInput = document.getElementById('icdInput');
var icdSuggestions = document.getElementById('icdSuggestions');
var icdDesc = document.getElementById('icdDesc');
var icdSelectedCode = document.getElementById('icdSelectedCode');
var icdTimer = null;

icdInput.addEventListener('input', function() {
    clearTimeout(icdTimer);
    var q = this.value.trim();
    icdSelectedCode.value = '';
    icdDesc.textContent = '';
    if (q.length < 2) { icdSuggestions.style.display = 'none'; return; }
    icdTimer = setTimeout(function() {
        icdSuggestions.innerHTML = '<div style="padding:8px;text-align:center;color:#636E72;font-size:12px;"><div class="spinner-border spinner-border-sm"></div> Searching ICD-10...</div>';
        icdSuggestions.style.display = 'block';
        fetch('/ajax/icd/search?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.results || data.results.length === 0) {
                    icdSuggestions.innerHTML = '<div style="padding:8px;text-align:center;color:#636E72;font-size:12px;">No ICD-10 codes found</div>';
                    return;
                }
                icdSuggestions.innerHTML = data.results.map(function(r, idx) {
                    return '<div class="icd-suggestion" data-idx="' + idx + '" onclick="selectICD(' + idx + ')">' +
                        '<strong dir="ltr">' + r.code + '</strong> — ' +
                        '<span dir="ltr">' + r.name + '</span></div>';
                }).join('');
                window._icdResults = data.results;
                icdSuggestions.style.display = 'block';
            })
            .catch(function() {
                icdSuggestions.innerHTML = '<div style="padding:8px;text-align:center;color:#dc3545;font-size:12px;">Search failed. Try typing the code directly.</div>';
            });
    }, 250);
});

icdInput.addEventListener('blur', function() {
    setTimeout(function() { icdSuggestions.style.display = 'none'; }, 200);
});

function selectICD(idx) {
    var r = window._icdResults && window._icdResults[idx];
    if (!r) return;
    icdSelectedCode.value = r.code;
    icdInput.value = r.code;
    icdDesc.textContent = r.name;
    icdDesc.style.color = '#0d9488';
    icdSuggestions.style.display = 'none';
}
</script>
