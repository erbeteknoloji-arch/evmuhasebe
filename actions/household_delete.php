<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_owner();
verify_csrf();
$confirm = trim($_POST['confirm_name'] ?? '');
$h = current_household();
if ($confirm !== $h['name']) {
    flash('error', 'Onay için ev adını birebir yazmalısınız.');
    redirect('households.php');
}
if (count(user_households()) <= 1) {
    flash('error', 'Son evinizi silemezsiniz. Önce yeni bir ev oluşturun.');
    redirect('households.php');
}
$stmt = db()->prepare('DELETE FROM households WHERE id = ?');
$stmt->execute([hid()]);
unset($_SESSION['household_id']);
flash('success', '“' . $h['name'] . '” evi ve tüm verileri silindi.');
redirect('households.php');
