<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in() || !feature_enabled('messages')) { http_response_code(403); echo json_encode(['messages'=>[]]); exit; }

$me = (int)$_SESSION['user_id'];
$with = (int)($_GET['with'] ?? 0);
$after = (int)($_GET['after'] ?? 0);
$pdo = db();
if ($with <= 0) { echo json_encode(['messages'=>[]]); exit; }

$stmt = $pdo->prepare(
    'SELECT * FROM direct_messages
      WHERE ((from_user=? AND to_user=?) OR (from_user=? AND to_user=?)) AND id > ?
      ORDER BY id ASC LIMIT 200'
);
$stmt->execute([$me, $with, $with, $me, $after]);
$rows = $stmt->fetchAll();

// Gelenleri okundu işaretle
$pdo->prepare('UPDATE direct_messages SET read_at=NOW() WHERE to_user=? AND from_user=? AND read_at IS NULL')
    ->execute([$me, $with]);

$out = [];
foreach ($rows as $r) {
    $out[] = [
        'id'   => (int)$r['id'],
        'mine' => (int)$r['from_user'] === $me,
        'message' => $r['message'],
        'time' => date('H:i', strtotime($r['created_at'])),
        'date' => format_date($r['created_at']),
    ];
}
echo json_encode(['messages'=>$out]);
