<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();
verify_csrf();
$me = (int)current_user()['id'];
$id = (int)($_POST['id'] ?? 0);
if ($id === $me) { flash('error', 'Kendi hesabınızı silemezsiniz.'); redirect('admin/users.php'); }
$pdo = db();
$u = $pdo->prepare('SELECT is_admin FROM users WHERE id=?'); $u->execute([$id]); $row = $u->fetch();
if ($row) {
    if ($row['is_admin']) {
        $cnt = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_admin=1')->fetchColumn();
        if ($cnt <= 1) { flash('error', 'Son yöneticiyi silemezsiniz.'); redirect('admin/users.php'); }
    }
    try {
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        flash('success', 'Kullanıcı silindi.');
    } catch (Throwable $e) {
        flash('error', 'Kullanıcı silinemedi. Bunun yerine pasifleştirebilirsiniz.');
    }
}
redirect('admin/users.php');
