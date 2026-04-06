<?php
$totalPages = (int)ceil($data['total'] / $data['limit']);
$statuses   = ['ממתין לאישור','אושר','מוכן','סופק','הוחזר חלקית','הוחזר','לא נלקח','נדחה'];
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <p class="text-muted"><?= number_format($data['total']) ?> הזמנות</p>
  </div>
  <div class="page-header-actions">
    <a href="/orders/create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> הזמנה חדשה</a>
  </div>
</div>

<!-- Stats strip -->
<div class="inv-stats-strip">
  <div class="inv-stat"><span><?= $stats['total'] ?></span><small>סה"כ</small></div>
  <div class="inv-stat inv-stat-amber"><span><?= $stats['pending'] ?></span><small>ממתינות</small></div>
  <div class="inv-stat inv-stat-green"><span><?= $stats['active'] ?></span><small>פעילות</small></div>
  <div class="inv-stat inv-stat-gray"><span><?= $stats['returned'] ?></span><small>הוחזרו</small></div>
  <?php if ($stats['overdue'] > 0): ?>
  <div class="inv-stat inv-stat-red"><span><?= $stats['overdue'] ?></span><small>באיחור</small></div>
  <?php endif; ?>
</div>

<!-- Filters -->
<div class="card filters-card">
  <form method="GET" action="/orders" class="filters-form">
    <div class="filter-row">
      <div class="filter-item filter-search">
        <input type="search" name="search" placeholder="חיפוש לפי מספר, פריט, מזמין…"
               value="<?= htmlspecialchars($filters['search']) ?>" class="form-control">
      </div>
      <div class="filter-item">
        <select name="status" class="form-control form-select">
          <option value="">כל הסטטוסים</option>
          <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status']===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-item">
        <select name="journal_id" class="form-control form-select">
          <option value="">כל היומנים</option>
          <?php foreach ($journals as $j): ?>
          <option value="<?= $j['id'] ?>" <?= ($filters['journal_id']??'')==$j['id']?'selected':'' ?>>
            <?= htmlspecialchars($j['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-item">
        <input type="date" name="date_from" class="form-control"
               value="<?= htmlspecialchars($filters['date_from']) ?>" placeholder="מתאריך">
      </div>
      <div class="filter-item">
        <input type="date" name="date_to" class="form-control"
               value="<?= htmlspecialchars($filters['date_to']) ?>" placeholder="עד תאריך">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> סנן</button>
      <a href="/orders" class="btn btn-outline"><i class="fa-solid fa-xmark"></i> נקה</a>
    </div>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>מס׳ הזמנה</th>
          <th>מזמין</th>
          <th>פריט</th>
          <th>יומן</th>
          <th>תאריך שאילה</th>
          <th>תאריך החזרה</th>
          <th>סטטוס</th>
          <th>פעולות</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['rows'] as $order):
          $isOverdue = $order['return_date'] < date('Y-m-d')
            && !in_array($order['status'],['הוחזר','נדחה','לא נלקח']);
        ?>
        <tr class="<?= $isOverdue ? 'row-overdue' : '' ?>">
          <td>
            <a href="/orders/<?= $order['id'] ?>" class="order-link">
              <?= htmlspecialchars($order['order_number']) ?>
            </a>
            <?php if ($isOverdue): ?><span class="overdue-tag">באיחור</span><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($order['requester_name']) ?></td>
          <td>
            <div><?= htmlspecialchars($order['item_name']) ?></div>
            <?php if ($order['item_barcode']): ?>
            <code class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($order['item_barcode']) ?></code>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($order['journal_name']): ?>
            <span class="journal-badge" style="background:<?= $order['journal_color'] ?>22;color:<?= $order['journal_color'] ?>">
              <?= htmlspecialchars($order['journal_name']) ?>
            </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?= date('d/m/Y', strtotime($order['loan_date'])) ?> <span class="text-muted"><?= substr($order['loan_time'],0,5) ?></span></td>
          <td><?= date('d/m/Y', strtotime($order['return_date'])) ?> <span class="text-muted"><?= substr($order['return_time'],0,5) ?></span></td>
          <td><span class="badge-status status-<?= ordStatusClass($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
          <td>
            <div class="action-btns">
              <a href="/orders/<?= $order['id'] ?>" class="btn btn-icon btn-sm" title="פרטים">
                <i class="fa-solid fa-eye"></i>
              </a>
              <form method="POST" action="/orders/<?= $order['id'] ?>/delete" style="display:inline"
                    onsubmit="return confirm('להעביר הזמנה לארכיון?')">
                <button class="btn btn-icon btn-sm btn-danger" title="ארכיון">
                  <i class="fa-solid fa-archive"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($data['rows'])): ?>
        <tr><td colspan="8" class="empty-state">
          <i class="fa-solid fa-clipboard-list"></i><p>לא נמצאו הזמנות</p>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php
    $qp = $_GET;
    for ($p=1; $p<=$totalPages; $p++):
      $qp['page']=$p;
    ?>
    <a href="/orders?<?= http_build_query($qp) ?>" class="page-btn <?= $p===$data['page']?'active':'' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<style>
.row-overdue td { background: rgba(239,68,68,.04) !important; }
.overdue-tag { display:inline-block; margin-right:6px; font-size:.68rem; padding:1px 6px;
  border-radius:10px; background:var(--red-bg); color:var(--red); }
.order-link { font-weight:600; font-family:monospace; font-size:.85rem; }
</style>

<?php
function ordStatusClass(string $s): string {
  return match($s) {
    'ממתין לאישור'=>'pending','אושר','מוכן'=>'approved',
    'סופק'=>'supplied','הוחזר','הוחזר חלקית'=>'returned',
    'נדחה','לא נלקח'=>'rejected',default=>'default'
  };
}
?>
