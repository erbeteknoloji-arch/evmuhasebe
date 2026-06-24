<?php
require_once __DIR__ . '/../includes/auth.php';
// Oturumu tamamen kapat
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
flash('success', 'Çıkış yaptınız. Görüşmek üzere!');
redirect('auth/login.php');
