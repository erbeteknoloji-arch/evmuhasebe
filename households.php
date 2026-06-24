<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();

$pdo = db();
$me  = current_user();

/* Mutlak uygulama adresi (davet bağlantısı için) */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$appBase = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();

/* Para birimi seçenekleri */
$currencies = $GLOBALS['CURRENCY_SYMBOLS'];

/* ============================================================
   DURUM A: Davet bağlantısı ile katılım (?katil=TOKEN)
   ============================================================ */
if (isset($_GET['katil'])) {
    $token = trim($_GET['katil']);
    $stmt = $pdo->prepare('SELECT i.*, h.name hname FROM invitations i JOIN households h ON h.id=i.household_id WHERE i.token=? LIMIT 1');
    $stmt->execute([$token]);
    $inv = $stmt->fetch();
    ?>
    <!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Davet · <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"></head>
    <body><div style="max-width:460px;margin:80px auto;padding:0 16px">
        <div class="card card-pad" style="text-align:center">
            <div style="font-size:42px">📨</div>
            <?php if ($inv && $inv['status']==='pending'): ?>
                <h1 style="font-size:24px"><?= e($inv['hname']) ?></h1>
                <p class="muted">Bu eve üye olarak davet edildiniz.</p>
                <form method="post" action="<?= url('actions/household_join.php') ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <button class="btn btn-primary btn-block">Eve Katıl</button>
                </form>
                <a href="<?= url('index.php') ?>" style="display:inline-block;margin-top:14px" class="muted">Vazgeç</a>
            <?php else: ?>
                <h1 style="font-size:22px">Davet geçersiz</h1>
                <p class="muted">Bu davet bağlantısı kullanılmış veya süresi dolmuş olabilir.</p>
                <a href="<?= url('households.php') ?>" class="btn btn-ghost mt-2">Evlerime Dön</a>
            <?php endif; ?>
        </div>
    </div></body></html>
    <?php
    exit;
}

$household = current_household();

/* ============================================================
   DURUM B: Hiç ev yok -> kurulum (sidebar olmadan)
   ============================================================ */
if (!$household) {
    ?>
    <!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>İlk Kurulum · <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"></head>
    <body><div style="max-width:560px;margin:60px auto;padding:0 16px">
        <div style="text-align:center;margin-bottom:24px">
            <div style="font-size:40px">🏠</div>
            <h1>Hadi başlayalım, <?= e($me['name']) ?>!</h1>
            <p class="muted">Yeni bir ev oluşturun ya da bir koda sahipseniz mevcut bir eve katılın.</p>
        </div>
        <?php $flashes = get_flashes(); foreach ($flashes as $f): ?>
            <div class="flash <?= e($f['type']) ?>" style="margin-bottom:12px"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <div class="card card-pad" style="margin-bottom:16px">
            <h3>Yeni Ev Oluştur</h3>
            <form method="post" action="<?= url('actions/household_create.php') ?>">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="field"><label>Ev Adı</label><input class="input" name="name" placeholder="Örn. Bizim Ev" required></div>
                    <div class="field"><label>Para Birimi</label><select class="input" name="currency">
                        <?php foreach ($currencies as $code=>$sym): ?><option value="<?= $code ?>"><?= $code ?> (<?= $sym ?>)</option><?php endforeach; ?>
                    </select></div>
                </div>
                <button class="btn btn-primary btn-block">Evi Oluştur</button>
            </form>
        </div>
        <div class="card card-pad">
            <h3>Mevcut Eve Katıl</h3>
            <form method="post" action="<?= url('actions/household_join.php') ?>" class="flex" style="gap:10px">
                <?= csrf_field() ?>
                <input class="input" name="join_code" placeholder="Katılım kodu (örn. AB12CD34)" style="text-transform:uppercase" required>
                <button class="btn btn-ghost">Katıl</button>
            </form>
        </div>
    </div></body></html>
    <?php
    exit;
}

/* ============================================================
   DURUM C: Normal yönetim ekranı
   ============================================================ */
$householdId = (int)$household['id'];
$owner = is_owner();

