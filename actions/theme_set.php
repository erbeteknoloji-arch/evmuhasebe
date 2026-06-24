<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
verify_csrf();

$theme = $_POST['theme'] ?? 'standart';
$themes = theme_list();
if (!isset($themes[$theme])) $theme = 'standart';

db()->prepare('UPDATE users SET theme=? WHERE id=?')->execute([$theme, $_SESSION['user_id']]);

// AJAX ise sessiz dön
if (($_POST['ajax'] ?? '') === '1') {
    http_response_code(204);
    exit;
}
flash('success', 'Tema güncellendi: ' . $themes[$theme][0]);
$back = $_POST['return'] ?? 'settings.php';
redirect($back);
