<?php
/**
 * =====================================================================
 *  Çoklu dil (i18n) altyapısı
 * =====================================================================
 *  Diller: tr (varsayılan), en, ar (RTL), de.
 *  Dil seçimi önceliği: oturum > kullanıcı tercihi > çerez > varsayılan.
 *  Eksik anahtarlar otomatik Türkçe'ye düşer.
 *  Kullanım:  echo t('nav.dashboard');   t('greeting', ['name'=>$ad]);
 * =====================================================================
 */

require_once __DIR__ . '/db.php';

/** Desteklenen diller: kod => [ad, bayrak, yön]. */
function available_languages(): array
{
    return [
        'tr' => ['Türkçe',   '🇹🇷', 'ltr'],
        'en' => ['English',  '🇬🇧', 'ltr'],
        'ar' => ['العربية',  '🇸🇦', 'rtl'],
        'de' => ['Deutsch',  '🇩🇪', 'ltr'],
    ];
}

/** Aktif dil kodu. */
function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $avail = available_languages();
    $l = $_SESSION['lang'] ?? null;

    if (!$l && function_exists('current_user')) {
        $u = current_user();
        if ($u && !empty($u['lang'])) {
            $l = $u['lang'];
        }
    }
    if (!$l && !empty($_COOKIE['lang'])) {
        $l = $_COOKIE['lang'];
    }
    if (!$l || !isset($avail[$l])) {
        $l = 'tr';
    }
    $lang = $l;
    return $lang;
}

/** Aktif dili belirle (oturum + çerez + kullanıcıya kaydet). */
function set_lang(string $l): bool
{
    $avail = available_languages();
    if (!isset($avail[$l])) {
        return false;
    }
    $_SESSION['lang'] = $l;
    @setcookie('lang', $l, time() + 31536000, '/');

    if (function_exists('current_user')) {
        $u = current_user();
        if ($u) {
            try {
                db()->prepare('UPDATE users SET lang = ? WHERE id = ?')->execute([$l, $u['id']]);
            } catch (Throwable $e) { /* lang sütunu yoksa yok say */ }
        }
    }
    return true;
}

/** Aktif dilin yazım yönü: ltr | rtl. */
function lang_dir(): string
{
    $a = available_languages();
    return $a[current_lang()][2] ?? 'ltr';
}

function is_rtl(): bool
{
    return lang_dir() === 'rtl';
}

/** Aktif dilin çeviri dizisini (TR'ye düşerek) yükler. */
function load_translations(): array
{
    static $cache = [];
    $l = current_lang();
    if (isset($cache[$l])) {
        return $cache[$l];
    }
    $base = [];
    $trFile = __DIR__ . '/../lang/tr.php';
    if (is_file($trFile)) {
        $base = require $trFile;
    }
    if ($l !== 'tr') {
        $f = __DIR__ . '/../lang/' . $l . '.php';
        if (is_file($f)) {
            $over = require $f;
            if (is_array($over)) {
                $base = array_merge($base, $over);
            }
        }
    }
    $cache[$l] = $base;
    return $base;
}

/** Çeviri getir. {param} yer tutucuları değiştirilir. */
function t(string $key, array $params = []): string
{
    $all = load_translations();
    $s = $all[$key] ?? $key;
    if ($params) {
        foreach ($params as $k => $v) {
            $s = str_replace('{' . $k . '}', (string)$v, $s);
        }
    }
    return $s;
}

/** Çeviriyi güvenli (HTML kaçışlı) bas. */
function te(string $key, array $params = []): string
{
    return htmlspecialchars(t($key, $params), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
