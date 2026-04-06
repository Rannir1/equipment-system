<?php
class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function check(): bool {
        self::start();
        if (empty($_SESSION['user_id'])) return false;
        // Timeout check
        if (!empty($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /dashboard');
            exit;
        }
    }

    public static function isAdmin(): bool {
        return self::check() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function user(): array {
        return [
            'id'        => $_SESSION['user_id']   ?? null,
            'name'      => $_SESSION['user_name']  ?? '',
            'role'      => $_SESSION['user_role']  ?? 'student',
            'id_number' => $_SESSION['user_id_number'] ?? '',
        ];
    }

    public static function login(array $user): void {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id']        = $user['id'];
        $_SESSION['user_name']      = $user['full_name'];
        $_SESSION['user_role']      = $user['role'];
        $_SESSION['user_id_number'] = $user['id_number'];
        $_SESSION['last_activity']  = time();
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}
