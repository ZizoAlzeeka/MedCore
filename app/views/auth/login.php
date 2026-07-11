<?php /** Login view */ ?>
<?php $title = 'تسجيل الدخول'; ?>
<h5 class="mb-1">تسجيل الدخول</h5>
<p class="text-muted small mb-4">أدخل بياناتك للوصول إلى المنصة</p>

<?php if (hasFlash('error')): ?>
    <div class="alert alert-danger py-2 small">
        <i class="bi bi-exclamation-circle"></i> <?= e(getFlash('error')) ?>
    </div>
<?php endif; ?>
<?php if (hasFlash('success')): ?>
    <div class="alert alert-success py-2 small">
        <i class="bi bi-check-circle"></i> <?= e(getFlash('success')) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="example@hospital.com" required autofocus>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            <button class="btn btn-eye" type="button" onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 btn-lg mt-2">
        <i class="bi bi-box-arrow-in-right"></i> دخول
    </button>
</form>

<hr class="my-4">
<div class="text-center">
    <small class="text-muted">ليس لديك حساب؟</small>
    <a href="<?= url('/register') ?>" class="text-decoration-none fw-bold">
        إنشاء حساب جديد <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="alert alert-light border mt-4 small">
    <strong><i class="bi bi-info-circle text-primary"></i> بيانات تجريبية:</strong>
    <ul class="mb-0 mt-1 small">
        <li>مدير: <code>admin@platform.com</code> / <code>admin123</code></li>
        <li>طبيب: <code>doctor1@platform.com</code> / <code>doctor123</code></li>
        <li>مريض: <code>patient1@platform.com</code> / <code>patient123</code></li>
    </ul>
</div>
