<?php
$pageTitle   = $stubTitle ?? 'בקרוב';
$currentPage = $stubPage  ?? '';
ob_start();
?>
<div class="page-header">
  <h1><?= htmlspecialchars($pageTitle) ?></h1>
</div>
<div class="card" style="padding:60px; text-align:center;">
  <div style="font-size:3rem; margin-bottom:20px; opacity:.3;">
    <i class="fa-solid fa-hammer"></i>
  </div>
  <h2 style="font-size:1.1rem; margin-bottom:10px;">עמוד זה בפיתוח</h2>
  <p style="color:var(--text-muted); font-size:.9rem;">
    עמוד <strong><?= htmlspecialchars($pageTitle) ?></strong> ייבנה בשלב הבא של הפיתוח.
  </p>
  <div style="margin-top:24px;">
    <a href="/dashboard" class="btn btn-outline"><i class="fa-solid fa-arrow-right"></i> חזרה ללוח הבקרה</a>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/main.php';
?>
