<?php /** Reception: doctor schedules */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-clock-fill"></i> <?= e($title) ?></h2>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label">اختر الطبيب</label>
                <select name="doctor_id" class="form-select" onchange="this.form.submit()">
                    <option value="">— اختر —</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $doctorId==$d['id']?'selected':'' ?>><?= e($d['full_name']) ?> — <?= e($d['department_name']) ?> (<?= e($d['specialty'] ?: '') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-info w-100"><i class="bi bi-eye"></i> عرض الجدول</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedDoctor): ?>
<div class="card">
    <div class="card-header gradient">
        <i class="bi bi-calendar-week"></i> جدول دوام: <strong><?= e($selectedDoctor['full_name']) ?></strong> — <?= e($selectedDoctor['dept']) ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>التاريخ</th><th>اليوم</th><th>من</th><th>إلى</th><th>مدة الكشف</th><th>الحالة</th></tr></thead>
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
                            <td><?= $s['slot_duration_min'] ?> دقيقة</td>
                            <td>
                                <?php if ($s['is_available']): ?>
                                    <span class="badge bg-success">متاح للحجز</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">معطّل</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($schedules)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">لا فترات دوام مسجّلة لهذا الطبيب</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
