<?php
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

/* ---- Filtreler (GET) ---- */
$fType   = $_GET['type'] ?? '';
$fCat    = (int)($_GET['category'] ?? 0);
$fAcc    = (int)($_GET['account'] ?? 0);
$fFrom   = $_GET['from'] ?? '';
$fTo     = $_GET['to'] ?? '';
$fSearch = trim($_GET['q'] ?? '');
$fMin    = trim($_GET['min'] ?? '');
$fMax    = trim($_GET['max'] ?? '');
$fTag    = trim($_GET['tag'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where = ['t.household_id = ?'];
$params = [$householdId];
if (in_array($fType, ['income','expense'], true)) { $where[] = 't.type = ?'; $params[] = $fType; }
if ($fCat)  { $where[] = 't.category_id = ?'; $params[] = $fCat; }
if ($fAcc)  { $where[] = 't.account_id = ?'; $params[] = $fAcc; }
if ($fFrom && strtotime($fFrom)) { $where[] = 't.transaction_date >= ?'; $params[] = date('Y-m-d', strtotime($fFrom)); }
if ($fTo && strtotime($fTo))     { $where[] = 't.transaction_date <= ?'; $params[] = date('Y-m-d', strtotime($fTo)); }
$minV = $fMin !== '' ? parse_money_tr($fMin) : null;
$maxV = $fMax !== '' ? parse_money_tr($fMax) : null;
if ($minV !== null) { $where[] = 't.amount >= ?'; $params[] = $minV; }
if ($maxV !== null) { $where[] = 't.amount <= ?'; $params[] = $maxV; }
if ($fTag !== '')   { $where[] = 't.tags LIKE ?'; $params[] = '%' . $fTag . '%'; }
if ($fSearch !== '') { $where[] = '(t.description LIKE ? OR t.tags LIKE ?)'; $params[] = '%' . $fSearch . '%'; $params[] = '%' . $fSearch . '%'; }
$whereSql = implode(' AND ', $where);

/* ---- Toplamlar (transferler hariç) ---- */
$sumStmt = $pdo->prepare("SELECT type, COALESCE(SUM(amount),0) s, COUNT(*) c FROM transactions t WHERE $whereSql AND t.transfer_id IS NULL GROUP BY type");
$sumStmt->execute($params);
$sumInc = 0; $sumExp = 0; $total = 0;
foreach ($sumStmt->fetchAll() as $r) {
    if ($r['type'] === 'income') $sumInc = (float)$r['s']; else $sumExp = (float)$r['s'];
    $total += (int)$r['c'];
}
$totalPages = max(1, (int)ceil($total / $perPage));

/* ---- Liste ---- */
$listSql = "SELECT t.*, c.name cat_name, c.color cat_color, c.icon cat_icon,
                   a.name acc_name, u.name user_name
              FROM transactions t
              LEFT JOIN categories c ON c.id = t.category_id
              LEFT JOIN accounts a ON a.id = t.account_id
              LEFT JOIN users u ON u.id = t.user_id
             WHERE $whereSql
          ORDER BY t.transaction_date DESC, t.id DESC
             LIMIT $perPage OFFSET $offset";
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

/* ---- Form için kategoriler & hesaplar ---- */
$cats = $pdo->prepare('SELECT id, name, type, icon FROM categories WHERE household_id = ? AND is_archived = 0 ORDER BY type DESC, name ASC');
$cats->execute([$householdId]);
$allCats = $cats->fetchAll();

$accs = $pdo->prepare('SELECT id, name, type FROM accounts WHERE household_id = ? AND is_active = 1 ORDER BY name ASC');
$accs->execute([$householdId]);
$allAccs = $accs->fetchAll();

$returnUrl = 'transactions.php' . (count($_GET) ? '?' . http_build_query($_GET) : '');

$page_title    = t('page.transactions.title');
$page_subtitle = $total . ' kayıt';
$active        = 'transactions';
$page_actions  = '<a href="' . url('import.php') . '" class="btn btn-ghost">📥 PDF İçe Aktar</a>'
               . '<button class="btn btn-primary" onclick="newTransaction(\'expense\')">+ Yeni İşlem</button>';

require __DIR__ . '/templates/header.php';

$typeAccents = ['expense' => '#E0533D', 'income' => '#15803D'];
$prefill = $_GET['yeni'] ?? '';
?>

<!-- Filtre çubuğu -->
<form method="get" class="filterbar">
    <div class="field">
        <label>Tür</label>
        <select name="type" class="input">
            <option value="">Tümü</option>
            <option value="income"  <?= $fType==='income'?'selected':'' ?>>Gelir</option>
            <option value="expense" <?= $fType==='expense'?'selected':'' ?>>Gider</option>
        </select>
    </div>
    <div class="field">
        <label>Kategori</label>
        <select name="category" class="input">
            <option value="0">Tümü</option>
            <?php foreach ($allCats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $fCat==$c['id']?'selected':'' ?>><?= $c['icon'] ?> <?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label>Hesap</label>
        <select name="account" class="input">
            <option value="0">Tümü</option>
            <?php foreach ($allAccs as $a): ?>
                <option value="<?= (int)$a['id'] ?>" <?= $fAcc==$a['id']?'selected':'' ?>><?= e($a['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label>Başlangıç</label>
        <input type="date" name="from" class="input" value="<?= e($fFrom) ?>">
    </div>
    <div class="field">
        <label>Bitiş</label>
        <input type="date" name="to" class="input" value="<?= e($fTo) ?>">
    </div>
    <div class="field" style="max-width:120px">
        <label>Min Tutar</label>
        <input type="text" name="min" class="input tabular" inputmode="decimal" value="<?= e($fMin) ?>" placeholder="0,00">
    </div>
    <div class="field" style="max-width:120px">
        <label>Maks Tutar</label>
        <input type="text" name="max" class="input tabular" inputmode="decimal" value="<?= e($fMax) ?>" placeholder="0,00">
    </div>
    <div class="field" style="max-width:150px">
        <label>Etiket</label>
        <input type="text" name="tag" class="input" value="<?= e($fTag) ?>" placeholder="ör. tatil">
    </div>
    <div class="field" style="flex:1;min-width:160px">
        <label>Ara</label>
        <input type="text" name="q" class="input" value="<?= e($fSearch) ?>" placeholder="Açıklama veya etikette ara…">
    </div>
    <button type="submit" class="btn btn-primary">Filtrele</button>
    <?php if (count($_GET)): ?><a href="<?= url('transactions.php') ?>" class="btn btn-ghost">Temizle</a><?php endif; ?>
</form>

<!-- Filtre toplamları -->
<div class="grid grid-3" style="margin-bottom:18px">
    <div class="card stat income"><div class="label">Toplam Gelir</div><div class="value" style="font-size:22px"><?= money($sumInc) ?></div></div>
    <div class="card stat expense"><div class="label">Toplam Gider</div><div class="value" style="font-size:22px"><?= money($sumExp) ?></div></div>
    <div class="card stat balance"><div class="label">Net</div><div class="value" style="font-size:22px;color:<?= ($sumInc-$sumExp)>=0?'var(--income)':'var(--expense)' ?>"><?= (($sumInc-$sumExp)>=0?'+':'').money($sumInc-$sumExp) ?></div></div>
</div>

<!-- Liste -->
<div class="card">
    <?php if ($rows): ?>
    <div class="table-wrap">
        <table class="data">
            <thead><tr>
                <th>Tarih</th><th>Açıklama</th><th>Kategori</th><th>Hesap</th><th>Ekleyen</th>
                <th class="num-right">Tutar</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $t): ?>
                <tr>
                    <td class="muted tabular" style="white-space:nowrap"><?= format_date($t['transaction_date']) ?></td>
                    <td>
                        <?= e($t['description'] ?: '—') ?>
                        <?php if ($t['source']==='import'): ?> <span class="pill import">PDF</span><?php endif; ?>
                        <?php if (!empty($t['transfer_id'])): ?> <span class="pill" style="background:var(--cream-2)">↔ Transfer</span><?php endif; ?>
                        <?php if (!empty($t['tags'])): ?>
                            <?php foreach (preg_split('/[,\s]+/', trim($t['tags'])) as $tg): if ($tg==='') continue; ?>
                                <span class="pill gray" style="font-size:11px">#<?= e($tg) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['cat_name']): ?>
                            <span class="cat-badge"><span class="swatch" style="background:<?= e($t['cat_color']) ?>22"><?= $t['cat_icon'] ?></span><?= e($t['cat_name']) ?></span>
                        <?php else: ?><span class="pill gray">Kategorisiz</span><?php endif; ?>
                    </td>
                    <td class="muted"><?= e($t['acc_name'] ?: '—') ?></td>
                    <td class="muted"><?= e($t['user_name'] ?: '—') ?></td>
                    <td class="num-right amount <?= $t['type'] ?>"><?= ($t['type']==='income'?'+':'−').money($t['amount']) ?></td>
                    <td>
                        <div class="row-actions">
                            <?php if (empty($t['transfer_id'])): ?>
                            <button class="icon-btn" title="Düzenle" onclick='editTransaction(<?= json_encode([
                                "id"=>$t["id"],"description"=>$t["description"],"amount"=>number_format((float)$t["amount"],2,",","."),
                                "date"=>$t["transaction_date"],"type"=>$t["type"],"tags"=>$t["tags"],
                                "category_id"=>$t["category_id"],"account_id"=>$t["account_id"]
                            ], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>)'>✎</button>
                            <?php endif; ?>
                            <form method="post" action="<?= url('actions/transaction_delete.php') ?>" style="display:inline" onsubmit="return confirmDelete(<?= !empty($t['transfer_id']) ? "'Bu transferin her iki kaydı da silinecek. Devam edilsin mi?'" : '' ?>)">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <input type="hidden" name="return" value="<?= e($returnUrl) ?>">
                                <button class="icon-btn danger" title="Sil">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex-between card-pad">
        <span class="muted">Sayfa <?= $page ?> / <?= $totalPages ?></span>
        <div class="flex">
            <?php $qp = $_GET; ?>
            <?php if ($page > 1): $qp['page'] = $page-1; ?>
                <a class="btn btn-ghost btn-sm" href="?<?= http_build_query($qp) ?>">← Önceki</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): $qp['page'] = $page+1; ?>
                <a class="btn btn-ghost btn-sm" href="?<?= http_build_query($qp) ?>">Sonraki →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
        <div class="empty"><div class="big">🔍</div><h3>İşlem bulunamadı</h3>
            <p class="muted">Filtreleri değiştirin veya yeni bir işlem ekleyin.</p></div>
    <?php endif; ?>
</div>

<!-- İşlem ekleme/düzenleme modalı -->
<div class="modal-overlay" id="txModal">
    <div class="modal">
        <div class="modal-head">
            <h3 id="txModalTitle">Yeni İşlem</h3>
            <button class="x" onclick="closeModal('txModal')">×</button>
        </div>
        <form method="post" action="<?= url('actions/transaction_save.php') ?>" id="txForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <input type="hidden" name="return" value="<?= e($returnUrl) ?>">
            <div class="modal-body">
                <div class="field">
                    <label>Tür</label>
                    <div class="seg">
                        <label class="on-income"><input type="radio" name="type" value="income"><span>↑ Gelir</span></label>
                        <label class="on-expense"><input type="radio" name="type" value="expense" checked><span>↓ Gider</span></label>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Tutar</label>
                        <input class="input tabular" type="text" name="amount" inputmode="decimal" placeholder="0,00" required>
                    </div>
                    <div class="field">
                        <label>Tarih</label>
                        <input class="input" type="date" name="transaction_date" required>
                    </div>
                </div>
                <div class="field">
                    <label>Açıklama</label>
                    <input class="input" type="text" name="description" maxlength="255" placeholder="Örn. Market alışverişi">
                </div>
                <div class="field">
                    <label>Etiketler <span class="hint">(opsiyonel, boşlukla ayırın)</span></label>
                    <input class="input" type="text" name="tags" maxlength="255" placeholder="ör. tatil acil iş">
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Kategori</label>
                        <select class="input" name="category_id">
                            <option value="">— Seçiniz —</option>
                            <?php foreach ($allCats as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" data-type="<?= e($c['type']) ?>"><?= $c['icon'] ?> <?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Hesap / Kart <span class="hint">(opsiyonel)</span></label>
                        <select class="input" name="account_id">
                            <option value="">— Seçiniz —</option>
                            <?php foreach ($allAccs as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"><?= e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('txModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
// Sayfa "?yeni=gelir/gider" ile açıldıysa modalı otomatik aç
if ($prefill === 'gelir' || $prefill === 'gider') {
    $t = $prefill === 'gelir' ? 'income' : 'expense';
    $inline_script = "document.addEventListener('DOMContentLoaded',function(){newTransaction('$t');});";
}
require __DIR__ . '/templates/footer.php';
