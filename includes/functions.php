<?php
/**
 * Genel yardımcı fonksiyonlar.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/i18n.php';

/* -------------------------------------------------------------------
 *  Güvenli çıktı (XSS koruması)
 * ----------------------------------------------------------------- */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* -------------------------------------------------------------------
 *  Taban URL  -  uygulamanın hangi alt klasörde olduğunu otomatik bulur
 *  Örn. site kökünde ise "/", /muhasebe altında ise "/muhasebe"
 * ----------------------------------------------------------------- */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    // index.php / login.php gibi dosyalar projenin kökünde veya auth/ altında olabilir.
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    // auth/ veya actions/ veya admin/ alt klasöründen çağrıldıysa bir üst dizine çık
    if (preg_match('#/(auth|actions|admin)$#', $script)) {
        $script = preg_replace('#/(auth|actions|admin)$#', '', $script);
    }
    $base = ($script === '/' || $script === '\\') ? '' : rtrim($script, '/');
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return base_path() . '/' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* -------------------------------------------------------------------
 *  Flash mesajları (tek seferlik bildirimler)
 * ----------------------------------------------------------------- */
function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $f;
}

/* -------------------------------------------------------------------
 *  Para biçimlendirme  -  Türkçe format: 1.234,56 ₺
 * ----------------------------------------------------------------- */
function currency_symbol(?string $code = null): string
{
    $code = $code ?: (current_household()['currency'] ?? 'TRY');
    return $GLOBALS['CURRENCY_SYMBOLS'][$code] ?? $code . ' ';
}

function money($amount, ?string $code = null): string
{
    $amount = (float)$amount;
    return number_format($amount, 2, ',', '.') . ' ' . currency_symbol($code);
}

/**
 * Türkçe biçimli tutarı ("1.234,56" veya "1,234.56") float'a çevirir.
 */
function parse_money_tr(string $raw): ?float
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    // Para birimi sembollerini ve boşlukları temizle
    $raw = preg_replace('/[^\d.,\-+]/u', '', $raw);
    $raw = str_replace(['+'], '', $raw);
    if ($raw === '' || $raw === '-') {
        return null;
    }

    $hasComma = strpos($raw, ',') !== false;
    $hasDot   = strpos($raw, '.') !== false;

    if ($hasComma && $hasDot) {
        // Hangisi sonda ise o ondalık ayraçtır
        if (strrpos($raw, ',') > strrpos($raw, '.')) {
            // Türkçe: nokta=binlik, virgül=ondalık
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            // İngilizce: virgül=binlik, nokta=ondalık
            $raw = str_replace(',', '', $raw);
        }
    } elseif ($hasComma) {
        // Yalnızca virgül -> ondalık ayraç
        $raw = str_replace(',', '.', $raw);
    }
    // Yalnızca nokta varsa olduğu gibi bırak (zaten ondalık)

    return is_numeric($raw) ? (float)$raw : null;
}

/* -------------------------------------------------------------------
 *  Rastgele kod/jeton üreteci
 * ----------------------------------------------------------------- */
function random_code(int $length = 8): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, $max)];
    }
    return $out;
}

/* -------------------------------------------------------------------
 *  Etkinlik günlüğü
 * ----------------------------------------------------------------- */
