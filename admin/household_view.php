<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();

$pdo = db();
$csrf = csrf_token();
$hidView = (int)($_GET['id'] ?? 0);

$hs = $pdo->prepare('SELECT * FROM households WHERE id = ? LIMIT 1');
$hs->execute([$hidView]);
$house = $hs->fetch();
if (!$house) {
    flash('error', 'Hane bulunamadı.');
    redirect('admin/households.php');
}
$cur = $house['currency'] ?: 'TRY';
$tl  = function ($n) use ($cur): string { return money($n, $cur); };

/* Üyeler */
$ms = $pdo->prepare(
    'SELECT u.id, u.name, u.username, u.email, u.avatar_color, hm.role, hm.joined_at
       FROM household_members hm JOIN users u ON u.id = hm.user_id
      WHERE hm.household_id = ? ORDER BY (hm.role="owner") DESC, u.name ASC'
);
$ms->execute([$hidView]);
$members = $ms->fetchAll();

/* Hesaplar + bakiye */
$as = $pdo->prepare(
    "SELECT a.*,
            COALESCE(SUM(CASE WHEN t.type='income' THEN t.amount END),0) inc,
            COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount END),0) exp
       FROM accounts a
       LEFT JOIN transactions t ON t.account_id = a.id AND t.household_id = a.household_id
      WHERE a.household_id = ?
   GROUP BY a.id ORDER BY a.name"
);
$as->execute([$hidView]);
$accountsList = $as->fetchAll();

/* Kategoriler & hesaplar (düzenleme modalı için) */
$cs = $pdo->prepare('SELECT id,name,type,icon FROM categories WHERE household_id=? ORDER BY type,name');
$cs->execute([$hidView]);
$cats = $cs->fetchAll();
$accForSelect = $pdo->prepare('SELECT id,name FROM accounts WHERE household_id=? ORDER BY name');
$accForSelect->execute([$hidView]);
$accSel = $accForSelect->fetchAll();

