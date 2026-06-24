<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
verify_csrf();

$u = current_user();
$name  = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
if ($name === '') $name = $u['name'];

// Bildirim tercihleri (checkbox -> 0/1)
$ne  = isset($_POST['notify_email']) ? 1 : 0;
$nt  = isset($_POST['notify_transactions']) ? 1 : 0;
$ni  = isset($_POST['notify_imports']) ? 1 : 0;
$nu  = isset($_POST['notify_upcoming']) ? 1 : 0;
$ng  = isset($_POST['notify_goals']) ? 1 : 0;

db()->prepare('UPDATE users SET name=?, notify_email=?, notify_transactions=?, notify_imports=?, notify_upcoming=?, notify_goals=? WHERE id=?')
    ->execute([$name,$ne,$nt,$ni,$nu,$ng,$u['id']]);

flash('success', 'Profil ve bildirim tercihleri kaydedildi.');
redirect('settings.php');
