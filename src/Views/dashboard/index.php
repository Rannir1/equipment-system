<?php
$pageTitle   = 'לוח בקרה';
$currentPage = 'dashboard';

// Stats
try {
    $invStats = InventoryModel::getStats();
    $pendingOrders = 0;
    $myOrders      = 0;
    $overdueOrders = 0;
    if ($isAdmin) {
        $pendingOrders = (int)Database::query("SELECT COUNT(*) FROM orders WHERE status='ממתין לאישור' AND is_deleted=0")->fetchColumn();
        $overdueOrders = (int)Database::query("SELECT COUNT(*) FROM orders WHERE return_date < CURDATE() AND status NOT IN ('הוחזר','נדחה','לא נלקח') AND is_deleted=0")->fetchColumn();
        $todayLoans    = (int)Database::query("SELECT COUNT(*) FROM orders WHERE loan_date = CURDATE() AND status IN ('אושר','מוכן') AND is_deleted=0")->fetchColumn();
    } else {
        $myOrders   = (int)Database::query("SELECT COUNT(*) FROM orders WHERE user_id=? AND is_deleted=0", [$currentUser['id']])->fetchColumn();
        $myPending  = (int)Database::query("SELECT COUNT(*) FROM orders WHERE user_id=? AND status='ממתין לאישור' AND is_deleted=0", [$currentUser['id']])->fetchColumn();
        $myActive   = (int)Database::query("SELECT COUNT(*) FROM orders WHERE user_id=? AND status IN ('אושר','מוכן','סופק') AND is_deleted=0", [$currentUser['id']])->fetchColumn();
    }
} catch(Exception $e) {
    $invStats = [];
}

ob_start();
?>

<div class="dashboard">

  <?php if ($isAdmin && $pendingOrders > 0): ?>
  <div class="alert alert-warning dashboard-alert">
    <i class="fa-solid fa-triangle-exclamation"></i>
    יש <strong><?= $pendingOrders ?></strong> הזמנות הממתינות לאישורך.
    <a href="/orders?status=ממתין לאישור" class="btn btn-sm btn-warning">עבור להזמנות</a>
  </div>
  <?php endif; ?>

  <div class="stats-grid">
    <?php if ($isAdmin): ?>
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($invStats['total'] ?? 0) ?></div>
        <div class="stat-label">פריטים במלאי</div>
      </div>
    </div>
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($invStats['available'] ?? 0) ?></div>
        <div class="stat-label">פריטים זמינים</div>
      </div>
    </div>
    <div class="stat-card stat-amber">
      <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $pendingOrders ?></div>
        <div class="stat-label">ממתינות לאישור</div>
      </div>
      <a href="/orders?status=ממתין לאישור" class="stat-link"></a>
    </div>
    <div class="stat-card <?= $overdueOrders > 0 ? 'stat-red' : 'stat-gray' ?>">
      <div class="stat-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $overdueOrders ?></div>
        <div class="stat-label">פריטים באיחור</div>
      </div>
    </div>
    <div class="stat-card stat-purple">
      <div class="stat-icon"><i class="fa-solid fa-wrench"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($invStats['in_repair'] ?? 0) ?></div>
        <div class="stat-label">בתיקון</div>
      </div>
    </div>
    <div class="stat-card stat-rose">
      <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($invStats['damaged'] ?? 0) ?></div>
        <div class="stat-label">פגומים</div>
      </div>
    </div>

    <?php else: ?>
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $myOrders ?></div>
        <div class="stat-label">ההזמנות שלי</div>
      </div>
      <a href="/my-orders" class="stat-link"></a>
    </div>
    <div class="stat-card stat-amber">
      <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $myPending ?? 0 ?></div>
        <div class="stat-label">ממתינות לאישור</div>
      </div>
    </div>
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $myActive ?? 0 ?></div>
        <div class="stat-label">הזמנות פעילות</div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Quick actions -->
  <div class="quick-actions">
    <h3><i class="fa-solid fa-bolt"></i> פעולות מהירות</h3>
    <div class="quick-grid">
      <?php if ($isAdmin): ?>
      <a href="/inventory/create" class="quick-btn">
        <i class="fa-solid fa-plus"></i> הוסף פריט למלאי
      </a>
      <a href="/orders/create" class="quick-btn">
        <i class="fa-solid fa-file-plus"></i> צור הזמנה
      </a>
      <a href="/users/create" class="quick-btn">
        <i class="fa-solid fa-user-plus"></i> הוסף משתמש
      </a>
      <a href="/reports" class="quick-btn">
        <i class="fa-solid fa-chart-bar"></i> דוחות
      </a>
      <?php else: ?>
      <a href="/orders/create" class="quick-btn">
        <i class="fa-solid fa-file-plus"></i> צור הזמנה חדשה
      </a>
      <a href="/my-orders" class="quick-btn">
        <i class="fa-solid fa-list"></i> ההזמנות שלי
      </a>
      <a href="/journals" class="quick-btn">
        <i class="fa-solid fa-calendar"></i> יומן זמינות
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isAdmin): ?>
  <!-- Recent orders table -->
  <?php
  $recentOrders = Database::query(
    "SELECT o.*, u.full_name AS requester_name, i.name AS item_name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     JOIN inventory i ON i.id = o.inventory_id
     WHERE o.is_deleted = 0
     ORDER BY o.created_at DESC LIMIT 8"
  )->fetchAll();
  ?>
  <div class="card mt-4">
    <div class="card-header">
      <h3><i class="fa-solid fa-clock-rotate-left"></i> הזמנות אחרונות</h3>
      <a href="/orders" class="btn btn-sm btn-outline">הצג הכל</a>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>מס׳ הזמנה</th>
            <th>מזמין</th>
            <th>פריט</th>
            <th>תאריך שאילה</th>
            <th>סטטוס</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $order): ?>
          <tr>
            <td><a href="/orders/<?= $order['id'] ?>"><?= htmlspecialchars($order['order_number']) ?></a></td>
            <td><?= htmlspecialchars($order['requester_name']) ?></td>
            <td><?= htmlspecialchars($order['item_name']) ?></td>
            <td><?= date('d/m/Y', strtotime($order['loan_date'])) ?></td>
            <td><span class="badge-status status-<?= self_statusClass($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentOrders)): ?>
          <tr><td colspan="5" class="text-center text-muted">אין הזמנות עדיין</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
function self_statusClass(string $s): string {
  return match($s) {
    'ממתין לאישור' => 'pending',
    'אושר','מוכן'  => 'approved',
    'סופק'         => 'supplied',
    'הוחזר'        => 'returned',
    'נדחה','לא נלקח' => 'rejected',
    default        => 'default',
  };
}

$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
