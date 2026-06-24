<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();

$householdId = hid();
$pdo = db();

$id         = (int)($_POST['id'] ?? 0);
$type       = ($_POST['type'] ?? 'expense') === 'income' ? 'income' : 'expense';
$title      = mb_substr(trim($_POST['title'] ?? ''), 0, 160);
$amount     = parse_money_tr($_POST['amount'] ?? '');
$due        = $_POST['due_date'] ?? '';
$recur      = in_array($_POST['recurrence'] ?? 'none', ['none','weekly','monthly','yearly'], true) ? $_POST['recurrence'] : 'none';
$categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
$accountId  = (int)($_POST['account_id'] ?? 0) ?: null;
$notes      = mb_substr(trim($_POST['notes'] ?? ''), 0, 255);
$autoPost   = !empty($_POST['auto_post']) ? 1 : 0;

$ts = strtotime($due);
if ($title === '' || $amount === null || $amount <= 0 || !$ts) {
    flash('error', 'Başlık, geçerli tutar ve tarih gereklidir.');
    redirect('calendar.php');
}
$due = date('Y-m-d', $ts);

// Sahiplik kontrolleri
if ($categoryId) {
    $c = $pdo->prepare('SELECT id FROM categories WHERE id=? AND household_id=?');
    $c->execute([$categoryId, $householdId]);
    if (!$c->fetch()) $categoryId = null;
}
if ($accountId) {
    $a = $pdo->prepare('SELECT id FROM accounts WHERE id=? AND household_id=?');
    $a->execute([$accountId, $householdId]);
    if (!$a->fetch()) $accountId = null;
}

if ($id > 0) {
    $stmt = $pdo->prepare(
        'UPDATE scheduled_items SET type=?, title=?, amount=?, due_date=?, recurrence=?, category_id=?, account_id=?, notes=?, auto_post=?
          WHERE id=? AND household_id=?'
    );
    $stmt->execute([$type,$title,$amount,$due,$recur,$categoryId,$accountId,$notes,$autoPost,$id,$householdId]);
    flash('success', 'Planlı ödeme güncellendi.');
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO scheduled_items (household_id, account_id, category_id, type, title, amount, due_date, recurrence, notes, auto_post, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([$householdId,$accountId,$categoryId,$type,$title,$amount,$due,$recur,$notes,$autoPost,$_SESSION['user_id']]);
    log_activity($householdId, 'scheduled_create', $title . ' · ' . money($amount));
    flash('success', 'Planlı ödeme eklendi.');
}
redirect('calendar.php');
