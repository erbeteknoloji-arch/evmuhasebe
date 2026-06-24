<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credit.php';
require_household();

$householdId = hid();
$pdo = db();

/* Kredi kartı hesap kesim tarihi geçtiyse eksik ekstreleri tembel üret */
cc_generate_statements($householdId);

$stmt = $pdo->prepare(
    "SELECT a.*,
            COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount END),0) inc,
            COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount END),0) exp,
            COUNT(t.id) cnt
       FROM accounts a
       LEFT JOIN transactions t ON t.account_id = a.id AND t.household_id = a.household_id
      WHERE a.household_id = ?
   GROUP BY a.id
   ORDER BY a.is_active DESC, a.name ASC"
);
$stmt->execute([$householdId]);
$accounts = $stmt->fetchAll();

$typeLabels = ['cash' => 'Nakit', 'bank' => 'Banka Hesabı', 'credit_card' => 'Kredi Kartı'];
$typeIcons  = ['cash' => '💵', 'bank' => '🏦', 'credit_card' => '💳'];

$page_title    = t('page.accounts.title');
$page_subtitle = count($accounts) . ' hesap';
$active        = 'accounts';
$page_actions  = '<button class="btn btn-ghost" onclick="openModal(\'transferModal\')">↔ Para Transferi</button>'
               . '<button class="btn btn-primary" onclick="newAccount()">+ Yeni Hesap</button>';

require __DIR__ . '/templates/header.php';
?>

<?php if ($accounts): ?>
<div class="grid grid-3">
    <?php foreach ($accounts as $a):
        $current = (float)$a['opening_balance'] + (float)$a['inc'] - (float)$a['exp'];
        $isCC    = $a['type'] === 'credit_card';
        $limit   = ($a['credit_limit'] !== null && $a['credit_limit'] !== '') ? (float)$a['credit_limit'] : null;
        $debt    = $isCC ? cc_debt($current) : 0.0;
        $avail   = $isCC ? cc_available($limit, $debt) : null;
        $minPct  = (float)($a['min_payment_pct'] ?? 20);
        $minPay  = $isCC ? cc_min_payment($debt, $minPct) : 0.0;
        $nextStmt = $isCC ? cc_next_statement_date($a) : null;
        $nextDue  = $isCC ? cc_next_due_date($a) : null;
        $usePct   = ($isCC && $limit && $limit > 0) ? min(100, round($debt / $limit * 100)) : 0;
    ?>
        <div class="card card-pad">
            <div class="flex-between">
                <span class="cat-badge"><span class="swatch" style="background:var(--cream-2)"><?= $typeIcons[$a['type']] ?></span><?= $typeLabels[$a['type']] ?></span>
                <div class="row-actions">
                    <button class="icon-btn" title="Düzenle" onclick='editAccount(<?= json_encode([
                        "id"=>$a["id"],"name"=>$a["name"],"type"=>$a["type"],"bank_name"=>$a["bank_name"],
                        "opening_balance"=>number_format((float)$a["opening_balance"],2,",","."),
                        "credit_limit"=>($limit!==null?number_format($limit,2,",","."):""),
                        "statement_day"=>($a["statement_day"]!==null?(int)$a["statement_day"]:""),
                        "due_day"=>($a["due_day"]!==null?(int)$a["due_day"]:""),
                        "min_payment_pct"=>number_format($minPct,2,",",".")
                    ], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>)'>✎</button>
                    <form method="post" action="<?= url('actions/account_delete.php') ?>" style="display:inline" onsubmit="return confirmDelete('Hesap silinsin mi? İşlemler hesapsız kalacaktır.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button class="icon-btn danger" title="Sil">🗑</button>
                    </form>
                </div>
            </div>
            <h3 style="margin:14px 0 2px;font-size:18px"><?= e($a['name']) ?></h3>
            <?php if ($a['bank_name']): ?><div class="muted" style="font-size:13px"><?= e($a['bank_name']) ?></div><?php endif; ?>

            <?php if ($isCC): ?>
                <div class="value tabular" style="font-family:var(--font-display);font-size:26px;font-weight:700;margin-top:14px;color:<?= $debt>0?'var(--expense)':'var(--forest)' ?>">
                    <?= money($debt) ?>
                </div>
                <div class="muted" style="font-size:12.5px;margin-top:2px">Güncel borç</div>

                <?php if ($limit !== null): ?>
                <div class="bar" style="margin-top:10px"><span class="<?= $usePct>=90?'over':'' ?>" style="width:<?= $usePct ?>%;<?= $usePct>=90?'':'background:var(--forest)' ?>"></span></div>
                <div class="flex-between" style="font-size:12.5px;margin-top:6px">
                    <span class="muted">Kullanılabilir</span>
                    <b class="tabular" style="color:<?= ($avail!==null&&$avail<0)?'var(--expense)':'var(--income)' ?>"><?= money($avail) ?></b>
                </div>
                <div class="flex-between" style="font-size:12.5px;margin-top:2px">
                    <span class="muted">Limit</span><span class="tabular"><?= money($limit) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex-between" style="font-size:12.5px;margin-top:2px">
                    <span class="muted">Asgari ödeme (%<?= rtrim(rtrim(number_format($minPct,2,',','.'),'0'),',') ?>)</span>
                    <b class="tabular"><?= money($minPay) ?></b>
                </div>
                <?php if ($nextStmt || $nextDue): ?>
                <div class="muted" style="font-size:12px;margin-top:8px;border-top:1px solid var(--cream-2);padding-top:8px">
                    <?php if ($nextStmt): ?>📄 Hesap kesim: <b><?= format_date($nextStmt) ?></b><?php endif; ?>
                    <?php if ($nextDue): ?><br>📅 Son ödeme: <b><?= format_date($nextDue) ?></b><?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="muted" style="font-size:12px;margin-top:6px"><?= (int)$a['cnt'] ?> işlem</div>
            <?php else: ?>
                <div class="value tabular" style="font-family:var(--font-display);font-size:26px;font-weight:700;margin-top:14px;color:<?= $current>=0?'var(--forest)':'var(--expense)' ?>">
                    <?= money($current) ?>
                </div>
                <div class="muted" style="font-size:12.5px;margin-top:4px">
                    Açılış: <?= money($a['opening_balance']) ?> · <?= (int)$a['cnt'] ?> işlem
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="card"><div class="empty"><div class="big">🏦</div><h3>Henüz hesap yok</h3>
        <p class="muted">Nakit cüzdanınızı, banka hesabınızı veya kredi kartınızı ekleyin.</p>
        <div style="margin-top:14px"><button class="btn btn-primary" onclick="newAccount()">Hesap Ekle</button></div>
    </div></div>
