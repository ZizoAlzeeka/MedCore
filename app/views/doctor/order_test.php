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
                    <label class="form-label">التشخيص المبدئي (ICD-10) <span class="text-muted">(اختياري)</span></label>
                    <div style="position:relative;">
                        <input type="text" name="diagnosis_icd" id="icdInput" class="form-control" placeholder="ابحث: ألم بطني، سكري،..." autocomplete="off">
                        <div id="icdSuggestions" style="display:none;position:absolute;top:100%;right:0;left:0;z-index:1050;max-height:240px;overflow-y:auto;background:#fff;border:1px solid #e0e0e8;border-radius:0 0 10px 10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);"></div>
                    </div>
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

// ⚡ ICD-10 live suggestions
var icdData = [
    {code:'R10', label:'ألم بطني'},
    {code:'R51', label:'صداع'},
    {code:'E11.9', label:'سكري من النوع 2'},
    {code:'I10', label:'ارتفاع ضغط الدم'},
    {code:'J00', label:'زكام حاد'},
    {code:'J45.9', label:'ربو'},
    {code:'K21.9', label:'ارتجاع المريء'},
    {code:'K76.9', label:'مرض كبدي'},
    {code:'N17.9', label:'فشل كلوي حاد'},
    {code:'N39.0', label:'التهاب مسالك بولية'},
    {code:'M54.5', label:'ألم أسفل الظهر'},
    {code:'M25.5', label:'ألم في المفصل'},
    {code:'D50.9', label:'فقر دم'},
    {code:'E78.5', label:'ارتفاع الكوليسترول'},
    {code:'E03.9', label:'قصور الغدة الدرقية'},
    {code:'E05.9', label:'فرط نشاط الغدة الدرقية'},
    {code:'L20.9', label:'إكزيما'},
    {code:'L30.9', label:'التهاب جلد'},
    {code:'H10.9', label:'التهاب ملتحمة العين'},
    {code:'H52.4', label:'ضعف النظر'},
    {code:'F41.1', label:'قلق عام'},
    {code:'F32.9', label:'اكتئاب'},
    {code:'R42', label:'دوخة'},
    {code:'R05', label:'سعال'},
    {code:'R50.9', label:'حمى'},
    {code:'R19.0', label:'ألم بطن علوي'},
    {code:'K59.0', label:'إمساك'},
    {code:'K52.9', label:'إسهال'},
    {code:'I63.9', label:'سكتة دماغية'},
    {code:'I50.9', label:'فشل قلبي'},
];

var icdInput = document.getElementById('icdInput');
var icdSuggestions = document.getElementById('icdSuggestions');

icdInput.addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    if (q.length < 1) { icdSuggestions.style.display = 'none'; return; }
    var matches = icdData.filter(function(item) {
        return item.code.toLowerCase().includes(q) || item.label.toLowerCase().includes(q);
    }).slice(0, 10);
    if (matches.length === 0) { icdSuggestions.style.display = 'none'; return; }
    icdSuggestions.innerHTML = matches.map(function(m) {
        return '<div class="icd-suggestion" onclick="selectICD(\'' + m.code + '\')"><strong>' + m.code + '</strong> — ' + m.label + '</div>';
    }).join('');
    icdSuggestions.style.display = 'block';
});

icdInput.addEventListener('blur', function() {
    setTimeout(function() { icdSuggestions.style.display = 'none'; }, 200);
});

function selectICD(code) {
    icdInput.value = code;
    icdSuggestions.style.display = 'none';
}
</script>
