<?php /** Doctor: treatment plan form (Quill) */
$extraScripts = '
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    initQuill("editor-container", "description_html");
});
</script>
';
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-capsules"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">المريض: <strong><?= e($order['patient_name']) ?></strong> — التحليل: <span class="loinc-code"><?= e($order['loinc_code']) ?></span> <?= e($order['name_ar']) ?></div>
    </div>
    <a href="<?= url('/doctor/patients/' . $order['patient_id']) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-right"></i> رجوع</a>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-clipboard2-data text-purple"></i> نتيجة التحليل</div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><td>القيمة:</td><td class="fw-bold"><?= e($order['result_value']) ?> <?= e($order['unit']) ?></td>
            <td>النطاق الطبيعي:</td><td><?= e($order['normal_range']) ?></td></tr>
            <tr><td>العلم:</td><td><?= statusBadge($order['flag']) ?></td>
            <td>تاريخ التنفيذ:</td><td><?= formatDate($order['performed_at'], true) ?></td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header gradient"><i class="bi bi-pencil-square"></i> كتابة خطة العلاج</div>
    <div class="card-body">
        <form method="post" action="<?= url('/doctor/orders/' . $order['id'] . '/treatment') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">اسم العلاج / الدواء <span class="text-danger">*</span></label>
                <input type="text" name="treatment_name" class="form-control" required placeholder="مثال: أوميبرازول 20mg">
            </div>
            <div class="mb-3">
                <label class="form-label">الوصف وطريقة الاستخدام <span class="text-danger">*</span></label>
                <div id="editor-container"></div>
                <input type="hidden" name="description_html" id="description_html">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> حفظ خطة العلاج</button>
                <a href="<?= url('/doctor/patients/' . $order['patient_id']) ?>" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
