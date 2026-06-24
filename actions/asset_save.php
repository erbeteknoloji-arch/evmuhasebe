<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rates.php';
require_household();
verify_csrf();

$householdId = hid();
$id     = (int)($_POST['id'] ?? 0);
$code   = strtoupper(preg_replace('/[^A-Za-z_]/', '', $_POST['asset_code'] ?? ''));
$qty    = parse_money_tr($_POST['quantity'] ?? '');
$cost   = isset($_POST['cost_basis_try']) && trim($_POST['cost_basis_try']) !== '' ? parse_money_tr($_POST['cost_basis_try']) : null;

$catalog = asset_catalog();
if (!isset($catalog[$code])) {
    flash('error', 'Geçersiz varlık türü.');
    redirect('assets.php');
}
if ($qty === null || $qty <= 0) {
    flash('error', 'Geçerli bir miktar girin.');
    redirect('assets.php');
}
$label = $catalog[$code][0];

$pdo = db();
if ($id > 0) {
    $pdo->prepare('UPDATE asset_holdings SET asset_code=?, label=?, quantity=?, cost_basis_try=? WHERE id=? AND household_id=?')
        ->execute([$code,$label,$qty,$cost,$id,$householdId]);
    flash('success', 'Varlık güncellendi.');
} else {
    $pdo->prepare('INSERT INTO asset_holdings (household_id, asset_code, label, quantity, cost_basis_try, created_by) VALUES (?,?,?,?,?,?)')
        ->execute([$householdId,$code,$label,$qty,$cost,$_SESSION['user_id']]);
    flash('success', 'Varlık eklendi: ' . e($label));
}
redirect('assets.php');
