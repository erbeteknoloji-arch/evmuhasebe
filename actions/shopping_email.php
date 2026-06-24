<?php
/**
 * Alışveriş listesini e-posta ile gönder.
 * Biçimlendirilmiş listeyi evin tüm AKTİF üyelerine, mevcut SMTP
 * yapılandırmasını kullanarak gönderir.
 * İçerir: Liste adı, tarih, alınan ürünler, kalan ürünler.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/mailer.php';
require_household();
require_feature('shopping');
verify_csrf();

$householdId = hid();
$pdo = db();
$listId = (int)($_POST['list_id'] ?? 0);

/* Liste bu eve mi ait? */
$ls = $pdo->prepare('SELECT * FROM shopping_lists WHERE id=? AND household_id=? LIMIT 1');
$ls->execute([$listId, $householdId]);
$list = $ls->fetch();
if (!$list) {
    flash('error', 'Liste bulunamadı.');
    redirect('shopping.php');
}

if (!EMAIL_ENABLED) {
    flash('error', 'E-posta gönderimi kapalı (yöneticiyle iletişime geçin).');
    redirect('shopping.php?list=' . $listId);
}

/* Ürünler */
$is = $pdo->prepare('SELECT * FROM shopping_items WHERE list_id=? ORDER BY is_done ASC, position ASC, id ASC');
$is->execute([$listId]);
$rows = $is->fetchAll();

$purchased = array_filter($rows, fn($i) => (int)$i['is_done'] === 1);
$remaining = array_filter($rows, fn($i) => (int)$i['is_done'] !== 1);

/* Alıcılar: evin aktif üyeleri */
$ms = $pdo->prepare(
    'SELECT u.id, u.name, u.email
       FROM household_members hm JOIN users u ON u.id = hm.user_id
      WHERE hm.household_id = ? AND u.is_active = 1'
);
$ms->execute([$householdId]);
$members = $ms->fetchAll();

$recipients = array_filter($members, fn($m) => filter_var($m['email'], FILTER_VALIDATE_EMAIL));
if (!$recipients) {
    flash('error', 'Geçerli e-posta adresi olan aktif üye bulunamadı.');
    redirect('shopping.php?list=' . $listId);
}

/* İçerik (HTML) */
$rowHtml = function (array $items, bool $done): string {
    if (!$items) {
        return '<p style="color:#6B7280;margin:4px 0 14px">— Yok —</p>';
    }
    $out = '<ul style="margin:4px 0 16px;padding-left:18px">';
    foreach ($items as $it) {
        $line = e($it['icon'] . ' ' . $it['name']);
        $meta = [];
        if (!empty($it['qty']))  $meta[] = e($it['qty']);
        if ($it['est_price'] !== null && (float)$it['est_price'] > 0) $meta[] = money($it['est_price']);
        if ($meta) $line .= ' <span style="color:#6B7280">(' . implode(' · ', $meta) . ')</span>';
        $style = $done ? 'text-decoration:line-through;color:#9CA3AF' : '';
        $out .= '<li style="margin:3px 0;' . $style . '">' . $line . '</li>';
    }
    return $out . '</ul>';
};

$total = count($rows);
$doneCount = count($purchased);
$pct = $total > 0 ? round($doneCount / $total * 100) : 0;
$allDone = ($total > 0 && $doneCount === $total);

$content = '<p><b>Liste:</b> ' . e($list['icon'] . ' ' . $list['name']) . '<br>'
    . '<b>Tarih:</b> ' . tr_date_long(date('Y-m-d')) . '<br>'
    . '<b>Durum:</b> ' . $doneCount . ' / ' . $total . ' alındı (%' . $pct . ')'
    . ($allDone ? ' · ✅ Tamamlandı' : '') . '</p>'
    . '<h3 style="margin:16px 0 4px">🛒 Alınan Ürünler (' . $doneCount . ')</h3>'
    . $rowHtml($purchased, true)
    . '<h3 style="margin:16px 0 4px">🧺 Kalan Ürünler (' . (count($remaining)) . ')</h3>'
    . $rowHtml($remaining, false);

$subject = APP_NAME . ' · Alışveriş Listesi: ' . $list['name'];
$html = email_layout('Alışveriş Listesi', $content, 'Listeyi Aç', absolute_url('shopping.php?list=' . $listId));

/* Gönder */
$sent = 0; $failed = 0;
foreach ($recipients as $m) {
    $err = null;
    if (send_app_mail($m['email'], $subject, $html, $m['name'], $err)) {
        $sent++;
    } else {
        $failed++;
        error_log('[shopping_email] ' . $m['email'] . ' · ' . (string)$err);
    }
}

log_activity($householdId, 'shopping_email', $list['name'] . ' · ' . $sent . ' alıcı');

if ($sent > 0) {
    flash('success', "Liste e-posta ile gönderildi ($sent alıcı" . ($failed ? ", $failed başarısız" : '') . ').');
} else {
    flash('error', 'E-posta gönderilemedi. SMTP ayarlarını kontrol edin.');
}
redirect('shopping.php?list=' . $listId);
