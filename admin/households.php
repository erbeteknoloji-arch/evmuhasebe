<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();
$tl = function ($n): string { return number_format((float)$n, 2, ',', '.') . ' ₺'; };
$q  = trim($_GET['q'] ?? '');

$sql =
    "SELECT h.id, h.name, h.currency, h.join_code, h.created_at,
            (SELECT GROUP_CONCAT(u.name SEPARATOR ', ')
               FROM household_members hm JOIN users u ON u.id = hm.user_id
              WHERE hm.household_id = h.id AND hm.role='owner') AS owners,
            (SELECT COUNT(*) FROM household_members hm WHERE hm.household_id = h.id) AS members,
            (SELECT COUNT(*) FROM accounts a WHERE a.household_id = h.id) AS accounts,
            (SELECT COUNT(*) FROM transactions t WHERE t.household_id = h.id) AS txns,
            (SELECT COALESCE(SUM(opening_balance),0) FROM accounts a WHERE a.household_id = h.id) AS opening,
            (SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE -amount END),0)
               FROM transactions t WHERE t.household_id = h.id) AS net
       FROM households h ";

if ($q !== '') {
    $sql .= " WHERE h.name LIKE :q OR EXISTS (
                SELECT 1 FROM household_members hm JOIN users u ON u.id = hm.user_id
                 WHERE hm.household_id = h.id AND (u.name LIKE :q2 OR u.username LIKE :q3 OR u.email LIKE :q4)
              ) ";
}
$sql .= " ORDER BY h.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt->execute([':q'=>$like, ':q2'=>$like, ':q3'=>$like, ':q4'=>$like]);
} else {
    $stmt->execute();
}
$rows = $stmt->fetchAll();

$page_title = 'Hane Yönetimi';
$page_subtitle = count($rows) . ' hane';
$active = 'admin';
require __DIR__ . '/../templates/header.php';
?>
<div class="flex" style="gap:8px;margin:6px 0 14px;flex-wrap:wrap">
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/index.php') ?>">← Panele dön</a>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/users.php') ?>">👤 Kullanıcılar</a>
    <form method="get" action="<?= url('admin/households.php') ?>" style="margin-left:auto">
        <div class="flex" style="gap:8px">
            <input class="input btn-sm" name="q" value="<?= e($q) ?>" placeholder="Hane adı veya üye ara…">
            <button class="btn btn-ghost btn-sm">Ara</button>
        </div>
    </form>
</div>

<?php if (!$rows): ?>
<div class="card card-pad" style="text-align:center"><div class="big" style="font-size:40px">🏠</div>
    <h3>Hane bulunamadı</h3><p class="muted"><?= $q ? 'Aramanıza uygun hane yok.' : 'Henüz hane yok.' ?></p></div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr>
                <th>#</th><th>Hane</th><th>Sahibi</th><th class="num-right">Üye</th>
                <th class="num-right">Hesap</th><th class="num-right">İşlem</th>
                <th class="num-right">Net Bakiye</th><th>Oluşturma</th><th class="num-right">İşlem</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $h):
                $balance = (float)$h['opening'] + (float)$h['net']; ?>
                <tr>
                    <td class="muted"><?= (int)$h['id'] ?></td>
                    <td><b><?= e($h['name']) ?></b><div class="muted" style="font-size:12px">Kod: <?= e($h['join_code']) ?> · <?= e($h['currency']) ?></div></td>
                    <td><?= e($h['owners'] ?: '—') ?></td>
                    <td class="num-right"><?= (int)$h['members'] ?></td>
                    <td class="num-right"><?= (int)$h['accounts'] ?></td>
                    <td class="num-right"><?= number_format((int)$h['txns'],0,',','.') ?></td>
                    <td class="num-right tabular" style="color:<?= $balance>=0?'var(--income)':'var(--expense)' ?>"><?= $tl($balance) ?></td>
                    <td class="muted"><?= format_date($h['created_at']) ?></td>
                    <td class="num-right"><a class="btn btn-sm btn-primary" href="<?= url('admin/household_view.php?id=' . (int)$h['id']) ?>">Görüntüle</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php';
