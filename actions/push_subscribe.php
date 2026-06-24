<?php
/**
 * Push aboneliğini kaydeder (mevcut kullanıcıya bağlı).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$userId   = (int)current_user()['id'];
$endpoint = trim($_POST['endpoint'] ?? '');
$p256dh   = trim($_POST['p256dh'] ?? '');
$auth     = trim($_POST['auth'] ?? '');
$ua       = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

if ($endpoint === '' || $p256dh === '' || $auth === '' || mb_strlen($endpoint) > 500) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

try {
    $stmt = db()->prepare(
        'INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, ua, last_used_at)
         VALUES (?,?,?,?,?,NOW())
         ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), p256dh=VALUES(p256dh), auth=VALUES(auth), ua=VALUES(ua), last_used_at=NOW()'
    );
    $stmt->execute([$userId, $endpoint, $p256dh, $auth, $ua]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'db']);
}
