<?php /** Lab Tech: upload result form */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-upload"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">المريض: <strong><?= e($order['patient_name']) ?></strong> — <span class="loinc-code"><?= e($order['loinc_code']) ?></span> <?= e($order['name_ar']) ?></div>
    </div>
    <a href="<?= url('/labtech/orders') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-right"></i> رجوع</a>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-info-circle text-purple"></i> تفاصيل الطلب</div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><td>المريض:</td><td class="fw-bold"><?= e($order['patient_name']) ?> <span class="uid-code"><?= e($order['patient_uid']) ?></span></td>
            <td>الهاتف:</td><td dir="ltr"><?= e($order['phone']) ?></td></tr>
            <tr><td>التحليل:</td><td><span class="loinc-code"><?= e($order['loinc_code']) ?></span> <?= e($order['name_ar']) ?> (<?= e($order['name_en']) ?>)</td>
            <td>الفئة:</td><td><?= e($order['category']) ?></td></tr>
            <tr><td>العينة:</td><td><span class="badge bg-info"><?= e($order['sample_type']) ?></span></td>
            <td>الطبيب:</td><td><?= e($order['doctor_name']) ?></td></tr>
            <tr><td>ICD-10 Diagnosis:</td><td dir="ltr" class="text-end"><?= e($order['diagnosis_icd'] ?: '-') ?></td>
            <td>تاريخ الطلب:</td><td class="small"><?= formatDate($order['ordered_at'], true) ?></td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header gradient"><i class="bi bi-pencil-square"></i> إدخال نتيجة التحليل</div>
    <div class="card-body">
        <form method="post" action="<?= url('/labtech/orders/' . $order['id'] . '/upload') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">قيمة النتيجة <span class="text-danger">*</span></label>
                    <input type="text" name="result_value" class="form-control" required placeholder="مثال: 12.5">
                </div>
                <div class="col-md-3">
                    <label class="form-label">الوحدة</label>
                    <input type="text" name="unit" class="form-control" placeholder="g/dL, mg/L, ...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">النطاق الطبيعي</label>
                    <input type="text" name="normal_range" class="form-control" placeholder="11.0 - 16.0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">العلم (Flag) <span class="text-danger">*</span></label>
                    <select name="flag" class="form-select" required>
                        <option value="normal">طبيعي (Normal)</option>
                        <option value="high">مرتفع (High)</option>
                        <option value="low">منخفض (Low)</option>
                        <option value="abnormal">غير طبيعي (Abnormal)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">تاريخ التنفيذ</label>
                    <input type="datetime-local" name="performed_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ملاحظات</label>
                    <input type="text" name="notes" class="form-control" placeholder="اختياري">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-cloud-upload"></i> رفع النتيجة وإشعار المريض والطبيب</button>
                <a href="<?= url('/labtech/orders') ?>" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
