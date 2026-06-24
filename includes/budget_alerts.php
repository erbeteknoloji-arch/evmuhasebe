<?php
/**
 * Bütçe aşım uyarıları.
 * Bu ayki harcama, kategori bütçesinin %80 veya %100'ünü aştığında:
 *  - panelde rozet gösterilir (döndürülen dizi ile),
 *  - dönem+seviye başına yalnızca BİR kez e-posta gönderilir
 *    (budget_alerts tablosu tekrar göndermeyi engeller).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notify.php';

/**
 * Eşikleri kontrol eder, gerekli e-postaları gönderir ve mevcut
 * uyarıların listesini döndürür.
 *
 * @return array<int,array{category:string,icon:string,limit:float,spent:float,pct:int,level:int}>
 */
function check_budget_alerts(int $householdId): array
{
    if ($householdId <= 0) return [];
    $pdo = db();

    // budget_alerts yoksa (göç uygulanmadıysa) sessizce yalnızca görünüm üret
    $canRecord = true;
    try { $pdo->query('SELECT 1 FROM budget_alerts LIMIT 1'); }
    catch (Throwable $e) { $canRecord = false; }

    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');
    $period     = date('Y-m');

    $stmt = $pdo->prepare(
        "SELECT b.id AS budget_id, b.monthly_limit, c.name, c.icon,
                COALESCE((SELECT SUM(t.amount) FROM transactions t
                           WHERE t.household_id = b.household_id
                             AND t.category_id = b.category_id
                             AND t.type='expense'
                             AND t.transfer_id IS NULL
                             AND t.transaction_date BETWEEN ? AND ?),0) AS spent
           FROM budgets b
           JOIN categories c ON c.id = b.category_id
          WHERE b.household_id = ? AND b.monthly_limit > 0"
    );
    $stmt->execute([$monthStart, $monthEnd, $householdId]);
    $rows = $stmt->fetchAll();

    $alerts = [];
    foreach ($rows as $r) {
        $limit = (float)$r['monthly_limit'];
        $spent = (float)$r['spent'];
        if ($limit <= 0) continue;
        $pct = (int)floor($spent / $limit * 100);

        $level = 0;
        if ($pct >= 100) $level = 100;
        elseif ($pct >= 80) $level = 80;
        if ($level === 0) continue;

        $alerts[] = [
            'category' => $r['name'], 'icon' => $r['icon'],
            'limit' => $limit, 'spent' => $spent, 'pct' => $pct, 'level' => $level,
        ];

        if (!$canRecord) continue;

        // Bu dönem+seviye için daha önce uyarı verildi mi?
        $chk = $pdo->prepare('SELECT id FROM budget_alerts WHERE budget_id=? AND period=? AND level=? LIMIT 1');
        $chk->execute([(int)$r['budget_id'], $period, $level]);
        if ($chk->fetch()) continue;

        // Kaydet (yarış durumunda da çiftlenmesin diye INSERT IGNORE benzeri)
        try {
            $ins = $pdo->prepare('INSERT INTO budget_alerts (household_id, budget_id, period, level) VALUES (?,?,?,?)');
            $ins->execute([$householdId, (int)$r['budget_id'], $period, $level]);
        } catch (Throwable $e) {
            continue; // muhtemelen aynı anda eklendi
        }

        // E-posta (mevcut bildirim altyapısı)
        $title = $level >= 100 ? 'Bütçe aşıldı' : 'Bütçe uyarısı (%80)';
        $body = '<p><b>' . e($r['icon'] . ' ' . $r['name']) . '</b> kategorisinde bu ay '
              . '<b>' . money($spent) . '</b> harcandı · limit <b>' . money($limit) . '</b> (%' . $pct . ').</p>'
              . ($level >= 100
                    ? '<p style="color:#b91c1c"><b>Aylık bütçe limiti aşıldı.</b></p>'
                    : '<p>Aylık bütçenizin %80\'ine ulaştınız.</p>');
        notify_household($householdId, 'transactions',
            APP_NAME . ' · ' . $title,
            $title,
            $body,
            null,
            'Raporları Gör', absolute_url('reports.php'));

        log_activity($householdId, 'budget_alert', $r['name'] . ' · %' . $pct);
    }

    return $alerts;
}
