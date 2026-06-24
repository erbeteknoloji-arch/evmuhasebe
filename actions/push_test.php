<?php
/**
 * Mevcut kullanıcıya test bildirimi gönderir.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/webpush.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

if (!webpush_supported()) {
    echo json_encode(['ok' => false, 'error' => 'unsupported']);
    exit;
}

$sent = push_to_user((int)current_user()['id'], [
    'title' => 'Ev Muhasebe',
    'body'  => 'Bildirimler çalışıyor 🎉 Bu bir test bildirimidir.',
    'url'   => url('index.php'),
]);

echo json_encode(['ok' => $sent > 0, 'sent' => $sent]);
