<?php
$weekStart = $weekStart ?? date('Y-m-d', strtotime('monday this week'));
$weekPrev  = date('Y-m-d', strtotime('-7 days', strtotime($weekStart)));
$weekNext  = date('Y-m-d', strtotime('+7 days', strtotime($weekStart)));
$today     = date('Y-m-d');

$hebrewDays = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];

ob_start(); // content already started by controller - this is the inner view
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <p class="text-muted">זמינות ציוד לפי יומן ושבוע</p>
  </div>
  <div class="page-header-actions">
    <a href="/orders/create<?= $selectedJournal ? '?journal='.$selectedJournal : '' ?>" class="btn btn-primary">
      <i class="fa-solid fa-plus"></i> הזמנה חדשה
    </a>
  </div>
</div>

<div class="journal-layout">

  <!-- Journal selector sidebar -->
  <div class="journal-sidebar">
    <div class="journal-sidebar-title">יומנים</div>
    <?php foreach ($journals as $j): ?>
    <a href="/journals?journal=<?= $j['id'] ?>&week=<?= $weekStart ?>"
       class="journal-tab <?= $selectedJournal == $j['id'] ? 'active' : '' ?>"
       style="--jcolor:<?= htmlspecialchars($j['color']) ?>">
      <span class="journal-dot"></span>
      <?= htmlspecialchars($j['name']) ?>
    </a>
    <?php endforeach; ?>
    <?php if (empty($journals)): ?>
    <p class="text-muted" style="padding:12px;font-size:.82rem;">אין יומנים</p>
    <?php endif; ?>
  </div>

  <!-- Calendar area -->
  <div class="journal-main">

    <!-- Week navigation -->
    <div class="week-nav">
      <a href="/journals?journal=<?= $selectedJournal ?>&week=<?= $weekPrev ?>" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
      <div class="week-label">
        <?php
        $wStart = new DateTime($weekStart);
        $wEnd   = new DateTime($weekStart); $wEnd->modify('+6 days');
        echo $wStart->format('d/m') . ' — ' . $wEnd->format('d/m/Y');
        ?>
      </div>
      <a href="/journals?journal=<?= $selectedJournal ?>&week=<?= $weekNext ?>" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-chevron-left"></i>
      </a>
      <a href="/journals?journal=<?= $selectedJournal ?>&week=<?= date('Y-m-d', strtotime('monday this week')) ?>"
         class="btn btn-outline btn-sm">היום</a>
    </div>

    <?php if ($journal && !empty($grid)): ?>

    <!-- Grid -->
    <div class="calendar-wrap">
      <table class="calendar-table">
        <thead>
          <tr>
            <th class="item-col">פריט</th>
            <?php foreach ($grid['days'] as $day):
              $dt      = new DateTime($day);
              $dayName = $hebrewDays[(int)$dt->format('w')];
              $isToday = $day === $today;
            ?>
            <th class="day-col <?= $isToday ? 'today-col' : '' ?>">
              <div class="day-header">
                <span class="day-name">יום <?= $dayName ?></span>
                <span class="day-date <?= $isToday ? 'today-badge' : '' ?>"><?= $dt->format('d/m') ?></span>
              </div>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grid['items'] as $item): ?>
          <tr>
            <td class="item-cell">
              <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
              <?php if ($item['barcode']): ?>
              <div class="item-barcode"><?= htmlspecialchars($item['barcode']) ?></div>
              <?php endif; ?>
              <?php if (!$item['is_loanable'] || $item['condition_status'] !== 'תקין'): ?>
              <span class="item-status-tag"><?= htmlspecialchars($item['condition_status']) ?></span>
              <?php endif; ?>
            </td>
            <?php foreach ($grid['days'] as $day):
              $cell = $grid['grid'][$day][$item['id']] ?? null;
              if (!$cell) { echo '<td class="cal-cell"></td>'; continue; }
              $busy = $cell['busy'];
              $orders = $cell['orders'];
            ?>
            <td class="cal-cell <?= $busy ? 'cal-busy' : 'cal-free' ?>">
              <?php if ($busy): ?>
                <?php foreach ($orders as $o): ?>
                <a href="/orders/<?= $o['id'] ?>" class="cal-order-tag"
                   title="<?= htmlspecialchars($o['requester_name']) ?> | <?= htmlspecialchars($o['order_number']) ?>">
                  <span class="cal-order-name"><?= mb_substr(htmlspecialchars($o['requester_name']),0,12) ?></span>
                  <span class="cal-order-status <?= calStatusClass($o['status']) ?>"></span>
                </a>
                <?php endforeach; ?>
              <?php else: ?>
                <?php if ($item['is_loanable'] && $item['condition_status']==='תקין'): ?>
                <a href="/orders/create?item=<?= $item['id'] ?>&date=<?= $day ?>"
                   class="cal-free-slot" title="צור הזמנה">
                  <i class="fa-solid fa-plus"></i>
                </a>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>

          <?php if (empty($grid['items'])): ?>
          <tr>
            <td colspan="8" class="empty-state">
              <i class="fa-solid fa-box-open"></i>
              <p>אין פריטים ביומן זה</p>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Legend -->
    <div class="calendar-legend">
      <span class="legend-item"><span class="legend-dot dot-free"></span> פנוי</span>
      <span class="legend-item"><span class="legend-dot dot-busy"></span> תפוס</span>
      <span class="legend-item"><span class="legend-dot dot-pending"></span> ממתין לאישור</span>
    </div>

    <?php elseif ($journal): ?>
    <div class="card" style="padding:48px;text-align:center;">
      <p class="text-muted">בחר יומן להצגת זמינות</p>
    </div>
    <?php else: ?>
    <div class="card" style="padding:48px;text-align:center;">
      <i class="fa-solid fa-calendar-days" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:12px;"></i>
      <p class="text-muted">בחר יומן מהרשימה משמאל</p>
    </div>
    <?php endif; ?>

  </div><!-- /journal-main -->
