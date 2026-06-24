<?php
/**
 * Yönetici: herhangi bir hanedeki işlemi sil.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();
verify_csrf();

$pdo = db();
$id  = (int)($_POST['id'] ?? 0);

$tx = $pdo->prepare('SELECT household_id, description, amount FROM transactions WHERE id = ? LIMIT 1');
$tx->execute([$id]);
$row = $tx->fetch();
if (!$row) {
    flash('error', 'İşlem bulunamadı.');
    redirect('admin/households.php');
}
$householdId = (int)$row['household_id'];

$pdo->prepare('DELETE FROM transactions WHERE id = ?')->execute([$id]);
log_activity($householdId, 'transaction_delete', '[admin] İşlem silindi #' . $id);
flash('success', 'İşlem silindi (yönetici).');
redirect('admin/household_view.php?id=' . $householdId);
