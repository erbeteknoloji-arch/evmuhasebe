<?php
/**
 * =====================================================================
 *  Web Push gönderici (kütüphanesiz, saf PHP)
 * =====================================================================
 *  - VAPID (RFC 8292) ES256 JWT ile kimlik doğrulama,
 *  - Yük şifreleme: aes128gcm (RFC 8188) + ECDH/HKDF (RFC 8291),
 *  - Gönderim: cURL ile push servisine POST.
 *
 *  Gereksinimler: PHP openssl (openssl_pkey_derive ≥ PHP 7.3),
 *  hash_hkdf, ext-curl. Paylaşımlı hostinglerin çoğunda mevcuttur.
 * =====================================================================
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
if (is_file(__DIR__ . '/../config/vapid.php')) {
    require_once __DIR__ . '/../config/vapid.php';
}

/** Web Push'un bu sunucuda çalışıp çalışamayacağını kontrol eder. */
function webpush_supported(): bool
{
    return defined('VAPID_PRIVATE_PEM')
        && defined('VAPID_PUBLIC_KEY')
        && function_exists('openssl_pkey_derive')
        && function_exists('hash_hkdf')
        && function_exists('curl_init');
}

/* ---------- base64url yardımcıları ---------- */
function b64u_encode(string $b): string
{
    return rtrim(strtr(base64_encode($b), '+/', '-_'), '=');
}
function b64u_decode(string $s): string
{
    $s = strtr($s, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad) $s .= str_repeat('=', 4 - $pad);
    return base64_decode($s);
}

/** Ham P-256 noktasından (65 bayt, 0x04||X||Y) PEM genel anahtar üretir. */
function p256_pem_from_raw(string $raw65): string
{
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw65;
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

/** DER ECDSA imzasını ham r||s (64 bayt) biçimine çevirir. */
function ecdsa_der_to_raw(string $der): string
{
    $off = 0;
    if (($der[$off++] ?? '') !== "\x30") return '';
    $seqLen = ord($der[$off++]);
    if ($seqLen & 0x80) { $off += ($seqLen & 0x7f); }
    if (($der[$off++] ?? '') !== "\x02") return '';
    $rlen = ord($der[$off++]); $r = substr($der, $off, $rlen); $off += $rlen;
    if (($der[$off++] ?? '') !== "\x02") return '';
    $slen = ord($der[$off++]); $s = substr($der, $off, $slen); $off += $slen;
    $r = ltrim($r, "\x00"); $s = ltrim($s, "\x00");
    $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);
    return $r . $s;
}

/** Endpoint'ten kaynağı (scheme://host) çıkarır (VAPID aud için). */
function push_origin(string $endpoint): string
{
    $p = parse_url($endpoint);
    if (!$p || empty($p['scheme']) || empty($p['host'])) return '';
    $o = $p['scheme'] . '://' . $p['host'];
    if (!empty($p['port'])) $o .= ':' . $p['port'];
    return $o;
}

/** Verilen kaynak için VAPID Authorization başlığını üretir. */
function vapid_authorization(string $origin): ?string
{
    $header = b64u_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims = b64u_encode(json_encode([
        'aud' => $origin,
        'exp' => time() + 12 * 3600,
        'sub' => VAPID_SUBJECT,
    ]));
    $input = $header . '.' . $claims;

    $pk = openssl_pkey_get_private(VAPID_PRIVATE_PEM);
    if (!$pk) return null;
    $der = '';
    if (!openssl_sign($input, $der, $pk, OPENSSL_ALGO_SHA256)) return null;
    $raw = ecdsa_der_to_raw($der);
    if (strlen($raw) !== 64) return null;
    $jwt = $input . '.' . b64u_encode($raw);
    return 'vapid t=' . $jwt . ', k=' . VAPID_PUBLIC_KEY;
}

/**
 * Yükü bir abonelik için aes128gcm ile şifreler.
 * @return string|null şifreli gövde (binary) ya da null
 */
