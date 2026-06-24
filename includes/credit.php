<?php
/**
 * =====================================================================
 *  Kredi Kartı yardımcıları
 * =====================================================================
 *  - Kart bakiyesi, güncel borç, kullanılabilir limit, asgari ödeme.
 *  - Hesap kesim (statement) / son ödeme tarihleri.
 *  - Hesap kesim tarihi geçtiğinde OTOMATİK ekstre üretimi:
 *      * cc_statements tablosuna kayıt,
 *      * takvime (scheduled_items) bir "ödeme yükümlülüğü" etkinliği.
 *
 *  Not: Arka planda zamanlayıcı (cron) bulunmadığından ekstreler
 *  "tembel" (lazy) üretilir: panel/hesaplar sayfası açıldığında
 *  cc_generate_statements() çağrılır ve kesim tarihi geçmiş tüm
 *  kartlar için eksik ekstreler oluşturulur.
 * =====================================================================
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Bir hesabın güncel bakiyesi: açılış + gelir − gider.
 * (transactions üzerinden hesaplanır, kalıcı veriden türetilir.)
 */
function account_balance(int $accountId, int $householdId): float
{
    $stmt = db()->prepare(
        "SELECT a.opening_balance
                + COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount END),0)
                - COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount END),0) AS bal
           FROM accounts a
           LEFT JOIN transactions t ON t.account_id = a.id AND t.household_id = a.household_id
          WHERE a.id = ? AND a.household_id = ?
          GROUP BY a.id"
    );
    $stmt->execute([$accountId, $householdId]);
    $row = $stmt->fetch();
    return $row ? (float)$row['bal'] : 0.0;
}

/**
 * Kart güncel borcu. Kredi kartında bakiye negatife düşerse (harcama
 * yapıldıkça) bu otomatik olarak borç sayılır.
 */
function cc_debt(float $balance): float
{
    return $balance < 0 ? round(-$balance, 2) : 0.0;
}

/**
 * Kullanılabilir limit = Kart limiti − Güncel borç.
 * Limit tanımsızsa null döner.
 */
function cc_available(?float $limit, float $debt): ?float
{
    if ($limit === null) {
        return null;
    }
    return round($limit - $debt, 2);
}

/** Asgari ödeme tutarı = borç × yüzde. */
function cc_min_payment(float $debt, float $pct): float
{
    return round($debt * $pct / 100, 2);
}

/**
 * Ayın belirli gününe (1-31) göre, verilen referans tarihten SONRAKİ
 * ilk tarihi döndürür. Kısa aylarda gün sayısına göre kırpılır.
 *
 * @param int    $day  ayın günü (1-31)
 * @param string $from referans (Y-m-d) — bu tarih dahil değildir
 */
function next_day_of_month(int $day, string $from): string
{
    $day = max(1, min(31, $day));
    $ts  = strtotime($from);
    if (!$ts) {
        $ts = time();
    }
    // Önce içinde bulunulan ay
    $y = (int)date('Y', $ts);
    $m = (int)date('n', $ts);
    for ($i = 0; $i < 14; $i++) {
        $dim  = (int)date('t', mktime(0, 0, 0, $m, 1, $y)); // o ayın gün sayısı
        $useD = min($day, $dim);
        $cand = sprintf('%04d-%02d-%02d', $y, $m, $useD);
        if (strtotime($cand) > $ts) {
            return $cand;
        }
        // sonraki aya geç
        $m++;
        if ($m > 12) { $m = 1; $y++; }
    }
    return date('Y-m-d', strtotime('+1 month', $ts));
}

/**
 * Ayın belirli gününe göre, referans tarihten ÖNCEKİ (veya eşit) en son
 * tarihi döndürür. (Geçmiş en son kesim tarihini bulmak için.)
 */
function last_day_of_month_on_or_before(int $day, string $from): string
{
    $day = max(1, min(31, $day));
    $ts  = strtotime($from);
    if (!$ts) {
        $ts = time();
    }
    $y = (int)date('Y', $ts);
    $m = (int)date('n', $ts);
    for ($i = 0; $i < 14; $i++) {
        $dim  = (int)date('t', mktime(0, 0, 0, $m, 1, $y));
        $useD = min($day, $dim);
        $cand = sprintf('%04d-%02d-%02d', $y, $m, $useD);
        if (strtotime($cand) <= $ts) {
            return $cand;
        }
        // bir önceki aya geç
        $m--;
        if ($m < 1) { $m = 12; $y--; }
    }
    return date('Y-m-d', strtotime('-1 month', $ts));
}

