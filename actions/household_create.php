<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
verify_csrf();
$name = trim($_POST['name'] ?? '');
$currency = $_POST['currency'] ?? 'TRY';
if (!isset($GLOBALS['CURRENCY_SYMBOLS'][$currency])) $currency = 'TRY';
if (mb_strlen($name) < 2) {
    flash('error', 'Ev adı en az 2 karakter olmalı.');
    redirect('households.php');
}
$pdo = db();
$pdo->beginTransaction();
try {
    $code = random_code(8);
    $h = $pdo->prepare('INSERT INTO households (name, currency, join_code, created_by) VALUES (?, ?, ?, ?)');
    $h->execute([$name, $currency, $code, $_SESSION['user_id']]);
    $hidNew = (int)$pdo->lastInsertId();
    $m = $pdo->prepare('INSERT INTO household_members (household_id, user_id, role) VALUES (?, ?, "owner")');
    $m->execute([$hidNew, $_SESSION['user_id']]);
    $pdo->commit();
    seed_default_categories($hidNew);
    $_SESSION['household_id'] = $hidNew;
    log_activity($hidNew, 'household_create', 'Ev oluşturuldu: ' . $name);
    flash('success', '“' . $name . '” evi oluşturuldu ve aktif edildi.');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'Ev oluşturulamadı.');
}
redirect('households.php');
