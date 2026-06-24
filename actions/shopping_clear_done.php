<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
require_feature('shopping');
verify_csrf();

$householdId = hid();
$pdo = db();
$listId = (int)($_POST['list_id'] ?? 0);

/* Listenin bu eve ait olduğunu doğrula */
$chk = $pdo->prepare('SELECT id FROM shopping_lists WHERE id=? AND household_id=?');
$chk->execute([$listId, $householdId]);
if ($chk->fetch()) {
    $pdo->prepare('DELETE FROM shopping_items WHERE list_id=? AND is_done=1')->execute([$listId]);
    flash('success', 'Tamamlanan ürünler temizlendi.');
}
redirect('shopping.php?list=' . $listId);
