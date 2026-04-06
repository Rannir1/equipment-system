<?php
class InventoryController {

    public function index(): void {
        Auth::requireAdmin();
        $filters = [
            'search'           => $_GET['search']           ?? '',
            'journal_id'       => $_GET['journal_id']       ?? '',
            'condition_status' => $_GET['condition_status'] ?? '',
            'is_loanable'      => $_GET['is_loanable']      ?? '',
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $data    = InventoryModel::getAll($filters, $page);
        $journals = InventoryModel::getAllJournals();
        $stats   = InventoryModel::getStats();
        require __DIR__ . '/../Views/inventory/index.php';
    }

    public function showCreate(): void {
        Auth::requireAdmin();
        $journals = InventoryModel::getAllJournals();
        require __DIR__ . '/../Views/inventory/form.php';
    }

    public function handleCreate(): void {
        Auth::requireAdmin();
        $data = $this->sanitizeFormData($_POST);
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $journals = InventoryModel::getAllJournals();
            $formErrors = $errors;
            $formData   = $data;
            require __DIR__ . '/../Views/inventory/form.php';
            return;
        }

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $data['image_path'] = $this->uploadImage($_FILES['image']);
        }

        $id = InventoryModel::create($data);
        $this->logActivity('inventory_create', 'inventory', $id, ['name' => $data['name']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'הפריט נוסף בהצלחה.'];
        header('Location: /inventory');
        exit;
    }

    public function showEdit(int $id): void {
        Auth::requireAdmin();
        $item = InventoryModel::findById($id);
        if (!$item) { http_response_code(404); die('פריט לא נמצא'); }
        $journals   = InventoryModel::getAllJournals();
        $editMode   = true;
        $formData   = $item;
        require __DIR__ . '/../Views/inventory/form.php';
    }

    public function handleEdit(int $id): void {
        Auth::requireAdmin();
        $item = InventoryModel::findById($id);
        if (!$item) { http_response_code(404); die('פריט לא נמצא'); }

        $data   = $this->sanitizeFormData($_POST);
        $errors = $this->validate($data, $id);

        if (!empty($errors)) {
            $journals   = InventoryModel::getAllJournals();
            $editMode   = true;
            $formErrors = $errors;
            $formData   = array_merge($item, $data);
            require __DIR__ . '/../Views/inventory/form.php';
            return;
        }

        if (!empty($_FILES['image']['name'])) {
            $data['image_path'] = $this->uploadImage($_FILES['image']);
        }

        InventoryModel::update($id, $data);
        $this->logActivity('inventory_update', 'inventory', $id, ['name' => $data['name']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'הפריט עודכן בהצלחה.'];
        header('Location: /inventory');
        exit;
    }

    public function handleRemove(int $id): void {
        Auth::requireAdmin();
        $reason = trim($_POST['reason'] ?? 'לא צוין');
        InventoryModel::remove($id, $reason);
        $this->logActivity('inventory_remove', 'inventory', $id, ['reason' => $reason]);
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'הפריט הועבר לפריטים שהוצאו.'];
        header('Location: /inventory');
        exit;
    }

    public function removedItems(): void {
        Auth::requireAdmin();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $data = InventoryModel::getRemoved($page);
        require __DIR__ . '/../Views/inventory/removed.php';
    }

    public function handleRestore(int $id): void {
        Auth::requireAdmin();
        InventoryModel::restore($id);
        $this->logActivity('inventory_restore', 'inventory', $id);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'הפריט שוחזר למלאי.'];
        header('Location: /inventory/removed');
        exit;
    }

    // AJAX: quick barcode lookup
    public function ajaxBarcode(): void {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $barcode = trim($_GET['barcode'] ?? '');
        if (!$barcode) { echo json_encode(['error' => 'חסר ברקוד']); return; }
        $item = InventoryModel::findByBarcode($barcode);
        echo json_encode($item ?: ['error' => 'לא נמצא']);
    }

    // ----------------------------------------------------------------
    private function sanitizeFormData(array $post): array {
        return [
            'barcode'           => trim($post['barcode']           ?? ''),
            'name'              => trim($post['name']              ?? ''),
            'description'       => trim($post['description']       ?? ''),
            'journal_id'        => $post['journal_id']  ? (int)$post['journal_id'] : null,
            'category'          => trim($post['category']          ?? ''),
            'brand'             => trim($post['brand']             ?? ''),
            'model'             => trim($post['model']             ?? ''),
            'serial_number'     => trim($post['serial_number']     ?? ''),
            'location'          => trim($post['location']          ?? ''),
            'condition_status'  => $post['condition_status']       ?? 'תקין',
            'is_loanable'       => isset($post['is_loanable']) ? 1 : 0,
            'quantity'          => max(1, (int)($post['quantity'] ?? 1)),
            'quantity_available'=> max(0, (int)($post['quantity_available'] ?? $post['quantity'] ?? 1)),
            'purchase_date'     => $post['purchase_date']          ?? null,
            'purchase_price'    => $post['purchase_price'] !== '' ? (float)$post['purchase_price'] : null,
            'notes'             => trim($post['notes']             ?? ''),
        ];
    }

    private function validate(array $data, ?int $excludeId = null): array {
        $errors = [];
        if (empty($data['name'])) $errors[] = 'שם הפריט הוא שדה חובה.';
        if ($data['quantity'] < 1) $errors[] = 'כמות חייבת להיות לפחות 1.';
        if ($data['quantity_available'] > $data['quantity'])
            $errors[] = 'כמות זמינה לא יכולה לעלות על הכמות הכוללת.';
        if (!empty($data['barcode'])) {
            $existing = InventoryModel::findByBarcode($data['barcode']);
            if ($existing && $existing['id'] !== $excludeId)
                $errors[] = 'ברקוד זה כבר קיים במערכת.';
        }
        return $errors;
    }

    private function uploadImage(array $file): ?string {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($file['type'], $allowed)) return null;
        $dir = __DIR__ . '/../../public/uploads/inventory/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = uniqid('item_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $dir . $name);
        return '/uploads/inventory/' . $name;
    }

    private function logActivity(string $action, string $entity, int $entityId, array $details = []): void {
        try {
            Database::query(
                'INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address)
                 VALUES (?,?,?,?,?,?)',
                [Auth::user()['id'], $action, $entity, $entityId,
                 json_encode($details), $_SERVER['REMOTE_ADDR'] ?? null]
            );
        } catch (Exception $e) { }
    }
}
