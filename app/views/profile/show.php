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