$mstmt = $pdo->prepare(
    'SELECT u.id, u.name, u.username, u.email, u.avatar_color, hm.role, hm.joined_at
       FROM household_members hm JOIN users u ON u.id = hm.user_id
      WHERE hm.household_id = ? ORDER BY (hm.role="owner") DESC, u.name ASC'
);
$mstmt->execute([$householdId]);
$members = $mstmt->fetchAll();
$ownerCount = count(array_filter($members, fn($m) => $m['role'] === 'owner'));
$soleOwner  = ($owner && $ownerCount <= 1);

$istmt = $pdo->prepare('SELECT * FROM invitations WHERE household_id = ? AND status = "pending" ORDER BY created_at DESC');
$istmt->execute([$householdId]);
$invites = $istmt->fetchAll();

$lstmt = $pdo->prepare(
    'SELECT al.*, u.name uname FROM activity_log al LEFT JOIN users u ON u.id = al.user_id
      WHERE al.household_id = ? ORDER BY al.created_at DESC LIMIT 12'
);
$lstmt->execute([$householdId]);
$activity = $lstmt->fetchAll();

$actionLabels = [
    'transaction_create'=>'işlem ekledi','transaction_update'=>'işlem güncelledi','transaction_delete'=>'işlem sildi',
    'import_commit'=>'PDF içe aktardı','household_create'=>'evi oluşturdu','household_update'=>'ev bilgilerini güncelledi',
    'member_join'=>'eve katıldı','member_invite'=>'davet gönderdi','member_remove'=>'üye çıkardı',
];

$newInviteToken = $_GET['davet'] ?? '';

$page_title    = t('page.households.title');
$page_subtitle = $household['name'] . ' · ' . count($members) . ' üye';
$active        = 'households';
$page_actions  = '<button class="btn btn-primary" onclick="openModal(\'createModal\')">+ Yeni Ev</button>';

require __DIR__ . '/templates/header.php';
?>

<?php if ($newInviteToken):
    $link = $appBase . '/households.php?katil=' . $newInviteToken; ?>
<div class="card card-pad" style="margin-bottom:18px;border-color:var(--gold)">
    <h3>📨 Davet bağlantısı hazır</h3>
    <p class="muted" style="margin-top:0">Aşağıdaki bağlantıyı davet ettiğiniz kişiye gönderin. Kişi giriş yaptıktan sonra bu bağlantıyla eve katılabilir.</p>
    <div class="flex" style="gap:8px">
        <input class="input" id="inviteLink" value="<?= e($link) ?>" readonly onclick="this.select()">
        <button class="btn btn-gold" onclick="copyText('inviteLink')">Kopyala</button>
    </div>
</div>
<?php endif; ?>

