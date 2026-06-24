<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/products.php';
require_household();
require_feature('shopping');

$householdId = hid();
$pdo = db();

/* Tüm listeler + ilerleme/tahmini tutar */
$lstmt = $pdo->prepare(
    "SELECT sl.*,
            (SELECT COUNT(*) FROM shopping_items si WHERE si.list_id=sl.id) AS total,
            (SELECT COUNT(*) FROM shopping_items si WHERE si.list_id=sl.id AND si.is_done=1) AS done_count,
            (SELECT COALESCE(SUM(est_price),0) FROM shopping_items si WHERE si.list_id=sl.id) AS est_total
       FROM shopping_lists sl
      WHERE sl.household_id=?
      ORDER BY sl.created_at DESC"
);
$lstmt->execute([$householdId]);
$lists = $lstmt->fetchAll();

/* Seçili liste */
$selId = (int)($_GET['list'] ?? 0);
$current = null;
foreach ($lists as $l) { if ((int)$l['id'] === $selId) { $current = $l; break; } }

$items = [];
if ($current) {
    $istmt = $pdo->prepare(
        "SELECT * FROM shopping_items WHERE list_id=? ORDER BY is_done ASC, position ASC, id ASC"
    );
    $istmt->execute([$current['id']]);
    $items = $istmt->fetchAll();
}

$csrf = csrf_token();

if ($current) {
    $page_title = $current['icon'] . ' ' . $current['name'];
    $page_subtitle = 'Alışveriş listesi';
    $page_actions = '<a class="btn btn-ghost" href="' . url('shopping.php') . '">← Tüm Listeler</a>'
                  . '<button class="btn btn-primary" onclick="openModal(\'addItemModal\')">+ Ürün Ekle</button>';
    $active = 'shopping';
} else {
    $page_title = t('page.shopping.title');
    $page_subtitle = 'Market ve ev alışverişlerinizi görsel ürünlerle planlayın';
    $page_actions = '<button class="btn btn-primary" onclick="newList()">+ Yeni Liste</button>';
    $active = 'shopping';
}
require __DIR__ . '/templates/header.php';
?>

