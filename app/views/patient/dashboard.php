<?php /** Patient dashboard */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-grid-1x2-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">مرحباً، <?= e(Auth::name()) ?> — رقمك المميز: <span class="uid-code"><?= e(Auth::uniqueId()) ?></span></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-purple"><i class="bi bi-clipboard2-pulse"></i></div>
            <div><div class="stat-value"><?= $stats['total_tests'] ?></div><div class="stat-label">إجمالي التحاليل</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-orange"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">قيد التنفيذ</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-green"><i class="bi bi-check2-circle"></i></div>
            <div><div class="stat-value"><?= $stats['completed'] ?></div><div class="stat-label">نتائج جاهزة</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-pink"><i class="bi bi-capsules"></i></div>
            <div><div class="stat-value"><?= $stats['treatments'] ?></div><div class="stat-label">خطط العلاج</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent results -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clipboard2-data text-purple"></i> آخر التحاليل</span>
                <a href="<?= url('/patient/results') ?>" class="btn btn-sm btn-link">عرض الكل</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>التحليل</th><th>الحالة</th><th>النتيجة</th><th>التاريخ</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><span class="loinc-code" dir="ltr"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                                    <td><?= statusBadge($o['status']) ?></td>
                                    <td class="small" dir="ltr" style="text-align:right;"><?= $o['result_value'] ? e($o['result_value']) . ' ' . e($o['unit']) : '-' ?></td>
                                    <td class="small text-muted"><?= formatDate($o['ordered_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentOrders)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">لا تحاليل بعد</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest treatment -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-prescription2 text-pink"></i> آخر خطة علاج</div>
            <div class="card-body">
                <?php if ($latestTreatment): ?>
                    <h6 class="text-purple fw-bold"><?= e($latestTreatment['treatment_name']) ?></h6>
                    <div class="small text-muted mb-2">بقلم <?= e($latestTreatment['doctor_name']) ?> — <?= formatDate($latestTreatment['created_at'], true) ?></div>
                    <div class="treatment-display"><?= $latestTreatment['description_html'] ?></div>
                    <a href="<?= url('/patient/treatment') ?>" class="btn btn-sm btn-primary mt-2"><i class="bi bi-eye"></i> عرض الكل</a>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-capsule"></i><h5>لا توجد خطة علاج</h5><p>ستظهر هنا بعد زيارتك للطبيب</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming appointments -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-check text-blue"></i> مواعيدي القادمة</div>
            <div class="card-body p-0">
                <?php if (empty($upcomingAppts)): ?>
                    <div class="empty-state"><i class="bi bi-calendar-x"></i><h5>لا مواعيد قادمة</h5><p>لحجز موعد، تواصل مع موظف الاستقبال في المستشفى</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>الطبيب</th><th>القسم</th><th>الموعد</th><th>الحالة</th></tr></thead>
                            <tbody>
                                <?php foreach ($upcomingAppts as $a): ?>
                                    <tr>
                                        <td class="fw-bold"><?= e($a['doctor_name']) ?></td>
                                        <td><span class="badge bg-info"><?= e($a['dept_name']) ?></span></td>
                                        <td><?= formatDate($a['appt_date'], true) ?></td>
                                        <td><?= statusBadge($a['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
