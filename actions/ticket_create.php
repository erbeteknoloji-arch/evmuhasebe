<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/mailer.php';
require_login();
verify_csrf();
require_feature('tickets');

$u = current_user();
$pdo = db();
$subject = mb_substr(trim($_POST['subject'] ?? ''), 0, 180);
$message = trim($_POST['message'] ?? '');
$priority = in_array($_POST['priority'] ?? 'normal', ['low','normal','high'], true) ? $_POST['priority'] : 'normal';

if ($subject === '' || $message === '') {
    flash('error', 'Konu ve mesaj gereklidir.');
    redirect('tickets.php');
}

$pdo->prepare('INSERT INTO tickets (user_id, subject, priority, status) VALUES (?,?,?,"open")')
    ->execute([$u['id'], $subject, $priority]);
$tid = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?,?,0,?)')
    ->execute([$tid, $u['id'], $message]);

// Yöneticilere bildir
if (EMAIL_ENABLED) {
    $admins = $pdo->query("SELECT name, email FROM users WHERE is_admin=1 AND is_active=1")->fetchAll();
    $html = email_layout('Yeni destek talebi #' . $tid,
        '<p><b>' . e($u['name']) . '</b> yeni bir destek talebi oluşturdu:</p>'
        . '<p><b>Konu:</b> ' . e($subject) . '</p><p>' . nl2br(e(mb_substr($message,0,500))) . '</p>',
        'Talebi Görüntüle', absolute_url('ticket_view.php?id=' . $tid));
    foreach ($admins as $a) { @send_app_mail($a['email'], '[Destek] ' . $subject, $html, $a['name']); }
}

flash('success', 'Destek talebiniz oluşturuldu. Yöneticiler en kısa sürede yanıtlayacak.');
redirect('ticket_view.php?id=' . $tid);