<?php if (!$current): /* ---------- LİSTELER GENEL GÖRÜNÜM ---------- */ ?>

    <?php if (!$lists): ?>
    <div class="card card-pad mt-2" style="text-align:center">
        <div class="big" style="font-size:42px">🛒</div>
        <h3>Henüz alışveriş listeniz yok</h3>
        <p class="muted">İlk listenizi oluşturun; ürünleri renkli görselleriyle ekleyin, markette tek tek işaretleyin.</p>
        <button class="btn btn-primary" onclick="newList()">+ İlk Listeni Oluştur</button>
    </div>
    <?php else: ?>
    <div class="shop-lists mt-2">
        <?php foreach ($lists as $l):
            $total = (int)$l['total']; $done = (int)$l['done_count'];
            $pct = $total > 0 ? round($done / $total * 100) : 0;
            $editData = json_encode([
                'id'=>$l['id'],'name'=>$l['name'],'icon'=>$l['icon'],'color'=>$l['color']
            ], JSON_HEX_APOS|JSON_HEX_QUOT);
        ?>
        <a class="shop-list-card" href="<?= url('shopping.php?list=' . (int)$l['id']) ?>">
            <div class="card card-pad" style="border-top:4px solid <?= e($l['color']) ?>">
                <div class="flex" style="justify-content:space-between;align-items:flex-start">
                    <div class="flex" style="gap:12px">
                        <div class="icon-badge" style="background:<?= e($l['color']) ?>22"><?= e($l['icon']) ?></div>
                        <div>
                            <h3 style="margin:0"><?= e($l['name']) ?></h3>
                            <span class="muted" style="font-size:12.5px">
                                <?= $total ? ($done . ' / ' . $total . ' alındı') : 'Boş liste' ?>
                            </span>
                        </div>
                    </div>
                    <button class="icon-btn" type="button"
                        onclick='event.preventDefault();editList(<?= $editData ?>)' title="Düzenle">⋯</button>
                </div>
                <div class="shop-prog"><i style="width:<?= $pct ?>%;background:<?= e($l['color']) ?>"></i></div>
                <div class="flex" style="justify-content:space-between;font-size:12.5px;margin-top:8px">
                    <span class="muted">%<?= $pct ?> tamamlandı</span>
                    <?php if ((float)$l['est_total'] > 0): ?>
                        <span class="muted tabular">~ <?= money($l['est_total']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php else: /* ---------- TEK LİSTE DETAYI ---------- */
    $total = count($items);
    $done = 0; $est = 0.0;
    foreach ($items as $it) { if ($it['is_done']) $done++; $est += (float)$it['est_price']; }
    $pct = $total > 0 ? round($done / $total * 100) : 0;
    $pending = array_filter($items, fn($i) => !$i['is_done']);
    $completed = array_filter($items, fn($i) => $i['is_done']);

    $listEdit = json_encode(['id'=>$current['id'],'name'=>$current['name'],'icon'=>$current['icon'],'color'=>$current['color']], JSON_HEX_APOS|JSON_HEX_QUOT);
?>
    <div class="card card-pad mt-2">
        <div class="flex" style="justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div class="flex" style="gap:12px">
                <div class="icon-badge" style="background:<?= e($current['color']) ?>22"><?= e($current['icon']) ?></div>
                <div>
                    <b style="font-size:18px"><span id="cntDone"><?= $done ?></span> / <span id="cntTotal"><?= $total ?></span> ürün alındı</b>
                    <div class="muted" style="font-size:13px">Tahmini tutar: <span class="tabular" id="estTotal"><?= money($est) ?></span></div>
                </div>
            </div>
            <div class="flex" style="gap:8px">
                <button class="btn btn-ghost btn-sm" onclick='editList(<?= $listEdit ?>)'>Listeyi Düzenle</button>
                <?php if ($items): ?>
                <form method="post" action="<?= url('actions/shopping_email.php') ?>" style="display:inline"
                      onsubmit="return confirm('Liste tüm aktif ev üyelerine e-posta ile gönderilsin mi?')">
                    <?= csrf_field() ?><input type="hidden" name="list_id" value="<?= (int)$current['id'] ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">✉️ E-posta ile Gönder</button>
                </form>
                <?php endif; ?>
                <?php if ($completed): ?>
                <form method="post" action="<?= url('actions/shopping_clear_done.php') ?>" style="display:inline"
                      onsubmit="return confirmDelete('Tamamlanan ürünler listeden silinsin mi?')">
                    <?= csrf_field() ?><input type="hidden" name="list_id" value="<?= (int)$current['id'] ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">Tamamlananları Temizle</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="shop-prog"><i id="progBar" style="width:<?= $pct ?>%;background:<?= e($current['color']) ?>"></i></div>
    </div>

    <?php if (!$items): ?>
    <div class="card card-pad mt-2" style="text-align:center">
        <div class="big" style="font-size:38px">🧺</div>
        <h3>Liste boş</h3>
        <p class="muted">Aşağıdaki butonla görsel ürün kataloğundan ürün ekleyin.</p>
        <button class="btn btn-primary" onclick="openModal('addItemModal')">+ Ürün Ekle</button>
    </div>
    <?php endif; ?>

    <div id="pendingWrap" style="<?= $pending ? '' : 'display:none' ?>">
        <div class="shop-section-title">Alınacaklar (<span id="pendingCount"><?= count($pending) ?></span>)</div>
        <div class="shop-items" id="pendingItems">
            <?php foreach ($pending as $it) renderShopItem($it, $csrf); ?>
        </div>
    </div>

    <div id="doneWrap" style="<?= $completed ? '' : 'display:none' ?>">
        <div class="shop-section-title">Sepete Eklenenler (<span id="doneSecCount"><?= count($completed) ?></span>)</div>
        <div class="shop-items" id="doneItems">
            <?php foreach ($completed as $it) renderShopItem($it, $csrf); ?>
        </div>
    </div>

    <!-- MODAL: Ürün ekle (görsel katalog) -->
    <div class="modal-overlay" id="addItemModal">
        <div class="modal modal-wide">
            <div class="modal-head"><h3>Ürün Ekle</h3><button class="x" onclick="closeModal('addItemModal')">×</button></div>
            <div class="modal-body">
                <!-- Özel ürün ekleme (katalogda olmayanlar / miktar-fiyat ile) -->
                <form method="post" action="<?= url('actions/shopping_item_save.php') ?>" id="customItemForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="list_id" value="<?= (int)$current['id'] ?>">
                    <input type="hidden" name="icon" id="customIcon" value="">
                    <input type="hidden" name="color" id="customColor" value="">
                    <div class="form-row-3">
                        <div class="field" style="margin-bottom:0">
                            <label>Ürün adı</label>
                            <input class="input" name="name" id="customName" placeholder="ör. Yumurta, Çamaşır suyu" autocomplete="off">
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Miktar (ops.)</label>
                            <input class="input" name="qty" placeholder="ör. 2 kg, 1 paket">
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Tahmini fiyat (ops.)</label>
                            <input class="input tabular" name="est_price" inputmode="decimal" placeholder="0,00">
                        </div>
                    </div>
                    <div class="flex" style="justify-content:flex-end;margin-top:10px">
                        <button class="btn btn-ghost btn-sm" type="submit">Özel Ürünü Ekle</button>
                    </div>
                </form>

                <div class="cat-search-row">
                    <input class="input" id="catSearch" placeholder="🔎 Ürün ara… (ör. süt, deterjan)" autocomplete="off"
                           oninput="catalogFilter(this.value)">
                    <div class="cat-chips" id="catChips">
                        <button type="button" class="cat-chip active" data-cat="all" onclick="catalogChip('all',this)">Tümü</button>
                        <?php foreach (product_categories() as $ck => $c): ?>
                        <button type="button" class="cat-chip" data-cat="<?= e($ck) ?>" onclick="catalogChip('<?= e($ck) ?>',this)"><?= e($c[0]) ?> <?= e($c[1]) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="cat-scroll" id="catScroll">
                    <?php foreach (product_catalog() as $ck => $prods):
                        $c = product_categories()[$ck];
                        $tile = $c[2];
                    ?>
                    <div class="cat-group" data-cat="<?= e($ck) ?>">
                        <div class="cat-group-title"><?= e($c[0]) ?> <?= e($c[1]) ?></div>
                        <div class="prod-grid">
                            <?php foreach ($prods as $p): ?>
                            <button type="button" class="prod-tile"
                                    data-name="<?= e($p[1]) ?>"
                                    data-icon="<?= e($p[0]) ?>"
                                    data-color="<?= e($tile) ?>"
                                    onclick="quickAdd(this)">
                                <span class="emoji" style="background:<?= e($tile) ?>"><?= e($p[0]) ?></span>
                                <span class="pname"><?= e($p[1]) ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="cat-empty" id="catEmpty">Eşleşen ürün yok. Yukarıdan “Özel Ürünü Ekle” ile ekleyebilirsiniz.</div>
                </div>
            </div>
            <div class="modal-foot">
                <span class="muted" style="font-size:12.5px;margin-right:auto">İpucu: Bir ürüne dokunmanız listeye eklemek için yeterli.</span>
                <button type="button" class="btn btn-primary" onclick="closeModal('addItemModal')">Bitti</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Ürün düzenle -->
    <div class="modal-overlay" id="editItemModal">
        <div class="modal">
            <div class="modal-head"><h3>Ürünü Düzenle</h3><button class="x" onclick="closeModal('editItemModal')">×</button></div>
            <form method="post" action="<?= url('actions/shopping_item_save.php') ?>" id="editItemForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="">
                <input type="hidden" name="list_id" value="<?= (int)$current['id'] ?>">
                <div class="modal-body">
                    <div class="form-row-3">
                        <div class="field"><label>İkon</label><input class="input" name="icon" maxlength="4" style="text-align:center;font-size:20px"></div>
                        <div class="field" style="grid-column:span 2"><label>Ürün adı</label><input class="input" name="name" required></div>
                    </div>
                    <div class="form-row-3">
                        <div class="field"><label>Miktar</label><input class="input" name="qty" placeholder="ör. 2 kg"></div>
                        <div class="field"><label>Tahmini fiyat</label><input class="input tabular" name="est_price" inputmode="decimal" placeholder="0,00"></div>
                        <div class="field"><label>Renk</label><input class="input" type="color" name="color" style="height:44px;padding:4px"></div>
                    </div>
                    <div class="field" style="margin-bottom:0"><label>Not (ops.)</label><input class="input" name="note" placeholder="ör. markası önemli"></div>
                </div>
                <div class="modal-foot">
                    <div id="itemDeleteWrap"></div><div class="grow"></div>
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editItemModal')">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL: Liste oluştur/düzenle (her iki görünümde de gerekli) -->
<div class="modal-overlay" id="listModal">
    <div class="modal">
        <div class="modal-head"><h3 id="listModalTitle">Yeni Liste</h3><button class="x" onclick="closeModal('listModal')">×</button></div>
        <form method="post" action="<?= url('actions/shopping_list_save.php') ?>" id="listForm">
            <?= csrf_field() ?><input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="field"><label>Liste Adı</label>
                    <input class="input" name="name" placeholder="ör. Haftalık Market, Kahvaltılık" required></div>
                <div class="form-row-3">
                    <div class="field" style="margin-bottom:0"><label>İkon</label>
                        <input class="input" name="icon" maxlength="4" value="🛒" style="text-align:center;font-size:20px"></div>
                    <div class="field" style="grid-column:span 2;margin-bottom:0"><label>Renk</label>
                        <input class="input" type="color" name="color" value="#14452F" style="height:44px;padding:4px"></div>
                </div>
            </div>
            <div class="modal-foot">
                <div id="listDeleteWrap"></div><div class="grow"></div>
                <button type="button" class="btn btn-ghost" onclick="closeModal('listModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
/* Ürün satırı şablonu (PHP tarafı) */
function renderShopItem(array $it, string $csrf): void
{
    $toggleUrl = url('actions/shopping_item_toggle.php');
    $done = (int)$it['is_done'] === 1;
    $meta = [];
    if (!empty($it['qty']))  $meta[] = e($it['qty']);
    if (!empty($it['note'])) $meta[] = e($it['note']);
    $metaHtml = $meta ? '<span>' . implode(' · ', $meta) . '</span>' : '';
    $priceHtml = ($it['est_price'] !== null && (float)$it['est_price'] > 0)
        ? '<span class="price tabular">' . money($it['est_price']) . '</span>' : '';
    $sep = ($metaHtml && $priceHtml) ? ' · ' : '';
    $editData = json_encode([
        'id'=>$it['id'],'name'=>$it['name'],'icon'=>$it['icon'],'color'=>$it['color'],
        'qty'=>$it['qty'],'note'=>$it['note'],
        'est_price'=>($it['est_price']!==null ? number_format((float)$it['est_price'],2,',','.') : '')
    ], JSON_HEX_APOS|JSON_HEX_QUOT);
    ?>
    <div class="shop-item <?= $done ? 'done' : '' ?>" id="item<?= (int)$it['id'] ?>" data-id="<?= (int)$it['id'] ?>">
        <form method="post" action="<?= $toggleUrl ?>" class="si-check-form" style="margin:0">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
            <button class="si-check" type="submit" title="İşaretle" aria-label="Alındı olarak işaretle">✓</button>
        </form>
        <div class="si-thumb" style="background:<?= e($it['color']) ?>"><?= e($it['icon']) ?></div>
        <div class="si-body">
            <div class="si-name"><?= e($it['name']) ?></div>
            <?php if ($metaHtml || $priceHtml): ?>
            <div class="si-meta"><?= $metaHtml . $sep . $priceHtml ?></div>
            <?php endif; ?>
        </div>
        <div class="si-actions">
            <button class="icon-btn" type="button" onclick='editItem(<?= $editData ?>)' title="Düzenle">✏️</button>
            <form method="post" action="<?= url('actions/shopping_item_delete.php') ?>" style="margin:0"
                  onsubmit="return confirmDelete('Bu ürün silinsin mi?')">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                <button class="icon-btn danger" type="submit" title="Sil">🗑️</button>
            </form>
        </div>
    </div>
    <?php
}

$delListUrl  = url('actions/shopping_list_delete.php');
$delItemUrl  = url('actions/shopping_item_delete.php');
$addItemUrl  = url('actions/shopping_item_save.php');
$toggleUrl   = url('actions/shopping_item_toggle.php');
$pollUrl     = url('actions/shopping_poll.php');
$listIdJs    = $current ? (int)$current['id'] : 0;
$csrfJs      = $csrf;

$inline_script = <<<JS
/* ---- Liste oluştur / düzenle ---- */
function newList(){
    var f=document.getElementById('listForm'); f.reset();
    f.querySelector('[name=id]').value='';
    f.querySelector('[name=icon]').value='🛒';
    f.querySelector('[name=color]').value='#14452F';
    document.getElementById('listModalTitle').textContent='Yeni Liste';
    document.getElementById('listDeleteWrap').innerHTML='';
    openModal('listModal');
}
function editList(d){
    var f=document.getElementById('listForm'); f.reset();
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=name]').value=d.name||'';
    f.querySelector('[name=icon]').value=d.icon||'🛒';
    f.querySelector('[name=color]').value=d.color||'#14452F';
    document.getElementById('listModalTitle').textContent='Listeyi Düzenle';
    document.getElementById('listDeleteWrap').innerHTML=
        '<form method="post" action="$delListUrl" onsubmit="return confirmDelete(\\'Liste ve içindeki tüm ürünler silinsin mi?\\')">'+
        '<input type="hidden" name="csrf_token" value="$csrfJs">'+
        '<input type="hidden" name="id" value="'+(d.id||'')+'">'+
        '<button class="btn btn-danger btn-sm" type="submit">Listeyi Sil</button></form>';
    openModal('listModal');
}