<div class="grid cols-2-1">
    <!-- Üyeler -->
    <div class="card">
        <div class="card-head"><h3>Ev Üyeleri</h3>
            <?php if ($owner): ?><button class="btn btn-ghost btn-sm" onclick="openModal('inviteModal')">+ Üye Davet Et</button><?php endif; ?>
        </div>
        <div class="card-pad">
            <div class="list">
                <?php foreach ($members as $m): ?>
                    <div class="item">
                        <div class="av" style="background:<?= e($m['avatar_color']) ?>"><?= e(mb_strtoupper(mb_substr($m['name'],0,1))) ?></div>
                        <div class="grow">
                            <b><?= e($m['name']) ?> <?php if ($m['id']==$me['id']): ?><span class="muted">(siz)</span><?php endif; ?></b>
                            <span>@<?= e($m['username']) ?> · katılım <?= format_date($m['joined_at']) ?></span>
                        </div>
                        <?php if ($m['role']==='owner'): ?>
                            <span class="pill owner">★ Ev Sahibi</span>
                        <?php else: ?>
                            <span class="pill gray">Üye</span>
                        <?php endif; ?>
                        <?php if ($owner && $m['role']!=='owner'): ?>
                            <form method="post" action="<?= url('actions/member_role.php') ?>" style="display:inline;margin-left:8px" onsubmit="return confirm('<?= e($m['name']) ?> ev sahibi yapılsın mı? Bu kişi de evi yönetebilecek.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
                                <input type="hidden" name="role" value="owner">
                                <button class="btn btn-ghost btn-sm" title="Ev sahibi yap">★ Ev sahibi yap</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($owner && $m['role']!=='owner' && $m['id']!=$me['id']): ?>
                            <form method="post" action="<?= url('actions/member_remove.php') ?>" style="display:inline;margin-left:8px" onsubmit="return confirmDelete('<?= e($m['name']) ?> evden çıkarılsın mı?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
                                <button class="icon-btn danger" title="Çıkar">🗑</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($invites): ?>
                <div class="section-title">Bekleyen Davetler</div>
                <div class="list">
                    <?php foreach ($invites as $iv): ?>
                        <div class="item">
                            <div class="av" style="background:var(--cream-2);color:var(--ink-soft)">@</div>
                            <div class="grow"><b><?= e($iv['email']) ?></b><span>davet edildi · <?= format_date($iv['created_at']) ?></span></div>
                            <a class="btn btn-ghost btn-sm" href="?davet=<?= e($iv['token']) ?>">Bağlantı</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sağ kolon: katılım kodu + ayarlar -->
    <div>
        <div class="card card-pad">
            <h3>Bu Eve Katılım Kodu</h3>
            <p class="muted" style="margin-top:0;font-size:13px">Bu kodu paylaşarak diğer kişileri eve davet edebilirsiniz.</p>
            <div class="flex flex-wrap" style="gap:10px">
                <span class="code-box" id="joinCode"><?= e($household['join_code']) ?></span>
                <button class="btn btn-ghost btn-sm" onclick="copyText('joinCode')">Kopyala</button>
            </div>
        </div>

        <?php if ($owner): ?>
        <div class="card card-pad mt-2">
            <h3>Ev Ayarları</h3>
            <form method="post" action="<?= url('actions/household_update.php') ?>">
                <?= csrf_field() ?>
                <div class="field"><label>Ev Adı</label><input class="input" name="name" value="<?= e($household['name']) ?>" required></div>
                <div class="field"><label>Para Birimi</label>
                    <select class="input" name="currency">
                        <?php foreach ($currencies as $code=>$sym): ?>
                            <option value="<?= $code ?>" <?= $household['currency']===$code?'selected':'' ?>><?= $code ?> (<?= $sym ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary">Kaydet</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Başka eve katıl -->
        <div class="card card-pad mt-2">
            <h3>Başka Bir Eve Katıl</h3>
            <form method="post" action="<?= url('actions/household_join.php') ?>">
                <?= csrf_field() ?>
                <div class="flex" style="gap:8px">
                    <input class="input" name="join_code" placeholder="Katılım kodu" style="text-transform:uppercase" required>
                    <button class="btn btn-ghost">Katıl</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Etkinlik günlüğü -->
<?php if ($activity): ?>
<div class="card mt-2">
    <div class="card-head"><h3>Son Etkinlikler</h3></div>
    <div class="card-pad"><div class="list">
        <?php foreach ($activity as $a): ?>
            <div class="item">
                <div class="av" style="background:var(--cream-2);color:var(--ink-soft);font-size:15px">🕘</div>
                <div class="grow"><b><?= e($a['uname'] ?: 'Birisi') ?></b> <?= e($actionLabels[$a['action']] ?? $a['action']) ?>
                    <?php if ($a['detail']): ?><span> — <?= e($a['detail']) ?></span><?php endif; ?></div>
                <span class="muted" style="font-size:12.5px;white-space:nowrap"><?= format_date($a['created_at']) ?></span>
            </div>
        <?php endforeach; ?>
    </div></div>
</div>
<?php endif; ?>

<!-- Evden Ayrıl (tüm üyeler görebilir) -->
<div class="card card-pad mt-2" style="border-color:#f0cdbb">
    <h3>Evden Ayrıl</h3>
    <p class="muted" style="margin-top:0">Bu evden ayrıldığınızda eve ait verilere (işlemler, hesaplar, listeler vb.) erişiminiz kaldırılır. Veriler ev için saklanmaya devam eder.</p>
    <?php if ($soleOwner): ?>
        <div class="flash" style="background:var(--gold-soft,#fff7e6);border:1px solid #f0d9a8;color:#7a5a00;padding:10px 12px;border-radius:10px">
            Bu evin tek ev sahibisiniz. Ayrılmadan önce yukarıdaki listeden başka bir üyeyi <b>“★ Ev sahibi yap”</b> ile yetkilendirin veya aşağıdan evi silin.
        </div>
    <?php else: ?>
        <form method="post" action="<?= url('actions/household_leave.php') ?>" onsubmit="return confirm('Bu evden ayrılmak istediğinize emin misiniz?')">
            <?= csrf_field() ?>
            <button class="btn btn-danger" type="submit">Evden Ayrıl</button>
        </form>
    <?php endif; ?>
</div>

<!-- Tehlikeli bölge (yalnızca owner) -->
<?php if ($owner): ?>
<div class="card card-pad mt-2" style="border-color:#f0cdbb">
    <h3 style="color:var(--danger)">Tehlikeli Bölge</h3>
    <p class="muted" style="margin-top:0">Bu evi silmek tüm işlemleri, kategorileri ve hesapları kalıcı olarak siler. Bu işlem geri alınamaz.</p>
    <button class="btn btn-danger" onclick="openModal('deleteModal')">Bu Evi Sil</button>
</div>
<?php endif; ?>

<!-- Yeni ev modalı -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-head"><h3>Yeni Ev Oluştur</h3><button class="x" onclick="closeModal('createModal')">×</button></div>
        <form method="post" action="<?= url('actions/household_create.php') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="field"><label>Ev Adı</label><input class="input" name="name" placeholder="Örn. Yazlık Ev" required></div>
                <div class="field"><label>Para Birimi</label><select class="input" name="currency">
                    <?php foreach ($currencies as $code=>$sym): ?><option value="<?= $code ?>"><?= $code ?> (<?= $sym ?>)</option><?php endforeach; ?>
                </select></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" onclick="closeModal('createModal')">Vazgeç</button><button class="btn btn-primary">Oluştur</button></div>
        </form>
    </div>
</div>

<!-- Üye davet modalı -->
<?php if ($owner): ?>
<div class="modal-overlay" id="inviteModal">
    <div class="modal">
        <div class="modal-head"><h3>Üye Davet Et</h3><button class="x" onclick="closeModal('inviteModal')">×</button></div>
        <form method="post" action="<?= url('actions/household_invite.php') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p class="muted" style="margin-top:0">E-posta girince paylaşabileceğiniz bir davet bağlantısı oluşturulur. Dilerseniz yukarıdaki <b>katılım kodunu</b> da paylaşabilirsiniz.</p>
                <div class="field"><label>E-posta</label><input class="input" type="email" name="email" placeholder="uye@ornek.com" required></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" onclick="closeModal('inviteModal')">Vazgeç</button><button class="btn btn-primary">Davet Oluştur</button></div>
        </form>
    </div>
</div>

<!-- Ev silme modalı -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-head"><h3 style="color:var(--danger)">Evi Sil</h3><button class="x" onclick="closeModal('deleteModal')">×</button></div>
        <form method="post" action="<?= url('actions/household_delete.php') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p>Onaylamak için ev adını birebir yazın: <b><?= e($household['name']) ?></b></p>
                <div class="field"><input class="input" name="confirm_name" placeholder="<?= e($household['name']) ?>" required></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" onclick="closeModal('deleteModal')">Vazgeç</button><button class="btn btn-danger">Kalıcı Olarak Sil</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
$inline_script = <<<JS
function copyText(id){
    const el=document.getElementById(id);
    const text=el.value!==undefined?el.value:el.textContent;
    navigator.clipboard.writeText(text.trim()).then(()=>{ alert('Kopyalandı: '+text.trim()); });
}
JS;
require __DIR__ . '/templates/footer.php';
