<?php /** 403 page */ ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>403 - غير مصرح</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<style>body{font-family:'Cairo',sans-serif;background:#f0f4f8;text-align:center;padding:80px 20px;}.code{font-size:120px;font-weight:900;background:linear-gradient(135deg,#FF6584,#FF416C);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;}</style>
</head>
<body>
<div class="code">403</div>
<h2 class="mt-3">غير مصرح بالوصول</h2>
<p class="text-muted">ليس لديك صلاحية للوصول إلى هذه الصفحة.</p>
<a href="<?= url('/') ?>" class="btn btn-primary mt-3"><i class="bi bi-house"></i> العودة للرئيسية</a>
</body>
</html>