/* ---- Ürün düzenle ---- */
function editItem(d){
    var f=document.getElementById('editItemForm'); if(!f) return; f.reset();
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=name]').value=d.name||'';
    f.querySelector('[name=icon]').value=d.icon||'🛒';
    f.querySelector('[name=color]').value=(d.color&&/^#/.test(d.color))?d.color:'#EFEBE0';
    f.querySelector('[name=qty]').value=d.qty||'';
    f.querySelector('[name=note]').value=d.note||'';
    f.querySelector('[name=est_price]').value=d.est_price||'';
    document.getElementById('itemDeleteWrap').innerHTML=
        '<form method="post" action="$delItemUrl" onsubmit="return confirmDelete(\\'Bu ürün silinsin mi?\\')">'+
        '<input type="hidden" name="csrf_token" value="$csrfJs">'+
        '<input type="hidden" name="item_id" value="'+(d.id||'')+'">'+
        '<button class="btn btn-danger btn-sm" type="submit">Sil</button></form>';
    openModal('editItemModal');
}

/* ---- Katalog filtre ---- */
function catalogFilter(q){
    q=(q||'').toLocaleLowerCase('tr');
    var any=false;
    document.querySelectorAll('#catScroll .cat-group').forEach(function(g){
        var groupHas=false;
        g.querySelectorAll('.prod-tile').forEach(function(t){
            var name=(t.getAttribute('data-name')||'').toLocaleLowerCase('tr');
            var show=name.indexOf(q)!==-1;
            t.style.display=show?'':'none';
            if(show) groupHas=true;
        });
        g.style.display=groupHas?'':'none';
        if(groupHas) any=true;
    });
    var emp=document.getElementById('catEmpty');
    if(emp) emp.style.display=any?'none':'block';
}
function catalogChip(cat,btn){
    document.querySelectorAll('#catChips .cat-chip').forEach(function(c){c.classList.remove('active');});
    if(btn) btn.classList.add('active');
    var s=document.getElementById('catSearch'); if(s) s.value='';
    var any=false;
    document.querySelectorAll('#catScroll .cat-group').forEach(function(g){
        g.querySelectorAll('.prod-tile').forEach(function(t){t.style.display='';});
        var show=(cat==='all')||(g.getAttribute('data-cat')===cat);
        g.style.display=show?'':'none';
        if(show) any=true;
    });
    var emp=document.getElementById('catEmpty'); if(emp) emp.style.display=any?'none':'block';
    var sc=document.getElementById('catScroll'); if(sc) sc.scrollTop=0;
}

