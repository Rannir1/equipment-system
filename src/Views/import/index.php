<?php
$pageTitle   = 'ייבוא נתונים';
$currentPage = 'settings';
ob_start();
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <p class="text-muted">ייבוא מלאי וסטודנטים מקבצי CSV</p>
  </div>
</div>

<div class="import-grid">

  <!-- Inventory import -->
  <div class="card import-card">
    <div class="card-header">
      <h3><i class="fa-solid fa-boxes-stacked"></i> ייבוא מלאי ציוד</h3>
    </div>
    <div class="import-body">
      <p class="text-muted" style="margin-bottom:16px; font-size:.875rem;">
        קובץ CSV עם עמודות: <code>ברקוד, שם, סוג, יצרן, מ. סידורי, מחיר, תאריך, יומן, להשאלה, סטטוס, מיקום</code>
      </p>
      <div class="import-notes">
        <div class="note-item"><i class="fa-solid fa-circle-check text-green"></i> ברקוד כפול יידלג אוטומטית</div>
        <div class="note-item"><i class="fa-solid fa-circle-check text-green"></i> יומן מוקצה לפי שם הפריט</div>
        <div class="note-item"><i class="fa-solid fa-circle-check text-green"></i> תמיכה ב-BOM/UTF-8</div>
      </div>
      <form method="POST" action="/import/inventory" enctype="multipart/form-data" class="import-form">
        <div class="form-group">
          <label>בחר קובץ CSV</label>
          <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-upload"></i> ייבא מלאי
        </button>
      </form>
    </div>
  </div>

  <!-- Students import -->
  <div class="card import-card">
    <div class="card-header">
      <h3><i class="fa-solid fa-users"></i> ייבוא סטודנטים</h3>
    </div>
    <div class="import-body">
      <p class="text-muted" style="margin-bottom:16px; font-size:.875rem;">
        קובץ CSV עם עמודות: <code>tz, name, phone, email, address, role, year, password</code>
      </p>
      <div class="import-notes">
        <div class="note-item"><i class="fa-solid fa-circle-check text-green"></i> ת.ז. כפולה תידלג</div>
        <div class="note-item"><i class="fa-solid fa-circle-check text-green"></i> סיסמאות מוצפנות (bcrypt)</div>
        <div class="note-item"><i class="fa-solid fa-circle-check text-green"></i> תפקיד ברירת מחדל: סטודנט</div>
      </div>
      <form method="POST" action="/import/students" enctype="multipart/form-data" class="import-form">
        <div class="form-group">
          <label>בחר קובץ CSV</label>
          <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-upload"></i> ייבא סטודנטים
        </button>
      </form>
    </div>
  </div>

</div>

<style>
.import-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 20px; }
.import-body { padding: 20px; }
.import-form { margin-top: 20px; display: flex; flex-direction: column; gap: 14px; }
.import-notes { display: flex; flex-direction: column; gap: 6px; }
.note-item { font-size: .82rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
