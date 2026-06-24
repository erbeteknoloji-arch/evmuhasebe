<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$back = $_POST['return'] ?? 'transactions.php';
$pdo = db();

// Transfer ise iki bacağı birden sil
$row = $pdo->prepare('SELECT transfer_id FROM transactions WHERE id = ? AND household_id = ?');
$row->execute([$id, hid()]);
$tx = $row->fetch();

if ($tx && !empty($tx['transfer_id'])) {
    $stmt = $pdo->prepare('DELETE FROM transactions WHERE transfer_id = ? AND household_id = ?');
    $stmt->execute([$tx['transfer_id'], hid()]);
    if ($stmt->rowCount()) {
        log_activity(hid(), 'transaction_delete', 'Transfer silindi (#' . $id . ')');
        flash('success', 'Transfer (her iki kayıt) silindi.');
    } else {
        flash('error', 'İşlem bulunamadı.');
    }
    redirect($back);
}

$stmt = $pdo->prepare('DELETE FROM transactions WHERE id = ? AND household_id = ?');
$stmt->execute([$id, hid()]);
if ($stmt->rowCount()) {
    log_activity(hid(), 'transaction_delete', 'İşlem silindi #' . $id);
    flash('success', 'İşlem silindi.');
} else {
    flash('error', 'İşlem bulunamadı.');
}
redirect($back);
