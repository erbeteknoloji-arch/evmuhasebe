<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
verify_csrf();
require_feature('tickets');

$u = current_user();
$admin = is_admin_user();
$pdo = db();
$tid = (int)($_POST['ticket_id'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['open','answered','closed'], true) ? $_POST['status'] : 'open';

$t = $pdo->prepare('SELECT * FROM tickets WHERE id=? LIMIT 1');
$t->execute([$tid]);
$ticket = $t->fetch();
if (!$ticket || (!$admin && (int)$ticket['user_id'] !== (int)$u['id'])) {
    flash('error', 'Talep bulunamadı.');
    redirect('tickets.php');
}
$pdo->prepare('UPDATE tickets SET status=?, updated_at=NOW() WHERE id=?')->execute([$status, $tid]);
flash('success', 'Talep durumu güncellendi.');
redirect('ticket_view.php?id=' . $tid);
