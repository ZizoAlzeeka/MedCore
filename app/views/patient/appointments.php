<?php /** Patient: appointments list */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-calendar-check-fill"></i> <?= e($title) ?></h2>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> لحجز موعد جديد، يرجى مراجعة موظف الاستقبال في المستشفى — الحجز يتم حصرياً عبر الاستقبال.
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>الطبيب</th><th>القسم</th><th>الموعد</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php foreach ($appts as $a): ?>
                        <tr>
                            <td><?= $a['id'] ?></td>
                            <td class="fw-bold"><?= e($a['doctor_name']) ?></td>
                            <td><span class="badge bg-info"><?= e($a['department_name']) ?></span></td>
                            <td><?= formatDate($a['appt_date'], true) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appts)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">لا مواعيد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
