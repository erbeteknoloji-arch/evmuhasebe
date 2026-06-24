<?php
/**
 * Bildirim yardımcıları.
 * Bir evde olan gelişmeleri (işlem, içe aktarma, yaklaşan ödeme, hedef)
 * ilgili bildirimi açık olan ev üyelerine e-posta ile iletir.
 * Gönderim "en iyi çaba" ilkesiyle yapılır; hata olsa bile akışı bozmaz.
 */

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/webpush.php';

/**
 * @param int    $householdId  ev
 * @param string $pref         hangi tercih sütunu: transactions|imports|upcoming|goals
 * @param string $subject      e-posta konusu
 * @param string $heading      e-posta başlığı
 * @param string $contentHtml  HTML içerik
 * @param int|null $excludeUserId  bu kullanıcıya gönderme (işlemi yapan kişi)
 */
function notify_household(int $householdId, string $pref, string $subject, string $heading,
                          string $contentHtml, ?int $excludeUserId = null,
                          ?string $ctaText = null, ?string $ctaUrl = null): void
{
    if ($householdId <= 0) {
        return;
    }
    $allowed = ['transactions', 'imports', 'upcoming', 'goals'];
    if (!in_array($pref, $allowed, true)) {
        return;
    }

    /* ---- Web Push (e-postadan bağımsız; kapalı olsa bile gönderilir) ---- */
    try {
        if (webpush_supported()) {
            $pushBody = trim(preg_replace('/\s+/', ' ', strip_tags($contentHtml)));
            if (mb_strlen($pushBody) > 160) $pushBody = mb_substr($pushBody, 0, 157) . '…';
            push_household($householdId, $heading, $pushBody, $ctaUrl, $excludeUserId);
        }
    } catch (Throwable $e) { /* push hatası akışı bozmaz */ }

    /* ---- E-posta ---- */
    if (!EMAIL_ENABLED) {
        return;
    }
    $col = 'notify_' . $pref;

    try {
        $stmt = db()->prepare(
            "SELECT u.id, u.name, u.email, u.notify_email, u.$col AS pref_on
               FROM users u
               JOIN household_members hm ON hm.user_id = u.id
              WHERE hm.household_id = ?"
        );
        $stmt->execute([$householdId]);
        $members = $stmt->fetchAll();
    } catch (Throwable $e) {
        return;
    }

    $html = email_layout($heading, $contentHtml, $ctaText, $ctaUrl);
    foreach ($members as $m) {
        if ((int)$m['id'] === (int)$excludeUserId) continue;
        if ((int)$m['notify_email'] !== 1) continue;
        if ((int)$m['pref_on'] !== 1) continue;
        if (!filter_var($m['email'], FILTER_VALIDATE_EMAIL)) continue;
        @send_app_mail($m['email'], $subject, $html, $m['name']);
    }
}
