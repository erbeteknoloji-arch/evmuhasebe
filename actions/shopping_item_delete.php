<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
require_feature('shopping');
verify_csrf();

$householdId = hid();
$pdo = db();
$itemId = (int)($_POST['item_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT si.id, si.list_id FROM shopping_items si
       JOIN shopping_lists sl ON sl.id = si.list_id
      WHERE si.id = ? AND sl.household_id = ?'
);
$stmt->execute([$itemId, $householdId]);
$item = $stmt->fetch();

if ($item) {
    $pdo->prepare('DELETE FROM shopping_items WHERE id=?')->execute([$itemId]);
    flash('success', 'Ürün silindi.');
    redirect('shopping.php?list=' . (int)$item['list_id']);
}
redirect('shopping.php');
