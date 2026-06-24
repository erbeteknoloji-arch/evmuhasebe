<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rates.php';
require_household();

$householdId = hid();
$pdo = db();

$rates   = get_rates();             // gerekirse otomatik yeniler
$lastUpd = rates_last_updated();
$catalog = asset_catalog();

$hStmt = $pdo->prepare('SELECT * FROM asset_holdings WHERE household_id=? ORDER BY asset_code');
$hStmt->execute([$householdId]);
$holdings = $hStmt->fetchAll();

$totalValue = 0.0; $totalCost = 0.0;
foreach ($holdings as $h) {
    $val = asset_value_try($h['asset_code'], (float)$h['quantity'], $rates);
    $totalValue += $val;
    if ($h['cost_basis_try'] !== null) $totalCost += (float)$h['cost_basis_try'];
}

$page_title = t('page.assets.title');
$page_subtitle = 'Döviz ve altın birikimleriniz güncel kurdan';
$page_actions = '<button class="btn btn-primary" onclick="newAsset()">+ Varlık Ekle</button>';
$active = 'assets';
require __DIR__ . '/templates/header.php';
?>

<div class="grid cols-2-1 mt-2">
    <!-- VARLIKLARIM -->
    <div class="card">
        <div class="card-head"><h3>Varlıklarım</h3>
            <span class="muted" style="font-size:12.5px">Toplam değer: <b class="tabular" style="color:var(--forest)"><?= money($totalValue) ?></b></span>
        </div>
        <?php if (!$holdings): ?>
            <div class="card-pad"><p class="muted">Henüz varlık eklemediniz. "Varlık Ekle" ile döviz veya altın birikiminizi girin; güncel kurdan TL değerini görelim.</p></div>
        <?php else: ?>
        <table class="data">
            <thead><tr><th>Varlık</th><th class="r">Miktar</th><th class="r">Birim Kur</th><th class="r">Değer (TL)</th><th class="r">Kâr/Zarar</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($holdings as $h):
                $code = $h['asset_code']; $qty = (float)$h['quantity'];
                $rate = $rates[$code] ?? 0; $val = $qty * $rate;
                $cost = $h['cost_basis_try'] !== null ? (float)$h['cost_basis_try'] : null;
                $pl = $cost !== null ? $val - $cost : null;
                $unit = $catalog[$code][2] ?? '';
                $editData = json_encode([
                    'id'=>$h['id'],'asset_code'=>$code,
                    'quantity'=>rtrim(rtrim(number_format($qty,4,',','.'),'0'),','),
                    'cost_basis_try'=>$cost!==null?number_format($cost,2,',','.'):''
                ], JSON_HEX_APOS|JSON_HEX_QUOT);
            ?>
                <tr>
                    <td><b><?= e(asset_label($code)) ?></b></td>
                    <td class="r tabular"><?= rtrim(rtrim(number_format($qty,4,',','.'),'0'),',') ?> <span class="muted"><?= e($unit) ?></span></td>
                    <td class="r tabular"><?= $rate>0 ? money($rate) : '—' ?></td>
                    <td class="r tabular"><b><?= money($val) ?></b></td>
                    <td class="r tabular" style="color:<?= $pl===null?'var(--ink-faint)':($pl>=0?'var(--income)':'var(--expense)') ?>">
                        <?= $pl===null ? '—' : (($pl>=0?'+':'').money($pl)) ?>
                    </td>
                    <td class="r"><button class="btn btn-ghost btn-sm" onclick='editAsset(<?= $editData ?>)'>⋯</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($totalCost > 0): ?>
            <tfoot><tr>
                <td colspan="3" class="r"><b>Toplam</b></td>
                <td class="r tabular"><b><?= money($totalValue) ?></b></td>
                <td class="r tabular" style="color:<?= ($totalValue-$totalCost)>=0?'var(--income)':'var(--expense)' ?>">
                    <b><?= (($totalValue-$totalCost)>=0?'+':'').money($totalValue-$totalCost) ?></b></td>
                <td></td>
            </tr></tfoot>
            <?php endif; ?>
        </table>
        <?php endif; ?>
    </div>

    <!-- GÜNCEL KURLAR -->
    <div class="card card-pad">
        <div class="flex" style="justify-content:space-between;align-items:center">
            <h3 style="margin:0">Güncel Kurlar</h3>
            <form method="post" action="<?= url('actions/rates_refresh.php') ?>" style="display:inline">
                <?= csrf_field() ?><button class="btn btn-ghost btn-sm" title="İnternetten yenile">↻ Yenile</button>
            </form>
        </div>
        <p class="muted" style="font-size:12px;margin:4px 0 10px">
            <?= $lastUpd ? 'Son güncelleme: '.date('d.m.Y H:i', strtotime($lastUpd)) : 'Henüz çekilmedi' ?>
        </p>
        <div class="list">
            <?php foreach ($catalog as $code => $info):
                $r = $rates[$code] ?? 0; ?>
            <div class="item" style="padding:7px 0">
                <div class="grow"><b><?= e($info[0]) ?></b> <span class="muted" style="font-size:11.5px"><?= e($code) ?></span></div>
                <div class="tabular"><?= $r>0 ? money($r) : '<span class="muted">—</span>' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <details style="margin-top:10px">
            <summary class="muted" style="cursor:pointer;font-size:12.5px">Kuru elle güncelle</summary>
            <form method="post" action="<?= url('actions/rate_manual.php') ?>" class="mt-2">
                <?= csrf_field() ?>
                <div class="grid grid-2">
                    <select class="input" name="code">
                        <?php foreach ($catalog as $code => $info): ?>
                            <option value="<?= e($code) ?>"><?= e($info[0]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="input tabular" name="rate_try" placeholder="TL kuru, ör. 41,25">
                </div>
                <button class="btn btn-ghost btn-sm mt-2">Kuru Kaydet</button>
            </form>
        </details>
        <p class="muted" style="font-size:11.5px;margin-top:10px">Kurlar otomatik çekilir; sunucunuzun internet erişimi yoksa yukarıdan elle girebilirsiniz. Altın türleri gram altından yaklaşık hesaplanır (piyasa primi hariç).</p>
    </div>
</div>

<!-- MODAL: Varlık ekle/düzenle -->
<div class="modal-overlay" id="assetModal">
    <div class="modal">
        <div class="modal-head"><h3 id="assetTitle">Varlık Ekle</h3><button class="x" onclick="closeModal('assetModal')">×</button></div>
        <form method="post" action="<?= url('actions/asset_save.php') ?>" id="assetForm">
            <?= csrf_field() ?><input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="field"><label>Varlık Türü</label>
                    <select class="input" name="asset_code">
                        <optgroup label="Döviz">
                            <?php foreach ($catalog as $code => $info): if ($info[1]==='doviz'): ?>
                                <option value="<?= e($code) ?>"><?= e($info[0]) ?></option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                        <optgroup label="Kıymetli Maden">
                            <?php foreach ($catalog as $code => $info): if ($info[1]==='maden'): ?>
                                <option value="<?= e($code) ?>"><?= e($info[0]) ?></option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                    </select></div>
                <div class="grid grid-2">
                    <div class="field"><label>Miktar</label>
                        <input class="input tabular" name="quantity" inputmode="decimal" placeholder="ör. 10 veya 2,5" required></div>
                    <div class="field"><label>Toplam Maliyet (TL, ops.)</label>
                        <input class="input tabular" name="cost_basis_try" inputmode="decimal" placeholder="aldığınız tutar"></div>
                </div>
                <p class="muted" style="font-size:12px;margin:0">Maliyeti girerseniz kâr/zarar otomatik hesaplanır.</p>
            </div>
            <div class="modal-foot">
                <div id="assetDeleteWrap"></div><div class="grow"></div>
                <button type="button" class="btn btn-ghost" onclick="closeModal('assetModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
$delUrl = url('actions/asset_delete.php');
$csrf = csrf_token();
$inline_script = <<<JS
function newAsset(){
    const f=document.getElementById('assetForm'); f.reset();
    f.querySelector('[name=id]').value='';
    document.getElementById('assetTitle').textContent='Varlık Ekle';
    document.getElementById('assetDeleteWrap').innerHTML='';
    openModal('assetModal');
}
function editAsset(d){
    const f=document.getElementById('assetForm'); f.reset();
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=asset_code]').value=d.asset_code||'USD';
    f.querySelector('[name=quantity]').value=d.quantity||'';
    f.querySelector('[name=cost_basis_try]').value=d.cost_basis_try||'';
    document.getElementById('assetTitle').textContent='Varlığı Düzenle';
    document.getElementById('assetDeleteWrap').innerHTML=
        '<form method="post" action="$delUrl" onsubmit="return confirmDelete(\\'Bu varlık silinsin mi?\\')">'+
        '<input type="hidden" name="csrf_token" value="$csrf">'+
        '<input type="hidden" name="id" value="'+(d.id||'')+'">'+
        '<button class="btn btn-danger btn-sm" type="submit">Sil</button></form>';
    openModal('assetModal');
}
JS;
require __DIR__ . '/templates/footer.php';
