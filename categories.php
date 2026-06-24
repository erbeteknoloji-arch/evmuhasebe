<?php
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

$cstmt = $pdo->prepare(
    'SELECT c.*, COALESCE(tc.cnt,0) cnt, b.monthly_limit
       FROM categories c
       LEFT JOIN (SELECT category_id, COUNT(*) cnt FROM transactions WHERE household_id = ? GROUP BY category_id) tc
              ON tc.category_id = c.id
       LEFT JOIN budgets b ON b.category_id = c.id AND b.household_id = ?
      WHERE c.household_id = ?
   ORDER BY c.type DESC, c.name ASC'
);
$cstmt->execute([$householdId, $householdId, $householdId]);
$all = $cstmt->fetchAll();
$expense = array_filter($all, fn($c) => $c['type'] === 'expense');
$income  = array_filter($all, fn($c) => $c['type'] === 'income');

$rstmt = $pdo->prepare(
    'SELECT r.*, c.name cat_name, c.icon, c.type
       FROM import_rules r JOIN categories c ON c.id = r.category_id
      WHERE r.household_id = ? ORDER BY r.match_text ASC'
);
$rstmt->execute([$householdId]);
$rules = $rstmt->fetchAll();

$page_title    = t('page.categories.title');
$page_subtitle = count($all) . ' kategori · ' . count($rules) . ' otomatik kural';
$active        = 'categories';
$page_actions  = '<button class="btn btn-primary" onclick="newCategory()">+ Yeni Kategori</button>';

require __DIR__ . '/templates/header.php';

/** Bir kategori tablosu çizen yardımcı */
function renderCatTable(array $list, bool $isExpense): void {
    if (!$list) {
        echo '<div class="empty"><div class="big">🏷️</div><p class="muted">Henüz kategori yok.</p></div>';
        return;
    }
    ?>
    <div class="table-wrap">
    <table class="data">
        <thead><tr>
            <th>Kategori</th><th>İşlem</th>
            <?php if ($isExpense): ?><th>Aylık Bütçe Limiti</th><?php endif; ?>
            <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($list as $c): ?>
            <tr>
                <td>
                    <span class="cat-badge"><span class="swatch" style="background:<?= e($c['color']) ?>22"><?= $c['icon'] ?></span><?= e($c['name']) ?></span>
                </td>
                <td class="muted"><?= (int)$c['cnt'] ?> işlem</td>
                <?php if ($isExpense): ?>
                <td>
                    <form method="post" action="<?= url('actions/budget_save.php') ?>" class="flex" style="gap:6px">
                        <?= csrf_field() ?>
                        <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
                        <input class="input tabular btn-sm" style="max-width:130px;padding:7px 10px" type="text"
                               name="monthly_limit" inputmode="decimal" placeholder="Limit yok"
                               value="<?= $c['monthly_limit'] ? number_format((float)$c['monthly_limit'],2,',','.') : '' ?>">
                        <button class="btn btn-ghost btn-sm" title="Kaydet">💾</button>
                    </form>
                </td>
                <?php endif; ?>
                <td>
                    <div class="row-actions">
                        <button class="icon-btn" title="Düzenle" onclick='editCategory(<?= json_encode([
                            "id"=>$c["id"],"name"=>$c["name"],"type"=>$c["type"],"color"=>$c["color"],"icon"=>$c["icon"]
                        ], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>)'>✎</button>
                        <form method="post" action="<?= url('actions/category_delete.php') ?>" style="display:inline"
                              onsubmit="return confirmDelete('Bu kategoriyi silmek istediğinize emin misiniz? İlgili işlemler kategorisiz olacaktır.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="icon-btn danger" title="Sil">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php
}
?>

<div class="card">
    <div class="card-head"><h3>💸 Gider Kategorileri</h3><span class="muted"><?= count($expense) ?> adet</span></div>
    <?php renderCatTable(array_values($expense), true); ?>
</div>

<div class="card mt-2">
    <div class="card-head"><h3>💰 Gelir Kategorileri</h3><span class="muted"><?= count($income) ?> adet</span></div>
    <?php renderCatTable(array_values($income), false); ?>
</div>

