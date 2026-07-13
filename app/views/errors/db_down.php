<?php
/** Friendly DB-down page rendered when MySQL is unreachable.
 *  Shown instead of letting the user wait 30+ seconds for a timeout. */
http_response_code(503);
header('Retry-After: 30');
header('Cache-Control: no-store, no-cache, must-revalidate');
$appName = Env::get('APP_NAME', 'منصة كشف التحاليل المكررة');
$errorMsg = $errorMsg ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($appName) ?> — صيانة</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Cairo', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6C63FF 0%, #FF6584 50%, #4FC3F7 100%);
    color: #2D3436;
    padding: 20px;
}
.card {
    background: #fff;
    border-radius: 24px;
    padding: 48px 40px;
    max-width: 540px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.icon {
    font-size: 80px;
    color: #6C63FF;
    margin-bottom: 20px;
    display: inline-block;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}
h1 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 14px;
    color: #2D3436;
}
p {
    font-size: 15px;
    line-height: 1.7;
    color: #636E72;
    margin-bottom: 20px;
}
.progress-bar {
    height: 6px;
    background: #F0F4F8;
    border-radius: 3px;
    overflow: hidden;
    margin: 24px 0;
}
.progress-bar::after {
    content: '';
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #6C63FF, #FF6584);
    width: 30%;
    animation: loading 1.5s infinite;
}
@keyframes loading {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}
.retry-btn {
    display: inline-block;
    background: linear-gradient(135deg, #6C63FF, #9D4EDD);
    color: #fff;
    text-decoration: none;
    padding: 12px 32px;
    border-radius: 12px;
    font-weight: 600;
    margin-top: 16px;
    transition: transform 0.2s;
}
.retry-btn:hover { transform: translateY(-2px); }
.tech-info {
    margin-top: 24px;
    padding: 12px;
    background: #F8FAFF;
    border-radius: 8px;
    font-size: 12px;
    color: #636E72;
    direction: ltr;
    text-align: left;
    font-family: monospace;
    word-break: break-all;
}
</style>
</head>
<body>
<div class="card">
    <div class="icon"><i class="bi bi-database-dash"></i></div>
    <h1>المنصة قيد الصيانة المؤقتة</h1>
    <p>نواجه حالياً بطئاً في الاتصال بقاعدة البيانات. فريقنا يعمل على إصلاح المشكلة، يرجى المحاولة مرة أخرى خلال لحظات.</p>
    <div class="progress-bar"></div>
    <a href="/" class="retry-btn">
        <i class="bi bi-arrow-clockwise"></i>
        إعادة المحاولة
    </a>
    <?php if (!empty($errorMsg) && Env::get('APP_DEBUG', 'false') === 'true'): ?>
        <div class="tech-info"><?= e($errorMsg) ?></div>
    <?php endif; ?>
</div>
<script>
// Auto-retry every 15 seconds
setTimeout(() => { window.location.reload(); }, 15000);
</script>
</body>
</html>
