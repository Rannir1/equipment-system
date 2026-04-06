<?php
class AuthController {

    public function showLogin(): void {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function handleLogin(): void {
        $idNumber = trim($_POST['id_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $error    = null;

        if (!$idNumber || !$password) {
            $error = 'נא למלא מספר תעודת זהות וסיסמה.';
        } else {
            $user = UserModel::findByIdNumber($idNumber);

            if (!$user || !$user['is_active']) {
                $error = 'מספר תעודת זהות או סיסמה שגויים.';
            } elseif (UserModel::isLocked($user)) {
                $minutes = LOGIN_LOCKOUT_MINUTES;
                $error = "החשבון ננעל זמנית. נסה שוב בעוד {$minutes} דקות.";
            } elseif (!password_verify($password, $user['password_hash'])) {
                UserModel::recordLoginAttempt($user['id'], false);
                $remaining = LOGIN_MAX_ATTEMPTS - ($user['login_attempts'] + 1);
                $error = $remaining > 0
                    ? "סיסמה שגויה. נותרו {$remaining} ניסיונות."
                    : 'החשבון ננעל. נסה שוב לאחר ' . LOGIN_LOCKOUT_MINUTES . ' דקות.';
            } else {
                UserModel::recordLoginAttempt($user['id'], true);
                Auth::login($user);
                // Migrate plain-text password to hash if needed (legacy)
                if (strlen($user['password_hash']) < 20) {
                    UserModel::update($user['id'], ['password' => $password]);
                }
                self::logActivity('login', 'user', $user['id']);
                header('Location: /dashboard');
                exit;
            }
        }

        require __DIR__ . '/../Views/auth/login.php';
    }

    public function handleLogout(): void {
        self::logActivity('logout', 'user', Auth::user()['id'] ?? null);
        Auth::logout();
        header('Location: /login');
        exit;
    }

    private static function logActivity(string $action, string $entity, ?int $entityId): void {
        try {
            Database::query(
                'INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address)
                 VALUES (?, ?, ?, ?, ?)',
                [Auth::user()['id'] ?? null, $action, $entity, $entityId,
                 $_SERVER['REMOTE_ADDR'] ?? null]
            );
        } catch (Exception $e) { /* non-critical */ }
    }
}
