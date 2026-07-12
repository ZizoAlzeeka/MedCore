<?php /** Admin dashboard */
$dupRate = $stats['orders_today'] > 0 ? round(($dupStats['prevented'] / max($stats['orders_today'], 1)) * 100, 1) : 0;
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-grid-1x2-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">مرحباً، <?= e(Auth::name()) ?> — نظرة عامة على النظام</div>
    </div>
    <div>
        <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> مستخدم جديد</a>
        <a href="<?= url('/admin/tests') ?>" class="btn btn-info btn-sm"><i class="bi bi-clipboard2-pulse"></i> كتالوج التحاليل</a>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-purple"><i class="bi bi-people"></i></div>
            <div><div class="stat-value"><?= $stats['doctors'] ?></div><div class="stat-label">أطباء</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-blue"><i class="bi bi-person-heart"></i></div>
            <div><div class="stat-value"><?= $stats['patients'] ?></div><div class="stat-label">مرضى</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-pink"><i class="bi bi-clipboard2-pulse"></i></div>
            <div><div class="stat-value"><?= $stats['tests'] ?></div><div class="stat-label">تحاليل في الكتالوج</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-green"><i class="bi bi-diagram-3"></i></div>
            <div><div class="stat-value"><?= $stats['departments'] ?></div><div class="stat-label">أقسام طبية</div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-orange"><i class="bi bi-calendar-day"></i></div>
            <div><div class="stat-value"><?= $stats['appointments_today'] ?></div><div class="stat-label">مواعيد اليوم</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-teal"><i class="bi bi-clipboard-check"></i></div>
            <div><div class="stat-value"><?= $stats['orders_today'] ?></div><div class="stat-label">طلبات اليوم</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-purple"><i class="bi bi-shield-check"></i></div>
            <div><div class="stat-value"><?= $dupStats['prevented'] ?></div><div class="stat-label">تحاليل ممنوعة</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-blue"><i class="bi bi-percent"></i></div>
            <div><div class="stat-value"><?= $dupRate ?>%</div><div class="stat-label">نسبة المنع</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent alerts -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bell text-pink"></i> آخر تنبيهات التكرار</span>
                <a href="<?= url('/admin/reports') ?>" class="btn btn-sm btn-link">عرض الكل</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentAlerts)): ?>
                    <div class="empty-state"><i class="bi bi-bell-slash"></i><h5>لا تنبيهات بعد</h5><p>ستظهر هنا تنبيهات كشف التكرار</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>المريض</th><th>التحليل</th><th>القرار</th><th>الأيام</th><th>التاريخ</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentAlerts as $a): ?>
                                <tr>
                                    <td><?= e($a['patient_name']) ?></td>
                                    <td><span class="loinc-code"><?= e($a['loinc_code']) ?></span> <?= e($a['test_name']) ?></td>
                                    <td><?= statusBadge($a['doctor_decision']) ?></td>
                                    <td><?= $a['days_diff'] ?> يوم</td>
                                    <td class="small text-muted"><?= formatDate($a['created_at'], true) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent users -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-clock text-purple"></i> أحدث المستخدمين</span>
                <a href="<?= url('/admin/users') ?>" class="btn btn-sm btn-link">عرض الكل</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentUsers as $u): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small"><?= e($u['full_name']) ?></div>
                                <div class="text-muted" style="font-size:11px;"><?= e($u['email']) ?> • <span class="uid-code"><?= e($u['unique_id']) ?></span></div>
                            </div>
                            <span class="badge bg-primary"><?= roleLabel($u['role']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
