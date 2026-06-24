<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_owner();
verify_csrf();
$email = trim(mb_strtolower($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Geçerli bir e-posta girin.');
    redirect('households.php');
}
$token = bin2hex(random_bytes(16));
$stmt = db()->prepare('INSERT INTO invitations (household_id, email, token, invited_by) VALUES (?, ?, ?, ?)');
$stmt->execute([hid(), $email, $token, $_SESSION['user_id']]);
log_activity(hid(), 'member_invite', 'Davet: ' . $email);
flash('success', 'Davet oluşturuldu. Aşağıdaki bağlantıyı ' . $email . ' adresine iletin.');
redirect('households.php?davet=' . $token);
