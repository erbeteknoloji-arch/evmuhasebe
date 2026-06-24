<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();

$pdo = db();
$me = (int)current_user()['id'];
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? ORDER BY id DESC");
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
}
$users = $stmt->fetchAll();
$csrf = csrf_token();

$page_title = 'Kullanıcı Yönetimi';
$page_subtitle = count($users) . ' kullanıcı';
$active = 'admin';
require __DIR__ . '/../templates/header.php';
?>
<div class="flex" style="gap:8px;margin:6px 0 14px">
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/index.php') ?>">← Panele dön</a>
    <form method="get" action="<?= url('admin/users.php') ?>" style="margin-left:auto">
        <div class="flex" style="gap:8px">
            <input class="input btn-sm" name="q" value="<?= e($q) ?>" placeholder="Ad, kullanıcı adı, e-posta ara…">
            <button class="btn btn-ghost btn-sm">Ara</button>
        </div>
    </form>
</div>

<div class="card">
    <table class="data">
        <thead><tr><th>#</th><th>Kullanıcı</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Kayıt</th><th class="r">İşlemler</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $self = (int)$u['id'] === $me; ?>
            <tr>
                <td class="muted"><?= (int)$u['id'] ?></td>
                <td><b><?= e($u['name']) ?></b><div class="muted" style="font-size:12px">@<?= e($u['username']) ?></div></td>
                <td><?= e($u['email']) ?></td>
                <td><?= $u['is_admin'] ? '<span class="pill" style="background:var(--gold-soft);color:var(--gold)">Yönetici</span>' : 'Üye' ?></td>
                <td><?= $u['is_active'] ? '<span style="color:var(--income)">● Aktif</span>' : '<span style="color:var(--expense)">● Pasif</span>' ?></td>
                <td><?= format_date($u['created_at']) ?></td>
                <td class="r">
                    <?php if ($self): ?>
                        <span class="muted" style="font-size:12px">(siz)</span>
                    <?php else: ?>
                    <div class="flex" style="gap:6px;justify-content:flex-end;flex-wrap:wrap">
                        <form method="post" action="<?= url('actions/admin_user_toggle.php') ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn btn-sm <?= $u['is_active']?'btn-ghost':'btn-primary' ?>"><?= $u['is_active'] ? 'Pasifleştir' : 'Aktifleştir' ?></button>
                        </form>
                        <form method="post" action="<?= url('actions/admin_user_admin.php') ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn btn-sm btn-ghost"><?= $u['is_admin'] ? 'Yöneticiliği Al' : 'Yönetici Yap' ?></button>
                        </form>
                        <form method="post" action="<?= url('actions/admin_user_delete.php') ?>" onsubmit="return confirm('<?= e($u['username']) ?> kalıcı olarak silinsin mi? Tüm verileri silinir.')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn btn-sm btn-danger">Sil</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../templates/footer.php';