<?php
$statuses = ['ממתין לאישור','אושר','מוכן','סופק','הוחזר חלקית','הוחזר','לא נלקח','נדחה'];
$hebrewDays = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
function dayLabel(string $date): string {
  global $hebrewDays;
  return 'יום ' . $hebrewDays[(int)(new DateTime($date))->format('w')];
}
function statusCls(string $s): string {
  return match($s) {
    'ממתין לאישור'=>'pending','אושר','מוכן'=>'approved',
    'סופק'=>'supplied','הוחזר','הוחזר חלקית'=>'returned',
    'נדחה','לא נלקח'=>'rejected',default=>'default'
  };
}
$isOverdue = $order['return_date'] < date('Y-m-d')
  && !in_array($order['status'],['הוחזר','נדחה','לא נלקח']);
?>

<div class="page-header">
  <div>
    <h1><?= htmlspecialchars($order['order_number']) ?></h1>
    <nav class="breadcrumb">
      <a href="<?= $isAdmin ? '/orders' : '/my-orders' ?>">הזמנות</a>
      <span>›</span><span><?= htmlspecialchars($order['order_number']) ?></span>
    </nav>
  </div>
  <div class="page-header-actions">
    <?php if (!$isAdmin && in_array($order['status'],['ממתין לאישור','אושר'])): ?>
    <form method="POST" action="/orders/<?= $order['id'] ?>/cancel"
          onsubmit="return confirm('לבטל הזמנה זו?')">
      <button class="btn btn-danger"><i class="fa-solid fa-xmark"></i> ביטול הזמנה</button>
    </form>
    <?php endif; ?>
    <?php if ($isAdmin): ?>
    <form method="POST" action="/orders/<?= $order['id'] ?>/delete"
          onsubmit="return confirm('להעביר לארכיון?')">
      <button class="btn btn-outline"><i class="fa-solid fa-archive"></i> ארכיון</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($isOverdue): ?>
<div class="alert alert-error">
  <i class="fa-solid fa-triangle-exclamation"></i>
  ציוד זה לא הוחזר במועד — תאריך החזרה היה <?= date('d/m/Y', strtotime($order['return_date'])) ?>
</div>
<?php endif; ?>

<div class="order-detail-grid">

  <!-- Main info -->
  <div>
    <div class="card detail-card">
      <div class="detail-header">
        <span class="badge-status status-<?= statusCls($order['status']) ?>" style="font-size:.9rem;padding:6px 14px">
          <?= htmlspecialchars($order['status']) ?>
        </span>
        <span class="detail-date text-muted">נוצר: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
      </div>

      <div class="detail-grid">
        <div class="detail-item">
          <div class="detail-label">פריט</div>
          <div class="detail-value">
            <strong><?= htmlspecialchars($order['item_name']) ?></strong>
            <?php if ($order['item_barcode']): ?>
            <code style="margin-right:8px;font-size:.78rem"><?= htmlspecialchars($order['item_barcode']) ?></code>
            <?php endif; ?>
            <div class="text-muted text-sm"><?= htmlspecialchars(($order['item_brand']??'') . ' ' . ($order['item_model']??'')) ?></div>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">יומן</div>
          <div class="detail-value">
            <?php if ($order['journal_name']): ?>
            <span class="journal-badge" style="background:<?= $order['journal_color'] ?>22;color:<?= $order['journal_color'] ?>">
              <?= htmlspecialchars($order['journal_name']) ?>
            </span>
            <?php else: ?>—<?php endif; ?>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">מזמין</div>
          <div class="detail-value">
            <?= htmlspecialchars($order['requester_name']) ?>
            <div class="text-muted text-sm">ת.ז. <?= htmlspecialchars($order['requester_tz']) ?></div>
            <?php if ($order['requester_email']): ?>
            <div class="text-sm"><a href="mailto:<?= htmlspecialchars($order['requester_email']) ?>"><?= htmlspecialchars($order['requester_email']) ?></a></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">תאריך שאילה</div>
          <div class="detail-value">
            <?= date('d/m/Y', strtotime($order['loan_date'])) ?>
            <span class="text-muted"><?= substr($order['loan_time'],0,5) ?></span>
            <div class="text-sm text-muted"><?= dayLabel($order['loan_date']) ?></div>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">תאריך החזרה</div>
          <div class="detail-value">
            <span class="<?= $isOverdue ? 'text-red' : '' ?>">
              <?= date('d/m/Y', strtotime($order['return_date'])) ?>
              <span class="text-muted"><?= substr($order['return_time'],0,5) ?></span>
            </span>
            <div class="text-sm text-muted"><?= dayLabel($order['return_date']) ?></div>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">מטרה</div>
          <div class="detail-value"><?= htmlspecialchars($order['purpose'] ?: '—') ?></div>
        </div>
        <?php if ($order['notes']): ?>
        <div class="detail-item detail-full">
          <div class="detail-label">הערות</div>
          <div class="detail-value"><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($order['admin_notes']): ?>
        <div class="detail-item detail-full">
          <div class="detail-label">הערות מנהל</div>
          <div class="detail-value admin-notes"><?= nl2br(htmlspecialchars($order['admin_notes'])) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($order['recurring_group']): ?>
        <div class="detail-item detail-full">
          <div class="detail-label">קבוצת הזמנות</div>
          <div class="detail-value text-muted text-sm"><?= htmlspecialchars($order['recurring_group']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Admin status panel -->
  <?php if ($isAdmin): ?>
  <div>
    <div class="card detail-card">
      <div class="card-header"><h3><i class="fa-solid fa-sliders"></i> עדכון סטטוס</h3></div>
      <form method="POST" action="/orders/<?= $order['id'] ?>/status" style="padding:20px;">
        <div class="form-group">
          <label>סטטוס</label>
          <select name="status" class="form-control form-select">
            <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>הערות מנהל</label>
          <textarea name="admin_notes" class="form-control" rows="3"><?= htmlspecialchars($order['admin_notes']??'') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">
          <i class="fa-solid fa-floppy-disk"></i> שמור
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div>

<style>
.order-detail-grid { display:grid; grid-template-columns:1fr <?= $isAdmin?'320px':'' ?>; gap:20px; align-items:start; }
.detail-card { padding:0; overflow:visible; }
.detail-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border); }
.detail-date { font-size:.8rem; }
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; }
.detail-item { padding:14px 20px; border-bottom:1px solid rgba(255,255,255,.04); }
.detail-item:last-child { border-bottom:none; }
.detail-full { grid-column:span 2; }
.detail-label { font-size:.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; }
.detail-value { font-size:.9rem; }
.admin-notes  { background:var(--bg-input); padding:8px 12px; border-radius:var(--radius); font-size:.85rem; }
@media (max-width:768px) {
  .order-detail-grid { grid-template-columns:1fr; }
  .detail-grid { grid-template-columns:1fr; }
  .detail-full { grid-column:span 1; }
}
</style>