</div><!-- /journal-layout -->

<?php
function calStatusClass(string $s): string {
    return match($s) {
        'ממתין לאישור' => 'cs-pending',
        'אושר','מוכן'  => 'cs-approved',
        'סופק'         => 'cs-supplied',
        default        => 'cs-other',
    };
}
?>

<style>
/* ── Journal layout ─────────────────────────────── */
.journal-layout {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 16px;
  align-items: start;
}
.journal-sidebar {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 12px 8px;
  position: sticky;
  top: calc(var(--topbar-h) + 16px);
}
.journal-sidebar-title {
  font-size: .72rem; font-weight: 700; letter-spacing: .06em;
  color: var(--text-muted); text-transform: uppercase;
  padding: 4px 10px 10px;
}
.journal-tab {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px; border-radius: var(--radius);
  color: var(--text-secondary); font-size: .85rem; font-weight: 500;
  transition: background var(--transition), color var(--transition);
  margin-bottom: 2px;
}
.journal-tab:hover { background: var(--bg-input); color: var(--text-primary); }
.journal-tab.active { background: rgba(var(--jcolor-r,79),var(--jcolor-g,142),var(--jcolor-b,247),.12);
  color: var(--text-primary); }
.journal-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--jcolor, #3b82f6); flex-shrink: 0;
}

/* ── Week nav ───────────────────────────────────── */
.week-nav {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 14px; flex-wrap: wrap;
}
.week-label { font-weight: 700; font-size: .95rem; flex: 1; text-align: center; }

/* ── Calendar table ─────────────────────────────── */
.calendar-wrap { overflow-x: auto; }
.calendar-table {
  width: 100%; border-collapse: collapse;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.calendar-table th {
  background: rgba(255,255,255,.03);
  padding: 10px 8px; font-size: .78rem; font-weight: 700;
  color: var(--text-muted); border-bottom: 1px solid var(--border);
  text-align: center; white-space: nowrap;
}
.item-col { text-align: right !important; min-width: 160px; padding: 10px 14px !important; }
.day-col  { min-width: 110px; }
.today-col { background: rgba(79,142,247,.06) !important; }
.day-header { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.day-name   { font-size: .75rem; }
.day-date   { font-size: .85rem; font-weight: 800; color: var(--text-primary); }
.today-badge {
  background: var(--accent); color: #fff;
  border-radius: 50%; width: 26px; height: 26px;
  display: inline-grid; place-items: center; font-size: .82rem;
}

.item-cell {
  padding: 10px 14px !important;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.item-name    { font-size: .82rem; font-weight: 600; }
.item-barcode { font-size: .72rem; color: var(--text-muted); font-family: monospace; }
.item-status-tag {
  display: inline-block; margin-top: 3px;
  font-size: .68rem; padding: 1px 6px; border-radius: 10px;
  background: var(--amber-bg); color: var(--amber);
}

.cal-cell {
  padding: 6px 5px !important;
  border-bottom: 1px solid rgba(255,255,255,.03);
  border-right: 1px solid rgba(255,255,255,.03);
  vertical-align: top; min-height: 52px;
  position: relative;
}
.cal-busy { background: rgba(239,68,68,.05); }
.cal-free { background: rgba(34,197,94,.03); }

.cal-order-tag {
  display: flex; align-items: center; gap: 5px;
  background: var(--red-bg); border: 1px solid rgba(239,68,68,.3);
  border-radius: 5px; padding: 4px 7px; margin-bottom: 3px;
  font-size: .72rem; color: var(--text-primary);
  transition: background var(--transition);
}
.cal-order-tag:hover { background: rgba(239,68,68,.18); }
.cal-order-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cal-order-status {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.cs-pending  { background: var(--amber); }
.cs-approved { background: var(--green); }
.cs-supplied { background: var(--accent); }
.cs-other    { background: var(--gray); }

.cal-free-slot {
  display: grid; place-items: center;
  width: 26px; height: 26px; border-radius: 6px;
  background: rgba(34,197,94,.1); color: var(--green);
  font-size: .78rem; margin: 0 auto;
  transition: background var(--transition);
}
.cal-free-slot:hover { background: rgba(34,197,94,.25); }

.calendar-legend {
  display: flex; gap: 16px; padding: 12px 0;
  font-size: .8rem; color: var(--text-muted);
}
.legend-item { display: flex; align-items: center; gap: 6px; }
.legend-dot  { width: 10px; height: 10px; border-radius: 3px; }
.dot-free    { background: var(--green); opacity: .5; }
.dot-busy    { background: var(--red); opacity: .6; }
.dot-pending { background: var(--amber); }

@media (max-width: 768px) {
  .journal-layout { grid-template-columns: 1fr; }
  .journal-sidebar { position: static; }
}
</style>