function log_activity(int $householdId, string $action, string $detail = ''): void
{
    $stmt = db()->prepare(
        'INSERT INTO activity_log (household_id, user_id, action, detail)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$householdId, $_SESSION['user_id'] ?? null, $action, mb_substr($detail, 0, 255)]);
}

/* -------------------------------------------------------------------
 *  Yeni ev için varsayılan kategoriler + otomatik kategori kuralları
 * ----------------------------------------------------------------- */
function seed_default_categories(int $householdId): void
{
    $pdo = db();

    // [ad, tip, renk, ikon]
    $defaults = [
        // Giderler
        ['Market & Gıda',     'expense', '#E0533D', '🛒'],
        ['Kira',              'expense', '#B45309', '🏠'],
        ['Faturalar',         'expense', '#0E7490', '🧾'],
        ['Elektrik',          'expense', '#CA8A04', '⚡'],
        ['Su',                'expense', '#0891B2', '💧'],
        ['Doğalgaz',          'expense', '#EA580C', '🔥'],
        ['İnternet & Telefon','expense', '#7C3AED', '📶'],
        ['Ulaşım & Yakıt',    'expense', '#1D4ED8', '⛽'],
        ['Sağlık',            'expense', '#DC2626', '💊'],
        ['Eğitim',            'expense', '#2563EB', '📚'],
        ['Giyim',             'expense', '#DB2777', '👕'],
        ['Eğlence',           'expense', '#9333EA', '🎬'],
        ['Abonelikler',       'expense', '#0D9488', '🔁'],
        ['Restoran & Kafe',   'expense', '#D97706', '☕'],
        ['Ev Bakımı',         'expense', '#65A30D', '🛠️'],
        ['Diğer Gider',       'expense', '#6B7280', '📦'],
        // Gelirler
        ['Maaş',              'income',  '#16A34A', '💼'],
        ['Ek Gelir',          'income',  '#059669', '➕'],
        ['Kira Geliri',       'income',  '#0D9488', '🏘️'],
        ['Faiz & Yatırım',    'income',  '#15803D', '📈'],
        ['Diğer Gelir',       'income',  '#22C55E', '💰'],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO categories (household_id, name, type, color, icon)
         VALUES (?, ?, ?, ?, ?)'
    );
    $ids = [];
    foreach ($defaults as $c) {
        $stmt->execute([$householdId, $c[0], $c[1], $c[2], $c[3]]);
        $ids[$c[0]] = (int)$pdo->lastInsertId();
    }

    // Otomatik kategori kuralları: PDF içe aktarmada açıklamaya göre eşleştirme
    // [anahtar kelime, kategori adı]
    $rules = [
        ['MIGROS', 'Market & Gıda'], ['CARREFOUR', 'Market & Gıda'], ['A101', 'Market & Gıda'],
        ['BIM', 'Market & Gıda'], ['SOK MARKET', 'Market & Gıda'], ['MACROCENTER', 'Market & Gıda'],
        ['GETIR', 'Market & Gıda'], ['MIGROS SANAL', 'Market & Gıda'],
        ['SHELL', 'Ulaşım & Yakıt'], ['OPET', 'Ulaşım & Yakıt'], ['BP ', 'Ulaşım & Yakıt'],
        ['PETROL OFISI', 'Ulaşım & Yakıt'], ['TOTAL', 'Ulaşım & Yakıt'], ['BENZIN', 'Ulaşım & Yakıt'],
        ['IBB', 'Ulaşım & Yakıt'], ['ISTANBULKART', 'Ulaşım & Yakıt'], ['UBER', 'Ulaşım & Yakıt'],
        ['BITAKSI', 'Ulaşım & Yakıt'], ['OTOYOL', 'Ulaşım & Yakıt'], ['HGS', 'Ulaşım & Yakıt'],
        ['NETFLIX', 'Abonelikler'], ['SPOTIFY', 'Abonelikler'], ['YOUTUBE', 'Abonelikler'],
        ['DISNEY', 'Abonelikler'], ['APPLE.COM', 'Abonelikler'], ['GOOGLE', 'Abonelikler'],
        ['AMAZON PRIME', 'Abonelikler'], ['EXXEN', 'Abonelikler'], ['BLUTV', 'Abonelikler'],
        ['TURKCELL', 'İnternet & Telefon'], ['VODAFONE', 'İnternet & Telefon'],
        ['TURK TELEKOM', 'İnternet & Telefon'], ['TTNET', 'İnternet & Telefon'],
        ['SUPERONLINE', 'İnternet & Telefon'], ['MILLENICOM', 'İnternet & Telefon'],
        ['ELEKTRIK', 'Elektrik'], ['CK ENERJI', 'Elektrik'], ['BEDAS', 'Elektrik'],
        ['AYEDAS', 'Elektrik'], ['ENERJISA', 'Elektrik'],
        ['ISKI', 'Su'], ['ASKI', 'Su'], ['SU FATURA', 'Su'],
        ['IGDAS', 'Doğalgaz'], ['DOGALGAZ', 'Doğalgaz'], ['BASKENTGAZ', 'Doğalgaz'],
        ['ECZANE', 'Sağlık'], ['HASTANE', 'Sağlık'], ['MEDICAL', 'Sağlık'], ['DENT', 'Sağlık'],
        ['STARBUCKS', 'Restoran & Kafe'], ['KAHVE', 'Restoran & Kafe'], ['MCDONALD', 'Restoran & Kafe'],
        ['BURGER', 'Restoran & Kafe'], ['DOMINO', 'Restoran & Kafe'], ['YEMEKSEPETI', 'Restoran & Kafe'],
        ['TRENDYOL', 'Giyim'], ['LC WAIKIKI', 'Giyim'], ['DEFACTO', 'Giyim'], ['ZARA', 'Giyim'],
        ['H&M', 'Giyim'], ['MAVI', 'Giyim'],
        ['MAAS', 'Maaş'], ['ODEME-MAAS', 'Maaş'], ['UCRET', 'Maaş'],
        ['FAIZ', 'Faiz & Yatırım'], ['TEMETTU', 'Faiz & Yatırım'], ['VADELI', 'Faiz & Yatırım'],
    ];

    $rstmt = $pdo->prepare(
        'INSERT INTO import_rules (household_id, match_text, category_id) VALUES (?, ?, ?)'
    );
    foreach ($rules as $r) {
        if (isset($ids[$r[1]])) {
            $rstmt->execute([$householdId, $r[0], $ids[$r[1]]]);
        }
    }
}

/* -------------------------------------------------------------------
 *  Tarih biçimleyici  -  2026-03-01 -> 01.03.2026
 * ----------------------------------------------------------------- */
function format_date(?string $ymd): string
{
    if (!$ymd) {
        return '-';
    }
    $ts = strtotime($ymd);
    return $ts ? date('d.m.Y', $ts) : e($ymd);
}

/* -------------------------------------------------------------------
 *  Türkçe ay adı
 * ----------------------------------------------------------------- */
function tr_month(int $m): string
{
    $months = [1=>'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
               'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    return $months[$m] ?? (string)$m;
}

/* -------------------------------------------------------------------
 *  Tema listesi  -  değiştirilebilir arayüz temaları
 * ----------------------------------------------------------------- */
function theme_list(): array
{
    // key => [görünen ad, örnek renk 1, örnek renk 2]
    return [
        'standart'    => ['Standart (Krem)', '#F6F4EE', '#14452F'],
        'koyu'        => ['Koyu (Dark)',     '#1A1D23', '#3DD68C'],
        'pembe'       => ['Pembe',           '#FFF1F6', '#D6336C'],
        'okyanus'     => ['Okyanus',         '#EEF6FB', '#0B6FB8'],
        'mor'         => ['Mor Gece',        '#1B1430', '#B98AF0'],
        'orman-gece'  => ['Orman Gecesi',    '#0F1A14', '#5BD08A'],
        'kahve'       => ['Kahve',           '#F3EDE6', '#7B4B27'],
    ];
}

function current_theme(): string
{
    $themes = theme_list();
    $u = current_user();
    $t = $u['theme'] ?? 'standart';
    return isset($themes[$t]) ? $t : 'standart';
}

/* -------------------------------------------------------------------
 *  Tarih: 2026-06-05 -> "5 Haziran 2026"
 * ----------------------------------------------------------------- */
function tr_date_long(?string $ymd): string
{
    if (!$ymd) return '-';
    $ts = strtotime($ymd);
    if (!$ts) return e($ymd);
    return (int)date('j', $ts) . ' ' . tr_month((int)date('n', $ts)) . ' ' . date('Y', $ts);
}

/** Bugünden hedef tarihe kalan tam gün (negatif = geçmiş). */
function days_until(?string $ymd): ?int
{
    if (!$ymd) return null;
    $ts = strtotime($ymd);
    if (!$ts) return null;
    $today = strtotime(date('Y-m-d'));
    return (int)floor(($ts - $today) / 86400);
}

/** Tekrarlayan planlı ödemede bir sonraki tarih. */
function next_due_date(string $ymd, string $recurrence): ?string
{
    $ts = strtotime($ymd);
    if (!$ts) return null;
    switch ($recurrence) {
        case 'weekly':  return date('Y-m-d', strtotime('+1 week', $ts));
        case 'monthly': return date('Y-m-d', strtotime('+1 month', $ts));
        case 'yearly':  return date('Y-m-d', strtotime('+1 year', $ts));
        default:        return null; // 'none'
    }
}
