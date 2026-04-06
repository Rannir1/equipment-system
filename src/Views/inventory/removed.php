<?php
$pageTitle   = 'פריטים שהוצאו';
$currentPage = 'removed_inventory';
$totalPages  = (int)ceil($data['total'] / $data['limit']);
ob_start();
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <p class="text-muted"><?= number_format($data['total']) ?> פריטים שהוצאו</p>
  </div>
  <a href="/inventory" class="btn btn-outline"><i class="fa-solid fa-arrow-right"></i> חזרה למלאי</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>ברקוד</th>
          <th>שם פריט</th>
          <th>יומן</th>
          <th>מצב</th>
          <th>סיבת הוצאה</th>
          <th>תאריך הוצאה</th>
          <th>פעולות</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['rows'] as $item): ?>
        <tr>
          <td><code class="barcode"><?= htmlspecialchars($item['barcode'] ?? '—') ?></code></td>
          <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
          <td><?= htmlspecialchars($item['journal_name'] ?? '—') ?></td>
          <td><span class="badge-condition cond-<?= condClassRemoved($item['condition_status']) ?>">
            <?= htmlspecialchars($item['condition_status']) ?></span></td>
          <td><?= htmlspecialchars($item['removed_reason'] ?? '—') ?></td>
          <td><?= $item['removed_at'] ? date('d/m/Y', strtotime($item['removed_at'])) : '—' ?></td>
          <td>
            <form method="POST" action="/inventory/<?= $item['id'] ?>/restore"
                  onsubmit="return confirm('לשחזר את הפריט למלאי הפעיל?')">
              <button class="btn btn-sm btn-success"><i class="fa-solid fa-rotate-right"></i> שחזר</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($data['rows'])): ?>
        <tr><td colspan="7" class="empty-state">
          <i class="fa-solid fa-check-circle"></i>
          <p>אין פריטים שהוצאו</p>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="/inventory/removed?page=<?= $p ?>" class="page-btn <?= $p === $data['page'] ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php
function condClassRemoved(string $c): string {
  return match($c) { 'תקין'=>'ok','פגום'=>'damaged','בתיקון'=>'repair','מושבת'=>'disabled',default=>'ok' };
}
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
