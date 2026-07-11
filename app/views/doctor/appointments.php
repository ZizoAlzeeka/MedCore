<?php /** Doctor: appointments list */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-calendar-check-fill"></i> <?= e($title) ?></h2>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>#</th><th>المريض</th><th>الهاتف</th><th>الموعد</th><th>الحالة</th><th>إجراء</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($appts as $a): ?>
                        <tr>
                            <td><?= $a['id'] ?></td>
                            <td class="fw-bold"><?= e($a['patient_name']) ?> <span class="uid-code"><?= e($a['patient_uid']) ?></span></td>
                            <td class="small" dir="ltr"><?= e($a['phone']) ?></td>
                            <td><?= formatDate($a['appt_date'], true) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                            <td>
                                <a href="<?= url('/doctor/patients/' . $a['patient_id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-folder"></i> الملف</a>
                                <?php if ($a['status'] === 'booked'): ?>
                                    <a href="<?= url('/doctor/patients/' . $a['patient_id'] . '/order-test') ?>" class="btn btn-sm btn-info"><i class="bi bi-plus"></i> طلب تحليل</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appts)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">لا مواعيد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
