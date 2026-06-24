<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();
verify_csrf();
$me = (int)current_user()['id'];
$id = (int)($_POST['id'] ?? 0);
if ($id === $me) { flash('error', 'Kendi hesabınızı pasifleştiremezsiniz.'); redirect('admin/users.php'); }
$pdo = db();
$u = $pdo->prepare('SELECT is_active FROM users WHERE id=?'); $u->execute([$id]); $row = $u->fetch();
if ($row) {
    $new = $row['is_active'] ? 0 : 1;
    $pdo->prepare('UPDATE users SET is_active=? WHERE id=?')->execute([$new, $id]);
    flash('success', $new ? 'Kullanıcı aktifleştirildi.' : 'Kullanıcı pasifleştirildi.');
}
redirect('admin/users.php');
