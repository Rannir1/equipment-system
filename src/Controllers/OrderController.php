<?php
class OrderController {

    // ── Orders list (admin) ──────────────────────────────
    public function index(): void {
        Auth::requireLogin();
        if (!Auth::isAdmin()) { header('Location: /my-orders'); exit; }

        $filters = [
            'status'    => $_GET['status']    ?? '',
            'search'    => $_GET['search']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
            'journal_id'=> $_GET['journal_id']?? '',
        ];
        $page    = max(1,(int)($_GET['page']??1));
        $data    = OrderModel::getAll($filters, $page);
        $journals = JournalModel::getAll();
        $stats   = OrderModel::getStats();
        $currentUser = Auth::user();
        $isAdmin = true;
        $pageTitle   = 'הזמנות';
        $currentPage = 'orders';
        ob_start();
        require __DIR__ . '/../Views/orders/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/main.php';
    }

    // ── My orders (student + admin) ──────────────────────
    public function myOrders(): void {
        Auth::requireLogin();
        $user    = Auth::user();
        $filters = ['user_id' => $user['id'],
                    'search'  => $_GET['search'] ?? '',
                    'status'  => $_GET['status'] ?? ''];
        $page    = max(1,(int)($_GET['page']??1));
        $data    = OrderModel::getAll($filters, $page);
        $stats   = OrderModel::getStats($user['id']);
        $currentUser = $user;
        $isAdmin = Auth::isAdmin();
        $pageTitle   = 'ההזמנות שלי';
        $currentPage = 'my_reports';
        ob_start();
        require __DIR__ . '/../Views/orders/my_orders.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/main.php';
    }

    // ── Show create form ─────────────────────────────────
    public function showCreate(): void {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $isAdmin     = Auth::isAdmin();
        $journals    = JournalModel::getAll(!$isAdmin); // students: no hidden
        $items       = InventoryModel::getAllForLoan();
        $users       = $isAdmin ? $this->getStudentList() : [];

        // Pre-fill from query string (from journal click)
        $prefill = [
            'inventory_id' => (int)($_GET['item'] ?? 0),
            'loan_date'    => $_GET['date'] ?? date('Y-m-d'),
            'return_date'  => $_GET['date'] ?? date('Y-m-d'),
        ];

        $pageTitle   = 'הזמנה חדשה';
        $currentPage = 'orders';
        ob_start();
        require __DIR__ . '/../Views/orders/create.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/main.php';
    }

    // ── Handle create ────────────────────────────────────
    public function handleCreate(): void {
        Auth::requireLogin();
        $user    = Auth::user();
        $isAdmin = Auth::isAdmin();
        $post    = $_POST;

        // Determine requester
        $userId = $isAdmin && !empty($post['user_id'])
            ? (int)$post['user_id']
            : $user['id'];

        $data = [
            'user_id'      => $userId,
            'inventory_id' => (int)($post['inventory_id'] ?? 0),
            'loan_date'    => $post['loan_date']   ?? '',
            'loan_time'    => $post['loan_time']   ?? '09:00',
            'return_date'  => $post['return_date'] ?? '',
            'return_time'  => $post['return_time'] ?? '17:00',
            'purpose'      => trim($post['purpose']      ?? ''),
            'notes'        => trim($post['notes']        ?? ''),
            'admin_notes'  => trim($post['admin_notes']  ?? ''),
            'created_by'   => $user['id'],
            'status'       => $isAdmin ? 'אושר' : 'ממתין לאישור',
        ];

        // Validate
        $errors = $this->validateOrder($data, $isAdmin, $post);
        if (!empty($errors)) {
            $currentUser = $user;
            $journals    = JournalModel::getAll(!$isAdmin);
            $items       = InventoryModel::getAllForLoan();
            $users       = $isAdmin ? $this->getStudentList() : [];
            $formErrors  = $errors;
            $formData    = array_merge($data, $post);
            $prefill     = [];
            $pageTitle   = 'הזמנה חדשה';
            $currentPage = 'orders';
            ob_start();
            require __DIR__ . '/../Views/orders/create.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        // Handle multi-date (admin only)
        if ($isAdmin && !empty($post['multi_dates'])) {
            $this->createMultiDate($data, $post);
            return;
        }

        // Single order
        $orderId = OrderModel::create($data);
        $this->notifyOnCreate($orderId, $data, $isAdmin);
        $this->logActivity('order_create','order',$orderId);

        $_SESSION['flash'] = ['type'=>'success','msg'=>'ההזמנה נוצרה בהצלחה.'];
        header('Location: ' . ($isAdmin ? '/orders' : '/my-orders'));
        exit;
    }

    // ── Show single order ────────────────────────────────
    public function show(int $id): void {
        Auth::requireLogin();
        $order = OrderModel::findById($id);
        if (!$order) { http_response_code(404); die('הזמנה לא נמצאה'); }
        $user    = Auth::user();
        $isAdmin = Auth::isAdmin();
        // Students can only see their own
        if (!$isAdmin && $order['user_id'] != $user['id']) {
            header('Location: /my-orders'); exit;
        }
        $currentUser = $user;
        $pageTitle   = 'הזמנה ' . $order['order_number'];
        $currentPage = $isAdmin ? 'orders' : 'my_reports';
        ob_start();
        require __DIR__ . '/../Views/orders/show.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/main.php';
    }

    // ── Update status (admin) ────────────────────────────
    public function updateStatus(int $id): void {
        Auth::requireAdmin();
        $order = OrderModel::findById($id);
        if (!$order) { http_response_code(404); exit; }

        $newStatus   = $_POST['status']      ?? '';
        $adminNotes  = trim($_POST['admin_notes'] ?? '');
        $validStatuses = ['ממתין לאישור','אושר','מוכן','סופק','הוחזר חלקית','הוחזר','לא נלקח','נדחה'];

        if (!in_array($newStatus, $validStatuses)) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'סטטוס לא חוקי.'];
            header("Location: /orders/$id"); exit;
        }

