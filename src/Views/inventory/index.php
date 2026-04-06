<?php
$pageTitle   = 'ניהול מלאי';
$currentPage = 'inventory';

$totalPages = (int)ceil($data['total'] / $data['limit']);
ob_start();
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <p class="text-muted"><?= number_format($data['total']) ?> פריטים סה"כ</p>
  </div>
  <div class="page-header-actions">
    <a href="/inventory/create" class="btn btn-primary">
      <i class="fa-solid fa-plus"></i> פריט חדש
    </a>
  </div>
</div>

<!-- Stats strip -->
<div class="inv-stats-strip">
  <div class="inv-stat"><span><?= $stats['total'] ?></span><small>כולל הכל</small></div>
  <div class="inv-stat inv-stat-green"><span><?= $stats['available'] ?></span><small>זמינים</small></div>
  <div class="inv-stat inv-stat-amber"><span><?= $stats['in_repair'] ?></span><small>בתיקון</small></div>
  <div class="inv-stat inv-stat-red"><span><?= $stats['damaged'] ?></span><small>פגומים</small></div>
  <div class="inv-stat inv-stat-gray"><span><?= $stats['removed'] ?></span><small>הוצאו</small></div>
</div>

<!-- Filters -->
<div class="card filters-card">
  <form method="GET" action="/inventory" class="filters-form">
    <div class="filter-row">
      <div class="filter-item filter-search">
        <input type="search" name="search" placeholder="חיפוש לפי שם, ברקוד, מותג, מק"ט…"
               value="<?= htmlspecialchars($filters['search']) ?>" class="form-control">
      </div>
      <div class="filter-item">
        <select name="journal_id" class="form-control form-select">
          <option value="">כל היומנים</option>
          <?php foreach ($journals as $j): ?>
          <option value="<?= $j['id'] ?>" <?= $filters['journal_id'] == $j['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($j['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-item">
        <select name="condition_status" class="form-control form-select">
          <option value="">כל המצבים</option>
          <?php foreach (['תקין','פגום','בתיקון','מושבת'] as $c): ?>
          <option value="<?= $c ?>" <?= $filters['condition_status'] === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-item">
        <select name="is_loanable" class="form-control form-select">
          <option value="">כל הסוגים</option>
          <option value="1" <?= $filters['is_loanable']==='1'?'selected':'' ?>>ניתן להשאלה</option>
          <option value="0" <?= $filters['is_loanable']==='0'?'selected':'' ?>>לא להשאלה</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> חפש</button>
      <a href="/inventory" class="btn btn-outline"><i class="fa-solid fa-xmark"></i> נקה</a>
    </div>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>ברקוד</th>
          <th>שם פריט</th>
          <th>יומן</th>
          <th>מותג / דגם</th>
          <th>מיקום</th>
          <th>מצב</th>
          <th>כמות</th>
          <th>זמינות</th>
          <th>להשאלה</th>
          <th>פעולות</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['rows'] as $item): ?>
        <tr>
          <td><code class="barcode"><?= htmlspecialchars($item['barcode'] ?? '—') ?></code></td>
          <td>
            <strong><?= htmlspecialchars($item['name']) ?></strong>
            <?php if ($item['description']): ?>
            <div class="text-muted text-sm"><?= htmlspecialchars(mb_substr($item['description'],0,50)) ?>…</div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($item['journal_name']): ?>
            <span class="journal-badge" style="background:<?= htmlspecialchars($item['journal_color']??'#999') ?>22;color:<?= htmlspecialchars($item['journal_color']??'#999') ?>">
              <?= htmlspecialchars($item['journal_name']) ?>
            </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?= htmlspecialchars(trim(($item['brand']??'').' '.($item['model']??''))) ?: '—' ?>
          </td>
          <td><?= htmlspecialchars($item['location'] ?? '—') ?></td>
          <td>
            <span class="badge-condition cond-<?= condClass($item['condition_status']) ?>">
              <?= htmlspecialchars($item['condition_status']) ?>
            </span>
          </td>
          <td class="text-center"><?= $item['quantity'] ?></td>
          <td class="text-center">
            <span class="qty-available <?= $item['quantity_available'] == 0 ? 'qty-zero' : '' ?>">
              <?= $item['quantity_available'] ?>
            </span>
          </td>
          <td class="text-center">
            <?= $item['is_loanable']
              ? '<i class="fa-solid fa-circle-check text-green"></i>'
              : '<i class="fa-solid fa-circle-xmark text-red"></i>' ?>
          </td>
          <td>
            <div class="action-btns">
              <a href="/inventory/<?= $item['id'] ?>/edit" class="btn btn-icon btn-sm" title="עריכה">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
              <button class="btn btn-icon btn-sm btn-danger"
                      onclick="confirmRemove(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>')"
                      title="הוצא מהמלאי">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($data['rows'])): ?>
        <tr><td colspan="10" class="empty-state">
          <i class="fa-solid fa-box-open"></i>
          <p>לא נמצאו פריטים</p>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php
    $qp = $_GET;
    for ($p = 1; $p <= $totalPages; $p++):
      $qp['page'] = $p;
      $active = $p === $data['page'] ? 'active' : '';
    ?>
    <a href="/inventory?<?= http_build_query($qp) ?>" class="page-btn <?= $active ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Remove modal -->
<div class="modal" id="removeModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3><i class="fa-solid fa-trash"></i> הוצאת פריט מהמלאי</h3>
      <button onclick="closeModal('removeModal')" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" id="removeForm">
      <input type="hidden" name="_method" value="DELETE">
      <div class="modal-body">
        <p>האם להוציא את הפריט <strong id="removeItemName"></strong> מהמלאי הפעיל?</p>
        <div class="form-group">
          <label>סיבת הוצאה</label>
          <textarea name="reason" class="form-control" rows="3" placeholder="תיאור הסיבה להוצאת הפריט…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('removeModal')" class="btn btn-outline">ביטול</button>
        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> הוצא מהמלאי</button>
      </div>
    </form>
  </div>
</div>

<script>
function condClass(c) {
  const map = {'תקין':'ok','פגום':'damaged','בתיקון':'repair','מושבת':'disabled'};
  return map[c] || 'ok';
}
function confirmRemove(id, name) {
  document.getElementById('removeForm').action = '/inventory/' + id + '/remove';
  document.getElementById('removeItemName').textContent = name;
  openModal('removeModal');
}
</script>

<?php
function condClass(string $c): string {
  return match($c) { 'תקין'=>'ok','פגום'=>'damaged','בתיקון'=>'repair','מושבת'=>'disabled',default=>'ok' };
}
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
