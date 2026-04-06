<?php
$formData   = $formData   ?? [];
$formErrors = $formErrors ?? [];
$prefill    = $prefill    ?? [];
$loanPolicy = '';
try { $loanPolicy = Database::query("SELECT `value` FROM settings WHERE `key`='loan_policy' LIMIT 1")->fetchColumn() ?: ''; } catch(Exception $e){}

$hebrewDays = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <nav class="breadcrumb">
      <a href="<?= $isAdmin ? '/orders' : '/my-orders' ?>">הזמנות</a>
      <span>›</span><span>הזמנה חדשה</span>
    </nav>
  </div>
</div>

<?php if (!empty($formErrors)): ?>
<div class="alert alert-error">
  <i class="fa-solid fa-circle-exclamation"></i>
  <ul class="mb-0"><?php foreach ($formErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="/orders/create" class="card form-card" id="orderForm">

  <div class="form-section">
    <h3 class="form-section-title"><i class="fa-solid fa-boxes-stacked"></i> בחירת פריט</h3>
    <div class="form-grid">

      <?php if ($isAdmin): ?>
      <div class="form-group">
        <label>מזמין</label>
        <select name="user_id" class="form-control form-select">
          <option value="">— בחר משתמש —</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= ($formData['user_id']??'')==$u['id']?'selected':'' ?>>
            <?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['id_number']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="form-group form-col-2">
        <label class="required">פריט להשאלה</label>
        <select name="inventory_id" id="inventorySelect" class="form-control form-select" required onchange="checkItemAvailability()">
          <option value="">— בחר פריט —</option>
          <?php
          $currentJournal = '';
          foreach ($items as $item):
            $jName = $item['journal_name'] ?? 'אחר';
            if ($jName !== $currentJournal):
              if ($currentJournal) echo '</optgroup>';
              echo '<optgroup label="' . htmlspecialchars($jName) . '">';
              $currentJournal = $jName;
            endif;
            $selected = ($formData['inventory_id']??$prefill['inventory_id']??0) == $item['id'] ? 'selected' : '';
          ?>
          <option value="<?= $item['id'] ?>" <?= $selected ?>
            data-barcode="<?= htmlspecialchars($item['barcode']??'') ?>">
            <?= htmlspecialchars($item['name']) ?>
            <?php if ($item['barcode']): ?> [<?= htmlspecialchars($item['barcode']) ?>]<?php endif; ?>
          </option>
          <?php endforeach; if ($currentJournal) echo '</optgroup>'; ?>
        </select>
        <div id="availabilityStatus" class="availability-indicator"></div>
      </div>

    </div>
  </div>

  <div class="form-section">
    <h3 class="form-section-title"><i class="fa-solid fa-calendar-days"></i> תאריכים ושעות</h3>
    <div class="form-grid">

      <div class="form-group">
        <label class="required">תאריך שאילה</label>
        <input type="date" name="loan_date" id="loanDate" class="form-control" required
               value="<?= htmlspecialchars($formData['loan_date']??$prefill['loan_date']??date('Y-m-d')) ?>"
               onchange="updateDayLabel('loanDate','loanDayLabel'); syncReturnDate(); checkItemAvailability()">
        <div class="day-label" id="loanDayLabel"></div>
      </div>

      <div class="form-group">
        <label class="required">שעת שאילה</label>
        <input type="time" name="loan_time" id="loanTime" class="form-control"
               value="<?= htmlspecialchars($formData['loan_time']??'09:00') ?>"
               onchange="checkItemAvailability()">
      </div>

      <div class="form-group">
        <label class="required">תאריך החזרה</label>
        <input type="date" name="return_date" id="returnDate" class="form-control" required
               value="<?= htmlspecialchars($formData['return_date']??$prefill['return_date']??date('Y-m-d')) ?>"
               onchange="updateDayLabel('returnDate','returnDayLabel'); checkItemAvailability()">
        <div class="day-label" id="returnDayLabel"></div>
      </div>

      <div class="form-group">
        <label class="required">שעת החזרה</label>
        <input type="time" name="return_time" id="returnTime" class="form-control"
               value="<?= htmlspecialchars($formData['return_time']??'17:00') ?>"
               onchange="checkItemAvailability()">
      </div>

    </div>
  </div>

  <div class="form-section">
    <h3 class="form-section-title"><i class="fa-solid fa-circle-info"></i> פרטים</h3>
    <div class="form-grid">

      <div class="form-group form-col-2">
        <label class="required">מטרה / פרויקט</label>
        <input type="text" name="purpose" class="form-control"
               placeholder="שם הפרויקט, קורס, שם המרצה"
               value="<?= htmlspecialchars($formData['purpose']??'') ?>" required>
      </div>

      <div class="form-group form-col-2">
        <label>הערות למנהל</label>
        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($formData['notes']??'') ?></textarea>
      </div>

      <?php if ($isAdmin): ?>
      <div class="form-group form-col-2">
        <label>הערות פנימיות (מנהל)</label>
        <textarea name="admin_notes" class="form-control" rows="2"><?= htmlspecialchars($formData['admin_notes']??'') ?></textarea>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <?php if (!$isAdmin && $loanPolicy): ?>
  <div class="form-section">
    <div class="policy-box">
      <h4><i class="fa-solid fa-scroll"></i> נהלי השאלה</h4>
      <p><?= nl2br(htmlspecialchars($loanPolicy)) ?></p>
      <label class="checkbox-label" style="margin-top:14px;">
        <input type="checkbox" name="policy_accept" value="1" required>
        קראתי ואני מסכים/ה לנהלי ההשאלה
      </label>
    </div>
  </div>
  <?php endif; ?>

  <div class="form-actions">
    <a href="<?= $isAdmin ? '/orders' : '/my-orders' ?>" class="btn btn-outline"><i class="fa-solid fa-xmark"></i> ביטול</a>
    <button type="submit" class="btn btn-primary" id="submitBtn">
      <i class="fa-solid fa-paper-plane"></i>
      <?= $isAdmin ? 'צור הזמנה (אושרה)' : 'שלח בקשת השאלה' ?>
    </button>
  </div>

</form>

<style>
.availability-indicator { margin-top:6px; font-size:.82rem; min-height:20px; }
.avail-ok    { color:var(--green); }
.avail-busy  { color:var(--red); }
.avail-check { color:var(--text-muted); }
.day-label   { font-size:.78rem; color:var(--accent); margin-top:4px; min-height:18px; }
.policy-box  { background:var(--bg-input); border:1px solid var(--border); border-radius:var(--radius);
               padding:16px 20px; }
.policy-box h4 { font-size:.9rem; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
.policy-box p  { font-size:.82rem; color:var(--text-secondary); line-height:1.6; }
</style>

<script>
const hebrewDays = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];

function updateDayLabel(inputId, labelId) {
  const val = document.getElementById(inputId)?.value;
  if (!val) return;
  const d = new Date(val + 'T00:00:00');
  document.getElementById(labelId).textContent = 'יום ' + hebrewDays[d.getDay()];
}

function syncReturnDate() {
  const ld = document.getElementById('loanDate').value;
  const rd = document.getElementById('returnDate');
  if (rd.value < ld) rd.value = ld;
}

let avTimer = null;
function checkItemAvailability() {
  clearTimeout(avTimer);
  avTimer = setTimeout(async () => {
    const invId  = document.getElementById('inventorySelect')?.value;
    const ld     = document.getElementById('loanDate')?.value;
    const lt     = document.getElementById('loanTime')?.value;
    const rd     = document.getElementById('returnDate')?.value;
    const rt     = document.getElementById('returnTime')?.value;
    const status = document.getElementById('availabilityStatus');
    if (!invId || !ld || !rd || !status) return;
    status.innerHTML = '<span class="avail-check"><i class="fa-solid fa-spinner fa-spin"></i> בודק זמינות…</span>';
    try {
      const r = await fetch(`/orders/availability?inventory_id=${invId}&loan_date=${ld}&loan_time=${lt}&return_date=${rd}&return_time=${rt}`);
      const d = await r.json();
      if (d.available) {
        status.innerHTML = '<span class="avail-ok"><i class="fa-solid fa-circle-check"></i> הפריט פנוי</span>';
        document.getElementById('submitBtn').disabled = false;
      } else {
        status.innerHTML = '<span class="avail-busy"><i class="fa-solid fa-circle-xmark"></i> הפריט תפוס בתאריכים אלו</span>';
        document.getElementById('submitBtn').disabled = true;
      }
    } catch(e) { status.innerHTML = ''; }
  }, 400);
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  updateDayLabel('loanDate',   'loanDayLabel');
  updateDayLabel('returnDate', 'returnDayLabel');
  const inv = document.getElementById('inventorySelect');
  if (inv?.value) checkItemAvailability();
});
</script>
