<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$back = $_POST['return'] ?? 'categories.php';
$stmt = db()->prepare('DELETE FROM import_rules WHERE id = ? AND household_id = ?');
$stmt->execute([$id, hid()]);
flash('success', 'Kural silindi.');
redirect($back);
