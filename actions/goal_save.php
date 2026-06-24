<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();

$householdId = hid();
$id      = (int)($_POST['id'] ?? 0);
$name    = mb_substr(trim($_POST['name'] ?? ''), 0, 160);
$target  = parse_money_tr($_POST['target_amount'] ?? '');
$tdate   = $_POST['target_date'] ?? '';
$color   = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#14452F';
$icon    = mb_substr(trim($_POST['icon'] ?? '🎯'), 0, 8) ?: '🎯';

if ($name === '' || $target === null || $target <= 0) {
    flash('error', 'Hedef adı ve geçerli bir hedef tutarı gereklidir.');
    redirect('goals.php');
}
$tdate = $tdate && strtotime($tdate) ? date('Y-m-d', strtotime($tdate)) : null;

$pdo = db();
if ($id > 0) {
    $pdo->prepare('UPDATE savings_goals SET name=?, target_amount=?, target_date=?, color=?, icon=? WHERE id=? AND household_id=?')
        ->execute([$name,$target,$tdate,$color,$icon,$id,$householdId]);
    flash('success', 'Hedef güncellendi.');
} else {
    $pdo->prepare('INSERT INTO savings_goals (household_id, name, target_amount, target_date, color, icon, created_by) VALUES (?,?,?,?,?,?,?)')
        ->execute([$householdId,$name,$target,$tdate,$color,$icon,$_SESSION['user_id']]);
    log_activity($householdId, 'goal_create', $name . ' · ' . money($target));
    flash('success', 'Birikim hedefi oluşturuldu: ' . e($name));
}
redirect('goals.php');
