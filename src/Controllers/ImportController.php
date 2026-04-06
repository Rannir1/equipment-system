<?php
/**
 * ImportController — ייבוא CSV ידני דרך ממשק המנהל
 * Supports: inventory CSV + students CSV
 */
class ImportController {

    // Journal keyword map (mirrors the Python logic)
    private static array $journalMap = [
        'מצלמה'=>1,'מצלמת'=>1,'עדשה'=>1,'גימבל'=>1,'ידית זום'=>1,'vtr'=>1,
        'פנס'=>2,'תאורה'=>2,'דימר'=>2,'רפלקטור'=>2,'בלנדה'=>2,'צימרה'=>2,
        'snoot'=>2,'אוהל ריכוך'=>2,'honey comb'=>2,'grid'=>2,'סט תאורה'=>2,'מד אור'=>2,
        'מיקרופון'=>3,'מיקס'=>3,'אוזניות'=>3,'אזניות'=>3,'בום'=>3,'מקליט'=>3,
        'recorder'=>3,'sound mixer'=>3,'dat'=>3,'mixer'=>3,'מגבר'=>3,'רמקול'=>3,
        'אינטרקום'=>3,'אקולייזר'=>3,'מגן רוח'=>3,
        'חצובה'=>4,'סטנד'=>4,'גריפ'=>4,'זרוע'=>4,'גלגלים לחצובה'=>4,
        'dolly'=>4,'cine stand'=>4,'מוט לתליית'=>4,
        'כבל'=>5,'מתאם'=>5,'super clamp'=>5,'spring clamp'=>5,'flash brackt'=>5,
        'חדר עריכה'=>6,'חדר לוגינג'=>6,
        'סוללה'=>7,'כרטיס זיכרון'=>7,'כרטיס sd'=>7,'מטען'=>7,'כונן'=>7,'דיסק'=>7,
    ];