/**
 * Bir kart için sonraki hesap kesim tarihi (bugünden sonra).
 */
function cc_next_statement_date(array $account): ?string
{
    $d = (int)($account['statement_day'] ?? 0);
    if ($d < 1) {
        return null;
    }
    return next_day_of_month($d, date('Y-m-d'));
}

/**
 * Bir kart için sonraki son ödeme tarihi (bugünden sonra).
 * due_day tanımlı değilse, kesim tarihinden ~10 gün sonrası varsayılır.
 */
function cc_next_due_date(array $account): ?string
{
    $dd = (int)($account['due_day'] ?? 0);
    if ($dd >= 1) {
        return next_day_of_month($dd, date('Y-m-d'));
    }
    $stmt = cc_next_statement_date($account);
    return $stmt ? date('Y-m-d', strtotime('+10 days', strtotime($stmt))) : null;
}

/**
 * Bir kartın belirli dönemdeki (period_start, period_end] net harcaması.
 * Harcama (expense) borcu artırır, kart ödemesi (income) azaltır.
 */
function cc_period_amount(int $accountId, int $householdId, ?string $periodStart, string $periodEnd): float
{
    $params = [$accountId, $householdId, $periodEnd];
    $startClause = '';
    if ($periodStart) {
        $startClause = ' AND t.transaction_date > ?';
        $params[] = $periodStart;
    }
    $stmt = db()->prepare(
        "SELECT COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount END),0)
                - COALESCE(SUM(CASE WHEN t.type='income' THEN t.amount END),0) AS net
           FROM transactions t
          WHERE t.account_id = ? AND t.household_id = ?
            AND t.transaction_date <= ?" . $startClause
    );
    // NOT: parametre sırası -> accountId, householdId, periodEnd, [periodStart]
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ? round((float)$row['net'], 2) : 0.0;
}

/**
 * TEMBEL ekstre üretimi.
 * Hesap kesim tarihi geçmiş ve henüz ekstresi oluşturulmamış her kredi
 * kartı için:
 *   1) cc_statements kaydı oluşturur,
 *   2) takvime (scheduled_items) son ödeme tarihli bir "ödeme
 *      yükümlülüğü" (expense) ekler.
 *
 * @return int üretilen ekstre sayısı
 */
