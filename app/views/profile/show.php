<?php /** Profile view */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-person-badge-fill"></i> <?= e($title) ?></h2>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-3">
                    <img src="<?= asset('img/logo.png') ?>" style="width:80px;height:80px;border-radius:50%;box-shadow:0 4px 14px rgba(108,99,255,0.3);">
                </div>
                <h5 class="fw-bold mb-1"><?= e($user['full_name']) ?></h5>
                <span class="badge bg-primary mb-2"><?= roleLabel($user['role']) ?></span>
                <div class="uid-code"><?= e($user['unique_id']) ?></div>
                <div class="small text-muted mt-2"><?= e($user['email']) ?></div>
                <?php if ($doctor && !empty($doctor['dept_name'])): ?>
                    <div class="small mt-1"><i class="bi bi-hospital"></i> <?= e($doctor['dept_name']) ?></div>
                <?php endif; ?>
                <?php if ($doctor && !empty($doctor['specialty'])): ?>
                    <div class="small"><i class="bi bi-stethoscope"></i> <?= e($doctor['specialty']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($medicalSummary)): ?>
        <!-- ⚡ Medical Summary for patients -->
        <div class="card mt-3">
            <div class="card-header gradient"><i class="bi bi-clipboard2-heart"></i> ملخص طبي</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td><i class="bi bi-clipboard2-data text-purple"></i> إجمالي التحاليل</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['total_tests'] ?></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-check2-circle text-success"></i> نتائج مكتملة</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['completed_tests'] ?></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-hourglass-split text-warning"></i> قيد التنفيذ</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['pending_tests'] ?></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-capsules text-pink"></i> خطط العلاج</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['treatments'] ?></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-calendar-check text-info"></i> المواعيد</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['appointments'] ?></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-calendar-event text-success"></i> مواعيد قادمة</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['upcoming_appointments'] ?></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-bell text-danger"></i> إشعارات غير مقروءة</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['unread_notifications'] ?></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-diagram-2 text-secondary"></i> إحالات</td>
                        <td class="fw-bold text-end"><?= $medicalSummary['referrals_received'] ?></td>
                    </tr>
                    <?php if (!empty($medicalSummary['last_visit'])): ?>
                    <tr>
                        <td><i class="bi bi-clock-history text-primary"></i> آخر زيارة</td>
                        <td class="small text-end"><?= formatDate($medicalSummary['last_visit'], true) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($user['workSummary'])): ?>
        <!-- ⚡ Work Summary for staff -->
        <div class="card mt-3">
            <div class="card-header gradient"><i class="bi bi-bar-chart"></i> ملخص الأداء</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <?php foreach ($user['workSummary'] as $label => $value): ?>
                        <tr>
                            <td><?= e($label) ?></td>
                            <td class="fw-bold text-end"><?= e($value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header gradient"><i class="bi bi-pencil"></i> تعديل البيانات</div>
            <div class="card-body">
                <form method="post" action="<?= url('/profile') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم الكامل</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                            <div class="form-text">لا يمكن تغيير البريد — تواصل مع المدير</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الموبايل</label>
                            <input type="text" name="phone" class="form-control" inputmode="numeric" pattern="[0-9]*" oninput="forceEnglishDigits(this)" value="<?= e($user['phone']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="address" class="form-control" value="<?= e($user['address']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">كلمة مرور جديدة (اختياري)</label>
                            <div class="input-group">
                                <input type="password" name="password" id="pw" class="form-control" placeholder="اتركها فارغة للإبقاء">
                                <button class="btn btn-eye" type="button" onclick="togglePassword('pw', this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الميلاد</label>
                            <input type="date" class="form-control" value="<?= e($user['birth_date']) ?>" disabled>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
