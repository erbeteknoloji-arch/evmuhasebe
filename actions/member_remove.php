<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_owner();
verify_csrf();
$userId = (int)($_POST['user_id'] ?? 0);
if ($userId === (int)$_SESSION['user_id']) {
    flash('error', 'Kendinizi çıkaramazsınız.');
    redirect('households.php');
}
$stmt = db()->prepare('DELETE FROM household_members WHERE household_id = ? AND user_id = ? AND role <> "owner"');
$stmt->execute([hid(), $userId]);
if ($stmt->rowCount()) {
    log_activity(hid(), 'member_remove', 'Üye çıkarıldı #' . $userId);
    flash('success', 'Üye evden çıkarıldı.');
} else {
    flash('error', 'Üye çıkarılamadı.');
}
redirect('households.php');
