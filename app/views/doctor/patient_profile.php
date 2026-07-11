<?php /** Doctor: patient profile */
$age = $patient['birth_date'] ? date('Y') - date('Y', strtotime($patient['birth_date'])) : '-';
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-person-vcard"></i> <?= e($title) ?></h2>
        <div class="page-subtitle"><?= e($patient['full_name']) ?> — <span class="uid-code"><?= e($patient['unique_id']) ?></span></div>
    </div>
    <div class="d-flex gap-1">
        <a href="<?= url('/doctor/patients/' . $patient['id'] . '/order-test') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> طلب تحليل</a>
    </div>
</div>

<div class="row g-3">
    <!-- Patient info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header gradient"><i class="bi bi-person"></i> بيانات المريض</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">الاسم:</td><td class="fw-bold"><?= e($patient['full_name']) ?></td></tr>
                    <tr><td class="text-muted">الرقم المميز:</td><td><span class="uid-code"><?= e($patient['unique_id']) ?></span></td></tr>
                    <tr><td class="text-muted">الجنس:</td><td><?= genderLabel($patient['gender']) ?></td></tr>
                    <tr><td class="text-muted">العمر:</td><td><?= $age ?> سنة</td></tr>
                    <tr><td class="text-muted">الهاتف:</td><td dir="ltr"><?= e($patient['phone']) ?></td></tr>
                    <tr><td class="text-muted">العنوان:</td><td><?= e($patient['address']) ?></td></tr>
                    <tr><td class="text-muted">البريد:</td><td class="small"><?= e($patient['email']) ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Refer patient -->
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-arrow-left-right text-pink"></i> إحالة لطبيب آخر</div>
            <div class="card-body">
                <form method="post" action="<?= url('/doctor/patients/' . $patient['id'] . '/refer') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">الطبيب</label>
                        <select name="to_doctor_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            <?php
                            $allDoctors = (new User())->doctors();
                            foreach ($allDoctors as $d):
                                if ($d['id'] == Database::fetchColumn("SELECT id FROM doctors WHERE user_id = ?", [Auth::id()])) continue;
                            ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?> — <?= e($d['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">السبب</label>
                        <textarea name="reason" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-sm btn-warning w-100"><i class="bi bi-send"></i> إحالة</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: history -->
    <div class="col-lg-8">
        <!-- Test orders -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clipboard2-pulse text-purple"></i> تاريخ التحاليل (<?= count($orders) ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>التحليل</th><th>الحالة</th><th>النتيجة</th><th>العلم</th><th>التاريخ</th><th>إجراء</th></tr></thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><span class="loinc-code"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                                    <td><?= statusBadge($o['status']) ?></td>
                                    <td class="small"><?= $o['result_value'] ? e($o['result_value']) . ' ' . e($o['unit']) : '-' ?></td>
                                    <td><?= $o['flag'] ? statusBadge($o['flag']) : '-' ?></td>
                                    <td class="small text-muted"><?= formatDate($o['ordered_at']) ?></td>
                                    <td>
                                        <?php if ($o['status'] === 'result_uploaded'): ?>
                                            <a href="<?= url('/doctor/orders/' . $o['id'] . '/treatment') ?>" class="btn btn-sm btn-success"><i class="bi bi-capsules"></i> خطة علاج</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">لا تحاليل سابقة</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Treatment plans -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-capsules text-purple"></i> خطط العلاج (<?= count($treatments) ?>)</div>
            <div class="card-body">
                <?php if (empty($treatments)): ?>
                    <div class="text-muted small"><i class="bi bi-info-circle"></i> لا خطط علاج بعد</div>
                <?php else: foreach ($treatments as $t): ?>
                    <div class="border rounded p-3 mb-2">
                        <div class="fw-bold text-purple"><?= e($t['treatment_name']) ?></div>
                        <div class="small text-muted">بتاريخ <?= formatDate($t['created_at'], true) ?> — بواسطة <?= e($t['doctor_name']) ?></div>
                        <div class="treatment-display mt-2"><?= $t['description_html'] ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Referrals -->
        <?php if (!empty($referrals)): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-arrow-left-right text-pink"></i> الإحالات</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>التاريخ</th><th>من</th><th>إلى</th><th>السبب</th></tr></thead>
                    <tbody>
                        <?php foreach ($referrals as $r): ?>
                            <tr>
                                <td class="small"><?= formatDate($r['referred_at']) ?></td>
                                <td class="small"><?= e($r['from_doctor']) ?> (<?= e($r['from_dept']) ?>)</td>
                                <td class="small"><?= e($r['to_doctor']) ?> (<?= e($r['to_dept']) ?>)</td>
                                <td class="small"><?= e($r['reason']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
