<?php
class UserModel {

    public static function findByIdNumber(string $idNumber): ?array {
        $stmt = Database::query('SELECT * FROM users WHERE id_number = ? LIMIT 1', [$idNumber]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array {
        $stmt = Database::query('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getAll(array $filters = [], int $page = 1): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(full_name LIKE ? OR id_number LIKE ? OR email LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s]);
        }
        if (isset($filters['role']) && $filters['role'] !== '') {
            $where[] = 'role = ?';
            $params[] = $filters['role'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'is_active = ?';
            $params[] = $filters['is_active'];
        }

        $whereStr = implode(' AND ', $where);
        $limit  = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $total = Database::query("SELECT COUNT(*) FROM users WHERE $whereStr", $params)->fetchColumn();
        $rows  = Database::query(
            "SELECT id, id_number, full_name, email, phone, role, is_active, created_at
             FROM users WHERE $whereStr ORDER BY full_name ASC LIMIT $limit OFFSET $offset",
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => (int)$total, 'page' => $page, 'limit' => $limit];
    }

    public static function create(array $data): int {
        Database::query(
            'INSERT INTO users (id_number, full_name, email, phone, role, password_hash)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['id_number'],
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'] ?? 'student',
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $fields = [];
        $params = [];
        $allowed = ['full_name','email','phone','role','is_active'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        if (empty($fields)) return;
        $params[] = $id;
        Database::query('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }

    public static function recordLoginAttempt(int $id, bool $success): void {
        if ($success) {
            Database::query(
                'UPDATE users SET login_attempts=0, locked_until=NULL WHERE id=?', [$id]
            );
        } else {
            Database::query(
                'UPDATE users SET login_attempts = login_attempts + 1,
                 locked_until = IF(login_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)
                 WHERE id = ?',
                [LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES, $id]
            );
        }
    }

    public static function isLocked(array $user): bool {
        if (empty($user['locked_until'])) return false;
        return strtotime($user['locked_until']) > time();
    }
}
