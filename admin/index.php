<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();
$stat = function (string $sql) use ($pdo): int {
    try { return (int)$pdo->query($sql)->fetchColumn(); }
    catch (Throwable $e) { return 0; }
};
$sum = function (string $sql) use ($pdo): float {
    try { return (float)$pdo->query($sql)->fetchColumn(); }
    catch (Throwable $e) { return 0.0; }
};
$tl = function ($n): string { return number_format((float)$n, 2, ',', '.') . ' ₺'; };

$users        = $stat('SELECT COUNT(*) FROM users');
$activeUsers  = $stat('SELECT COUNT(*) FROM users WHERE is_active=1');
$admins       = $stat('SELECT COUNT(*) FROM users WHERE is_admin=1');
$householdCount = $stat('SELECT COUNT(*) FROM households');
$txns         = $stat('SELECT COUNT(*) FROM transactions');
$accounts     = $stat('SELECT COUNT(*) FROM accounts');
$openTickets  = $stat("SELECT COUNT(*) FROM tickets WHERE status<>'closed'");
$chatMsgs     = $stat('SELECT COUNT(*) FROM chat_messages');
$dms          = $stat('SELECT COUNT(*) FROM direct_messages');

$totIncome  = $sum("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'");
$totExpense = $sum("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense'");
$volume     = $totIncome + $totExpense;

// Son 7 günde yeni kayıt
$newUsers7  = $stat("SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 7 DAY)");
$newTx7     = $stat("SELECT COUNT(*) FROM transactions WHERE created_at >= (NOW() - INTERVAL 7 DAY)");

$recentUsers = $pdo->query('SELECT id,name,username,email,is_active,is_admin,created_at,last_login_at FROM users ORDER BY id DESC LIMIT 8')->fetchAll();

// Genel etkinlik akışı (tüm haneler)
$activity = [];
try {
    $activity = $pdo->query(
        "SELECT al.action, al.detail, al.created_at, u.name AS uname, h.name AS hname
           FROM activity_log al
           LEFT JOIN users u ON u.id = al.user_id
           LEFT JOIN households h ON h.id = al.household_id
       ORDER BY al.created_at DESC LIMIT 12"
    )->fetchAll();
} catch (Throwable $e) { $activity = []; }

$actionLabels = [
    'transaction_create'=>'işlem ekledi','transaction_update'=>'işlem güncelledi','transaction_delete'=>'işlem sildi',
    'import_commit'=>'PDF içe aktardı','household_create'=>'ev oluşturdu','household_update'=>'ev güncelledi',
    'member_join'=>'eve katıldı','member_invite'=>'davet gönderdi','member_remove'=>'üye çıkardı',
    'member_leave'=>'evden ayrıldı','member_role'=>'rol değiştirdi','goal_create'=>'hedef oluşturdu',
    'goal_contribute'=>'birikim ekledi','goal_delete'=>'hedef sildi','scheduled_create'=>'planlı ödeme ekledi',
    'scheduled_pay'=>'ödeme işledi','scheduled_delete'=>'planlı ödeme sildi','account_create'=>'hesap ekledi',
    'account_update'=>'hesap güncelledi','cc_statement'=>'kart ekstresi oluştu','shopping_email'=>'liste e-postaladı',
];

$maintenance  = site_setting('maintenance_mode', '0') === '1';
$registration = site_setting('registration_enabled', '1') === '1';
$captcha      = site_setting('captcha_enabled', '1') === '1';
$emailOn      = defined('EMAIL_ENABLED') && EMAIL_ENABLED;

$page_title = 'Yönetim Paneli';
$page_subtitle = 'Site geneli özet ve yönetim';
$active = 'admin';
require __DIR__ . '/../templates/header.php';
?>
<div class="flex" style="gap:8px;margin:6px 0 14px;flex-wrap:wrap">
    <a class="btn btn-primary btn-sm" href="<?= url('admin/users.php') ?>">👤 Kullanıcılar</a>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/households.php') ?>">🏠 Haneler</a>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/settings.php') ?>">⚙️ Site Ayarları</a>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/tickets.php') ?>">🎫 Destek Talepleri</a>
</div>

<div class="grid grid-4">
    <div class="stat balance"><div class="label">Toplam Kullanıcı</div><div class="value"><?= $users ?></div><div class="meta"><?= $activeUsers ?> aktif · <?= $admins ?> yönetici · +<?= $newUsers7 ?> (7g)</div></div>
    <a class="stat income" href="<?= url('admin/households.php') ?>" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="label">Toplam Hane ↗</div><div class="value"><?= $householdCount ?></div><div class="meta">Listelemek için tıklayın</div>
    </a>
    <div class="stat balance"><div class="label">Toplam İşlem</div><div class="value"><?= number_format($txns,0,',','.') ?></div><div class="meta">+<?= $newTx7 ?> (7g) · <?= $accounts ?> hesap</div></div>
    <div class="stat expense"><div class="label">Açık Talep</div><div class="value"><?= $openTickets ?></div></div>
