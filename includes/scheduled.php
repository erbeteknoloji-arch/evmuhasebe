<?php
/**
 * Planlı ödemelerin otomatik işlenmesi.
 * "auto_post=1" işaretli ve vadesi gelmiş (due_date <= bugün) bekleyen
 * kalemler, sayfa açılışında otomatik olarak işleme alınır:
 *   - gerçek bir işlem (transaction) oluşturulur,
 *   - tekrarlayan ise bir sonraki tarihe ilerletilir, değilse "paid" yapılır.
 * Arka planda zamanlayıcı (cron) gerekmez. Geçmişe dönük birikmiş
 * dönemler de (sınırlı sayıda) yakalanır.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notify.php';

/**
 * @return int işlenen ödeme sayısı
 */
function process_due_scheduled(int $householdId): int
{
    if ($householdId <= 0) return 0;
    $pdo = db();

    // auto_post sütunu yoksa (göç uygulanmadıysa) sessizce çık
    try { $pdo->query('SELECT auto_post FROM scheduled_items LIMIT 1'); }
    catch (Throwable $e) { return 0; }

    $today = date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT * FROM scheduled_items
          WHERE household_id = ? AND auto_post = 1 AND status = 'pending' AND due_date <= ?
          ORDER BY due_date ASC"
    );
    $stmt->execute([$householdId, $today]);
    $items = $stmt->fetchAll();
    if (!$items) return 0;

    $ins = $pdo->prepare(
        'INSERT INTO transactions (household_id, account_id, category_id, user_id, type, amount, description, transaction_date, source)
         VALUES (?,?,?,?,?,?,?,?,"manual")'
    );

    $processed = 0;
    foreach ($items as $item) {
        $id = (int)$item['id'];
        $recurrence = $item['recurrence'] ?? 'none';
        $due = $item['due_date'];

        if ($recurrence === 'none') {
            // Tek seferlik: tek işlem oluştur, "paid" yap
            $ins->execute([$householdId, $item['account_id'] ?: null, $item['category_id'] ?: null,
                $item['created_by'] ?: null, $item['type'], $item['amount'], $item['title'], $due]);
            $pdo->prepare('UPDATE scheduled_items SET status="paid", last_paid_on=? WHERE id=? AND household_id=?')
                ->execute([$due, $id, $householdId]);
            $processed++;
        } else {
            // Tekrarlayan: birikmiş dönemleri yakala (en çok 24 kez)
            $guard = 0;
            while ($due && strtotime($due) <= strtotime($today) && $guard < 24) {
                $ins->execute([$householdId, $item['account_id'] ?: null, $item['category_id'] ?: null,
                    $item['created_by'] ?: null, $item['type'], $item['amount'], $item['title'], $due]);
                $processed++;
                $guard++;
                $due = next_due_date($due, $recurrence);
            }
            $pdo->prepare('UPDATE scheduled_items SET due_date=?, last_paid_on=?, status="pending" WHERE id=? AND household_id=?')
                ->execute([$due, $today, $id, $householdId]);
        }

        log_activity($householdId, 'scheduled_pay', '[oto] ' . $item['title'] . ' · ' . money($item['amount']));
    }

    if ($processed > 0) {
        notify_household($householdId, 'upcoming',
            APP_NAME . ' · Otomatik ödemeler işlendi',
            'Planlı ödemeler otomatik işlendi',
            '<p>Vadesi gelen <b>' . $processed . '</b> planlı işlem otomatik olarak kaydedildi.</p>',
            null,
            'İşlemleri Gör', absolute_url('transactions.php'));
    }

    return $processed;
}
