<?php
class InventoryModel {

    public static function getAll(array $filters = [], int $page = 1): array {
        $where  = ['i.is_removed = 0'];
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(i.name LIKE ? OR i.barcode LIKE ? OR i.brand LIKE ? OR i.model LIKE ? OR i.serial_number LIKE ?)';
            $params  = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        if (!empty($filters['journal_id'])) {
            $where[] = 'i.journal_id = ?';
            $params[] = $filters['journal_id'];
        }
        if (!empty($filters['condition_status'])) {
            $where[] = 'i.condition_status = ?';
            $params[] = $filters['condition_status'];
        }
        if (isset($filters['is_loanable']) && $filters['is_loanable'] !== '') {
            $where[] = 'i.is_loanable = ?';
            $params[] = $filters['is_loanable'];
        }

        $whereStr = implode(' AND ', $where);
        $limit    = ITEMS_PER_PAGE;
        $offset   = ($page - 1) * $limit;

        $total = Database::query(
            "SELECT COUNT(*) FROM inventory i WHERE $whereStr", $params
        )->fetchColumn();

        $rows = Database::query(
            "SELECT i.*, j.name AS journal_name, j.color AS journal_color
             FROM inventory i
             LEFT JOIN journals j ON j.id = i.journal_id
             WHERE $whereStr
             ORDER BY j.sort_order ASC, i.name ASC
             LIMIT $limit OFFSET $offset",
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => (int)$total, 'page' => $page, 'limit' => $limit];
    }

    public static function getAllForLoan(): array {
        return Database::query(
            "SELECT i.*, j.name AS journal_name
             FROM inventory i
             LEFT JOIN journals j ON j.id = i.journal_id
             WHERE i.is_removed = 0 AND i.is_loanable = 1 AND i.condition_status = 'תקין'
               AND i.quantity_available > 0
             ORDER BY j.sort_order ASC, i.name ASC"
        )->fetchAll();
    }

    public static function findById(int $id): ?array {
        $stmt = Database::query(
            "SELECT i.*, j.name AS journal_name
             FROM inventory i LEFT JOIN journals j ON j.id = i.journal_id
             WHERE i.id = ? LIMIT 1",
            [$id]
        );
        return $stmt->fetch() ?: null;
    }

    public static function findByBarcode(string $barcode): ?array {
        $stmt = Database::query(
            "SELECT i.*, j.name AS journal_name
             FROM inventory i LEFT JOIN journals j ON j.id = i.journal_id
             WHERE i.barcode = ? AND i.is_removed = 0 LIMIT 1",
            [$barcode]
        );
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        Database::query(
            'INSERT INTO inventory
             (barcode, name, description, journal_id, category, brand, model,
              serial_number, location, condition_status, is_loanable, quantity,
              quantity_available, purchase_date, purchase_price, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['barcode']           ?? null,
                $data['name'],
                $data['description']       ?? null,
                $data['journal_id']        ?? null,
                $data['category']          ?? null,
                $data['brand']             ?? null,
                $data['model']             ?? null,
                $data['serial_number']     ?? null,
                $data['location']          ?? null,
                $data['condition_status']  ?? 'תקין',
                $data['is_loanable']       ?? 1,
                $data['quantity']          ?? 1,
                $data['quantity_available'] ?? $data['quantity'] ?? 1,
                $data['purchase_date']     ?? null,
                $data['purchase_price']    ?? null,
                $data['notes']             ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $allowed = ['barcode','name','description','journal_id','category','brand','model',
                    'serial_number','location','condition_status','is_loanable','quantity',
                    'quantity_available','purchase_date','purchase_price','notes'];
        $fields = $params = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f] === '' ? null : $data[$f];
            }
        }
        if (empty($fields)) return;
        $params[] = $id;
        Database::query('UPDATE inventory SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }

    public static function remove(int $id, string $reason): void {
        Database::query(
            'UPDATE inventory SET is_removed=1, removed_at=NOW(), removed_reason=? WHERE id=?',
            [$reason, $id]
        );
    }

    public static function restore(int $id): void {
        Database::query(
            'UPDATE inventory SET is_removed=0, removed_at=NULL, removed_reason=NULL WHERE id=?',
            [$id]
        );
    }

    public static function getRemoved(int $page = 1): array {
        $limit  = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $total  = Database::query('SELECT COUNT(*) FROM inventory WHERE is_removed=1')->fetchColumn();
        $rows   = Database::query(
            "SELECT i.*, j.name AS journal_name FROM inventory i
             LEFT JOIN journals j ON j.id = i.journal_id
             WHERE i.is_removed = 1
             ORDER BY i.removed_at DESC LIMIT $limit OFFSET $offset"
        )->fetchAll();
        return ['rows' => $rows, 'total' => (int)$total, 'page' => $page, 'limit' => $limit];
    }

    public static function getStats(): array {
        return Database::query(
            "SELECT
               COUNT(*) AS total,
               SUM(is_removed = 0 AND condition_status = 'תקין' AND is_loanable = 1) AS available,
               SUM(is_removed = 0 AND condition_status = 'פגום') AS damaged,
               SUM(is_removed = 0 AND condition_status = 'בתיקון') AS in_repair,
               SUM(is_removed = 1) AS removed,
               SUM(is_removed = 0 AND quantity_available = 0 AND is_loanable = 1) AS all_on_loan
             FROM inventory"
        )->fetch();
    }

    public static function getAllJournals(): array {
        return Database::query('SELECT * FROM journals ORDER BY sort_order ASC, name ASC')->fetchAll();
    }
}
