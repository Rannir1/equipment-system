<?php
class JournalModel {

    public static function getAll(bool $includeHidden = true): array {
        $sql = 'SELECT * FROM journals';
        if (!$includeHidden) $sql .= ' WHERE is_hidden = 0';
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Database::query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        return Database::query('SELECT * FROM journals WHERE id=? LIMIT 1', [$id])->fetch() ?: null;
    }

    /**
     * Build a week-view grid: for each day in [startDate .. startDate+6],
     * return which items are occupied and which are free.
     *
     * Returns: [ 'YYYY-MM-DD' => [ inventory_id => ['status'=>'busy'|'free', ...] ] ]
     */
    public static function getWeekGrid(int $journalId, string $weekStart): array {
        $days = [];
        $dt   = new \DateTime($weekStart);
        for ($i = 0; $i < 7; $i++) {
            $days[] = $dt->format('Y-m-d');
            $dt->modify('+1 day');
        }
        $weekEnd = end($days);

        // Items in this journal
        $items = Database::query(
            "SELECT id, name, barcode, condition_status, is_loanable, quantity_available
             FROM inventory
             WHERE journal_id = ? AND is_removed = 0
             ORDER BY name ASC",
            [$journalId]
        )->fetchAll();

        // Orders overlapping this week
        $orders = OrderModel::getForJournalRange($journalId, $weekStart, $weekEnd);

        // Index orders by item+day
        $busyMap = [];
        foreach ($orders as $o) {
            $loanDt   = new \DateTime($o['loan_date']);
            $returnDt = new \DateTime($o['return_date']);
            foreach ($days as $day) {
                $d = new \DateTime($day);
                if ($d >= $loanDt && $d <= $returnDt) {
                    $busyMap[$o['inventory_id']][$day][] = $o;
                }
            }
        }

        $grid = [];
        foreach ($days as $day) {
            $grid[$day] = [];
            foreach ($items as $item) {
                $busy = $busyMap[$item['id']][$day] ?? [];
                $grid[$day][$item['id']] = [
                    'item'   => $item,
                    'busy'   => !empty($busy),
                    'orders' => $busy,
                ];
            }
        }
        return ['days' => $days, 'items' => $items, 'grid' => $grid];
    }

    /** Hebrew day names */
    public static function hebrewDayName(string $date): string {
        $names = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
        return 'יום ' . $names[(int)(new \DateTime($date))->format('w')];
    }

    public static function createOrUpdate(array $data, ?int $id = null): int {
        if ($id) {
            Database::query(
                'UPDATE journals SET name=?,description=?,color=?,is_hidden=?,sort_order=? WHERE id=?',
                [$data['name'],$data['description']??null,$data['color']??'#3b82f6',
                 $data['is_hidden']??0,$data['sort_order']??0,$id]
            );
            return $id;
        }
        Database::query(
            'INSERT INTO journals (name,description,color,is_hidden,sort_order) VALUES (?,?,?,?,?)',
            [$data['name'],$data['description']??null,$data['color']??'#3b82f6',
             $data['is_hidden']??0,$data['sort_order']??0]
        );
        return (int)Database::lastInsertId();
    }
}