<?php endif; ?>

<!-- Para transferi modalı -->
<div class="modal-overlay" id="transferModal">
    <div class="modal">
        <div class="modal-head"><h3>Para Transferi</h3><button class="x" onclick="closeModal('transferModal')">×</button></div>
        <form method="post" action="<?= url('actions/transfer_save.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="accounts.php">
            <div class="modal-body">
                <?php if (count($accounts) < 2): ?>
                    <p class="muted">Transfer için en az iki hesabınız olmalı. Önce bir hesap daha ekleyin.</p>
                <?php else: ?>
                <div class="form-row">
                    <div class="field">
                        <label>Kaynak Hesap (çıkış)</label>
                        <select class="input" name="from_account" required>
                            <?php foreach ($accounts as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"><?= e($a['name']) ?> (<?= $typeLabels[$a['type']] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Hedef Hesap (giriş)</label>
                        <select class="input" name="to_account" required>
                            <?php foreach ($accounts as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"><?= e($a['name']) ?> (<?= $typeLabels[$a['type']] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Tutar</label>
                        <input class="input tabular" type="text" name="amount" inputmode="decimal" placeholder="0,00" required>
                    </div>
                    <div class="field">
                        <label>Tarih</label>
                        <input class="input" type="date" name="transaction_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="field">
                    <label>Açıklama <span class="hint">(opsiyonel)</span></label>
                    <input class="input" type="text" name="description" maxlength="200" placeholder="Örn. Kart borcu ödemesi">
                </div>
                <p class="muted" style="font-size:12px;margin:0">💡 Hedef bir kredi kartıysa bu işlem kartın borcunu azaltır (kart ödemesi). Transferler gelir/gider raporlarına dahil edilmez.</p>
                <?php endif; ?>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('transferModal')">Vazgeç</button>
                <?php if (count($accounts) >= 2): ?><button type="submit" class="btn btn-primary">Transferi Yap</button><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Hesap ekleme/düzenleme modalı -->
<div class="modal-overlay" id="accModal">
    <div class="modal">
        <div class="modal-head"><h3 id="accModalTitle">Yeni Hesap</h3><button class="x" onclick="closeModal('accModal')">×</button></div>
        <form method="post" action="<?= url('actions/account_save.php') ?>" id="accForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="field">
                    <label>Hesap Adı</label>
                    <input class="input" type="text" name="name" maxlength="120" placeholder="Örn. Maaş Hesabım" required>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Hesap Türü</label>
                        <select class="input" name="type" onchange="accTypeChange(this.value)">
                            <option value="bank">🏦 Banka Hesabı</option>
                            <option value="cash">💵 Nakit</option>
                            <option value="credit_card">💳 Kredi Kartı</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Banka Adı <span class="hint">(opsiyonel)</span></label>
                        <input class="input" type="text" name="bank_name" maxlength="120" placeholder="Örn. Garanti BBVA">
                    </div>
                </div>
                <div class="field">
                    <label id="openingLabel">Açılış Bakiyesi</label>
                    <input class="input tabular" type="text" name="opening_balance" inputmode="decimal" placeholder="0,00" value="0,00">
                    <span class="hint" id="openingHint">Hesabın şu anki başlangıç tutarı. Sonradan eklenen işlemler bunun üzerine eklenir.</span>
                </div>

                <!-- Kredi kartı alanları (yalnızca tür Kredi Kartı seçilince görünür) -->
                <div id="ccFields" style="display:none">
                    <div class="form-row">
                        <div class="field">
                            <label>Kredi Kartı Limiti</label>
                            <input class="input tabular" type="text" name="credit_limit" inputmode="decimal" placeholder="0,00">
                        </div>
                        <div class="field">
                            <label>Kullanılabilir Limit</label>
                            <input class="input tabular" type="text" id="availPreview" value="—" readonly>
                            <span class="hint">Limit − güncel borç (otomatik)</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Hesap Kesim Günü</label>
                            <input class="input" type="number" name="statement_day" min="1" max="31" placeholder="örn. 15">
                            <span class="hint">Ayın günü (1-31)</span>
                        </div>
                        <div class="field">
                            <label>Son Ödeme Günü <span class="hint">(ops.)</span></label>
                            <input class="input" type="number" name="due_day" min="1" max="31" placeholder="örn. 25">
                        </div>
                    </div>
                    <div class="field">
                        <label>Asgari Ödeme Yüzdesi</label>
                        <input class="input tabular" type="text" name="min_payment_pct" inputmode="decimal" value="20" placeholder="20">
                        <span class="hint">Türkiye bankalarında tipik asgari oran %20'dir (yüksek limitlerde %40 olabilir).</span>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('accModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
$inline_script = <<<JS
function accTypeChange(type){
    var cc=document.getElementById('ccFields');
    var lbl=document.getElementById('openingLabel');
    var hint=document.getElementById('openingHint');
    if(type==='credit_card'){
        cc.style.display='';
        lbl.textContent='Açılış Bakiyesi (borç için eksi girin)';
        hint.textContent='Mevcut borcunuz varsa eksi (-) tutar girin; borç otomatik hesaplanır.';
    } else {
        cc.style.display='none';
        lbl.textContent='Açılış Bakiyesi';
        hint.textContent='Hesabın şu anki başlangıç tutarı. Sonradan eklenen işlemler bunun üzerine eklenir.';
    }
    accCalcAvail();
}
function accParseNum(v){
    if(!v) return null;
    v=(''+v).replace(/[^\\d.,-]/g,'').replace(/\\./g,'').replace(',', '.');
    var n=parseFloat(v); return isNaN(n)?null:n;
}
function accCalcAvail(){
    var f=document.getElementById('accForm');
    var limit=accParseNum(f.querySelector('[name=credit_limit]').value);
    var open=accParseNum(f.querySelector('[name=opening_balance]').value);
    var debt=(open!==null && open<0)? -open : 0;
    var prev=document.getElementById('availPreview');
    if(limit===null){ prev.value='—'; return; }
    var avail=limit-debt;
    prev.value=avail.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' ₺';
}
document.addEventListener('input',function(e){
    if(e.target.matches('#accForm [name=credit_limit], #accForm [name=opening_balance]')) accCalcAvail();
});
function newAccount(){
    const f=document.getElementById('accForm'); f.reset();
    f.querySelector('[name=id]').value=''; f.querySelector('[name=opening_balance]').value='0,00';
    f.querySelector('[name=type]').value='bank';
    f.querySelector('[name=min_payment_pct]').value='20';
    document.getElementById('accModalTitle').textContent='Yeni Hesap';
    accTypeChange('bank');
    openModal('accModal');
}
function editAccount(d){
    const f=document.getElementById('accForm'); f.reset();
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=name]').value=d.name||'';
    f.querySelector('[name=type]').value=d.type||'bank';
    f.querySelector('[name=bank_name]').value=d.bank_name||'';
    f.querySelector('[name=opening_balance]').value=d.opening_balance||'0,00';
    f.querySelector('[name=credit_limit]').value=d.credit_limit||'';
    f.querySelector('[name=statement_day]').value=d.statement_day||'';
    f.querySelector('[name=due_day]').value=d.due_day||'';
    f.querySelector('[name=min_payment_pct]').value=d.min_payment_pct||'20';
    document.getElementById('accModalTitle').textContent='Hesabı Düzenle';
    accTypeChange(d.type||'bank');
    openModal('accModal');
}
JS;
require __DIR__ . '/templates/footer.php';
