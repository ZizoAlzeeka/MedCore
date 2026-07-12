<?php /** Reception: appointments list */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-calendar-check-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">جميع المواعيد — يمكن الإلغاء</div>
    </div>
    <a href="<?= url('/reception/book') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> حجز جديد</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>المريض</th><th>الطبيب</th><th>القسم</th><th>الموعد</th><th>سجّلها</th><th>الحالة</th><th>إجراء</th></tr></thead>
                <tbody>
                    <?php foreach ($appts as $a): ?>
                        <tr>
                            <td><?= $a['id'] ?></td>
                            <td class="fw-bold"><?= e($a['patient_name']) ?> <span class="uid-code"><?= e($a['patient_uid']) ?></span></td>
                            <td class="small"><?= e($a['doctor_name']) ?></td>
                            <td><span class="badge bg-info"><?= e($a['department_name']) ?></span></td>
                            <td><?= formatDate($a['appt_date'], true) ?></td>
                            <td class="small text-muted"><?= e($a['receptionist_name']) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                            <td>
                                <?php if ($a['status'] === 'booked'): ?>
                                    <form method="post" action="<?= url('/reception/appointments/' . $a['id'] . '/cancel') ?>" style="display:inline" onsubmit="return confirm('إلغاء الموعد؟')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> إلغاء</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appts)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">لا مواعيد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
