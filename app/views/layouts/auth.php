<?php
/** Auth layout — modern split-screen design */
$appName = Env::get('APP_NAME', 'منصة كشف التحاليل المكررة');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($appName) ?> — <?= e($title ?? 'تسجيل') ?></title>
<meta name="theme-color" content="#6C63FF">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="<?= asset('img/favicon.ico') ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32x32.png') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= asset('img/favicon-16x16.png') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= asset('img/apple-touch-icon.png') ?>">
<link rel="manifest" href="<?= asset('manifest.json') ?>">

<!-- Fonts + CSS -->
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-modern">
    <!-- Left: Brand panel (decorative) -->
    <div class="auth-brand-panel">
        <div class="auth-brand-bg"></div>
        <div class="auth-brand-content">
            <div class="auth-brand-logo">
                <img src="<?= asset('img/logo.png') ?>" alt="logo">
            </div>
            <h1 class="auth-brand-title">منصة طبية ذكية</h1>
            <p class="auth-brand-desc">نظام متكامل لربط أقسام المستشفى ومنع تكرار التحاليل المخبرية — يوفّر الوقت والمال ويحسن تجربة المريض.</p>
            <ul class="auth-brand-features">
                <li><i class="bi bi-search-heart"></i> كشف تلقائي للتحاليل المكررة بمعيار LOINC</li>
                <li><i class="bi bi-bell"></i> تنبيهات فورية للأطباء قبل تأكيد الطلب</li>
                <li><i class="bi bi-link-45deg"></i> ربط جميع الأقسام بسجل موحد</li>
                <li><i class="bi bi-graph-up"></i> تقارير وإحصائيات لقياس الأداء</li>
                <li><i class="bi bi-file-earmark-pdf"></i> طباعة تقارير المرضى بـ PDF</li>
                <li><i class="bi bi-shield-lock"></i> أمان وحماية البيانات الطبية</li>
            </ul>
            <div class="auth-brand-footer">
                <i class="bi bi-c-circle"></i> <?= date('Y') ?> — <?= e($appName) ?>
            </div>
        </div>
    </div>

    <!-- Right: Form panel -->
    <div class="auth-form-panel">
        <div class="auth-form-container">
            <?= $view ?? '' ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<script src="<?= asset('js/app.js') ?>" defer></script>

<!-- SweetAlert2 toast notifications for flash messages -->
<?php
$flashSuccess = getFlash('success');
$flashError = getFlash('error');
?>
<?php if ($flashSuccess || $flashError): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        var config = {
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        };
        <?php if ($flashSuccess): ?>
        config.icon = 'success';
        config.title = <?= json_encode($flashSuccess, JSON_UNESCAPED_UNICODE) ?>;
        <?php elseif ($flashError): ?>
        config.icon = 'error';
        config.title = <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>;
        <?php endif; ?>
        Swal.fire(config);
    }
});
</script>
<?php endif; ?>
</body>
</html>
