<?php /** 404 page */ ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>404 - الصفحة غير موجودة</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<style>body{font-family:'Cairo',sans-serif;background:#f0f4f8;text-align:center;padding:80px 20px;}.code{font-size:120px;font-weight:900;background:linear-gradient(135deg,#6C63FF,#FF6584,#4FC3F7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;}</style>
</head>
<body>
<div class="code">404</div>
<h2 class="mt-3">الصفحة غير موجودة</h2>
<p class="text-muted">عذراً، الصفحة التي تبحث عنها غير متوفرة أو تم نقلها.</p>
<a href="<?= url('/') ?>" class="btn btn-primary mt-3"><i class="bi bi-house"></i> العودة للرئيسية</a>
</body>
</html>
