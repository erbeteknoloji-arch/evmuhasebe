<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
require_feature('shopping');
verify_csrf();

$householdId = hid();
$id = (int)($_POST['id'] ?? 0);
db()->prepare('DELETE FROM shopping_lists WHERE id=? AND household_id=?')->execute([$id, $householdId]);
flash('success', 'Liste silindi.');
redirect('shopping.php');
