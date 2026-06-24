<?php
/**
 * =====================================================================
 *  Mailer  -  Saf PHP SMTP istemcisi (PHPMailer gerektirmez)
 * =====================================================================
 *  config/config.php içindeki SMTP_* sabitlerini kullanır.
 *  SSL (465) ve STARTTLS (587) destekler, AUTH LOGIN ile kimlik doğrular.
 *  Paylaşımlı hosting'lerde de çalışacak şekilde tasarlanmıştır.
 * =====================================================================
 */

require_once __DIR__ . '/functions.php';

/**
 * Uygulama içinden e-posta gönderir. EMAIL_ENABLED kapalıysa sessizce false döner.
 *
 * @param string      $to        alıcı e-posta
 * @param string      $subject   konu
 * @param string      $htmlBody  HTML gövde
 * @param string|null $toName    alıcı adı (ops.)
 * @param string|null $error     hata mesajı (referans)
 * @return bool gönderim başarılı mı
 */
function send_app_mail(string $to, string $subject, string $htmlBody, ?string $toName = null, ?string &$error = null): bool
{
    if (!EMAIL_ENABLED) {
        $error = 'E-posta gönderimi kapalı (config.php → EMAIL_ENABLED).';
        return false;
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz alıcı e-posta adresi.';
        return false;
    }
    try {
        return smtp_send($to, $toName ?? '', $subject, $htmlBody, $error);
    } catch (Throwable $e) {
        $error = $e->getMessage();
        error_log('[EvMuhasebe Mailer] ' . $e->getMessage());
        return false;
    }
}

/**
 * Ham SMTP gönderimi.
 */
function smtp_send(string $to, string $toName, string $subject, string $htmlBody, ?string &$error = null): bool
{
    $host   = SMTP_HOST;
    $port   = SMTP_PORT;
    $secure = strtolower(SMTP_SECURE);
    $user   = SMTP_USER;
    $pass   = SMTP_PASS;
    $from   = MAIL_FROM;
    $fromNm = MAIL_FROM_NAME;
    $timeout = 15;

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $ctx = stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true],
    ]);
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        $error = "SMTP sunucusuna bağlanılamadı ($host:$port): $errstr";
        return false;
    }
    stream_set_timeout($fp, $timeout);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // Çok satırlı yanıtta 4. karakter '-' ise devam ediyor demektir
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $c) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        return $read();
    };
    $expect = function (string $resp, string $codes) use (&$error): bool {
        $code = substr(trim($resp), 0, 3);
        if (strpos($codes, $code) === false) {
            $error = 'SMTP beklenmeyen yanıt: ' . trim($resp);
            return false;
        }
        return true;
    };

    $greet = $read();
    if (!$expect($greet, '220')) { fclose($fp); return false; }

    $ehloHost = parse_url(APP_URL ?: 'http://localhost', PHP_URL_HOST) ?: 'localhost';
    if (!$expect($cmd('EHLO ' . $ehloHost), '250')) {
        // bazı sunucular HELO ister
        if (!$expect($cmd('HELO ' . $ehloHost), '250')) { fclose($fp); return false; }
    }

    if ($secure === 'tls') {
        if (!$expect($cmd('STARTTLS'), '220')) { fclose($fp); return false; }
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT
                | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            $error = 'TLS şifrelemesi başlatılamadı.';
            fclose($fp);
            return false;
        }
        $cmd('EHLO ' . $ehloHost);
    }

    if ($user !== '') {
        if (!$expect($cmd('AUTH LOGIN'), '334')) { fclose($fp); return false; }
        if (!$expect($cmd(base64_encode($user)), '334')) { fclose($fp); return false; }
        if (!$expect($cmd(base64_encode($pass)), '235')) { fclose($fp); return false; }
    }

    if (!$expect($cmd('MAIL FROM:<' . $from . '>'), '250')) { fclose($fp); return false; }
    if (!$expect($cmd('RCPT TO:<' . $to . '>'), '250251')) { fclose($fp); return false; }
    if (!$expect($cmd('DATA'), '354')) { fclose($fp); return false; }

    $boundary = 'evm_' . bin2hex(random_bytes(8));
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromHeader = '=?UTF-8?B?' . base64_encode($fromNm) . '?= <' . $from . '>';
    $toHeader   = ($toName !== '' ? '=?UTF-8?B?' . base64_encode($toName) . '?= ' : '') . '<' . $to . '>';

    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($htmlBody)));

    $headers = [
        'Date: ' . date('r'),
        'From: ' . $fromHeader,
        'To: ' . $toHeader,
        'Subject: ' . $encSubject,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    $body  = '';
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($plain)) . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $body .= '--' . $boundary . "--\r\n";

    // Nokta ile başlayan satırları kaçır (SMTP veri sonu kuralı)
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $data = preg_replace('/^\./m', '..', $data);

    fwrite($fp, $data . "\r\n.\r\n");
    $final = $read();
    $cmd('QUIT');
    fclose($fp);

    return $expect($final, '250');
}

/**
 * Standart HTML e-posta şablonu (marka başlığı + içerik + alt bilgi).
 */
function email_layout(string $heading, string $contentHtml, ?string $ctaText = null, ?string $ctaUrl = null): string
{
    $appName = e(APP_NAME);
    $btn = '';
    if ($ctaText && $ctaUrl) {
        $btn = '<tr><td style="padding:8px 0 4px"><a href="' . e($ctaUrl) . '" '
             . 'style="display:inline-block;background:#14452F;color:#ffffff;text-decoration:none;'
             . 'padding:12px 22px;border-radius:10px;font-weight:600;font-family:Arial,sans-serif">'
             . e($ctaText) . '</a></td></tr>';
    }
    return '<!doctype html><html><body style="margin:0;background:#F6F4EE;padding:24px;font-family:Arial,Helvetica,sans-serif;color:#1C1F26">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
        . '<table role="presentation" width="540" cellpadding="0" cellspacing="0" style="max-width:540px;width:100%;background:#ffffff;border:1px solid #E4DFD2;border-radius:16px;overflow:hidden">'
        . '<tr><td style="background:#14452F;padding:18px 28px;color:#fff;font-size:18px;font-weight:700">₺ ' . $appName . '</td></tr>'
        . '<tr><td style="padding:28px">'
        . '<h2 style="margin:0 0 12px;font-size:19px;color:#14452F">' . e($heading) . '</h2>'
        . '<div style="font-size:14.5px;line-height:1.6;color:#4A4F5A">' . $contentHtml . '</div>'
        . '<table role="presentation" cellpadding="0" cellspacing="0">' . $btn . '</table>'
        . '</td></tr>'
        . '<tr><td style="padding:16px 28px;border-top:1px solid #EDE9DE;font-size:12px;color:#8A8F99">'
        . 'Bu e-posta ' . $appName . ' tarafından otomatik gönderilmiştir. Bildirimleri Ayarlar sayfasından kapatabilirsiniz.'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/**
 * Site kök adresini döndürür (e-posta bağlantıları için mutlak URL).
 */
function app_base_url(): string
{
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_path();
}

function absolute_url(string $path): string
{
    return app_base_url() . '/' . ltrim($path, '/');
}
