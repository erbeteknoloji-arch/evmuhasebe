<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in() || !feature_enabled('chat')) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { http_response_code(419); echo json_encode(['ok'=>false,'err'=>'csrf']); exit; }

$u = current_user();
$pdo = db();
$kind = ($_POST['kind'] ?? 'chat') === 'price' ? 'price' : 'chat';

if ($kind === 'price') {
    $product = mb_substr(trim($_POST['product'] ?? ''), 0, 160);
    $price   = parse_money_tr($_POST['price'] ?? '');
    $store   = mb_substr(trim($_POST['store'] ?? ''), 0, 120);
    $note    = mb_substr(trim($_POST['message'] ?? ''), 0, 1000);
    if ($product === '' || $price === null) { echo json_encode(['ok'=>false,'err'=>'Ürün ve fiyat gerekli']); exit; }
    $pdo->prepare('INSERT INTO chat_messages (user_id, kind, message, product, price, store) VALUES (?,?,?,?,?,?)')
        ->execute([$u['id'], 'price', $note, $product, $price, $store]);
} else {
    $msg = mb_substr(trim($_POST['message'] ?? ''), 0, 1000);
    if ($msg === '') { echo json_encode(['ok'=>false,'err'=>'Boş mesaj']); exit; }
    $pdo->prepare('INSERT INTO chat_messages (user_id, kind, message) VALUES (?,?,?)')
        ->execute([$u['id'], 'chat', $msg]);
}
echo json_encode(['ok'=>true, 'id'=>(int)$pdo->lastInsertId()]);
