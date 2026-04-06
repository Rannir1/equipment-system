<?php
class JournalController {

    public function index(): void {
        Auth::requireLogin();
        $user    = Auth::user();
        $isAdmin = Auth::isAdmin();

        $journals     = JournalModel::getAll(!$isAdmin);
        $selectedJournal = (int)($_GET['journal'] ?? ($journals[0]['id'] ?? 0));
        $weekStart    = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
        // Normalize to Monday
        $weekStart    = date('Y-m-d', strtotime('monday this week', strtotime($weekStart)));

        $grid = [];
        $journal = null;
        if ($selectedJournal) {
            $journal = JournalModel::findById($selectedJournal);
            $grid    = JournalModel::getWeekGrid($selectedJournal, $weekStart);
        }

        $currentUser = $user;
        $pageTitle   = 'יומני הזמנות';
        $currentPage = 'journals';
        ob_start();
        require __DIR__ . '/../Views/journals/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/main.php';
    }

    // AJAX endpoint — returns busy/free JSON for a week
    public function ajaxWeek(): void {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $journalId = (int)($_GET['journal'] ?? 0);
        $weekStart = $_GET['week']    ?? date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($weekStart)));
        if (!$journalId) { echo json_encode(['error'=>'missing journal']); return; }
        $grid = JournalModel::getWeekGrid($journalId, $weekStart);
        echo json_encode($grid);
    }
}
