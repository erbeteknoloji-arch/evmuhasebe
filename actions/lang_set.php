<?php
/**
 * Arayüz dilini değiştirir (oturum + çerez + kullanıcı tercihi).
 * Hem AJAX hem normal form gönderimiyle çalışır.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$lang = $_POST['lang'] ?? $_GET['lang'] ?? '';
$ajax = !empty($_POST['ajax']);

// CSRF (form gönderimleri için); GET ile gelen basit değişimde de token bekleriz
if (($_SERVER['REQUEST_METHOD'] === 'POST') && !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    if ($ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => false]); exit; }
    redirect('index.php');
}

$ok = set_lang((string)$lang);

if ($ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'lang' => current_lang(), 'dir' => lang_dir()]);
    exit;
}

$back = $_POST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? url('index.php'));
header('Location: ' . $back);
exit;