function cc_generate_statements(int $householdId): int
{
    if ($householdId <= 0) {
        return 0;
    }
    $pdo = db();

    // cc_statements tablosu yoksa (göç uygulanmadıysa) sessizce çık.
    try {
        $pdo->query('SELECT 1 FROM cc_statements LIMIT 1');
    } catch (Throwable $e) {
        return 0;
    }

    $stmt = $pdo->prepare(
        "SELECT * FROM accounts
          WHERE household_id = ? AND type = 'credit_card'
            AND statement_day IS NOT NULL AND statement_day >= 1"
    );
    $stmt->execute([$householdId]);
    $cards = $stmt->fetchAll();
    if (!$cards) {
        return 0;
    }

    $today = date('Y-m-d');
    $created = 0;

    foreach ($cards as $card) {
        $accountId = (int)$card['id'];
        $stmtDay   = (int)$card['statement_day'];

        // Geçmişteki en son kesim tarihi (bugün dahil)
        $periodEnd = last_day_of_month_on_or_before($stmtDay, $today);
        if (!$periodEnd || strtotime($periodEnd) > strtotime($today)) {
            continue;
        }
        $periodStart = date('Y-m-d', strtotime('-1 month', strtotime($periodEnd)));

        // Bu dönem için zaten ekstre var mı?
        $chk = $pdo->prepare('SELECT id FROM cc_statements WHERE account_id = ? AND period_end = ? LIMIT 1');
        $chk->execute([$accountId, $periodEnd]);
        if ($chk->fetch()) {
            continue; // zaten üretilmiş
        }

        $amount = cc_period_amount($accountId, $householdId, $periodStart, $periodEnd);
        if ($amount <= 0) {
            // Borç yoksa boş ekstre kaydı bırak (tekrar denenmesini önlemek için),
            // ancak takvim etkinliği oluşturma.
            $ins = $pdo->prepare(
                'INSERT INTO cc_statements (household_id, account_id, period_start, period_end, due_date, statement_amount, min_payment, scheduled_item_id)
                 VALUES (?,?,?,?,?,?,?,NULL)'
            );
            $dueZero = next_day_of_month(((int)($card['due_day'] ?? 0)) ?: 1, $periodEnd);
            if ((int)($card['due_day'] ?? 0) < 1) {
                $dueZero = date('Y-m-d', strtotime('+10 days', strtotime($periodEnd)));
            }
            $ins->execute([$householdId, $accountId, $periodStart, $periodEnd, $dueZero, 0, 0]);
            continue;
        }

        $pct = (float)($card['min_payment_pct'] ?? 20);
        $minPay = cc_min_payment($amount, $pct);

        // Son ödeme tarihi: due_day varsa kesimden sonraki o gün, yoksa +10 gün
        $dueDay = (int)($card['due_day'] ?? 0);
        if ($dueDay >= 1) {
            $dueDate = next_day_of_month($dueDay, $periodEnd);
        } else {
            $dueDate = date('Y-m-d', strtotime('+10 days', strtotime($periodEnd)));
        }

        // Takvim etkinliği (ödeme yükümlülüğü). account_id = NULL bırakılır:
        // bu kayıt "İşle" ile ödendiğinde gerçek nakit gideriniz kaydedilir;
        // kartın türetilmiş borcu kart işlemlerinden hesaplanmaya devam eder.
        $title = '💳 ' . $card['name'] . ' ekstre ödemesi';
        $notes = 'Otomatik ekstre · Asgari: ' . money($minPay);
        $si = $pdo->prepare(
            'INSERT INTO scheduled_items (household_id, account_id, category_id, type, title, amount, due_date, recurrence, status, notes, created_by)
             VALUES (?, NULL, NULL, "expense", ?, ?, ?, "none", "pending", ?, NULL)'
        );
        $si->execute([$householdId, $title, $amount, $dueDate, $notes]);
        $scheduledItemId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare(
            'INSERT INTO cc_statements (household_id, account_id, period_start, period_end, due_date, statement_amount, min_payment, scheduled_item_id)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $ins->execute([$householdId, $accountId, $periodStart, $periodEnd, $dueDate, $amount, $minPay, $scheduledItemId]);

        if (function_exists('log_activity')) {
            log_activity($householdId, 'cc_statement', $card['name'] . ' ekstre · ' . money($amount));
        }
        $created++;
    }

    return $created;
}

/**
 * Bir evdeki tüm kredi kartlarının özet bilgilerini döndürür.
 * Panel ve hesaplar sayfası için tek noktadan hesaplama.
 *
 * @return array{cards: array, total_debt: float, total_min: float,
 *               total_available: ?float, next_statement: ?string, next_due: ?string}
 */
function cc_summary(int $householdId): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT a.*,
                COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount END),0) inc,
                COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount END),0) exp
           FROM accounts a
           LEFT JOIN transactions t ON t.account_id = a.id AND t.household_id = a.household_id
          WHERE a.household_id = ? AND a.type = 'credit_card'
       GROUP BY a.id
       ORDER BY a.name ASC"
    );
    $stmt->execute([$householdId]);
    $rows = $stmt->fetchAll();

    $cards = [];
    $totalDebt = 0.0; $totalMin = 0.0; $totalAvail = null;
    $nextStmt = null; $nextDue = null;

    foreach ($rows as $a) {
        $balance = (float)$a['opening_balance'] + (float)$a['inc'] - (float)$a['exp'];
        $debt    = cc_debt($balance);
        $limit   = ($a['credit_limit'] !== null && $a['credit_limit'] !== '') ? (float)$a['credit_limit'] : null;
        $avail   = cc_available($limit, $debt);
        $pct     = (float)($a['min_payment_pct'] ?? 20);
        $minPay  = cc_min_payment($debt, $pct);
        $ns      = cc_next_statement_date($a);
        $nd      = cc_next_due_date($a);

        $totalDebt += $debt;
        $totalMin  += $minPay;
        if ($avail !== null) {
            $totalAvail = ($totalAvail ?? 0) + $avail;
        }
        if ($ns && ($nextStmt === null || strtotime($ns) < strtotime($nextStmt))) $nextStmt = $ns;
        if ($nd && ($nextDue === null || strtotime($nd) < strtotime($nextDue)))   $nextDue  = $nd;

        $cards[] = [
            'id' => (int)$a['id'], 'name' => $a['name'], 'bank_name' => $a['bank_name'],
            'balance' => $balance, 'debt' => $debt, 'limit' => $limit, 'available' => $avail,
            'min_pct' => $pct, 'min_payment' => $minPay,
            'next_statement' => $ns, 'next_due' => $nd,
        ];
    }

    return [
        'cards' => $cards,
        'total_debt' => round($totalDebt, 2),
        'total_min' => round($totalMin, 2),
        'total_available' => $totalAvail !== null ? round($totalAvail, 2) : null,
        'next_statement' => $nextStmt,
        'next_due' => $nextDue,
    ];
}
