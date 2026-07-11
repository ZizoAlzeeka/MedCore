<?php /** Lab Tech: orders list by status */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-list-task"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">تصفية الطلبات حسب الحالة</div>
    </div>
</div>

<div class="mb-3 d-flex gap-1 flex-wrap">
    <a href="<?= url('/labtech/orders?status=ordered') ?>" class="btn btn-sm <?= $status==='ordered'?'btn-warning':'btn-outline-warning' ?>">بانتظار التنفيذ</a>
    <a href="<?= url('/labtech/orders?status=result_uploaded') ?>" class="btn btn-sm <?= $status==='result_uploaded'?'btn-success':'btn-outline-success' ?>">مرفوعة النتائج</a>
    <a href="<?= url('/labtech/orders?status=cancelled') ?>" class="btn btn-sm <?= $status==='cancelled'?'btn-danger':'btn-outline-danger' ?>">ملغاة</a>
    <a href="<?= url('/labtech/orders?status=duplicate_skipped') ?>" class="btn btn-sm <?= $status==='duplicate_skipped'?'btn-secondary':'btn-outline-secondary' ?>">اكتفاء بالسابق</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>المريض</th><th>التحليل</th><th>العينة</th><th>الطبيب</th><th>التاريخ</th><th>إجراء</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td class="fw-bold"><?= e($o['patient_name']) ?> <span class="uid-code"><?= e($o['patient_uid']) ?></span></td>
                            <td><span class="loinc-code"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                            <td><span class="badge bg-info"><?= e($o['sample_type']) ?></span></td>
                            <td class="small"><?= e($o['doctor_name']) ?></td>
                            <td class="small text-muted"><?= formatDate($o['ordered_at']) ?></td>
                            <td>
                                <?php if ($status === 'ordered'): ?>
                                    <a href="<?= url('/labtech/orders/' . $o['id'] . '/upload') ?>" class="btn btn-sm btn-success"><i class="bi bi-upload"></i> رفع</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">لا طلبات بهذه الحالة</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
