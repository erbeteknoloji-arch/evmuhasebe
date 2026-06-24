<?php
/**
 * =====================================================================
 *  EV MUHASEBE  -  Genel Yapılandırma
 * =====================================================================
 *  Sunucunuza yüklerken yalnızca aşağıdaki veritabanı bilgilerini
 *  kendi hosting/sunucu bilgilerinizle değiştirmeniz yeterlidir.
 * =====================================================================
 */

// ----- VERİTABANI BAĞLANTI BİLGİLERİ -------------------------------
define('DB_HOST', getenv('EV_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('EV_DB_PORT') ?: '3306');
define('DB_NAME', getenv('EV_DB_NAME') ?: 'u2605166_evmuhasebe');
define('DB_USER', getenv('EV_DB_USER') ?: 'u2605166_evmuhasebe');
define('DB_PASS', getenv('EV_DB_PASS') ?: '514278Er*');
define('DB_CHARSET', 'utf8mb4');

// ----- UYGULAMA AYARLARI -------------------------------------------
define('APP_NAME', 'Ev Muhasebe');
define('APP_TIMEZONE', 'Europe/Istanbul');

// Yüklenen PDF ekstrelerinin saklanacağı klasör
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads');
define('MAX_UPLOAD_MB', 15);                 // izin verilen en büyük PDF boyutu

// PDF okuma aracı (pdftotext) tam yolu.
//  Linux/Mac'te PATH'te ise boş bırakın. Windows/XAMPP'te Poppler'ı kurup
//  pdftotext.exe yolunu yazın, örn:
//  define('PDFTOTEXT_PATH', 'C:\\poppler\\Library\\bin\\pdftotext.exe');
define('PDFTOTEXT_PATH', getenv('EV_PDFTOTEXT') ?: '');

// Para birimi sembolleri
$GLOBALS['CURRENCY_SYMBOLS'] = [
    'TRY' => '₺',
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
];

// ----- SİTE ADRESİ (e-posta bağlantıları için) ---------------------
//  Boş bırakırsanız otomatik algılanır. Kendi alan adınızı yazabilirsiniz,
//  örn: 'https://muhasebe.alanadiniz.com'
define('APP_URL', getenv('EV_APP_URL') ?: '');

// ----- E-POSTA / SMTP AYARLARI -------------------------------------
// Bildirim ve şifre sıfırlama e-postaları için.

define('EMAIL_ENABLED', filter_var(
    getenv('EV_EMAIL_ENABLED') ?: 'true',
    FILTER_VALIDATE_BOOLEAN
));

define('SMTP_HOST',   getenv('EV_SMTP_HOST')   ?: 'mail.kurumsaleposta.com'); // ör: smtp.gmail.com
define('SMTP_PORT',   (int)(getenv('EV_SMTP_PORT') ?: 465));
define('SMTP_SECURE', getenv('EV_SMTP_SECURE') ?: 'ssl'); // 'ssl' | 'tls' | ''

define('SMTP_USER',   getenv('EV_SMTP_USER')   ?: 'admin@toteminsaat.com');
define('SMTP_PASS',   getenv('EV_SMTP_PASS')   ?: 'mngWowCity-07');

define('MAIL_FROM',      getenv('EV_MAIL_FROM')      ?: 'admin@toteminsaat.com');
define('MAIL_FROM_NAME', getenv('EV_MAIL_FROM_NAME') ?: 'Ev Muhasebe');

// ----- DÖVİZ / ALTIN KURLARI ---------------------------------------
//  Varlıklar (USD, EUR, altın, gümüş) güncel kurdan değerlenir.
//  Kurlar otomatik çekilir ve veritabanında önbelleğe alınır.
define('RATES_AUTO_FETCH', filter_var(getenv('EV_RATES_AUTO') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('RATES_TTL_HOURS',  (int)(getenv('EV_RATES_TTL') ?: 6));          // kaç saatte bir yenilensin


// ----- ÇALIŞMA ZAMANI ----------------------------------------------
date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');

// Geliştirme sırasında hataları görmek için açık; canlıda kapatabilirsiniz.
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Oturum çerezi güvenlik ayarları
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('EVMUHASEBESID');
    session_start();
}
