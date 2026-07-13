<?php /** Admin: Reports */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-bar-chart-fill"></i> <?= e($title) ?></h2>
</div>

<!-- Stats summary -->
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-purple"><i class="bi bi-shield-check"></i></div>
            <div><div class="stat-value"><?= $dupStats['total'] ?></div><div class="stat-label">إجمالي التنبيهات</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-green"><i class="bi bi-check2-circle"></i></div>
            <div><div class="stat-value"><?= $dupStats['prevented'] ?></div><div class="stat-label">تحاليل ممنوعة</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-orange"><i class="bi bi-clipboard-check"></i></div>
            <div><div class="stat-value"><?= $ordersByStatus['ordered'] ?></div><div class="stat-label">بانتظار التنفيذ</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon bg-blue"><i class="bi bi-clipboard-data"></i></div>
            <div><div class="stat-value"><?= $ordersByStatus['result_uploaded'] ?></div><div class="stat-label">نتائج مرفوعة</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Orders by status -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart text-purple"></i> توزيع الطلبات حسب الحالة</div>
            <div class="card-body">
                <?php
                $total = max(array_sum($ordersByStatus), 1);
                foreach ($ordersByStatus as $status => $count):
                    $pct = round(($count / $total) * 100);
                    $colors = ['ordered'=>'warning','result_uploaded'=>'success','cancelled'=>'danger','duplicate_skipped'=>'info'];
                ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= statusLabel($status) ?></span>
                            <span class="fw-bold"><?= $count ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-<?= $colors[$status] ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Departments -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-diagram-3 text-purple"></i> الأطباء حسب القسم</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>القسم</th><th>عدد الأطباء</th><th>النسبة</th></tr></thead>
                    <tbody>
                        <?php
                        $maxDoctors = max(array_column($deptStats, 'doctors_count') ?: [1]);
                        foreach ($deptStats as $d):
                            $pct = round(($d['doctors_count'] / $maxDoctors) * 100);
                        ?>
                            <tr>
                                <td><?= e($d['name_ar']) ?></td>
                                <td class="fw-bold"><?= $d['doctors_count'] ?></td>
                                <td><div class="progress" style="height: 6px; min-width: 80px;"><div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div></div></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top doctors -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-trophy text-purple"></i> أكثر الأطباء طلباً للتحاليل</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>الطبيب</th><th>عدد الطلبات</th></tr></thead>
                    <tbody>
                        <?php foreach ($topDoctors as $i => $d): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= e($d['full_name']) ?></td>
                                <td class="fw-bold"><?= $d['orders_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topDoctors)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">لا بيانات</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent duplicate alerts -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bell text-pink"></i> سجل تنبيهات التكرار (آخر 50)</div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>التاريخ</th><th>المريض</th><th>التحليل</th><th>الطبيب</th><th>القرار</th><th>الأيام</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentAlerts as $a): ?>
                                <tr>
                                    <td class="small text-muted"><?= formatDate($a['created_at'], true) ?></td>
                                    <td><?= e($a['patient_name']) ?></td>
                                    <td><span class="loinc-code"><?= e($a['loinc_code']) ?></span> <?= e($a['test_name']) ?></td>
                                    <td class="small"><?= e($a['doctor_name'] ?: '-') ?></td>
                                    <td><?= statusBadge($a['doctor_decision']) ?></td>
                                    <td><?= $a['days_diff'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentAlerts)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">لا تنبيهات</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ⚡ NEW: Tests by Category -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-tags text-purple"></i> توزيع التحاليل حسب الفئة</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>الفئة</th><th>عدد التحاليل</th><th>النسبة</th></tr></thead>
                    <tbody>
                        <?php $maxCat = max(array_column($testsByCategory, 'cnt') ?: [1]); ?>
                        <?php foreach ($testsByCategory as $t): ?>
                            <tr>
                                <td><?= e($t['category']) ?></td>
                                <td class="fw-bold"><?= $t['cnt'] ?></td>
                                <td>
                                    <div class="progress" style="height: 6px; min-width: 80px;">
                                        <div class="progress-bar bg-info" style="width: <?= round(($t['cnt']/$maxCat)*100) ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($testsByCategory)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">لا بيانات</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ⚡ NEW: Orders by Department -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-diagram-3-fill text-purple"></i> الطلبات حسب القسم</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>القسم</th><th>عدد الطلبات</th><th>النسبة</th></tr></thead>
                    <tbody>
                        <?php $maxOrders = max(array_column($ordersByDept, 'orders_count') ?: [1]); ?>
                        <?php foreach ($ordersByDept as $d): ?>
                            <tr>
                                <td><?= e($d['name_ar']) ?></td>
                                <td class="fw-bold"><?= $d['orders_count'] ?></td>
                                <td>
                                    <div class="progress" style="height: 6px; min-width: 80px;">
                                        <div class="progress-bar bg-success" style="width: <?= round(($d['orders_count']/$maxOrders)*100) ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ordersByDept)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">لا بيانات بعد</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
