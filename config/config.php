<?php
// =====================================================
// Application Configuration
// =====================================================

define('APP_NAME', 'מערכת ניהול ציוד');
define('APP_ENV', $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production');
define('APP_SECRET', $_ENV['APP_SECRET'] ?? getenv('APP_SECRET') ?? 'fallback_secret_change_me');

// Database
define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'db');
define('DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'equipment_db');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'equipment_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? 'equipment_pass');

// Session
define('SESSION_LIFETIME', 1800); // 30 minutes
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Timezone
date_default_timezone_set('Asia/Jerusalem');

// Error reporting
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
