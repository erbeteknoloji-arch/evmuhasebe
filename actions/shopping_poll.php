<?php
/**
 * Alışveriş listesi canlı senkronizasyon ucu (salt-okunur, JSON).
 * Aynı evin üyeleri bir ürünü ekleyince/işaretleyince/silince diğer
 * üyeler sayfayı yenilemeden güncel durumu görür (JS yoklaması ile).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
require_feature('shopping');

header('Content-Type: application/json; charset=utf-8');

$householdId = hid();
$pdo = db();
$listId = (int)($_GET['list'] ?? $_POST['list'] ?? 0);

// Liste bu eve mi ait?
$ls = $pdo->prepare('SELECT id, name FROM shopping_lists WHERE id=? AND household_id=? LIMIT 1');
$ls->execute([$listId, $householdId]);
$list = $ls->fetch();
if (!$list) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$is = $pdo->prepare('SELECT * FROM shopping_items WHERE list_id=? ORDER BY is_done ASC, position ASC, id ASC');
$is->execute([$listId]);
$rows = $is->fetchAll();

$items = [];
$done = 0; $total = 0; $est = 0.0;
foreach ($rows as $it) {
    $total++;
    if ((int)$it['is_done'] === 1) $done++;
    $est += (float)$it['est_price'];
    $price = ($it['est_price'] !== null && (float)$it['est_price'] > 0) ? money($it['est_price']) : '';
    $items[] = [
        'id'      => (int)$it['id'],
        'name'    => $it['name'],
        'icon'    => $it['icon'],
        'color'   => $it['color'],
        'qty'     => $it['qty'],
        'note'    => $it['note'],
        'price'   => $price,
        'est_price' => ($it['est_price'] !== null ? number_format((float)$it['est_price'], 2, ',', '.') : ''),
        'is_done' => (int)$it['is_done'] === 1,
    ];
}

// İmza: istemci bununla değişiklik olup olmadığını anlar
$sig = md5(json_encode(array_map(fn($i) => [$i['id'], $i['is_done'], $i['name'], $i['icon'], $i['color'], $i['qty'], $i['note'], $i['price']], $items)));

echo json_encode([
    'ok'       => true,
    'sig'      => $sig,
    'items'    => $items,
    'done'     => $done,
    'total'    => $total,
    'est_total'=> money($est),
]);