/* İşlemler (sayfalı) */
$perPage = 50;
$page = max(1, (int)($_GET['p'] ?? 1));
$txCount = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE household_id=' . $hidView)->fetchColumn();
$pages = max(1, (int)ceil($txCount / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$ts = $pdo->prepare(
    'SELECT t.*, c.name cat_name, c.icon cat_icon, c.color cat_color, a.name acc_name, u.name user_name
       FROM transactions t
       LEFT JOIN categories c ON c.id = t.category_id
       LEFT JOIN accounts a ON a.id = t.account_id
       LEFT JOIN users u ON u.id = t.user_id
      WHERE t.household_id = ?
   ORDER BY t.transaction_date DESC, t.id DESC
      LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
);
$ts->execute([$hidView]);
$txs = $ts->fetchAll();

$page_title = 'Hane: ' . $house['name'];
$page_subtitle = count($members) . ' üye · ' . number_format($txCount,0,',','.') . ' işlem · ' . e($cur);
$active = 'admin';
require __DIR__ . '/../templates/header.php';
?>
<div class="flex" style="gap:8px;margin:6px 0 14px;flex-wrap:wrap">
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/households.php') ?>">← Haneler</a>
    <span class="pill">Kod: <?= e($house['join_code']) ?></span>
    <span class="muted" style="font-size:12.5px;align-self:center">Oluşturma: <?= format_date($house['created_at']) ?></span>
</div>

<div class="grid cols-2-1">
    <!-- Üyeler -->
    <div class="card">
        <div class="card-head"><h3>Üyeler</h3></div>
        <div class="card-pad"><div class="list">
            <?php foreach ($members as $m): ?>
            <div class="item">
                <div class="av" style="background:<?= e($m['avatar_color'] ?: 'var(--cream-2)') ?>"><?= e(mb_strtoupper(mb_substr($m['name'],0,1))) ?></div>
                <div class="grow"><b><?= e($m['name']) ?></b><span>@<?= e($m['username']) ?> · <?= e($m['email']) ?></span></div>
                <?= $m['role']==='owner' ? '<span class="pill owner">★ Ev Sahibi</span>' : '<span class="pill gray">Üye</span>' ?>
            </div>
            <?php endforeach; ?>
        </div></div>
    </div>

    <!-- Hesaplar -->
    <div class="card card-pad">
        <h3>Hesaplar</h3>
        <?php if (!$accountsList): ?>
            <p class="muted">Hesap yok.</p>
        <?php else: ?>
        <div class="list" style="margin-top:6px">
            <?php foreach ($accountsList as $a): $bal=(float)$a['opening_balance']+(float)$a['inc']-(float)$a['exp']; ?>
            <div class="item">
                <div class="grow"><b><?= e($a['name']) ?></b><span><?= e($a['bank_name'] ?: $a['type']) ?></span></div>
                <b class="tabular" style="color:<?= $bal>=0?'var(--income)':'var(--expense)' ?>"><?= $tl($bal) ?></b>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- İşlemler -->
<div class="card mt-2">
    <div class="card-head">
        <h3>İşlemler (<?= number_format($txCount,0,',','.') ?>)</h3>
        <span class="muted" style="font-size:12.5px">Sayfa <?= $page ?> / <?= $pages ?></span>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Tarih</th><th>Açıklama</th><th>Kategori</th><th>Hesap</th><th>Ekleyen</th><th class="num-right">Tutar</th><th class="num-right">İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($txs as $t):
                $editData = json_encode([
                    'id'=>$t['id'],'type'=>$t['type'],'description'=>$t['description'],
                    'amount'=>number_format((float)$t['amount'],2,',','.'),
                    'date'=>$t['transaction_date'],
                    'category_id'=>$t['category_id'],'account_id'=>$t['account_id']
                ], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE); ?>
                <tr>
                    <td class="muted tabular"><?= format_date($t['transaction_date']) ?></td>
                    <td><?= e($t['description'] ?: '—') ?><?= $t['source']==='import'?' <span class="pill import">PDF</span>':'' ?></td>
                    <td><?php if ($t['cat_name']): ?><span class="cat-badge"><span class="swatch" style="background:<?= e($t['cat_color']) ?>22"><?= $t['cat_icon'] ?></span><?= e($t['cat_name']) ?></span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
                    <td class="muted"><?= e($t['acc_name'] ?: '—') ?></td>
                    <td class="muted"><?= e($t['user_name'] ?: '—') ?></td>
                    <td class="num-right amount <?= $t['type'] ?> tabular"><?= ($t['type']==='income'?'+':'−').$tl($t['amount']) ?></td>
                    <td class="num-right">
                        <div class="flex" style="gap:6px;justify-content:flex-end">
                            <button class="icon-btn" title="Düzenle" onclick='adminEditTx(<?= $editData ?>)'>✎</button>
                            <form method="post" action="<?= url('actions/admin_tx_delete.php') ?>" style="display:inline" onsubmit="return confirm('Bu işlem kalıcı olarak silinsin mi?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <button class="icon-btn danger" title="Sil">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$txs): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:20px">İşlem yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="modal-foot" style="justify-content:center;gap:6px;border-top:1px solid var(--line-soft)">
        <?php if ($page>1): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/household_view.php?id='.$hidView.'&p='.($page-1)) ?>">← Önceki</a><?php endif; ?>
        <span class="muted" style="align-self:center;font-size:12.5px">Sayfa <?= $page ?> / <?= $pages ?></span>
        <?php if ($page<$pages): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/household_view.php?id='.$hidView.'&p='.($page+1)) ?>">Sonraki →</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Düzenleme modalı -->
<div class="modal-overlay" id="txModal">
    <div class="modal">
        <div class="modal-head"><h3>İşlemi Düzenle (Yönetici)</h3><button class="x" onclick="closeModal('txModal')">×</button></div>
        <form method="post" action="<?= url('actions/admin_tx_save.php') ?>" id="adminTxForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="grid grid-2">
                    <div class="field"><label>Tür</label>
                        <select class="input" name="type">
                            <option value="expense">Gider</option>
                            <option value="income">Gelir</option>
                        </select></div>
                    <div class="field"><label>Tarih</label>
                        <input class="input" type="date" name="transaction_date" required></div>
                </div>
                <div class="field"><label>Açıklama</label>
                    <input class="input" name="description" maxlength="255"></div>
                <div class="grid grid-2">
                    <div class="field"><label>Tutar</label>
                        <input class="input tabular" name="amount" inputmode="decimal" placeholder="0,00" required></div>
                    <div class="field"><label>Hesap</label>
                        <select class="input" name="account_id">
                            <option value="">—</option>
                            <?php foreach ($accSel as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
                        </select></div>
                </div>
                <div class="field"><label>Kategori</label>
                    <select class="input" name="category_id">
                        <option value="">— Kategorisiz —</option>
                        <optgroup label="Gider">
                            <?php foreach ($cats as $c): if ($c['type']==='expense'): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['icon'].' '.$c['name']) ?></option><?php endif; endforeach; ?>
                        </optgroup>
                        <optgroup label="Gelir">
                            <?php foreach ($cats as $c): if ($c['type']==='income'): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['icon'].' '.$c['name']) ?></option><?php endif; endforeach; ?>
                        </optgroup>
                    </select></div>
            </div>
            <div class="modal-foot">
                <div class="grow"></div>
                <button type="button" class="btn btn-ghost" onclick="closeModal('txModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
$inline_script = <<<JS
function adminEditTx(d){
    var f=document.getElementById('adminTxForm');
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=type]').value=d.type||'expense';
    f.querySelector('[name=description]').value=d.description||'';
    f.querySelector('[name=amount]').value=d.amount||'';
    f.querySelector('[name=transaction_date]').value=d.date||'';
    f.querySelector('[name=account_id]').value=d.account_id||'';
    f.querySelector('[name=category_id]').value=d.category_id||'';
    openModal('txModal');
}
JS;
require __DIR__ . '/../templates/footer.php';
