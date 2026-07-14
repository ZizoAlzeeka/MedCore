<?php /** Login view — modern compact form */ ?>
<?php $title = 'تسجيل الدخول'; ?>
<div class="auth-modern-header">
    <h2>تسجيل الدخول</h2>
    <p>أدخل بياناتك للوصول إلى المنصة</p>
</div>

<form method="post" action="<?= url('/login') ?>" class="auth-modern-form">
    <?= csrf_field() ?>

    <div class="auth-modern-field">
        <label class="form-label">البريد الإلكتروني</label>
        <div class="auth-modern-input-wrap">
            <i class="bi bi-envelope auth-modern-icon"></i>
            <input type="email" name="email" class="form-control" placeholder="example@hospital.com" required autofocus>
        </div>
    </div>

    <div class="auth-modern-field">
        <label class="form-label">كلمة المرور</label>
        <div class="auth-modern-input-wrap">
            <i class="bi bi-lock auth-modern-icon"></i>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            <button class="auth-modern-eye" type="button" onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 auth-modern-submit">
        <i class="bi bi-box-arrow-in-right"></i> دخول
    </button>
</form>

<div class="auth-modern-divider">
    <span>أو</span>
</div>

<div class="auth-modern-switch">
    <small>ليس لديك حساب؟</small>
    <a href="<?= url('/register') ?>">
        إنشاء حساب جديد <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="auth-modern-demo">
    <div class="auth-demo-title">
        <i class="bi bi-info-circle"></i> بيانات تجريبية:
    </div>
    <div class="auth-demo-list">
        <div class="auth-demo-item">
            <span class="auth-demo-role">مدير</span>
            <code>admin@platform.com</code>
            <span class="auth-demo-pass">admin123</span>
        </div>
        <div class="auth-demo-item">
            <span class="auth-demo-role">طبيب</span>
            <code>doctor1@platform.com</code>
            <span class="auth-demo-pass">doctor123</span>
        </div>
        <div class="auth-demo-item">
            <span class="auth-demo-role">مريض</span>
            <code>patient1@platform.com</code>
            <span class="auth-demo-pass">patient123</span>
        </div>
    </div>
</div>
