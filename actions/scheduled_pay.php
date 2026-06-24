<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notify.php';
require_household();
verify_csrf();

$householdId = hid();
$pdo = db();
$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM scheduled_items WHERE id=? AND household_id=? LIMIT 1');
$stmt->execute([$id, $householdId]);
$item = $stmt->fetch();
if (!$item) {
    flash('error', 'Planlı ödeme bulunamadı.');
    redirect('calendar.php');
}

// Gerçek işlem oluştur
$payDate = date('Y-m-d');
$ins = $pdo->prepare(
    'INSERT INTO transactions (household_id, account_id, category_id, user_id, type, amount, description, transaction_date, source)
     VALUES (?,?,?,?,?,?,?,?,"manual")'
);
$ins->execute([$householdId, $item['account_id'] ?: null, $item['category_id'] ?: null, $_SESSION['user_id'],
    $item['type'], $item['amount'], $item['title'], $payDate]);

// Tekrarlayan ise sonraki tarihe ilerlet, değilse "paid"
if ($item['recurrence'] !== 'none') {
    $next = next_due_date($item['due_date'], $item['recurrence']);
    $upd = $pdo->prepare('UPDATE scheduled_items SET due_date=?, last_paid_on=?, status="pending" WHERE id=? AND household_id=?');
    $upd->execute([$next, $payDate, $id, $householdId]);
    $msg = 'Ödeme işlendi. Sonraki tarih: ' . format_date($next);
} else {
    $upd = $pdo->prepare('UPDATE scheduled_items SET status="paid", last_paid_on=? WHERE id=? AND household_id=?');
    $upd->execute([$payDate, $id, $householdId]);
    $msg = 'Ödeme işlendi ve işlemlere eklendi.';
}

log_activity($householdId, 'scheduled_pay', $item['title'] . ' · ' . money($item['amount']));
notify_household($householdId, 'transactions',
    APP_NAME . ' · Ödeme işlendi',
    'Planlı ödeme gerçekleşti',
    '<p><b>' . e($item['title']) . '</b> için <b>' . money($item['amount']) . '</b> tutarında '
    . ($item['type'] === 'income' ? 'gelir' : 'gider') . ' işlendi (' . format_date($payDate) . ').',
    (int)$_SESSION['user_id'],
    'İşlemleri Gör', absolute_url('transactions.php'));

flash('success', $msg);
redirect('calendar.php');
