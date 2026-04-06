<?php
$totalPages = (int)ceil($data['total'] / $data['limit']);
$statuses   = ['ממתין לאישור','אושר','מוכן','סופק','הוחזר חלקית','הוחזר','לא נלקח','נדחה'];
function myOrdStatusCls(string $s): string {
  return match($s) {
    'ממתין לאישור'=>'pending','אושר','מוכן'=>'approved',
    'סופק'=>'supplied','הוחזר','הוחזר חלקית'=>'returned',
    'נדחה','לא נלקח'=>'rejected',default=>'default'
  };
}
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <p class="text-muted"><?= number_format($data['total']) ?> הזמנות סה"כ</p>
  </div>
  <a href="/orders/create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> הזמנה חדשה</a>
</div>

<!-- Quick stats -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr))">
  <div class="stat-card stat-blue">
    <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">סה"כ</div></div>
  </div>
  <div class="stat-card stat-amber">
    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">ממתינות</div></div>
  </div>
  <div class="stat-card stat-green">
    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $stats['active'] ?></div><div class="stat-label">פעילות</div></div>
  </div>
  <?php if ($stats['overdue'] > 0): ?>
  <div class="stat-card stat-red">
    <div class="stat-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $stats['overdue'] ?></div><div class="stat-label">באיחור</div></div>
  </div>
  <?php endif; ?>
</div>

<!-- Filters -->
<div class="card filters-card">
  <form method="GET" action="/my-orders" class="filters-form">
    <div class="filter-row">
      <div class="filter-item filter-search">
        <input type="search" name="search" placeholder="חיפוש…"
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
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> סנן</button>
      <a href="/my-orders" class="btn btn-outline"><i class="fa-solid fa-xmark"></i> נקה</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>מס׳ הזמנה</th>
          <th>פריט</th>
          <th>תאריך שאילה</th>
          <th>תאריך החזרה</th>
          <th>מטרה</th>
          <th>סטטוס</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['rows'] as $order):
          $isOverdue = $order['return_date'] < date('Y-m-d')
            && !in_array($order['status'],['הוחזר','נדחה','לא נלקח']);
        ?>
        <tr>
          <td>
            <a href="/orders/<?= $order['id'] ?>" class="order-link"><?= htmlspecialchars($order['order_number']) ?></a>
            <?php if ($isOverdue): ?><span class="overdue-tag">באיחור</span><?php endif; ?>
          </td>
          <td><strong><?= htmlspecialchars($order['item_name']) ?></strong></td>
          <td><?= date('d/m/Y', strtotime($order['loan_date'])) ?></td>
          <td class="<?= $isOverdue?'text-red':'' ?>"><?= date('d/m/Y', strtotime($order['return_date'])) ?></td>
          <td class="text-muted text-sm"><?= htmlspecialchars(mb_substr($order['purpose']??'',0,40)) ?></td>
          <td><span class="badge-status status-<?= myOrdStatusCls($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
          <td>
            <a href="/orders/<?= $order['id'] ?>" class="btn btn-icon btn-sm" title="פרטים">
              <i class="fa-solid fa-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($data['rows'])): ?>
        <tr><td colspan="7" class="empty-state">
          <i class="fa-solid fa-clipboard-list"></i>
          <p>אין הזמנות עדיין — <a href="/orders/create">צור הזמנה</a></p>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php $qp=$_GET; for($p=1;$p<=$totalPages;$p++){ $qp['page']=$p; ?>
    <a href="/my-orders?<?= http_build_query($qp) ?>" class="page-btn <?= $p===$data['page']?'active':'' ?>"><?= $p ?></a>
    <?php } ?>
  </div>
  <?php endif; ?>
</div>

<style>
.overdue-tag { display:inline-block;margin-right:6px;font-size:.68rem;padding:1px 6px;
  border-radius:10px;background:var(--red-bg);color:var(--red); }
.order-link { font-weight:600;font-family:monospace;font-size:.85rem; }
</style>
