<?php /** Admin: User form (create/edit) */
$isEdit = isset($user);
$u = $user ?? [];
?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-<?= $isEdit ? 'pencil' : 'person-plus' ?>"></i> <?= e($title) ?></h2>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? url('/admin/users/' . $u['id'] . '/update') : url('/admin/users/store') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required value="<?= e($u['full_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required value="<?= e($u['email'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">كلمة المرور <span class="text-danger">*</span> <?= $isEdit ? '(اتركها فارغة للإبقاء)' : '' ?></label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" <?= $isEdit ? '' : 'required minlength="6"' ?> placeholder="<?= $isEdit ? '••••••' : '6 أحرف على الأقل' ?>">
                        <button class="btn btn-eye" type="button" onclick="togglePassword('password', this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم الموبايل <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" inputmode="numeric" pattern="[0-9]*" required oninput="forceEnglishDigits(this)" value="<?= e($u['phone'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">الدور <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                        <option value="">— اختر —</option>
                        <option value="admin" <?= ($u['role'] ?? '')==='admin'?'selected':'' ?>>مدير</option>
                        <option value="doctor" <?= ($u['role'] ?? '')==='doctor'?'selected':'' ?>>طبيب</option>
                        <option value="reception" <?= ($u['role'] ?? '')==='reception'?'selected':'' ?>>استقبال</option>
                        <option value="lab_tech" <?= ($u['role'] ?? '')==='lab_tech'?'selected':'' ?>>فني مختبر</option>
                        <option value="patient" <?= ($u['role'] ?? '')==='patient'?'selected':'' ?>>مريض</option>
                    </select>
                    <?php if ($isEdit): ?><input type="hidden" name="role" value="<?= e($u['role']) ?>"><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الجنس <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">— اختر —</option>
                        <option value="male" <?= ($u['gender'] ?? '')==='male'?'selected':'' ?>>ذكر</option>
                        <option value="female" <?= ($u['gender'] ?? '')==='female'?'selected':'' ?>>أنثى</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">تاريخ الميلاد</label>
                    <input type="date" name="birth_date" class="form-control" value="<?= e($u['birth_date'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">العنوان</label>
                    <input type="text" name="address" class="form-control" value="<?= e($u['address'] ?? '') ?>">
                </div>

                <!-- Doctor-specific fields -->
                <div class="col-12">
                    <hr>
                    <h6 class="text-purple"><i class="bi bi-stethoscope"></i> بيانات الطبيب (تظهر فقط إذا الدور = طبيب)</h6>
                </div>
                <div class="col-md-6">
                    <label class="form-label">القسم</label>
                    <select name="department_id" class="form-select">
                        <option value="">— اختر —</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($u['department_id'] ?? '')==$d['id']?'selected':'' ?>><?= e($d['name_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">التخصص الدقيق</label>
                    <input type="text" name="specialty" class="form-control" value="<?= e($u['specialty'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">رقم الترخيص</label>
                    <input type="text" name="license_no" class="form-control" value="<?= e($u['license_no'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'تحديث' : 'إضافة' ?></button>
                <a href="<?= url('/admin/users') ?>" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
