<?php /** Lab Tech dashboard */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-grid-1x2-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">مرحباً، <?= e(Auth::name()) ?> — الطلبات الواردة للمختبر</div>
    </div>
    <a href="<?= url('/labtech/orders') ?>" class="btn btn-primary btn-sm"><i class="bi bi-list-task"></i> كل الطلبات</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4 col-6">
        <div class="stat-card">
            <div class="icon bg-orange"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">بانتظار التنفيذ</div></div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="stat-card">
            <div class="icon bg-green"><i class="bi bi-upload"></i></div>
            <div><div class="stat-value"><?= $stats['uploaded_today'] ?></div><div class="stat-label">رفعت اليوم</div></div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="stat-card">
            <div class="icon bg-purple"><i class="bi bi-check2-all"></i></div>
            <div><div class="stat-value"><?= $stats['uploaded_total'] ?></div><div class="stat-label">إجمالي مرفوع</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-task text-purple"></i> الطلبات بانتظار التنفيذ</div>
    <div class="card-body p-0">
        <?php if (empty($pending)): ?>
            <div class="empty-state"><i class="bi bi-check-circle"></i><h5>لا طلبات معلقة</h5><p>جميع الطلبات تمت معالجتها</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>المريض</th><th>التحليل</th><th>العينة</th><th>الطبيب</th><th>التاريخ</th><th>إجراء</th></tr></thead>
                    <tbody>
                        <?php foreach ($pending as $o): ?>
                            <tr>
                                <td class="fw-bold"><?= e($o['patient_name']) ?> <span class="uid-code"><?= e($o['patient_uid']) ?></span></td>
                                <td><span class="loinc-code"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                                <td><span class="badge bg-info"><?= e($o['sample_type']) ?></span></td>
                                <td class="small"><?= e($o['doctor_name']) ?></td>
                                <td class="small text-muted"><?= formatDate($o['ordered_at']) ?></td>
                                <td>
                                    <a href="<?= url('/labtech/orders/' . $o['id'] . '/upload') ?>" class="btn btn-sm btn-success"><i class="bi bi-upload"></i> رفع النتيجة</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