<!-- Otomatik kategori kuralları -->
<div class="card mt-2">
    <div class="card-head">
        <h3>🤖 Otomatik Kategori Kuralları</h3>
    </div>
    <div class="card-pad">
        <p class="muted" style="margin-top:0">PDF ekstre içe aktarırken açıklamada aşağıdaki kelimeler geçen işlemler otomatik olarak ilgili kategoriye atanır.</p>
        <form method="post" action="<?= url('actions/rule_save.php') ?>" class="flex flex-wrap" style="gap:10px;margin-bottom:18px">
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="categories.php">
            <input class="input" type="text" name="match_text" placeholder="Anahtar kelime (örn. MIGROS)" style="max-width:240px" required>
            <select class="input" name="category_id" style="max-width:240px" required>
                <option value="">Kategori seç…</option>
                <?php foreach ($all as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= $c['icon'] ?> <?= e($c['name']) ?> (<?= $c['type']==='income'?'gelir':'gider' ?>)</option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary">+ Kural Ekle</button>
        </form>

        <?php if ($rules): ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Anahtar Kelime</th><th>Kategori</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rules as $r): ?>
                    <tr>
                        <td><code style="background:var(--cream-2);padding:3px 8px;border-radius:6px"><?= e($r['match_text']) ?></code></td>
                        <td><span class="cat-badge"><span class="swatch" style="background:#e5e0d5"><?= $r['icon'] ?></span><?= e($r['cat_name']) ?></span></td>
                        <td class="num-right">
                            <form method="post" action="<?= url('actions/rule_delete.php') ?>" style="display:inline" onsubmit="return confirmDelete('Kural silinsin mi?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="return" value="categories.php">
                                <button class="icon-btn danger" title="Sil">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="muted">Henüz kural yok.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Kategori ekleme/düzenleme modalı -->
<div class="modal-overlay" id="catModal">
    <div class="modal">
        <div class="modal-head"><h3 id="catModalTitle">Yeni Kategori</h3><button class="x" onclick="closeModal('catModal')">×</button></div>
        <form method="post" action="<?= url('actions/category_save.php') ?>" id="catForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="field">
                    <label>Tür</label>
                    <div class="seg">
                        <label class="on-income"><input type="radio" name="type" value="income"><span>↑ Gelir</span></label>
                        <label class="on-expense"><input type="radio" name="type" value="expense" checked><span>↓ Gider</span></label>
                    </div>
                </div>
                <div class="field">
                    <label>Kategori Adı</label>
                    <input class="input" type="text" name="name" maxlength="120" required>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>İkon (emoji)</label>
                        <input class="input" type="text" name="icon" id="catIcon" maxlength="4" value="📁" style="font-size:18px">
                        <div class="flex flex-wrap" style="gap:5px;margin-top:8px">
                            <?php foreach (['🛒','🏠','🧾','⚡','💧','🔥','📶','⛽','💊','📚','👕','🎬','🔁','☕','🛠️','💼','📈','💰','🎁','🚗','✈️','🍔'] as $emo): ?>
                                <button type="button" class="icon-btn" onclick="document.getElementById('catIcon').value='<?= $emo ?>'"><?= $emo ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="field">
                        <label>Renk</label>
                        <input class="input" type="color" name="color" id="catColor" value="#C2410C" style="height:46px;padding:4px">
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('catModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
$inline_script = <<<JS
function newCategory(){
    const f=document.getElementById('catForm'); f.reset();
    f.querySelector('[name=id]').value='';
    document.getElementById('catIcon').value='📁';
    document.getElementById('catColor').value='#C2410C';
    setType('expense');
    document.getElementById('catModalTitle').textContent='Yeni Kategori';
    openModal('catModal');
}
function editCategory(d){
    const f=document.getElementById('catForm');
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=name]').value=d.name||'';
    document.getElementById('catIcon').value=d.icon||'📁';
    document.getElementById('catColor').value=d.color||'#C2410C';
    setType(d.type||'expense');
    document.getElementById('catModalTitle').textContent='Kategoriyi Düzenle';
    openModal('catModal');
}
JS;
require __DIR__ . '/templates/footer.php';
