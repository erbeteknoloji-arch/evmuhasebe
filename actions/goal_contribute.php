<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notify.php';
require_household();
verify_csrf();

$householdId = hid();
$pdo = db();
$goalId = (int)($_POST['goal_id'] ?? 0);
$amount = parse_money_tr($_POST['amount'] ?? '');
$note   = mb_substr(trim($_POST['note'] ?? ''), 0, 200);
$onDate = $_POST['contributed_on'] ?? date('Y-m-d');
$onDate = strtotime($onDate) ? date('Y-m-d', strtotime($onDate)) : date('Y-m-d');

// Hedef bu eve mi ait?
$g = $pdo->prepare('SELECT * FROM savings_goals WHERE id=? AND household_id=? LIMIT 1');
$g->execute([$goalId, $householdId]);
$goal = $g->fetch();
if (!$goal) {
    flash('error', 'Hedef bulunamadı.');
    redirect('goals.php');
}
if ($amount === null || $amount == 0) {
    flash('error', 'Geçerli bir tutar girin (para çekmek için eksi değer kullanabilirsiniz).');
    redirect('goals.php');
}

$pdo->prepare('INSERT INTO goal_contributions (goal_id, amount, note, contributed_on, user_id) VALUES (?,?,?,?,?)')
    ->execute([$goalId, $amount, $note, $onDate, $_SESSION['user_id']]);

// Toplam birikim hedefe ulaştı mı?
$sum = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM goal_contributions WHERE goal_id=' . (int)$goalId)->fetchColumn();
if ($sum >= (float)$goal['target_amount'] && $goal['status'] !== 'reached') {
    $pdo->prepare('UPDATE savings_goals SET status="reached" WHERE id=?')->execute([$goalId]);
    notify_household($householdId, 'goals',
        APP_NAME . ' · Hedefe ulaşıldı 🎉',
        $goal['icon'] . ' ' . $goal['name'] . ' hedefine ulaşıldı!',
        '<p><b>' . e($goal['name']) . '</b> hedefi için ' . money($goal['target_amount']) . ' birikim tamamlandı. Tebrikler!</p>',
        null, 'Hedefleri Gör', absolute_url('goals.php'));
    flash('success', 'Tebrikler! "' . e($goal['name']) . '" hedefine ulaştınız 🎉');
} else {
    log_activity($householdId, 'goal_contribute', $goal['name'] . ' · ' . money($amount));
    flash('success', 'Birikim kaydedildi: ' . money($amount));
}
redirect('goals.php');
