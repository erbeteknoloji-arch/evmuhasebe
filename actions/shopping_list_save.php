<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
require_feature('shopping');
verify_csrf();

$householdId = hid();
$id    = (int)($_POST['id'] ?? 0);
$name  = mb_substr(trim($_POST['name'] ?? ''), 0, 160);
$color = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#14452F';
$icon  = mb_substr(trim($_POST['icon'] ?? '🛒'), 0, 8) ?: '🛒';

if ($name === '') {
    flash('error', 'Liste adı gereklidir.');
    redirect('shopping.php');
}

$pdo = db();
if ($id > 0) {
    $pdo->prepare('UPDATE shopping_lists SET name=?, color=?, icon=? WHERE id=? AND household_id=?')
        ->execute([$name, $color, $icon, $id, $householdId]);
    flash('success', 'Liste güncellendi.');
    redirect('shopping.php?list=' . $id);
} else {
    $pdo->prepare('INSERT INTO shopping_lists (household_id, name, icon, color, created_by) VALUES (?,?,?,?,?)')
        ->execute([$householdId, $name, $icon, $color, $_SESSION['user_id']]);
    $newId = (int)$pdo->lastInsertId();
    log_activity($householdId, 'shopping_list_create', $name);
    flash('success', 'Alışveriş listesi oluşturuldu: ' . $name);
    redirect('shopping.php?list=' . $newId);
}