/* ---- Sayaçları güncelle ---- */
function shopRecount(){
    var pend=document.querySelectorAll('#pendingItems .shop-item').length;
    var done=document.querySelectorAll('#doneItems .shop-item').length;
    var total=pend+done;
    var set=function(id,v){var el=document.getElementById(id); if(el) el.textContent=v;};
    set('cntDone',done); set('cntTotal',total);
    set('pendingCount',pend); set('doneSecCount',done);
    var pw=document.getElementById('pendingWrap'); if(pw) pw.style.display=pend?'':'none';
    var dw=document.getElementById('doneWrap');   if(dw) dw.style.display=done?'':'none';
    var bar=document.getElementById('progBar'); if(bar) bar.style.width=(total? Math.round(done/total*100):0)+'%';
}

/* ---- Yeni ürün satırı (DOM) ---- */
function buildItemRow(item){
    var div=document.createElement('div');
    div.className='shop-item';
    div.id='item'+item.id;
    div.setAttribute('data-id',item.id);
    var meta=[];
    if(item.qty) meta.push(item.qty);
    if(item.note) meta.push(item.note);
    var metaStr=meta.join(' · ');
    var price=item.price?('<span class="price tabular">'+item.price+'</span>'):'';
    var sep=(metaStr&&price)?' · ':'';
    var ed=JSON.stringify(item).replace(/'/g,'&#39;');
    div.innerHTML=
        '<form method="post" action="$toggleUrl" class="si-check-form" style="margin:0">'+
        '<input type="hidden" name="csrf_token" value="$csrfJs">'+
        '<input type="hidden" name="item_id" value="'+item.id+'">'+
        '<button class="si-check" type="submit" title="İşaretle">✓</button></form>'+
        '<div class="si-thumb" style="background:'+item.color+'">'+item.icon+'</div>'+
        '<div class="si-body"><div class="si-name">'+escapeHtml(item.name)+'</div>'+
        ((metaStr||price)?('<div class="si-meta">'+escapeHtml(metaStr)+sep+price+'</div>'):'')+'</div>'+
        '<div class="si-actions">'+
        '<button class="icon-btn" type="button" title="Düzenle" onclick=\\'editItem('+ed+')\\'>✏️</button>'+
        '<form method="post" action="$delItemUrl" style="margin:0" onsubmit="return confirmDelete(\\'Bu ürün silinsin mi?\\')">'+
        '<input type="hidden" name="csrf_token" value="$csrfJs">'+
        '<input type="hidden" name="item_id" value="'+item.id+'">'+
        '<button class="icon-btn danger" type="submit" title="Sil">🗑️</button></form></div>';
    return div;
}
function escapeHtml(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ---- Katalogdan hızlı ekleme (AJAX, yedek: form gönderimi) ---- */
function quickAdd(btn){
    var name=btn.getAttribute('data-name');
    var icon=btn.getAttribute('data-icon');
    var color=btn.getAttribute('data-color');
    btn.style.opacity='.5';
    var body=new URLSearchParams();
    body.set('ajax','1'); body.set('csrf_token','$csrfJs');
    body.set('list_id','$listIdJs'); body.set('name',name);
    body.set('icon',icon); body.set('color',color);
    fetch('$addItemUrl',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
        .then(function(r){return r.json();})
        .then(function(d){
            btn.style.opacity='';
            if(!d||!d.ok){ quickAddFallback(name,icon,color); return; }
            var pi=document.getElementById('pendingItems');
            if(pi){ pi.appendChild(buildItemRow(d.item)); shopRecount(); flashTiny(name+' eklendi'); }
            else { window.location='shopping.php?list=$listIdJs'; }
        })
        .catch(function(){ btn.style.opacity=''; quickAddFallback(name,icon,color); });
}
function quickAddFallback(name,icon,color){
    var f=document.getElementById('customItemForm');
    f.querySelector('[name=name]').value=name;
    document.getElementById('customIcon').value=icon;
    document.getElementById('customColor').value=color;
    f.submit();
}

/* ---- İşaretleme (AJAX, yedek: form gönderimi) ---- */
document.addEventListener('submit', function(e){
    var f=e.target;
    if(!f.classList || !f.classList.contains('si-check-form')) return;
    e.preventDefault();
    var id=f.querySelector('[name=item_id]').value;
    var row=document.getElementById('item'+id);
    var body=new URLSearchParams();
    body.set('ajax','1'); body.set('csrf_token','$csrfJs'); body.set('item_id',id);
    fetch('$toggleUrl',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d||!d.ok){ HTMLFormElement.prototype.submit.call(f); return; }
            if(!row) return;
            if(d.is_done){ row.classList.add('done'); var dc=document.getElementById('doneItems'); if(dc) dc.appendChild(row); }
            else { row.classList.remove('done'); var pc=document.getElementById('pendingItems'); if(pc) pc.appendChild(row); }
            shopRecount();
        })
        .catch(function(){ HTMLFormElement.prototype.submit.call(f); });
});

/* ---- Canlı senkronizasyon (paylaşımlı liste) ----
   Aynı evin başka bir üyesi ürün ekleyince/işaretleyince/silince,
   bu liste sayfa yenilemeden ~8 sn'de bir güncellenir. */
function shopApplyPoll(items){
    var pend=document.getElementById('pendingItems');
    var donec=document.getElementById('doneItems');
    if(!pend||!donec) return;
    // Kullanıcı bir modalde düzenleme yapıyorsa bu turu atla
    if(document.querySelector('.modal-overlay.open')) return;
    pend.innerHTML=''; donec.innerHTML='';
    items.forEach(function(it){
        var row=buildItemRow(it);
        if(it.is_done){ row.classList.add('done'); donec.appendChild(row); }
        else { pend.appendChild(row); }
    });
    shopRecount();
}
var __shopSig=null, __shopBusy=false;
function shopPoll(){
    if(!$listIdJs || __shopBusy) return;
    __shopBusy=true;
    fetch('$pollUrl?list=$listIdJs',{headers:{'X-Requested-With':'fetch'}})
        .then(function(r){return r.json();})
        .then(function(d){
            __shopBusy=false;
            if(!d||!d.ok) return;
            if(__shopSig===null){ __shopSig=d.sig; return; } // ilk turda DOM zaten güncel
            if(d.sig!==__shopSig){ __shopSig=d.sig; shopApplyPoll(d.items); }
        })
        .catch(function(){ __shopBusy=false; });
}
if($listIdJs){
    // Sekme görünürse yokla (gizli sekmelerde gereksiz istek yapma)
    setInterval(function(){ if(!document.hidden) shopPoll(); }, 8000);
}
JS;
require __DIR__ . '/templates/footer.php';
