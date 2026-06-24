<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$householdId = hid();
$id = (int)($_POST['id'] ?? 0);
db()->prepare('DELETE FROM asset_holdings WHERE id=? AND household_id=?')->execute([$id, $householdId]);
flash('success', 'Varlık silindi.');
redirect('assets.php');
