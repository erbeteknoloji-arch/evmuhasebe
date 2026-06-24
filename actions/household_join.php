<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
verify_csrf();
$code  = strtoupper(trim($_POST['join_code'] ?? ''));
$token = trim($_POST['token'] ?? '');
$pdo = db();
$house = null;

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM invitations WHERE token = ? AND status = "pending" LIMIT 1');
    $stmt->execute([$token]);
    $inv = $stmt->fetch();
    if ($inv) {
        $hs = $pdo->prepare('SELECT * FROM households WHERE id = ?');
        $hs->execute([$inv['household_id']]);
        $house = $hs->fetch();
        if ($house) {
            $upd = $pdo->prepare('UPDATE invitations SET status = "accepted" WHERE id = ?');
            $upd->execute([$inv['id']]);
        }
    }
} elseif ($code !== '') {
    $stmt = $pdo->prepare('SELECT * FROM households WHERE join_code = ? LIMIT 1');
    $stmt->execute([$code]);
    $house = $stmt->fetch();
}

if (!$house) {
    flash('error', 'Geçersiz katılım kodu veya davet bağlantısı.');
    redirect('households.php');
}

// Zaten üye mi?
if (membership((int)$house['id'])) {
    set_active_household((int)$house['id']);
    flash('info', 'Bu evin zaten üyesisiniz.');
    redirect('households.php');
}

$m = $pdo->prepare('INSERT INTO household_members (household_id, user_id, role) VALUES (?, ?, "member")');
$m->execute([$house['id'], $_SESSION['user_id']]);
set_active_household((int)$house['id']);
log_activity((int)$house['id'], 'member_join', current_user()['name'] . ' eve katıldı');
flash('success', '“' . $house['name'] . '” evine katıldınız!');
redirect('index.php');
