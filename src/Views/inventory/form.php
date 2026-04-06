<?php
$editMode  = $editMode ?? false;
$formData  = $formData ?? [];
$pageTitle = $editMode ? 'עריכת פריט' : 'הוספת פריט חדש';
$currentPage = 'inventory';
$action    = $editMode ? '/inventory/' . $formData['id'] . '/edit' : '/inventory/create';

ob_start();
?>

<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <nav class="breadcrumb">
      <a href="/inventory">מלאי ציוד</a> <span>›</span> <span><?= $pageTitle ?></span>
    </nav>
  </div>
</div>

<?php if (!empty($formErrors)): ?>
<div class="alert alert-error">
  <i class="fa-solid fa-circle-exclamation"></i>
  <ul class="mb-0">
    <?php foreach ($formErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="card form-card">

  <div class="form-section">
    <h3 class="form-section-title"><i class="fa-solid fa-tag"></i> פרטי הפריט</h3>
    <div class="form-grid">

      <div class="form-group form-col-2">
        <label class="required">שם הפריט</label>
        <input type="text" name="name" class="form-control"
               value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label>ברקוד / מק"ט</label>
        <div class="input-with-btn">
          <input type="text" name="barcode" id="barcodeInput" class="form-control"
                 value="<?= htmlspecialchars($formData['barcode'] ?? '') ?>">
          <button type="button" class="btn btn-outline btn-sm" onclick="generateBarcode()">
            <i class="fa-solid fa-barcode"></i> ייצר
          </button>
        </div>
      </div>

      <div class="form-group">
        <label>יומן / קטגוריה</label>
        <select name="journal_id" class="form-control form-select">
          <option value="">— בחר יומן —</option>
          <?php foreach ($journals as $j): ?>
          <option value="<?= $j['id'] ?>"
            <?= ($formData['journal_id'] ?? '') == $j['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($j['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>מותג</label>
        <input type="text" name="brand" class="form-control"
               value="<?= htmlspecialchars($formData['brand'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>דגם</label>
        <input type="text" name="model" class="form-control"
               value="<?= htmlspecialchars($formData['model'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>מספר סידורי</label>
        <input type="text" name="serial_number" class="form-control"
               value="<?= htmlspecialchars($formData['serial_number'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>מיקום במחסן</label>
        <input type="text" name="location" class="form-control" placeholder="לדוגמה: מדף A1"
               value="<?= htmlspecialchars($formData['location'] ?? '') ?>">
      </div>

      <div class="form-group form-col-2">
        <label>תיאור</label>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
      </div>

    </div>
  </div>

  <div class="form-section">
    <h3 class="form-section-title"><i class="fa-solid fa-sliders"></i> סטטוס וכמות</h3>
    <div class="form-grid">

      <div class="form-group">
        <label>מצב הפריט</label>
        <select name="condition_status" class="form-control form-select">
          <?php foreach (['תקין','פגום','בתיקון','מושבת'] as $c): ?>
          <option value="<?= $c ?>" <?= ($formData['condition_status'] ?? 'תקין') === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="required">כמות כוללת</label>
        <input type="number" name="quantity" class="form-control" min="1"
               value="<?= (int)($formData['quantity'] ?? 1) ?>" id="qtyTotal" oninput="syncQty()">
      </div>

      <div class="form-group">
        <label>כמות זמינה</label>
        <input type="number" name="quantity_available" class="form-control" min="0"
               value="<?= (int)($formData['quantity_available'] ?? $formData['quantity'] ?? 1) ?>" id="qtyAvail">
      </div>

      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="is_loanable" value="1"
                 <?= ($formData['is_loanable'] ?? 1) ? 'checked' : '' ?>>
          ניתן להשאלה
        </label>
      </div>

    </div>
  </div>

  <div class="form-section">
    <h3 class="form-section-title"><i class="fa-solid fa-receipt"></i> מידע רכישה (אופציונלי)</h3>
    <div class="form-grid">

      <div class="form-group">
        <label>תאריך רכישה</label>
        <input type="date" name="purchase_date" class="form-control"
               value="<?= htmlspecialchars($formData['purchase_date'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>מחיר רכישה (₪)</label>
        <input type="number" name="purchase_price" class="form-control" min="0" step="0.01"
               value="<?= htmlspecialchars($formData['purchase_price'] ?? '') ?>">
      </div>

    </div>
  </div>

  <div class="form-section">
    <h3 class="form-section-title"><i class="fa-solid fa-image"></i> תמונה והערות</h3>
    <div class="form-grid">

      <div class="form-group">
        <label>תמונת הפריט</label>
        <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
        <?php if (!empty($formData['image_path'])): ?>
        <div class="image-preview"><img src="<?= htmlspecialchars($formData['image_path']) ?>" alt="תמונה נוכחית"></div>
        <?php endif; ?>
        <div id="imgPreview"></div>
      </div>

      <div class="form-group form-col-2">
        <label>הערות</label>
        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
      </div>

    </div>
  </div>

  <div class="form-actions">
    <a href="/inventory" class="btn btn-outline"><i class="fa-solid fa-xmark"></i> ביטול</a>
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk"></i>
      <?= $editMode ? 'שמור שינויים' : 'הוסף פריט' ?>
    </button>
  </div>

</form>

<script>
function syncQty() {
  const total = parseInt(document.getElementById('qtyTotal').value) || 1;
  const avail = document.getElementById('qtyAvail');
  if (parseInt(avail.value) > total) avail.value = total;
  avail.max = total;
}
function generateBarcode() {
  const ts  = Date.now().toString().slice(-8);
  const rnd = Math.floor(Math.random() * 100).toString().padStart(2,'0');
  document.getElementById('barcodeInput').value = 'ITEM-' + ts + rnd;
}
function previewImage(input) {
  const preview = document.getElementById('imgPreview');
  preview.innerHTML = '';
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      preview.innerHTML = '<div class="image-preview"><img src="' + e.target.result + '" alt="תצוגה מקדימה"></div>';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
