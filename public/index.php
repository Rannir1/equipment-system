<?php
// =====================================================
// Front Controller — Equipment Management System
// =====================================================

define('ROOT', dirname(__DIR__));

require ROOT . '/config/config.php';
require ROOT . '/src/Database.php';
require ROOT . '/src/Middleware/Auth.php';
require ROOT . '/src/Models/UserModel.php';
require ROOT . '/src/Models/InventoryModel.php';
require ROOT . '/src/Models/OrderModel.php';
require ROOT . '/src/Models/JournalModel.php';
require ROOT . '/src/Controllers/AuthController.php';
require ROOT . '/src/Controllers/InventoryController.php';
require ROOT . '/src/Controllers/ImportController.php';
require ROOT . '/src/Controllers/OrderController.php';
require ROOT . '/src/Controllers/JournalController.php';

Auth::start();

// ── Simple router ───────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = strtok($_SERVER['REQUEST_URI'], '?');
$uri    = rtrim($uri, '/') ?: '/';

// ── Static assets pass-through (Apache handles these) ───
// (no action needed here — Apache will serve /assets/, /uploads/ directly)

// ─────────────────────────────────────────────────────────
// ROUTES
// ─────────────────────────────────────────────────────────
$auth      = new AuthController();
$inventory = new InventoryController();
$import    = new ImportController();
$orders    = new OrderController();
$journals  = new JournalController();

// AUTH
if ($uri === '/' || $uri === '/login') {
    if ($method === 'POST') { $auth->handleLogin(); }
    else                    { $auth->showLogin(); }
    exit;
}
if ($uri === '/logout') {
    $auth->handleLogout();
    exit;
}

// DASHBOARD
if ($uri === '/dashboard') {
    Auth::requireLogin();
    $currentUser = Auth::user();
    $isAdmin     = Auth::isAdmin();
    require ROOT . '/src/Views/dashboard/index.php';
    exit;
}

// INVENTORY
if ($uri === '/inventory' && $method === 'GET') {
    $inventory->index();
    exit;
}
if ($uri === '/inventory/create' && $method === 'GET') {
    $inventory->showCreate();
    exit;
}
if ($uri === '/inventory/create' && $method === 'POST') {
    $inventory->handleCreate();
    exit;
}
if ($uri === '/inventory/removed') {
    $inventory->removedItems();
    exit;
}
if ($uri === '/inventory/barcode') {
    $inventory->ajaxBarcode();
    exit;
}

// /inventory/{id}/edit
if (preg_match('#^/inventory/(\d+)/edit$#', $uri, $m)) {
    if ($method === 'POST') { $inventory->handleEdit((int)$m[1]); }
    else                    { $inventory->showEdit((int)$m[1]); }
    exit;
}
// /inventory/{id}/remove
if (preg_match('#^/inventory/(\d+)/remove$#', $uri, $m) && $method === 'POST') {
    $inventory->handleRemove((int)$m[1]);
    exit;
}
// /inventory/{id}/restore
if (preg_match('#^/inventory/(\d+)/restore$#', $uri, $m) && $method === 'POST') {
    $inventory->handleRestore((int)$m[1]);
    exit;
}

// ORDERS
if ($uri === '/orders' && $method === 'GET')          { $orders->index();      exit; }
if ($uri === '/my-orders')                            { $orders->myOrders();   exit; }
if ($uri === '/orders/create' && $method === 'GET')   { $orders->showCreate(); exit; }
if ($uri === '/orders/create' && $method === 'POST')  { $orders->handleCreate(); exit; }
if ($uri === '/orders/availability')                  { $orders->ajaxCheckAvailability(); exit; }
if (preg_match('#^/orders/(\d+)$#', $uri, $m)) {
    $orders->show((int)$m[1]); exit;
}
if (preg_match('#^/orders/(\d+)/status$#', $uri, $m) && $method === 'POST') {
    $orders->updateStatus((int)$m[1]); exit;
}
if (preg_match('#^/orders/(\d+)/delete$#', $uri, $m) && $method === 'POST') {
    $orders->delete((int)$m[1]); exit;
}
if (preg_match('#^/orders/(\d+)/cancel$#', $uri, $m) && $method === 'POST') {
    $orders->cancelRequest((int)$m[1]); exit;
}

// JOURNALS
if ($uri === '/journals' && $method === 'GET')        { $journals->index();   exit; }
if ($uri === '/journals/week')                        { $journals->ajaxWeek(); exit; }

// IMPORT
if ($uri === '/import' && $method === 'GET') {
    $import->showImport();
    exit;
}
if ($uri === '/import/inventory' && $method === 'POST') {
    $import->handleInventoryImport();
    exit;
}
if ($uri === '/import/students' && $method === 'POST') {
    $import->handleStudentsImport();
    exit;
}

// PLACEHOLDER PAGES (stubs for nav links — will be built next)
$stubPages = [
    '/loans'         => ['השאלות / החזרות', 'loans'],
    '/users'         => ['ניהול משתמשים',   'users'],
    '/reports'       => ['דוחות',           'reports'],
    '/settings'      => ['הגדרות',          'settings'],
    '/notifications' => ['התראות',          'notifications'],
    '/search'        => ['תוצאות חיפוש',    'search'],
];

if (isset($stubPages[$uri])) {
    Auth::requireLogin();
    [$stubTitle, $stubPage] = $stubPages[$uri];
    $currentUser = Auth::user();
    $isAdmin     = Auth::isAdmin();
    require ROOT . '/src/Views/stub.php';
    exit;
}

// ── 404 ─────────────────────────────────────────────────
http_response_code(404);
$currentUser = Auth::check() ? Auth::user() : ['name'=>'אורח','role'=>'student'];
$isAdmin     = Auth::isAdmin();
require ROOT . '/src/Views/404.php';