        OrderModel::update($id, ['status'=>$newStatus,'admin_notes'=>$adminNotes]);
        $this->notifyStatusChange($id, $order, $newStatus);
        $this->logActivity('order_status_change','order',$id,
            ['from'=>$order['status'],'to'=>$newStatus]);

        $_SESSION['flash'] = ['type'=>'success','msg'=>"סטטוס עודכן ל: {$newStatus}"];
        header("Location: /orders/$id"); exit;
    }

    // ── Soft delete (admin) ──────────────────────────────
    public function delete(int $id): void {
        Auth::requireAdmin();
        OrderModel::softDelete($id);
        $this->logActivity('order_delete','order',$id);
        $_SESSION['flash'] = ['type'=>'warning','msg'=>'ההזמנה הועברה לארכיון.'];
        header('Location: /orders'); exit;
    }

    // ── Cancel request (student) ─────────────────────────
    public function cancelRequest(int $id): void {
        Auth::requireLogin();
        $order = OrderModel::findById($id);
        $user  = Auth::user();
        if (!$order || $order['user_id'] != $user['id']) {
            header('Location: /my-orders'); exit;
        }
        if (!in_array($order['status'], ['ממתין לאישור','אושר'])) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'לא ניתן לבטל הזמנה בסטטוס זה.'];
            header('Location: /my-orders'); exit;
        }
        OrderModel::update($id, ['status'=>'לא נלקח']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'הבקשה לביטול נשלחה.'];
        header('Location: /my-orders'); exit;
    }

    // ── AJAX: availability check ─────────────────────────
    public function ajaxCheckAvailability(): void {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $invId      = (int)($_GET['inventory_id'] ?? 0);
        $loanDate   = $_GET['loan_date']   ?? '';
        $loanTime   = $_GET['loan_time']   ?? '00:00';
        $returnDate = $_GET['return_date'] ?? '';
        $returnTime = $_GET['return_time'] ?? '23:59';
        $excludeId  = (int)($_GET['exclude'] ?? 0) ?: null;

        if (!$invId || !$loanDate || !$returnDate) {
            echo json_encode(['available'=>false,'error'=>'Missing params']);
            return;
        }
        $available = OrderModel::checkAvailability(
            $invId,$loanDate,$loanTime,$returnDate,$returnTime,$excludeId
        );
        echo json_encode(['available'=>$available]);
    }

    // ── Helpers ────────────────────────────────────────────
    private function validateOrder(array $data, bool $isAdmin, array $post): array {
        $errors = [];
        if (!$data['inventory_id'])  $errors[] = 'יש לבחור פריט.';
        if (!$data['loan_date'])     $errors[] = 'יש לבחור תאריך שאילה.';
        if (!$data['return_date'])   $errors[] = 'יש לבחור תאריך החזרה.';
        if ($data['loan_date'] > $data['return_date'])
            $errors[] = 'תאריך החזרה חייב להיות אחרי תאריך השאילה.';
        if (!$isAdmin && empty($post['policy_accept']))
            $errors[] = 'יש לאשר את נהלי ההשאלה.';

        if ($data['inventory_id'] && $data['loan_date'] && $data['return_date']) {
            // Skip availability check for multi-date (handled per-date)
            if (empty($post['multi_dates'])) {
                $ok = OrderModel::checkAvailability(
                    $data['inventory_id'],
                    $data['loan_date'], $data['loan_time'],
                    $data['return_date'], $data['return_time']
                );
                if (!$ok) $errors[] = 'הפריט תפוס בחלון הזמן שנבחר.';
            }
        }
        return $errors;
    }

    private function createMultiDate(array $baseData, array $post): void {
        $dates  = json_decode($post['multi_dates'], true) ?? [];
        $group  = 'GRP-' . date('Ymd') . '-' . substr(uniqid(),8);
        $created = 0;
        $skipped = 0;

        foreach ($dates as $d) {
            $data = array_merge($baseData, [
                'loan_date'      => $d['loan_date'],
                'return_date'    => $d['return_date'],
                'loan_time'      => $d['loan_time']   ?? $baseData['loan_time'],
                'return_time'    => $d['return_time'] ?? $baseData['return_time'],
                'recurring_group'=> $group,
            ]);
            $ok = OrderModel::checkAvailability(
                $data['inventory_id'],
                $data['loan_date'], $data['loan_time'],
                $data['return_date'], $data['return_time']
            );
            if ($ok) { OrderModel::create($data); $created++; }
            else     { $skipped++; }
        }
        $_SESSION['flash'] = [
            'type' => $skipped ? 'warning' : 'success',
            'msg'  => "נוצרו {$created} הזמנות." . ($skipped ? " {$skipped} דולגו (תפוסות)." : ''),
        ];
        header('Location: /orders'); exit;
    }

    private function notifyOnCreate(int $orderId, array $data, bool $isAdmin): void {
        try {
            if (!$isAdmin) {
                // Notify admins
                $admins = Database::query(
                    "SELECT id FROM users WHERE role='admin' AND is_active=1"
                )->fetchAll();
                $item = InventoryModel::findById($data['inventory_id']);
                foreach ($admins as $admin) {
                    Database::query(
                        'INSERT INTO notifications (user_id,title,body,type,related_order_id)
                         VALUES (?,?,?,?,?)',
                        [
                            $admin['id'],
                            'הזמנה חדשה ממתינה לאישור',
                            'פריט: ' . ($item['name']??'') . ' | תאריך: ' . $data['loan_date'],
                            'info',
                            $orderId,
                        ]
                    );
                }
            }
        } catch (\Exception $e) {}
    }

    private function notifyStatusChange(int $orderId, array $order, string $newStatus): void {
        try {
            Database::query(
                'INSERT INTO notifications (user_id,title,body,type,related_order_id) VALUES (?,?,?,?,?)',
                [
                    $order['user_id'],
                    'עדכון סטטוס הזמנה ' . $order['order_number'],
                    "הסטטוס עודכן ל: {$newStatus}",
                    in_array($newStatus,['נדחה','לא נלקח']) ? 'warning' : 'success',
                    $orderId,
                ]
            );
        } catch (\Exception $e) {}
    }

    private function getStudentList(): array {
        return Database::query(
            "SELECT id, full_name, id_number FROM users
             WHERE is_active=1 ORDER BY full_name ASC"
        )->fetchAll();
    }

    private function logActivity(string $action, string $entity, int $id, array $details=[]): void {
        try {
            Database::query(
                'INSERT INTO activity_log (user_id,action,entity_type,entity_id,details,ip_address)
                 VALUES (?,?,?,?,?,?)',
                [Auth::user()['id'],$action,$entity,$id,
                 json_encode($details),$_SERVER['REMOTE_ADDR']??null]
            );
        } catch (\Exception $e) {}
    }
}
