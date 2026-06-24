<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/mailer.php';
require_login();
verify_csrf();
require_feature('tickets');

$u = current_user();
$admin = is_admin_user();
$pdo = db();
$tid = (int)($_POST['ticket_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

$t = $pdo->prepare('SELECT t.*, us.name AS oname, us.email AS oemail FROM tickets t JOIN users us ON us.id=t.user_id WHERE t.id=? LIMIT 1');
$t->execute([$tid]);
$ticket = $t->fetch();
if (!$ticket || (!$admin && (int)$ticket['user_id'] !== (int)$u['id'])) {
    flash('error', 'Talep bulunamadı.');
    redirect('tickets.php');
}
if ($message === '') {
    flash('error', 'Boş mesaj gönderilemez.');
    redirect('ticket_view.php?id=' . $tid);
}
if ($ticket['status'] === 'closed') {
    flash('error', 'Kapalı talebe yanıt verilemez.');
    redirect('ticket_view.php?id=' . $tid);
}

$pdo->prepare('INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?,?,?,?)')
    ->execute([$tid, $u['id'], $admin ? 1 : 0, $message]);
// Admin yanıtladıysa "yanıtlandı", kullanıcı yazdıysa "açık"
$newStatus = $admin ? 'answered' : 'open';
$pdo->prepare('UPDATE tickets SET status=?, updated_at=NOW() WHERE id=?')->execute([$newStatus, $tid]);

// Karşı tarafa bildir
if (EMAIL_ENABLED) {
    if ($admin) {
        // kullanıcıya
        $html = email_layout('Talebinize yanıt geldi #' . $tid,
            '<p>"' . e($ticket['subject']) . '" başlıklı talebinize yönetici yanıt verdi:</p><p>' . nl2br(e(mb_substr($message,0,500))) . '</p>',
            'Yanıtı Gör', absolute_url('ticket_view.php?id=' . $tid));
        @send_app_mail($ticket['oemail'], '[Destek] Yanıt: ' . $ticket['subject'], $html, $ticket['oname']);
    } else {
        // yöneticilere
        $admins = $pdo->query("SELECT name, email FROM users WHERE is_admin=1 AND is_active=1")->fetchAll();
        $html = email_layout('Destek talebine kullanıcı yanıtı #' . $tid,
            '<p><b>' . e($u['name']) . '</b> "' . e($ticket['subject']) . '" talebine yanıt yazdı:</p><p>' . nl2br(e(mb_substr($message,0,500))) . '</p>',
            'Talebi Gör', absolute_url('ticket_view.php?id=' . $tid));
        foreach ($admins as $a) { @send_app_mail($a['email'], '[Destek] Kullanıcı yanıtı: ' . $ticket['subject'], $html, $a['name']); }
    }
}

flash('success', 'Yanıtınız gönderildi.');
redirect('ticket_view.php?id=' . $tid);
