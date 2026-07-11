<?php /** Register view (patient self-registration) */ ?>
<span class="role-tag">حساب مريض</span>
<h5 class="mb-3">إنشاء حساب جديد</h5>
<p class="text-muted small mb-4">سجّل بياناتك — سيتم تفعيل الحساب فوراً بدون تأكيد إيميل</p>

<?php if (hasFlash('error')): ?>
    <div class="alert alert-danger py-2 small">
        <i class="bi bi-exclamation-circle"></i> <?= e(getFlash('error')) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/register') ?>">
    <?= csrf_field() ?>

    <div class="mb-2">
        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="full_name" class="form-control" placeholder="الاسم الرباعي" required value="<?= old('full_name') ?>">
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="example@email.com" required value="<?= old('email') ?>">
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="6 أحرف على الأقل" required minlength="6">
            <button class="btn btn-eye" type="button" onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label">رقم الموبايل <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-phone"></i></span>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="05xxxxxxxx"
                   inputmode="numeric" pattern="[0-9]*" required
                   oninput="forceEnglishDigits(this)" value="<?= old('phone') ?>">
        </div>
        <div class="form-text">أرقام إنجليزية فقط</div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label">تاريخ الميلاد <span class="text-danger">*</span></label>
            <input type="date" name="birth_date" class="form-control" required value="<?= old('birth_date') ?>">
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label">الجنس <span class="text-danger">*</span></label>
            <select name="gender" class="form-select" required>
                <option value="">— اختر —</option>
                <option value="male" <?= old('gender')==='male'?'selected':'' ?>>ذكر</option>
                <option value="female" <?= old('gender')==='female'?'selected':'' ?>>أنثى</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">العنوان <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
            <input type="text" name="address" class="form-control" placeholder="المدينة - الحي - الشارع" required value="<?= old('address') ?>">
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="bi bi-person-plus"></i> إنشاء الحساب
    </button>
</form>

<hr class="my-3">
<div class="text-center">
    <small class="text-muted">لديك حساب بالفعل؟</small>
    <a href="<?= url('/login') ?>" class="text-decoration-none fw-bold">
        <i class="bi bi-arrow-right"></i> تسجيل الدخول
    </a>
</div>
