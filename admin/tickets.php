<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();
$filter = in_array($_GET['s'] ?? '', ['open','answered','closed'], true) ? $_GET['s'] : 'all';
$where = $filter === 'all' ? '' : "WHERE t.status = " . $pdo->quote($filter);

$tickets = $pdo->query(
    "SELECT t.*, u.name AS uname, u.email AS uemail,
            (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id=t.id) AS msg_count
       FROM tickets t JOIN users u ON u.id=t.user_id
       $where ORDER BY t.updated_at DESC"
)->fetchAll();

$counts = [];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM tickets GROUP BY status")->fetchAll() as $r) $counts[$r['status']] = (int)$r['c'];
$statusLabel = ['open' => ['Açık', 'var(--info)'], 'answered' => ['Yanıtlandı', 'var(--income)'], 'closed' => ['Kapalı', 'var(--ink-faint)']];

$page_title = 'Destek Talepleri (Yönetim)';
$page_subtitle = count($tickets) . ' talep';
$active = 'admin';
require __DIR__ . '/../templates/header.php';
?>
<div class="flex" style="gap:8px;margin:6px 0 14px;flex-wrap:wrap">
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/index.php') ?>">← Panele dön</a>
    <div style="margin-left:auto" class="flex">
        <a class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/tickets.php') ?>">Tümü</a>
        <a class="btn btn-sm <?= $filter==='open'?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/tickets.php?s=open') ?>">Açık (<?= $counts['open'] ?? 0 ?>)</a>
        <a class="btn btn-sm <?= $filter==='answered'?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/tickets.php?s=answered') ?>">Yanıtlandı (<?= $counts['answered'] ?? 0 ?>)</a>
        <a class="btn btn-sm <?= $filter==='closed'?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/tickets.php?s=closed') ?>">Kapalı (<?= $counts['closed'] ?? 0 ?>)</a>
    </div>
</div>

<div class="card">
    <?php if (!$tickets): ?>
        <div class="card-pad"><p class="muted">Bu filtrede talep yok.</p></div>
    <?php else: ?>
    <table class="data">
        <thead><tr><th>#</th><th>Konu</th><th>Kullanıcı</th><th>Durum</th><th>Öncelik</th><th class="r">Mesaj</th><th class="r">Güncelleme</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): $st = $statusLabel[$t['status']]; ?>
            <tr>
                <td class="muted"><?= (int)$t['id'] ?></td>
                <td><b><?= e($t['subject']) ?></b></td>
                <td><?= e($t['uname']) ?><div class="muted" style="font-size:12px"><?= e($t['uemail']) ?></div></td>
                <td><span class="pill" style="background:transparent;color:<?= $st[1] ?>;border:1px solid <?= $st[1] ?>"><?= $st[0] ?></span></td>
                <td><?= e($t['priority']) ?></td>
                <td class="r"><?= (int)$t['msg_count'] ?></td>
                <td class="r"><?= format_date($t['updated_at']) ?></td>
                <td class="r"><a class="btn btn-ghost btn-sm" href="<?= url('ticket_view.php?id='.(int)$t['id']) ?>">Aç →</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/footer.php';