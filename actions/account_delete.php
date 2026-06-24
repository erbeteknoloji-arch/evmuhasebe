<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('DELETE FROM accounts WHERE id = ? AND household_id = ?');
$stmt->execute([$id, hid()]);
if ($stmt->rowCount()) flash('success', 'Hesap silindi.');
else flash('error', 'Hesap bulunamadı.');
redirect('accounts.php');
