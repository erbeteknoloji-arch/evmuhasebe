<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in() || !feature_enabled('messages')) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { http_response_code(419); echo json_encode(['ok'=>false]); exit; }

$me = (int)$_SESSION['user_id'];
$to = (int)($_POST['to'] ?? 0);
$msg = mb_substr(trim($_POST['message'] ?? ''), 0, 2000);
$pdo = db();

if ($to <= 0 || $to === $me || $msg === '') { echo json_encode(['ok'=>false,'err'=>'Geçersiz']); exit; }
$chk = $pdo->prepare('SELECT id FROM users WHERE id=? AND is_active=1');
$chk->execute([$to]);
if (!$chk->fetch()) { echo json_encode(['ok'=>false,'err'=>'Kullanıcı yok']); exit; }

$pdo->prepare('INSERT INTO direct_messages (from_user, to_user, message) VALUES (?,?,?)')
    ->execute([$me, $to, $msg]);
echo json_encode(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
