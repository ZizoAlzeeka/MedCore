<?php /** Doctor: schedule management */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-clock-history"></i> <?= e($title) ?></h2>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header gradient"><i class="bi bi-plus-circle"></i> إضافة فترة دوام</div>
            <div class="card-body">
                <form method="post" action="<?= url('/doctor/schedule/store') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="work_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">من <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" required value="09:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">إلى <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required value="13:00">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">مدة الكشف (دقائق)</label>
                        <input type="text" name="slot_duration_min" class="form-control" inputmode="numeric" pattern="[0-9]*" oninput="forceEnglishDigits(this)" value="20">
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> إضافة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-week text-purple"></i> فترات الدوام (<?= count($schedules) ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>التاريخ</th><th>اليوم</th><th>من</th><th>إلى</th><th>مدة الكشف</th><th>الحالة</th><th>إجراء</th></tr></thead>
                        <tbody>
                            <?php
                            $daysMap = ['sat'=>'السبت','sun'=>'الأحد','mon'=>'الإثنين','tue'=>'الثلاثاء','wed'=>'الأربعاء','thu'=>'الخميس','fri'=>'الجمعة'];
                            foreach ($schedules as $s):
                            ?>
                                <tr>
                                    <td class="fw-bold"><?= formatDate($s['work_date']) ?></td>
                                    <td><?= $daysMap[$s['day_of_week']] ?? $s['day_of_week'] ?></td>
                                    <td dir="ltr"><?= $s['start_time'] ?></td>
                                    <td dir="ltr"><?= $s['end_time'] ?></td>
                                    <td><?= $s['slot_duration_min'] ?> د</td>
                                    <td>
                                        <?php if ($s['is_available']): ?>
                                            <span class="badge bg-success">متاح</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">معطّل</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= url('/doctor/schedule/' . $s['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('حذف الفترة؟')">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($schedules)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">لا فترات دوام — أضف فترة جديدة</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
