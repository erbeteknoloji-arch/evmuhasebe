<?php
/**
 * Push aboneliğini kaldırır.
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

try {
    if ($endpoint !== '') {
        db()->prepare('DELETE FROM push_subscriptions WHERE user_id=? AND endpoint=?')->execute([$userId, $endpoint]);
    } else {
        db()->prepare('DELETE FROM push_subscriptions WHERE user_id=?')->execute([$userId]);
    }
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'db']);
}
