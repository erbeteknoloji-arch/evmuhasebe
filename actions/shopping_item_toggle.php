<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$isAjax = (($_POST['ajax'] ?? '') === '1');

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    if (!is_logged_in() || !feature_enabled('shopping')) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { http_response_code(419); echo json_encode(['ok'=>false,'err'=>'csrf']); exit; }
    require_household();
} else {
    require_household();
    require_feature('shopping');
    verify_csrf();
}

$householdId = hid();
$pdo = db();
$itemId = (int)($_POST['item_id'] ?? 0);

/* Ürünün, bu eve ait bir listeye bağlı olduğunu doğrula */
$stmt = $pdo->prepare(
    'SELECT si.id, si.is_done, si.list_id
       FROM shopping_items si
       JOIN shopping_lists sl ON sl.id = si.list_id
      WHERE si.id = ? AND sl.household_id = ?'
);
$stmt->execute([$itemId, $householdId]);
$item = $stmt->fetch();

if (!$item) {
    if ($isAjax) { echo json_encode(['ok'=>false,'err'=>'not_found']); exit; }
    flash('error', 'Ürün bulunamadı.');
    redirect('shopping.php');
}

$new = $item['is_done'] ? 0 : 1;
$pdo->prepare('UPDATE shopping_items SET is_done=?, done_at=' . ($new ? 'NOW()' : 'NULL') . ' WHERE id=?')
    ->execute([$new, $itemId]);

if ($isAjax) {
    echo json_encode(['ok'=>true, 'is_done'=>$new]);
    exit;
}
redirect('shopping.php?list=' . (int)$item['list_id']);
