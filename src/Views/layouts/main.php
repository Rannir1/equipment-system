<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<?php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$currentUser = Auth::user();
$isAdmin     = Auth::isAdmin();
$currentPage = $currentPage ?? '';
?>

<!-- ═══════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <?php if (!empty($logoPath) && file_exists(__DIR__ . '/../../public' . $logoPath)): ?>
        <img src="<?= $logoPath ?>" alt="לוגו" class="logo-img">
      <?php else: ?>
        <div class="logo-icon"><i class="fa-solid fa-camera-retro"></i></div>
      <?php endif; ?>
    </div>
    <div class="sidebar-title">
      <span class="institution-name">בית הספר לתקשורת</span>
      <span class="institution-sub">מחסן ציוד</span>
    </div>
    <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <nav class="sidebar-nav">
    <a href="/dashboard" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="fa-solid fa-gauge-high"></i><span>לוח בקרה</span>
    </a>

    <?php if ($isAdmin): ?>
    <div class="nav-section-label">ניהול ציוד</div>
    <a href="/inventory" class="nav-item <?= $currentPage==='inventory'?'active':'' ?>">
      <i class="fa-solid fa-boxes-stacked"></i><span>מלאי ציוד</span>
    </a>
    <a href="/inventory/removed" class="nav-item <?= $currentPage==='removed_inventory'?'active':'' ?>">
      <i class="fa-solid fa-box-archive"></i><span>פריטים שהוצאו</span>
    </a>

    <div class="nav-section-label">הזמנות</div>
    <a href="/orders" class="nav-item <?= $currentPage==='orders'?'active':'' ?>">
      <i class="fa-solid fa-clipboard-list"></i><span>הזמנות</span>
    </a>
    <a href="/loans" class="nav-item <?= $currentPage==='loans'?'active':'' ?>">
      <i class="fa-solid fa-right-left"></i><span>השאלות / החזרות</span>
    </a>

    <div class="nav-section-label">ניהול</div>
    <a href="/users" class="nav-item <?= $currentPage==='users'?'active':'' ?>">
      <i class="fa-solid fa-users"></i><span>משתמשים</span>
    </a>
    <a href="/reports" class="nav-item <?= $currentPage==='reports'?'active':'' ?>">
      <i class="fa-solid fa-chart-bar"></i><span>דוחות</span>
    </a>
    <a href="/settings" class="nav-item <?= $currentPage==='settings'?'active':'' ?>">
      <i class="fa-solid fa-gear"></i><span>הגדרות</span>
    </a>
    <a href="/import" class="nav-item <?= $currentPage==='import'?'active':'' ?>">
      <i class="fa-solid fa-file-arrow-up"></i><span>ייבוא נתונים</span>
    </a>
    <?php else: ?>
    <div class="nav-section-label">הזמנות שלי</div>
    <a href="/my-orders" class="nav-item <?= $currentPage==='my_reports'?'active':'' ?>">
      <i class="fa-solid fa-clipboard-list"></i><span>ההזמנות שלי</span>
    </a>
    <?php endif; ?>

    <div class="nav-section-label">כללי</div>
    <a href="/journals" class="nav-item <?= $currentPage==='journals'?'active':'' ?>">
      <i class="fa-solid fa-calendar-days"></i><span>יומני הזמנות</span>
    </a>
    <a href="/notifications" class="nav-item <?= $currentPage==='notifications'?'active':'' ?>">
      <i class="fa-solid fa-bell"></i><span>התראות</span>
      <?php
      $unread = 0;
      try { $unread = (int)Database::query('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$currentUser['id']])->fetchColumn(); } catch(Exception $e){}
      if ($unread): ?><span class="badge"><?= $unread ?></span><?php endif; ?>
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= mb_substr($currentUser['name'], 0, 1) ?></div>
      <div class="user-details">
        <span class="user-name"><?= htmlspecialchars($currentUser['name']) ?></span>
        <span class="user-role"><?= $isAdmin ? 'מנהל' : 'סטודנט' ?></span>
      </div>
    </div>
    <a href="/logout" class="logout-btn" title="יציאה"><i class="fa-solid fa-right-from-bracket"></i></a>
  </div>
</aside>

<!-- ═══════════════════════════════════════════════ OVERLAY -->
<div class="overlay" id="overlay"></div>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main-wrapper">
  <header class="topbar">
    <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
    <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></div>
    <div class="topbar-actions">
      <form class="search-form" action="/search" method="get">
        <input type="search" name="q" placeholder="חיפוש…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="search-input">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
    </div>
  </header>

  <?php if ($flash): ?>
  <div class="flash flash-<?= $flash['type'] ?>" id="flashMsg">
    <i class="fa-solid <?= $flash['type']==='success'?'fa-circle-check':($flash['type']==='warning'?'fa-triangle-exclamation':'fa-circle-info') ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
    <button onclick="this.parentElement.remove()" class="flash-close"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <?php endif; ?>

  <main class="content">
    <?= $content ?? '' ?>
  </main>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
