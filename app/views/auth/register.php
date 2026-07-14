<?php /** Register view — modern compact form */ ?>
<span class="auth-modern-badge">حساب مريض</span>
<div class="auth-modern-header">
    <h2>إنشاء حساب جديد</h2>
    <p>سيتم تفعيل الحساب فوراً بدون تأكيد إيميل</p>
</div>

<form method="post" action="<?= url('/register') ?>" class="auth-modern-form">
    <?= csrf_field() ?>

    <div class="auth-modern-field">
        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
        <div class="auth-modern-input-wrap">
            <i class="bi bi-person auth-modern-icon"></i>
            <input type="text" name="full_name" class="form-control" placeholder="الاسم الرباعي" required value="<?= old('full_name') ?>">
        </div>
    </div>

    <div class="auth-modern-field">
        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
        <div class="auth-modern-input-wrap">
            <i class="bi bi-envelope auth-modern-icon"></i>
            <input type="email" name="email" class="form-control" placeholder="example@email.com" required value="<?= old('email') ?>">
        </div>
    </div>

    <div class="auth-modern-field">
        <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
        <div class="auth-modern-input-wrap">
            <i class="bi bi-lock auth-modern-icon"></i>
            <input type="password" name="password" id="password" class="form-control" placeholder="6 أحرف على الأقل" required minlength="6">
            <button class="auth-modern-eye" type="button" onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="auth-modern-field">
        <label class="form-label">رقم الموبايل <span class="text-danger">*</span></label>
        <div class="auth-modern-input-wrap">
            <i class="bi bi-phone auth-modern-icon"></i>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="05xxxxxxxx"
                   inputmode="numeric" pattern="[0-9]*" required
                   oninput="forceEnglishDigits(this)" value="<?= old('phone') ?>">
        </div>
    </div>

    <div class="auth-modern-row">
        <div class="auth-modern-field">
            <label class="form-label">تاريخ الميلاد <span class="text-danger">*</span></label>
            <input type="date" name="birth_date" class="form-control" required value="<?= old('birth_date') ?>">
        </div>
        <div class="auth-modern-field">
            <label class="form-label">الجنس <span class="text-danger">*</span></label>
            <select name="gender" class="form-select" required>
                <option value="">— اختر —</option>
                <option value="male" <?= old('gender')==='male'?'selected':'' ?>>ذكر</option>
                <option value="female" <?= old('gender')==='female'?'selected':'' ?>>أنثى</option>
            </select>
        </div>
    </div>

    <div class="auth-modern-field">
        <label class="form-label">العنوان <span class="text-danger">*</span></label>
        <div class="auth-modern-input-wrap">
            <i class="bi bi-geo-alt auth-modern-icon"></i>
            <input type="text" name="address" class="form-control" placeholder="المدينة - الحي - الشارع" required value="<?= old('address') ?>">
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 auth-modern-submit">
        <i class="bi bi-person-plus"></i> إنشاء الحساب
    </button>
</form>

<div class="auth-modern-divider">
    <span>أو</span>
</div>

<div class="auth-modern-switch">
    <small>لديك حساب بالفعل؟</small>
    <a href="<?= url('/login') ?>">
        <i class="bi bi-arrow-right"></i> تسجيل الدخول
    </a>
</div>
