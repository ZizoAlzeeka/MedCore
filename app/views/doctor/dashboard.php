<?php /** Doctor dashboard */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-grid-1x2-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">مرحباً، <?= e(Auth::name()) ?> — مواعيد اليوم وآخر الطلبات</div>
    </div>
    <a href="<?= url('/doctor/patients') ?>" class="btn btn-primary btn-sm"><i class="bi bi-people"></i> مرضاي</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-blue"><i class="bi bi-calendar-day"></i></div>
            <div><div class="stat-value"><?= count($todayAppts) ?></div><div class="stat-label">مواعيد اليوم</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-purple"><i class="bi bi-people"></i></div>
            <div><div class="stat-value"><?= $totalPatients ?></div><div class="stat-label">عدد مرضاي</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-orange"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="stat-value"><?= $pendingOrders ?></div><div class="stat-label">طلبات بانتظار المختبر</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-green"><i class="bi bi-clipboard-check"></i></div>
            <div><div class="stat-value"><?= $uploadedToday ?></div><div class="stat-label">نتائج رفعت اليوم</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Today's appointments -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-check text-blue"></i> مواعيد اليوم</span>
                <a href="<?= url('/doctor/appointments') ?>" class="btn btn-sm btn-link">عرض الكل</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todayAppts)): ?>
                    <div class="empty-state"><i class="bi bi-calendar-x"></i><h5>لا مواعيد اليوم</h5></div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($todayAppts as $a): ?>
                            <a href="<?= url('/doctor/patients/' . $a['patient_id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small"><?= e($a['patient_name']) ?></div>
                                    <div class="text-muted" style="font-size:11px;">UID: <?= e($a['patient_uid']) ?> • <?= e($a['phone']) ?></div>
                                </div>
                                <span class="badge bg-primary" dir="ltr"><?= date('H:i', strtotime($a['appt_date'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent orders -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-clock-history text-purple"></i> آخر الطلبات</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>المريض</th><th>التحليل</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><a href="<?= url('/doctor/patients/' . $o['patient_id']) ?>" class="text-decoration-none fw-bold"><?= e($o['patient_name']) ?></a></td>
                                    <td class="small"><span class="loinc-code"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                                    <td><?= statusBadge($o['status']) ?></td>
                                    <td class="small text-muted"><?= formatDate($o['ordered_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentOrders)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">لا طلبات</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
