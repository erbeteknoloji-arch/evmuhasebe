<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/products.php';

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

$itemId = (int)($_POST['id'] ?? 0);
$listId = (int)($_POST['list_id'] ?? 0);

/* İlgili listenin bu eve ait olduğunu doğrula */
$chk = $pdo->prepare('SELECT id FROM shopping_lists WHERE id=? AND household_id=?');
$chk->execute([$listId, $householdId]);
if (!$chk->fetch()) {
    if ($isAjax) { echo json_encode(['ok'=>false,'err'=>'list_not_found']); exit; }
    flash('error', 'Liste bulunamadı.');
    redirect('shopping.php');
}

$name  = mb_substr(trim($_POST['name'] ?? ''), 0, 160);
$icon  = mb_substr(trim($_POST['icon'] ?? ''), 0, 8);
$color = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '';
$qty   = mb_substr(trim($_POST['qty'] ?? ''), 0, 40) ?: null;
$note  = mb_substr(trim($_POST['note'] ?? ''), 0, 200) ?: null;
$price = parse_money_tr($_POST['est_price'] ?? '');

if ($name === '') {
    if ($isAjax) { echo json_encode(['ok'=>false,'err'=>'Ürün adı gerekli']); exit; }
    flash('error', 'Ürün adı gereklidir.');
    redirect('shopping.php?list=' . $listId);
}

/* İkon ya da renk verilmediyse ürün adından akıllıca tahmin et */
if ($icon === '' || $color === '') {
    [$gIcon, $gColor] = guess_product_icon($name);
    if ($icon === '')  $icon  = $gIcon;
    if ($color === '') $color = $gColor;
}

if ($itemId > 0 && !$isAjax) {
    /* Düzenleme - ürünün gerçekten bu listeye ait olduğunu doğrula */
    $own = $pdo->prepare('SELECT id FROM shopping_items WHERE id=? AND list_id=?');
    $own->execute([$itemId, $listId]);
    if ($own->fetch()) {
        $pdo->prepare('UPDATE shopping_items SET name=?, icon=?, color=?, qty=?, note=?, est_price=? WHERE id=?')
            ->execute([$name, $icon, $color, $qty, $note, $price, $itemId]);
        flash('success', 'Ürün güncellendi.');
    }
    redirect('shopping.php?list=' . $listId);
}

/* Yeni ürün */
$posRow = $pdo->prepare('SELECT COALESCE(MAX(position),0)+1 AS p FROM shopping_items WHERE list_id=?');
$posRow->execute([$listId]);
$pos = (int)$posRow->fetch()['p'];
$pdo->prepare('INSERT INTO shopping_items (list_id, name, icon, color, qty, note, est_price, position, created_by) VALUES (?,?,?,?,?,?,?,?,?)')
    ->execute([$listId, $name, $icon, $color, $qty, $note, $price, $pos, $_SESSION['user_id']]);
$newId = (int)$pdo->lastInsertId();

if ($isAjax) {
    echo json_encode(['ok'=>true, 'item'=>[
        'id'    => $newId,
        'name'  => $name,
        'icon'  => $icon,
        'color' => $color,
        'qty'   => $qty,
        'note'  => $note,
        'price' => $price !== null ? money($price) : null,
    ]]);
    exit;
}
flash('success', $name . ' eklendi.');
redirect('shopping.php?list=' . $listId);
