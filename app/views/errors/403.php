<?php /** 403 page — professional access-denied screen */
$appName = Env::get('APP_NAME', 'منصة كشف التحاليل المكررة');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($appName) ?> — 403 غير مصرح</title>
<meta name="theme-color" content="#FF6584">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Cairo', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6C63FF 0%, #FF6584 50%, #FF416C 100%);
    padding: 20px;
}
.error-card {
    background: #fff;
    border-radius: 24px;
    padding: 48px 40px;
    max-width: 540px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
.icon-wrap {
    width: 110px;
    height: 110px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF6584, #FF416C);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 56px;
    box-shadow: 0 12px 30px rgba(255, 65, 108, 0.4);
    animation: shake 1.2s ease-in-out;
}
@keyframes shake {
    0%, 100% { transform: rotate(0deg); }
    20% { transform: rotate(-8deg); }
    40% { transform: rotate(8deg); }
    60% { transform: rotate(-4deg); }
    80% { transform: rotate(4deg); }
}
.code {
    font-size: 80px;
    font-weight: 900;
    line-height: 1;
    background: linear-gradient(135deg, #FF6584, #FF416C);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 12px;
}
h2 {
    font-size: 22px;
    color: #2D3436;
    margin-bottom: 10px;
}
p.text-muted {
    color: #636E72;
    font-size: 14px;
    margin-bottom: 24px;
    line-height: 1.6;
}
.btn-back {
    display: inline-block;
    background: linear-gradient(135deg, #6C63FF, #9D4EDD);
    color: #fff;
    text-decoration: none;
    padding: 12px 28px;
    border-radius: 12px;
    font-weight: 600;
    margin: 4px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108,99,255,0.4);
    color: #fff;
}
.btn-secondary {
    background: #f0f4f8;
    color: #2D3436;
}
.info-block {
    margin-top: 24px;
    padding: 14px;
    background: #F8FAFF;
    border-radius: 10px;
    font-size: 12px;
    color: #636E72;
    direction: ltr;
    text-align: left;
    font-family: monospace;
}
</style>
</head>
<body>
<div class="error-card">
    <div class="icon-wrap">
        <i class="bi bi-shield-lock"></i>
    </div>
    <div class="code">403</div>
    <h2>غير مصرح بالوصول</h2>
    <p class="text-muted">
        ليس لديك صلاحية للوصول إلى هذه الصفحة.<br>
        قد تكون الجلسة منتهية أو أن حسابك لا يملك الدور المطلوب.
    </p>
    <a href="<?= url('/') ?>" class="btn-back">
        <i class="bi bi-house"></i> العودة للرئيسية
    </a>
    <a href="<?= url('/login') ?>" class="btn-back btn-secondary">
        <i class="bi bi-box-arrow-in-right"></i> تسجيل الدخول
    </a>
    <div class="info-block">
        <?php if (Auth::check()): ?>
            Signed in as: <?= e(Auth::name() ?? 'Unknown') ?> (<?= e(Auth::role() ?? 'guest') ?>)
        <?php else: ?>
            Not signed in
        <?php endif; ?>
    </div>
</div>
</body>
</html>
