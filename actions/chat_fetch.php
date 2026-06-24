<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in() || !feature_enabled('chat')) { http_response_code(403); echo json_encode(['messages'=>[]]); exit; }

$pdo = db();
$after = (int)($_GET['after'] ?? 0);
$me = (int)$_SESSION['user_id'];

if ($after > 0) {
    $stmt = $pdo->prepare(
        'SELECT c.*, u.name AS uname, u.avatar_color FROM chat_messages c
           JOIN users u ON u.id=c.user_id WHERE c.id > ? ORDER BY c.id ASC LIMIT 100'
    );
    $stmt->execute([$after]);
    $rows = $stmt->fetchAll();
} else {
    // İlk yükleme: son 60 mesaj (artan sırada)
    $rows = array_reverse($pdo->query(
        'SELECT c.*, u.name AS uname, u.avatar_color FROM chat_messages c
           JOIN users u ON u.id=c.user_id ORDER BY c.id DESC LIMIT 60'
    )->fetchAll());
}

$out = [];
foreach ($rows as $r) {
    $out[] = [
        'id'    => (int)$r['id'],
        'mine'  => (int)$r['user_id'] === $me,
        'name'  => $r['uname'],
        'color' => $r['avatar_color'] ?: '#14452F',
        'kind'  => $r['kind'],
        'message' => $r['message'],
        'product' => $r['product'],
        'price'   => $r['price'] !== null ? money($r['price']) : null,
        'store'   => $r['store'],
        'time'  => date('H:i', strtotime($r['created_at'])),
        'date'  => format_date($r['created_at']),
    ];
}
echo json_encode(['messages'=>$out]);
