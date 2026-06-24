<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_owner();
verify_csrf();
$name = trim($_POST['name'] ?? '');
$currency = $_POST['currency'] ?? 'TRY';
if (!isset($GLOBALS['CURRENCY_SYMBOLS'][$currency])) $currency = 'TRY';
if (mb_strlen($name) < 2) { flash('error', 'Ev adı en az 2 karakter olmalı.'); redirect('households.php'); }
$stmt = db()->prepare('UPDATE households SET name = ?, currency = ? WHERE id = ?');
$stmt->execute([$name, $currency, hid()]);
log_activity(hid(), 'household_update', 'Ev güncellendi: ' . $name);
flash('success', 'Ev bilgileri güncellendi.');
redirect('households.php');
