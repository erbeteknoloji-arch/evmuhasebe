<?php
/**
 * CSRF (Cross-Site Request Forgery) koruması.
 * Tüm POST formlarında gizli bir jeton bulunur ve sunucuda doğrulanır.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Forma eklenecek gizli alan */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** POST isteklerinde jetonu doğrula; geçersizse durdur */
function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        die('Oturum doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}

/** AJAX/JSON uçları için: jetonu doğrular, true/false döner (durdurmaz). */
function verify_csrf_token(?string $sent): bool
{
    return is_string($sent) && $sent !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $sent);
}
