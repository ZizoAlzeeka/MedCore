<?php /** Patient: result detail */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-file-medical"></i> <?= e($title) ?></h2>
        <div class="page-subtitle"><?= e($order['name_ar']) ?> — <span class="loinc-code"><?= e($order['loinc_code']) ?></span></div>
    </div>
    <a href="<?= url('/patient/results') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-right"></i> رجوع</a>
</div>

<div id="reportSection">
<div class="card mb-3">
    <div class="card-header gradient"><i class="bi bi-person"></i> بيانات المريض</div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><td>الاسم:</td><td class="fw-bold"><?= e($order['patient_name']) ?></td>
            <td>الرقم المميز:</td><td><span class="uid-code"><?= e($order['patient_uid']) ?></span></td></tr>
            <tr><td>الجنس:</td><td><?= genderLabel($order['gender']) ?></td>
            <td>تاريخ الميلاد:</td><td><?= formatDate($order['birth_date']) ?></td></tr>
            <tr><td>الهاتف:</td><td dir="ltr"><?= e($order['phone']) ?></td>
            <td>الطبيب:</td><td><?= e($order['doctor_name']) ?></td></tr>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header gradient"><i class="bi bi-clipboard2-data"></i> نتيجة التحليل</div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><td>التحليل:</td><td><span class="loinc-code"><?= e($order['loinc_code']) ?></span> <strong><?= e($order['name_ar']) ?></strong> (<?= e($order['name_en']) ?>)</td></tr>
            <tr><td>الفئة:</td><td><?= e($order['category']) ?></td></tr>
            <tr><td>نوع العينة:</td><td><?= e($order['sample_type']) ?></td></tr>
            <tr><td>القيمة:</td><td class="fw-bold fs-5 text-purple"><?= e($order['result_value']) ?> <?= e($order['unit']) ?></td></tr>
            <tr><td>النطاق الطبيعي:</td><td><?= e($order['normal_range']) ?></td></tr>
            <tr><td>العلم:</td><td><?= statusBadge($order['flag']) ?></td></tr>
            <tr><td>تاريخ التنفيذ:</td><td><?= formatDate($order['performed_at'], true) ?></td></tr>
            <tr><td>تاريخ الرفع:</td><td><?= formatDate($order['uploaded_at'], true) ?></td></tr>
            <tr><td>فني المختبر:</td><td><?= e($order['lab_tech_name']) ?></td></tr>
            <tr><td>تشخيص الطبيب (ICD):</td><td><?= e($order['diagnosis_icd'] ?: '-') ?></td></tr>
        </table>
    </div>
</div>
</div>

<div class="no-print">
    <button class="btn btn-primary" onclick="printToPDF('reportSection', 'تقرير-تحاليل-<?= e($order['patient_uid']) ?>.pdf')">
        <i class="bi bi-printer"></i> طباعة / تحميل PDF
    </button>
    <a href="<?= url('/patient/results') ?>" class="btn btn-secondary">رجوع للقائمة</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>