    public function showImport(): void {
        Auth::requireAdmin();
        $pageTitle   = 'ייבוא נתונים';
        $currentPage = 'settings';
        $currentUser = Auth::user();
        $isAdmin     = true;
        ob_start();
        require __DIR__ . '/../Views/import/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function handleInventoryImport(): void {
        Auth::requireAdmin();
        if (empty($_FILES['csv_file']['tmp_name'])) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'לא הועלה קובץ.'];
            header('Location: /import'); exit;
        }

        $file    = $_FILES['csv_file']['tmp_name'];
        $content = file_get_contents($file);
        // Strip BOM
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines   = array_filter(explode("\n", str_replace("\r\n", "\n", $content)));
        $lines   = array_values($lines);

        if (empty($lines)) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'הקובץ ריק.'];
            header('Location: /import'); exit;
        }

        // Parse header
        $header = str_getcsv(array_shift($lines));
        $header = array_map('trim', $header);

        // Map Hebrew column names to keys
        $colMap = [
            'ברקוד'      => 'barcode',
            'שם'         => 'name',
            'סוג'        => 'type',
            'יצרן'       => 'brand',
            'מ. סידורי'  => 'serial',
            'מחיר'       => 'price',
            'תאריך'      => 'purchase_date',
            'יומן'       => 'journal_raw',
            'להשאלה'     => 'loanable',
            'סטטוס'      => 'status',
            'מיקום'      => 'location',
        ];

        $colIdx = [];
        foreach ($header as $i => $h) {
            $h = trim($h);
            if (isset($colMap[$h])) $colIdx[$colMap[$h]] = $i;
        }

        if (!isset($colIdx['name'])) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'עמודת "שם" לא נמצאה בקובץ.'];
            header('Location: /import'); exit;
        }

        $imported = $skipped = $errors = 0;
        Database::beginTransaction();

        try {
            foreach ($lines as $lineNum => $line) {
                $line = trim($line);
                if (!$line) continue;
                $row = str_getcsv($line);

                $get = fn(string $k) => isset($colIdx[$k]) ? trim($row[$colIdx[$k]] ?? '') : '';

                $name = $get('name');
                if (!$name) { $skipped++; continue; }

                $journalId = $this->inferJournal($get('journal_raw'), $name, $get('type'));
                $loanable  = strtolower($get('loanable')) === 'false' ? 0 : 1;
                $pdate     = $this->parseDate($get('purchase_date'));
                $price     = is_numeric($get('price')) && (float)$get('price') > 0
                             ? (float)$get('price') : null;

                // Check barcode uniqueness
                $barcode = $get('barcode') ?: null;
                if ($barcode) {
                    $exists = Database::query(
                        'SELECT id FROM inventory WHERE barcode = ? LIMIT 1', [$barcode]
                    )->fetchColumn();
                    if ($exists) { $skipped++; continue; }
                }

                Database::query(
                    'INSERT INTO inventory
                     (barcode,name,description,journal_id,brand,serial_number,
                      location,condition_status,is_loanable,quantity,quantity_available,
                      purchase_date,purchase_price)
                     VALUES (?,?,?,?,?,?,?,?,?,1,1,?,?)',
                    [
                        $barcode,
                        $name,
                        $get('type') !== $name ? ($get('type') ?: null) : null,
                        $journalId,
                        $get('brand') ?: null,
                        $get('serial') ?: null,
                        $get('location') ?: null,
                        'תקין',
                        $loanable,
                        $pdate,
                        $price,
                    ]
                );
                $imported++;
            }

            Database::commit();
            $this->logActivity('inventory_csv_import', null, null,
                ['imported'=>$imported,'skipped'=>$skipped]);
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => "ייבוא הושלם: {$imported} פריטים נוספו, {$skipped} דולגו (ברקוד כפול / שורה ריקה)."
            ];
        } catch (\Exception $e) {
            Database::rollback();
            $_SESSION['flash'] = ['type'=>'error','msg'=>'שגיאה בייבוא: ' . $e->getMessage()];
        }

        header('Location: /inventory'); exit;
    }

    public function handleStudentsImport(): void {
        Auth::requireAdmin();
        if (empty($_FILES['csv_file']['tmp_name'])) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'לא הועלה קובץ.'];
            header('Location: /import'); exit;
        }

        $content = ltrim(file_get_contents($_FILES['csv_file']['tmp_name']), "\xEF\xBB\xBF");
        $lines   = array_values(array_filter(explode("\n", str_replace("\r\n","\n",$content))));
        $header  = str_getcsv(array_shift($lines));
        $header  = array_map('trim', $header);

        $colIdx = array_flip($header);

        $imported = $skipped = 0;
        Database::beginTransaction();
        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) continue;
                $row = str_getcsv($line);
                $get = fn($k) => trim($row[$colIdx[$k] ?? 999] ?? '');

                $tz   = $get('tz');
                $name = $get('name');
                $pass = $get('password');

                if (!$tz || !$name || !$pass) { $skipped++; continue; }

                // Check duplicate
                $exists = Database::query(
                    'SELECT id FROM users WHERE id_number=? LIMIT 1', [$tz]
                )->fetchColumn();
                if ($exists) { $skipped++; continue; }

                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
                Database::query(
                    'INSERT INTO users (id_number,full_name,email,phone,role,password_hash)
                     VALUES (?,?,?,?,?,?)',
                    [
                        $tz, $name,
                        $get('email') ?: null,
                        $get('phone') ?: null,
                        'student',
                        $hash,
                    ]
                );
                $imported++;
            }
            Database::commit();
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => "ייבוא סטודנטים הושלם: {$imported} נוספו, {$skipped} דולגו."
            ];
        } catch (\Exception $e) {
            Database::rollback();
            $_SESSION['flash'] = ['type'=>'error','msg'=>'שגיאה: '.$e->getMessage()];
        }

        header('Location: /users'); exit;
    }

    // ── Helpers ────────────────────────────────────────────
    private function inferJournal(string $raw, string $name, string $type): int {
        $raw = strtolower(trim($raw));
        if ($raw === 'j1') return 3;
        if ($raw === 'j2') return 1;
        if ($raw === 'j3') return 6;

        $text = strtolower($name . ' ' . $type);
        foreach (self::$journalMap as $kw => $jid) {
            if (str_contains($text, strtolower($kw))) return $jid;
        }
        return 8;
    }

    private function parseDate(string $s): ?string {
        $s = trim($s);
        if (!$s) return null;
        try {
            $d = new \DateTime($s);
            if ($d->format('Y') < '1990') return null;
            return $d->format('Y-m-d');
        } catch (\Exception $e) { return null; }
    }

    private function logActivity(string $action, ?string $entity, ?int $id, array $details=[]): void {
        try {
            Database::query(
                'INSERT INTO activity_log (user_id,action,entity_type,entity_id,details,ip_address)
                 VALUES (?,?,?,?,?,?)',
                [Auth::user()['id'],$action,$entity,$id,json_encode($details),$_SERVER['REMOTE_ADDR']??null]
            );
        } catch (\Exception $e) {}
    }
}
