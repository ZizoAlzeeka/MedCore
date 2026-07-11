<?php /** Reception dashboard */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-grid-1x2-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">مرحباً، <?= e(Auth::name()) ?> — نظرة عامة على المواعيد</div>
    </div>
    <a href="<?= url('/reception/book') ?>" class="btn btn-primary btn-sm"><i class="bi bi-calendar-plus"></i> حجز موعد جديد</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-blue"><i class="bi bi-calendar-day"></i></div>
            <div><div class="stat-value"><?= $stats['appointments_today'] ?></div><div class="stat-label">مواعيد اليوم</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-purple"><i class="bi bi-people"></i></div>
            <div><div class="stat-value"><?= $stats['total_patients'] ?></div><div class="stat-label">إجمالي المرضى</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-pink"><i class="bi bi-person-badge"></i></div>
            <div><div class="stat-value"><?= $stats['total_doctors'] ?></div><div class="stat-label">الأطباء</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-green"><i class="bi bi-diagram-3"></i></div>
            <div><div class="stat-value"><?= $stats['departments'] ?></div><div class="stat-label">الأقسام</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-check text-blue"></i> مواعيد اليوم</span>
        <a href="<?= url('/reception/appointments') ?>" class="btn btn-sm btn-link">عرض الكل</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($todayAppts)): ?>
            <div class="empty-state"><i class="bi bi-calendar-x"></i><h5>لا مواعيد اليوم</h5><p>احجز موعداً جديداً للمرضى</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>المريض</th><th>الهاتف</th><th>الطبيب</th><th>القسم</th><th>الموعد</th><th>الحالة</th></tr></thead>
                    <tbody>
                        <?php foreach ($todayAppts as $a): ?>
                            <tr>
                                <td class="fw-bold"><?= e($a['patient_name']) ?> <span class="uid-code"><?= e($a['patient_uid']) ?></span></td>
                                <td class="small" dir="ltr"><?= e($a['phone']) ?></td>
                                <td class="small"><?= e($a['doctor_name']) ?></td>
                                <td><span class="badge bg-info"><?= e($a['dept_name']) ?></span></td>
                                <td dir="ltr"><?= date('H:i', strtotime($a['appt_date'])) ?></td>
                                <td><?= statusBadge($a['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
