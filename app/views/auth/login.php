<?php /** Login view — compact elegant form */ ?>
<?php $title = 'تسجيل الدخول'; ?>
<div class="auth-form-header">
    <h5>تسجيل الدخول</h5>
    <p class="text-muted">أدخل بياناتك للوصول إلى المنصة</p>
</div>

<form method="post" action="<?= url('/login') ?>" class="auth-form">
    <?= csrf_field() ?>

    <div class="auth-field">
        <label class="form-label">البريد الإلكتروني</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="example@hospital.com" required autofocus>
        </div>
    </div>

    <div class="auth-field">
        <label class="form-label">كلمة المرور</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            <button class="btn btn-eye" type="button" onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 auth-submit-btn">
        <i class="bi bi-box-arrow-in-right"></i> دخول
    </button>
</form>

<div class="auth-footer">
    <small class="text-muted">ليس لديك حساب؟</small>
    <a href="<?= url('/register') ?>" class="text-decoration-none fw-bold">
        إنشاء حساب جديد <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="auth-demo-creds">
    <strong><i class="bi bi-info-circle text-primary"></i> بيانات تجريبية:</strong>
    <div class="creds-list">
        <span class="cred-item"><code>admin@platform.com</code> / <code>admin123</code></span>
        <span class="cred-item"><code>doctor1@platform.com</code> / <code>doctor123</code></span>
        <span class="cred-item"><code>patient1@platform.com</code> / <code>patient123</code></span>
    </div>
</div>
