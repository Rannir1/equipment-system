<?php
$pageTitle   = 'שגיאה 404';
$currentPage = '';
ob_start();
?>
<div style="text-align:center; padding:80px 20px;">
  <div style="font-size:5rem; font-weight:800; color:var(--border-light); line-height:1; margin-bottom:16px;">404</div>
  <h2 style="margin-bottom:10px;">העמוד לא נמצא</h2>
  <p style="color:var(--text-muted); margin-bottom:28px;">הכתובת שביקשת אינה קיימת במערכת.</p>
  <a href="/dashboard" class="btn btn-primary"><i class="fa-solid fa-house"></i> חזרה לבית</a>
</div>
<?php
$content = ob_get_clean();
if (Auth::check()) {
    require __DIR__ . '/layouts/main.php';
} else {
    echo '<!DOCTYPE html><html lang="he" dir="rtl"><head><meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <title>404</title></head><body style="display:grid;place-items:center;min-height:100vh">' . $content . '</body></html>';
}
?>
