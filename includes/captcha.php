<?php
/**
 * Bot korumalı doğrulama (captcha).
 * GD eklentisi varsa resim captcha; yoksa basit matematik sorusu.
 * Admin panelinden açılıp kapatılabilir (captcha_enabled).
 * Ayrıca formlarda gizli "honeypot" alanı ile basit bot tuzağı kullanılır.
 */

require_once __DIR__ . '/settings.php';

function captcha_enabled(): bool
{
    return site_setting('captcha_enabled', '1') === '1';
}

function captcha_gd_available(): bool
{
    return function_exists('imagecreatetruecolor') && extension_loaded('gd');
}

/** Formda gösterilecek captcha HTML'i (gizli honeypot dahil). */
function captcha_field_html(): string
{
    if (!captcha_enabled()) {
        // Captcha kapalı olsa bile honeypot tuzağını koy
        return honeypot_html();
    }
    if (captcha_gd_available()) {
        $src = url('captcha.php') . '?r=' . substr(bin2hex(random_bytes(4)), 0, 8);
        $html  = '<div class="field"><label>Güvenlik Doğrulaması</label>';
        $html .= '<div class="captcha-row">';
        $html .= '<img src="' . e($src) . '" alt="captcha" class="captcha-img" id="capImg">';
        $html .= '<button type="button" class="btn btn-ghost btn-sm" onclick="refreshCaptcha()" title="Yenile">↻</button>';
        $html .= '</div>';
        $html .= '<input class="input" type="text" name="captcha" autocomplete="off" inputmode="text" placeholder="Yukarıdaki kodu girin" required>';
        $html .= '</div>';
        return $html . honeypot_html();
    }
    // Matematik yedeği
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['captcha_mode'] = 'math';
    $_SESSION['captcha_answer'] = (string)($a + $b);
    $html  = '<div class="field"><label>Güvenlik Sorusu</label>';
    $html .= '<div class="muted" style="font-size:13.5px;margin-bottom:6px">Lütfen şu işlemi çözün: <b>' . $a . ' + ' . $b . ' = ?</b></div>';
    $html .= '<input class="input" type="text" name="captcha" inputmode="numeric" autocomplete="off" placeholder="Sonucu yazın" required>';
    $html .= '</div>';
    return $html . honeypot_html();
}

/** Gizli honeypot alanı (botlar doldurur, insanlar göremez). */
function honeypot_html(): string
{
    return '<div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">'
         . '<label>Bu alanı boş bırakın</label>'
         . '<input type="text" name="website" tabindex="-1" autocomplete="off"></div>';
}

/**
 * Captcha + honeypot doğrulaması.
 * @return bool geçerli mi
 */
function captcha_verify(): bool
{
    // Honeypot doluysa bot kabul et
    if (!empty($_POST['website'])) {
        return false;
    }
    if (!captcha_enabled()) {
        return true;
    }
    $input = trim($_POST['captcha'] ?? '');
    $mode  = $_SESSION['captcha_mode'] ?? '';
    $ok = false;
    if ($mode === 'math') {
        $ok = ($input !== '' && $input === ($_SESSION['captcha_answer'] ?? null));
    } elseif ($mode === 'image') {
        $ok = ($input !== '' && isset($_SESSION['captcha_code'])
            && strtolower($input) === strtolower($_SESSION['captcha_code']));
    }
    // Tek kullanımlık: doğrulama sonrası temizle
    unset($_SESSION['captcha_code'], $_SESSION['captcha_answer'], $_SESSION['captcha_mode']);
    return $ok;
}
