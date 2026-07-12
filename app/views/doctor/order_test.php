<?php /** Doctor: order new test with duplicate detection */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-clipboard2-plus"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">المريض: <strong><?= e($patient['full_name']) ?></strong> — <span class="uid-code"><?= e($patient['unique_id']) ?></span></div>
    </div>
    <a href="<?= url('/doctor/patients/' . $patient['id']) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-right"></i> رجوع</a>
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
                    <input type="text" name="diagnosis_icd" class="form-control" placeholder="مثال: R10 (ألم بطني)">
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
            <table class="table table-sm mb-0">
                <thead><tr><th>التحليل</th><th>الحالة</th><th>النتيجة</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><span class="loinc-code"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                            <td><?= statusBadge($o['status']) ?></td>
                            <td class="small"><?= $o['result_value'] ? e($o['result_value']) . ' ' . e($o['unit']) : '-' ?></td>
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
                container.innerHTML = data.tests.map(t => `
                    <div class="border rounded p-2 mb-1 cursor-pointer test-item" onclick="selectTest(${t.id}, '${escapeHtml(t.loinc_code)}', '${escapeHtml(t.name_ar)}', '${escapeHtml(t.name_en || '')}', '${escapeHtml(t.sample_type || '')}')">
                        <span class="loinc-code">${t.loinc_code}</span>
                        <strong>${t.name_ar}</strong>
                        <span class="text-muted small">${t.name_en || ''}</span>
                        <span class="badge bg-info">${t.category || ''}</span>
                    </div>
                `).join('');
            });
    }, 300);
});

function selectTest(id, code, nameAr, nameEn, sample) {
    document.getElementById('test_id').value = id;
    document.getElementById('testInfo').innerHTML = `<span class="loinc-code">${code}</span> <strong>${nameAr}</strong> (${nameEn}) — عينة: ${sample}`;
    document.getElementById('orderForm').style.display = 'block';
    document.getElementById('duplicateAlert').innerHTML = '';
    document.getElementById('usePrevBtn').style.display = 'none';
    document.getElementById('decision').value = 'proceed';

    // Check duplicate
    fetch(`/ajax/check-duplicate?patient_id=${patientId}&test_id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.duplicate) {
                const prev = data.prev;
                const alertHtml = `
                    <div class="dup-alert">
                        <div class="dup-title"><i class="bi bi-exclamation-triangle-fill"></i> تنبيه: هذا التحليل تم إجراؤه مؤخراً!</div>
                        <div class="dup-msg">تم إجراء نفس التحليل قبل <strong>${prev.days_diff} يوم</strong> بتاريخ <strong>${prev.ordered_at}</strong>.</div>
                        <div class="prev-result">
                            <strong>النتيجة السابقة:</strong> ${prev.result_value} ${prev.unit}<br>
                            <strong>العلم:</strong> ${prev.flag}<br>
                            <strong>الطبيب:</strong> ${prev.doctor_name || '-'}
                        </div>
                    </div>
                `;
                document.getElementById('duplicateAlert').innerHTML = alertHtml;
                document.getElementById('usePrevBtn').style.display = 'inline-block';
            }
        });
}

function escapeHtml(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
</script>