function webpush_encrypt(string $payload, string $p256dhB64, string $authB64): ?string
{
    $uaPublic = b64u_decode($p256dhB64);        // 65 bayt (0x04||X||Y)
    $authSecret = b64u_decode($authB64);        // 16 bayt
    if (strlen($uaPublic) !== 65 || strlen($authSecret) < 16) return null;

    $clientPub = openssl_pkey_get_public(p256_pem_from_raw($uaPublic));
    if (!$clientPub) return null;

    $eph = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if (!$eph) return null;

    $secret = openssl_pkey_derive($clientPub, $eph, 32);
    if ($secret === false || strlen($secret) === 0) return null;

    $d = openssl_pkey_get_details($eph);
    $asPublic = "\x04"
        . str_pad($d['ec']['x'], 32, "\x00", STR_PAD_LEFT)
        . str_pad($d['ec']['y'], 32, "\x00", STR_PAD_LEFT);

    $salt = random_bytes(16);

    // RFC 8291: IKM
    $keyInfo = "WebPush: info\x00" . $uaPublic . $asPublic;
    $ikm = hash_hkdf('sha256', $secret, 32, $keyInfo, $authSecret);

    // RFC 8188: CEK + NONCE
    $cek   = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

    $plain = $payload . "\x02"; // tek kayıt, son kayıt sınırlayıcısı
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($cipher === false) return null;

    // aes128gcm gövde başlığı: salt(16) | rs(4) | idlen(1) | keyid | ct
    return $salt . pack('N', 4096) . chr(65) . $asPublic . $cipher . $tag;
}

/**
 * Tek bir aboneliğe bildirim gönderir.
 * @return int HTTP durum kodu (0 = bağlantı hatası)
 */
function webpush_send_one(array $sub, string $payloadJson): int
{
    if (!webpush_supported()) return 0;
    $origin = push_origin($sub['endpoint']);
    if ($origin === '') return 0;

    $auth = vapid_authorization($origin);
    if (!$auth) return 0;

    $body = webpush_encrypt($payloadJson, $sub['p256dh'], $sub['auth']);
    if ($body === null) return 0;

    $ch = curl_init($sub['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $auth,
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
            'Urgency: normal',
        ],
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

/** Bir kullanıcının tüm cihazlarına gönderir; ölü abonelikleri siler. */
function push_to_user(int $userId, array $payload): int
{
    if (!webpush_supported() || $userId <= 0) return 0;
    try {
        $stmt = db()->prepare('SELECT * FROM push_subscriptions WHERE user_id = ?');
        $stmt->execute([$userId]);
        $subs = $stmt->fetchAll();
    } catch (Throwable $e) { return 0; }

    $sent = 0;
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    foreach ($subs as $sub) {
        $code = webpush_send_one($sub, $json);
        if ($code === 201 || $code === 200 || $code === 202) {
            $sent++;
            try { db()->prepare('UPDATE push_subscriptions SET last_used_at=NOW() WHERE id=?')->execute([$sub['id']]); } catch (Throwable $e) {}
        } elseif ($code === 404 || $code === 410) {
            // Abonelik geçersiz → temizle
            try { db()->prepare('DELETE FROM push_subscriptions WHERE id=?')->execute([$sub['id']]); } catch (Throwable $e) {}
        }
    }
    return $sent;
}

/**
 * Bir evin üyelerine (push tercihi açık olanlara) bildirim gönderir.
 * @param int|null $excludeUserId işlemi yapan kişiye gönderme
 */
function push_household(int $householdId, string $title, string $body, ?string $url = null, ?int $excludeUserId = null): int
{
    if (!webpush_supported() || $householdId <= 0) return 0;
    try {
        $stmt = db()->prepare(
            "SELECT ps.* FROM push_subscriptions ps
               JOIN household_members hm ON hm.user_id = ps.user_id
               JOIN users u ON u.id = ps.user_id
              WHERE hm.household_id = ?
                AND (u.notify_push IS NULL OR u.notify_push = 1)"
        );
        $stmt->execute([$householdId]);
        $subs = $stmt->fetchAll();
    } catch (Throwable $e) { return 0; }

    $payload = json_encode([
        'title' => $title,
        'body'  => $body,
        'url'   => $url ?: '',
    ], JSON_UNESCAPED_UNICODE);

    $sent = 0;
    foreach ($subs as $sub) {
        if ($excludeUserId !== null && (int)$sub['user_id'] === (int)$excludeUserId) continue;
        $code = webpush_send_one($sub, $payload);
        if ($code === 201 || $code === 200 || $code === 202) {
            $sent++;
        } elseif ($code === 404 || $code === 410) {
            try { db()->prepare('DELETE FROM push_subscriptions WHERE id=?')->execute([$sub['id']]); } catch (Throwable $e) {}
        }
    }
    return $sent;
}
