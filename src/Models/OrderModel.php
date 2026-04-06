<?php
class OrderModel {

    private static int $seq = 0;

    /** Generate unique order number like ORD-20240615-0001 */
    public static function generateOrderNumber(): string {
        $date = date('Ymd');
        $last = Database::query(
            "SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["ORD-{$date}-%"]
        )->fetchColumn();
        $seq = $last ? ((int)substr($last, -4) + 1) : 1;
        return sprintf('ORD-%s-%04d', $date, $seq);
    }

    /** Get all orders with filters + pagination */
    public static function getAll(array $filters = [], int $page = 1, bool $includeDeleted = false): array {
        $where  = $includeDeleted ? ['1=1'] : ['o.is_deleted = 0'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'o.user_id = ?'; $params[] = $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'o.status = ?'; $params[] = $filters['status'];
        }
        if (!empty($filters['inventory_id'])) {
            $where[] = 'o.inventory_id = ?'; $params[] = $filters['inventory_id'];
        }
        if (!empty($filters['journal_id'])) {
            $where[] = 'j.id = ?'; $params[] = $filters['journal_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'o.loan_date >= ?'; $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'o.loan_date <= ?'; $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(o.order_number LIKE ? OR i.name LIKE ? OR u.full_name LIKE ? OR o.purpose LIKE ?)';
            $params  = array_merge($params, [$s,$s,$s,$s]);
        }
        if (isset($filters['is_deleted']) && $filters['is_deleted'] !== '') {
            $where[] = 'o.is_deleted = ?'; $params[] = $filters['is_deleted'];
        }

        $whereStr = implode(' AND ', $where);
        $limit    = ITEMS_PER_PAGE;
        $offset   = ($page - 1) * $limit;

        $total = Database::query(
            "SELECT COUNT(*) FROM orders o
             JOIN users u ON u.id = o.user_id
             JOIN inventory i ON i.id = o.inventory_id
             LEFT JOIN journals j ON j.id = i.journal_id
             WHERE $whereStr",
            $params
        )->fetchColumn();

        $rows = Database::query(
            "SELECT o.*, u.full_name AS requester_name, u.id_number AS requester_tz,
                    i.name AS item_name, i.barcode AS item_barcode,
                    j.name AS journal_name, j.color AS journal_color
             FROM orders o
             JOIN users u ON u.id = o.user_id
             JOIN inventory i ON i.id = o.inventory_id
             LEFT JOIN journals j ON j.id = i.journal_id
             WHERE $whereStr
             ORDER BY o.created_at DESC
             LIMIT $limit OFFSET $offset",
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => (int)$total, 'page' => $page, 'limit' => $limit];
    }

    /** Single order by ID */
    public static function findById(int $id): ?array {
        $row = Database::query(
            "SELECT o.*, u.full_name AS requester_name, u.id_number AS requester_tz,
                    u.email AS requester_email, u.phone AS requester_phone,
                    i.name AS item_name, i.barcode AS item_barcode,
                    i.brand AS item_brand, i.model AS item_model,
                    j.name AS journal_name, j.color AS journal_color,
                    cb.full_name AS created_by_name
             FROM orders o
             JOIN users u ON u.id = o.user_id
             JOIN inventory i ON i.id = o.inventory_id
             LEFT JOIN journals j ON j.id = i.journal_id
             LEFT JOIN users cb ON cb.id = o.created_by
             WHERE o.id = ? LIMIT 1",
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /** Check if an item is available for a given time window (optionally exclude an order id) */
    public static function checkAvailability(
        int $inventoryId,
        string $loanDate,
        string $loanTime,
        string $returnDate,
        string $returnTime,
        ?int $excludeOrderId = null
    ): bool {
        $params = [
            $inventoryId,
            $loanDate,   $loanTime,
            $returnDate, $returnTime,
            $loanDate,   $loanTime,
            $returnDate, $returnTime,
        ];
        $excludeSql = $excludeOrderId ? ' AND o.id != ?' : '';
        if ($excludeOrderId) $params[] = $excludeOrderId;

        $conflict = Database::query(
            "SELECT COUNT(*) FROM orders o
             WHERE o.inventory_id = ?
               AND o.status NOT IN ('הוחזר','נדחה','לא נלקח')
               AND o.is_deleted = 0
               -- overlap: new_start < existing_end AND new_end > existing_start
               AND (
                 CONCAT(o.loan_date,' ',o.loan_time) < CONCAT(?,    ' ',?)
                 AND CONCAT(o.return_date,' ',o.return_time) > CONCAT(?,' ',?)
               ) = 0
               -- OR overlap the other way
               AND NOT (
                 CONCAT(?,    ' ',?) >= CONCAT(o.return_date,' ',o.return_time)
                 OR CONCAT(?,' ',?) <= CONCAT(o.loan_date,' ',o.loan_time)
               )
               $excludeSql",
            $params
        )->fetchColumn();

        return (int)$conflict === 0;
    }

    /** Simpler overlap check used in journal view */
    public static function getConflicts(int $inventoryId, string $loanDate, string $returnDate): array {
        return Database::query(
            "SELECT o.id, o.order_number, o.loan_date, o.loan_time, o.return_date, o.return_time,
                    o.status, u.full_name AS requester_name
             FROM orders o JOIN users u ON u.id = o.user_id
             WHERE o.inventory_id = ?
               AND o.is_deleted = 0
               AND o.status NOT IN ('הוחזר','נדחה','לא נלקח')
               AND o.return_date >= ? AND o.loan_date <= ?
             ORDER BY o.loan_date ASC",
            [$inventoryId, $loanDate, $returnDate]
        )->fetchAll();
    }

    /** Get orders for a specific item on or after a date (for journal calendar) */
    public static function getForJournalRange(int $journalId, string $dateFrom, string $dateTo): array {
        return Database::query(
            "SELECT o.inventory_id, o.loan_date, o.loan_time, o.return_date, o.return_time,
                    o.status, o.order_number, o.id,
                    u.full_name AS requester_name,
                    i.name AS item_name, i.barcode
             FROM orders o
             JOIN inventory i ON i.id = o.inventory_id
             JOIN users u ON u.id = o.user_id
             WHERE i.journal_id = ?
               AND o.is_deleted = 0
               AND o.status NOT IN ('הוחזר','נדחה','לא נלקח')
               AND o.return_date >= ? AND o.loan_date <= ?
             ORDER BY o.loan_date ASC, o.loan_time ASC",
            [$journalId, $dateFrom, $dateTo]
        )->fetchAll();
    }

    /** Create a single order */
    public static function create(array $data): int {
        $num = self::generateOrderNumber();
        Database::query(
            'INSERT INTO orders
             (order_number,user_id,inventory_id,status,loan_date,loan_time,
              return_date,return_time,purpose,notes,admin_notes,recurring_group,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $num,
                $data['user_id'],
                $data['inventory_id'],
                $data['status']        ?? 'ממתין לאישור',
                $data['loan_date'],
                $data['loan_time']     ?? '09:00:00',
                $data['return_date'],
                $data['return_time']   ?? '17:00:00',
                $data['purpose']       ?? null,
                $data['notes']         ?? null,
                $data['admin_notes']   ?? null,
                $data['recurring_group'] ?? null,
                $data['created_by'],
            ]
        );
        return (int)Database::lastInsertId();
    }

    /** Update order fields */
    public static function update(int $id, array $data): void {
        $allowed = ['status','loan_date','loan_time','return_date','return_time',
                    'purpose','notes','admin_notes','user_id','inventory_id'];
        $fields = $params = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) return;
        $params[] = $id;
        Database::query('UPDATE orders SET '.implode(', ',$fields).' WHERE id=?', $params);
    }

    /** Soft-delete an order */
    public static function softDelete(int $id): void {
        Database::query(
            'UPDATE orders SET is_deleted=1, deleted_at=NOW() WHERE id=?', [$id]
        );
    }

    /** Statistics for dashboard/reports */
    public static function getStats(?int $userId = null): array {
        $userFilter = $userId ? " AND user_id = $userId" : '';
        return Database::query(
            "SELECT
               COUNT(*) AS total,
               SUM(status='ממתין לאישור' AND is_deleted=0) AS pending,
               SUM(status IN ('אושר','מוכן','סופק') AND is_deleted=0) AS active,
               SUM(status='הוחזר' AND is_deleted=0) AS returned,
               SUM(status='נדחה' AND is_deleted=0) AS rejected,
               SUM(return_date < CURDATE() AND status NOT IN ('הוחזר','נדחה','לא נלקח') AND is_deleted=0) AS overdue
             FROM orders WHERE 1=1 $userFilter"
        )->fetch();
    }

    /** Orders going out today */
    public static function getTodayLoans(): array {
        return Database::query(
            "SELECT o.*, i.name AS item_name, u.full_name AS requester_name
             FROM orders o
             JOIN inventory i ON i.id = o.inventory_id
             JOIN users u ON u.id = o.user_id
             WHERE o.loan_date = CURDATE()
               AND o.status IN ('אושר','מוכן')
               AND o.is_deleted = 0
             ORDER BY o.loan_time ASC"
        )->fetchAll();
    }
}
