<?php /** Admin: Tests Catalog */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-clipboard2-pulse-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">قاعدة بيانات التحاليل بمعيار LOINC — يستخدمها الأطباء عند الطلب</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#testModal" onclick="resetTestForm()">
        <i class="bi bi-plus-circle"></i> إضافة تحليل
    </button>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-9">
                <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="بحث بالاسم العربي أو الإنجليزي أو كود LOINC أو الفئة">
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100"><i class="bi bi-search"></i> بحث</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>كود LOINC</th>
                        <th>الاسم (عربي)</th>
                        <th>الاسم (إنجليزي)</th>
                        <th>الفئة</th>
                        <th>نوع العينة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><span class="loinc-code"><?= e($t['loinc_code']) ?></span></td>
                            <td class="fw-bold"><?= e($t['name_ar']) ?></td>
                            <td class="small" dir="ltr"><?= e($t['name_en']) ?></td>
                            <td><span class="badge bg-info"><?= e($t['category']) ?></span></td>
                            <td class="small"><?= e($t['sample_type']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick='editTest(<?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>)'><i class="bi bi-pencil"></i></button>
                                <form method="post" action="<?= url('/admin/tests/' . $t['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('تأكيد الحذف؟')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tests)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">لا تحاليل</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="testForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="test_id">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="testModalTitle">إضافة تحليل</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">كود LOINC <span class="text-danger">*</span></label>
                        <input type="text" name="loinc_code" id="test_loinc" class="form-control" required placeholder="مثال: 6690-2">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                        <input type="text" name="name_ar" id="test_name_ar" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" id="test_name_en" class="form-control" dir="ltr">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">الفئة</label>
                        <input type="text" name="category" id="test_cat" class="form-control" placeholder="أمراض دم / كيمياء حيوية / ...">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">نوع العينة</label>
                        <input type="text" name="sample_type" id="test_sample" class="form-control" placeholder="دم / مصل / بلازما / بول">
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
function resetTestForm() {
    document.getElementById('testForm').action = '<?= url("/admin/tests/store") ?>';
    document.getElementById('test_id').value = '';
    document.getElementById('test_loinc').value = '';
    document.getElementById('test_name_ar').value = '';
    document.getElementById('test_name_en').value = '';
    document.getElementById('test_cat').value = '';
    document.getElementById('test_sample').value = '';
    document.getElementById('testModalTitle').textContent = 'إضافة تحليل';
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
    new bootstrap.Modal(document.getElementById('testModal')).show();
}
</script>
