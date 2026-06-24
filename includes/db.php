<?php
/**
 * Veritabanı bağlantısı (PDO - MySQL/MariaDB).
 * Tüm sorgular hazır ifade (prepared statement) ile çalışır -> SQL enjeksiyonuna karşı güvenli.
 */

require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<!doctype html><html lang="tr"><head><meta charset="utf-8">'
            . '<title>Veritabanı Hatası</title>'
            . '<style>body{font-family:system-ui,sans-serif;background:#F6F4EE;color:#1A1D23;'
            . 'display:flex;align-items:center;justify-content:center;height:100vh;margin:0}'
            . '.box{max-width:560px;background:#fff;border:1px solid #E5E0D5;border-radius:16px;'
            . 'padding:32px;box-shadow:0 10px 30px rgba(0,0,0,.06)}'
            . 'h1{font-size:20px;margin:0 0 12px}code{background:#F0EDE4;padding:2px 6px;border-radius:6px}'
            . '</style></head><body><div class="box">'
            . '<h1>Veritabanına bağlanılamadı</h1>'
            . '<p><code>config/config.php</code> dosyasındaki veritabanı bilgilerini kontrol edin '
            . '(sunucu, kullanıcı adı, şifre, veritabanı adı) ve <code>database/schema.sql</code> '
            . 'dosyasını içeri aktardığınızdan emin olun.</p>'
            . '<p style="color:#9b1c1c;font-size:13px">Teknik mesaj: '
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div></body></html>';
        exit;
    }

    return $pdo;
}