</div>

<div class="grid grid-4 mt-2">
    <div class="stat income"><div class="label">Toplam Gelir (kayıtlı)</div><div class="value" style="font-size:20px"><?= $tl($totIncome) ?></div></div>
    <div class="stat expense"><div class="label">Toplam Gider (kayıtlı)</div><div class="value" style="font-size:20px"><?= $tl($totExpense) ?></div></div>
    <div class="stat balance"><div class="label">İşlem Hacmi</div><div class="value" style="font-size:20px"><?= $tl($volume) ?></div></div>
    <div class="stat"><div class="label">Mesajlar</div><div class="value"><?= number_format($chatMsgs + $dms,0,',','.') ?></div><div class="meta"><?= $chatMsgs ?> topluluk · <?= $dms ?> özel</div></div>
</div>

<!-- Sistem durumu -->
<div class="card mt-2">
    <div class="card-head"><h3>Sistem Durumu</h3><a class="btn btn-ghost btn-sm" href="<?= url('admin/settings.php') ?>">Ayarlar →</a></div>
    <div class="card-pad">
        <div class="grid grid-4">
            <div><div class="muted" style="font-size:12px">Bakım Modu</div><b style="color:<?= $maintenance?'var(--expense)':'var(--income)' ?>"><?= $maintenance?'● Açık':'● Kapalı' ?></b></div>
            <div><div class="muted" style="font-size:12px">Yeni Kayıtlar</div><b style="color:<?= $registration?'var(--income)':'var(--expense)' ?>"><?= $registration?'● Açık':'● Kapalı' ?></b></div>
            <div><div class="muted" style="font-size:12px">Captcha</div><b><?= $captcha?'● Açık':'● Kapalı' ?></b></div>
            <div><div class="muted" style="font-size:12px">E-posta (SMTP)</div><b style="color:<?= $emailOn?'var(--income)':'var(--expense)' ?>"><?= $emailOn?'● Açık':'● Kapalı' ?></b></div>
        </div>
    </div>
</div>

<div class="grid cols-2-1 mt-2">
    <!-- Son kayıtlar -->
    <div class="card">
        <div class="card-head"><h3>Son Kayıtlar</h3><a class="btn btn-ghost btn-sm" href="<?= url('admin/users.php') ?>">Tümü →</a></div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Kullanıcı</th><th>E-posta</th><th>Durum</th><th>Kayıt</th><th>Son Giriş</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsers as $ru): ?>
                    <tr>
                        <td><b><?= e($ru['name']) ?></b> <span class="muted">@<?= e($ru['username']) ?></span>
                            <?= $ru['is_admin'] ? '<span class="pill" style="background:var(--gold-soft);color:var(--gold)">admin</span>' : '' ?></td>
                        <td><?= e($ru['email']) ?></td>
                        <td><?= $ru['is_active'] ? '<span style="color:var(--income)">Aktif</span>' : '<span style="color:var(--expense)">Pasif</span>' ?></td>
                        <td><?= format_date($ru['created_at']) ?></td>
                        <td><?= $ru['last_login_at'] ? format_date($ru['last_login_at']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Genel etkinlik -->
    <div class="card card-pad">
        <h3>Son Etkinlikler (tüm haneler)</h3>
        <?php if (!$activity): ?>
            <p class="muted">Henüz etkinlik yok.</p>
        <?php else: ?>
        <div class="list" style="margin-top:6px">
            <?php foreach ($activity as $a): ?>
            <div class="item">
                <div class="av" style="background:var(--cream-2);color:var(--ink-soft);font-size:14px">🕘</div>
                <div class="grow" style="font-size:13px">
                    <b><?= e($a['uname'] ?: 'Birisi') ?></b> <?= e($actionLabels[$a['action']] ?? $a['action']) ?>
                    <?php if (!empty($a['hname'])): ?><span class="muted"> · <?= e($a['hname']) ?></span><?php endif; ?>
                    <?php if ($a['detail']): ?><div class="muted" style="font-size:12px"><?= e($a['detail']) ?></div><?php endif; ?>
                </div>
                <span class="muted" style="font-size:11.5px;white-space:nowrap"><?= format_date($a['created_at']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php';
