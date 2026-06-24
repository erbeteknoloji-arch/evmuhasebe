<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
verify_csrf();
$id = (int)($_POST['household_id'] ?? 0);
if (set_active_household($id)) {
    flash('success', 'Aktif ev değiştirildi.');
} else {
    flash('error', 'Bu eve erişim yetkiniz yok.');
}
redirect($_POST['return'] ?? 'index.php');
