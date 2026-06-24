<?php
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

$gStmt = $pdo->prepare(
    "SELECT g.*,
            COALESCE((SELECT SUM(amount) FROM goal_contributions gc WHERE gc.goal_id=g.id),0) AS saved,
            (SELECT MAX(contributed_on) FROM goal_contributions gc WHERE gc.goal_id=g.id) AS last_on
       FROM savings_goals g
      WHERE g.household_id=?
      ORDER BY g.status='reached', g.created_at DESC"
);
$gStmt->execute([$householdId]);
$goals = $gStmt->fetchAll();

// Son katkılar (her hedef için ayrı çekmek yerine toplu)
$contribByGoal = [];
if ($goals) {
    $ids = array_column($goals, 'id');
    $in = implode(',', array_map('intval', $ids));
    $cs = $pdo->query(
        "SELECT gc.*, u.name AS uname FROM goal_contributions gc
           LEFT JOIN users u ON u.id=gc.user_id
          WHERE gc.goal_id IN ($in) ORDER BY gc.contributed_on DESC, gc.id DESC"
    )->fetchAll();
    foreach ($cs as $c) {
        $contribByGoal[$c['goal_id']][] = $c;
    }
}

$totalTarget = array_sum(array_map(fn($g) => (float)$g['target_amount'], $goals));
$totalSaved  = array_sum(array_map(fn($g) => (float)$g['saved'], $goals));

$page_title = t('page.goals.title');
$page_subtitle = 'Hayallerinize ne kadar yaklaştığınızı görün';
$page_actions = '<button class="btn btn-primary" onclick="newGoal()">+ Yeni Hedef</button>';
$active = 'goals';
require __DIR__ . '/templates/header.php';
?>

<?php if ($goals): ?>
<div class="grid grid-3 mt-2">
    <div class="stat balance"><div class="label">Toplam Hedef</div><div class="value tabular"><?= money($totalTarget) ?></div></div>
    <div class="stat income"><div class="label">Toplam Birikim</div><div class="value tabular"><?= money($totalSaved) ?></div>
        <div class="meta"><?= $totalTarget>0 ? '%'.number_format($totalSaved/$totalTarget*100,0).' tamamlandı' : '' ?></div></div>
    <div class="stat expense"><div class="label">Kalan</div><div class="value tabular"><?= money(max(0,$totalTarget-$totalSaved)) ?></div></div>
</div>
<?php endif; ?>

<?php if (!$goals): ?>
<div class="card card-pad mt-2" style="text-align:center">
    <div class="big" style="font-size:42px">🎯</div>
    <h3>Henüz birikim hedefiniz yok</h3>
    <p class="muted">Örneğin bir araba, tatil veya acil durum fonu için hedef koyun; ne kadar biriktirdiğinizi takip edelim.</p>
    <button class="btn btn-primary" onclick="newGoal()">+ İlk Hedefini Oluştur</button>
</div>
<?php else: ?>
<div class="grid grid-2 mt-2">
    <?php foreach ($goals as $g):
        $saved = (float)$g['saved']; $target = (float)$g['target_amount'];
        $pct = $target > 0 ? min(100, $saved / $target * 100) : 0;
        $remaining = max(0, $target - $saved);
        $reached = $g['status'] === 'reached' || $saved >= $target;
        $dleft = days_until($g['target_date']);
        $reqMonthly = null;
        if ($g['target_date'] && $remaining > 0 && $dleft !== null && $dleft > 0) {
            $monthsLeft = max(1, $dleft / 30.4);
            $reqMonthly = $remaining / $monthsLeft;
        }
        $editData = json_encode([
            'id'=>$g['id'],'name'=>$g['name'],
            'target_amount'=>number_format($target,2,',','.'),
            'target_date'=>$g['target_date'],'color'=>$g['color'],'icon'=>$g['icon']
        ], JSON_HEX_APOS|JSON_HEX_QUOT);
    ?>
    <div class="card card-pad goal-card" style="border-top:4px solid <?= e($g['color']) ?>">
        <div class="flex" style="justify-content:space-between;align-items:flex-start">
            <div class="flex" style="gap:10px">
                <div class="goal-icon" style="background:<?= e($g['color']) ?>22"><?= e($g['icon']) ?></div>
                <div>
                    <h3 style="margin:0"><?= e($g['name']) ?> <?= $reached?'<span class="pill" style="background:var(--income-bg);color:var(--income)">Tamamlandı ✓</span>':'' ?></h3>
                    <span class="muted" style="font-size:12.5px">
                        <?= $g['target_date'] ? 'Hedef: '.format_date($g['target_date']).($dleft!==null && $dleft>=0?' · '.$dleft.' gün':($dleft!==null?' · süre doldu':'')) : 'Tarih hedefi yok' ?>
                    </span>
                </div>
            </div>
            <button class="btn btn-ghost btn-sm" onclick='editGoal(<?= $editData ?>)'>⋯</button>
        </div>

        <div class="goal-prog"><div class="goal-prog-bar" style="width:<?= $pct ?>%;background:<?= e($g['color']) ?>"></div></div>
        <div class="flex" style="justify-content:space-between;font-size:13px;margin-top:6px">
            <b class="tabular"><?= money($saved) ?></b>
            <span class="muted tabular">/ <?= money($target) ?> · %<?= number_format($pct,0) ?></span>
        </div>

        <div class="flex" style="gap:14px;margin-top:10px;flex-wrap:wrap">
            <div><div class="muted" style="font-size:11.5px">Kalan</div><b class="tabular"><?= money($remaining) ?></b></div>
            <?php if ($reqMonthly !== null): ?>
            <div><div class="muted" style="font-size:11.5px">Hedef için aylık</div><b class="tabular"><?= money($reqMonthly) ?></b></div>
            <?php endif; ?>
            <?php if ($g['last_on']): ?>
            <div><div class="muted" style="font-size:11.5px">Son ekleme</div><b><?= format_date($g['last_on']) ?></b></div>
            <?php endif; ?>
        </div>

        <div class="flex mt-2" style="gap:8px">
            <?php if (!$reached): ?>
                <button class="btn btn-primary btn-sm" onclick="contributeGoal(<?= (int)$g['id'] ?>, '<?= e(addslashes($g['name'])) ?>')">+ Para Ekle</button>
            <?php endif; ?>
            <button class="btn btn-ghost btn-sm" onclick="toggleHist(<?= (int)$g['id'] ?>)">Geçmiş</button>
        </div>

        <div class="goal-hist" id="hist<?= (int)$g['id'] ?>" style="display:none">
            <?php if (empty($contribByGoal[$g['id']])): ?>
                <p class="muted" style="font-size:12.5px;margin:8px 0 0">Henüz katkı yok.</p>
            <?php else: ?>
                <div class="list" style="margin-top:8px">
                    <?php foreach (array_slice($contribByGoal[$g['id']],0,6) as $c): ?>
                    <div class="item" style="padding:6px 0">
                        <div class="grow"><b class="tabular" style="color:<?= $c['amount']<0?'var(--expense)':'var(--income)' ?>"><?= ($c['amount']<0?'':'+').money($c['amount']) ?></b>
                            <span><?= format_date($c['contributed_on']) ?><?= $c['note']?' · '.e($c['note']):'' ?><?= $c['uname']?' · '.e($c['uname']):'' ?></span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MODAL: Hedef ekle/düzenle -->
<div class="modal-overlay" id="goalModal">
    <div class="modal">
        <div class="modal-head"><h3 id="goalTitle">Yeni Hedef</h3><button class="x" onclick="closeModal('goalModal')">×</button></div>
        <form method="post" action="<?= url('actions/goal_save.php') ?>" id="goalForm">
            <?= csrf_field() ?><input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="field"><label>Hedef Adı</label>
                    <input class="input" name="name" placeholder="ör. Araba, Tatil, Acil Durum Fonu" required></div>
                <div class="grid grid-2">
                    <div class="field"><label>Hedef Tutar</label>
                        <input class="input tabular" name="target_amount" inputmode="decimal" placeholder="0,00" required></div>
                    <div class="field"><label>Hedef Tarih (ops.)</label>
                        <input class="input" type="date" name="target_date"></div>
                </div>
                <div class="grid grid-2">
                    <div class="field"><label>İkon</label>
                        <input class="input" name="icon" maxlength="4" value="🎯"></div>
                    <div class="field"><label>Renk</label>
                        <input class="input" type="color" name="color" value="#14452F" style="height:44px;padding:4px"></div>
                </div>
            </div>
            <div class="modal-foot">
                <div id="goalDeleteWrap"></div><div class="grow"></div>
                <button type="button" class="btn btn-ghost" onclick="closeModal('goalModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Para ekle -->
<div class="modal-overlay" id="contribModal">
    <div class="modal">
        <div class="modal-head"><h3 id="contribTitle">Para Ekle</h3><button class="x" onclick="closeModal('contribModal')">×</button></div>
        <form method="post" action="<?= url('actions/goal_contribute.php') ?>" id="contribForm">
            <?= csrf_field() ?><input type="hidden" name="goal_id" value="">
            <div class="modal-body">
                <div class="grid grid-2">
                    <div class="field"><label>Tutar</label>
                        <input class="input tabular" name="amount" inputmode="decimal" placeholder="0,00" required autofocus></div>
                    <div class="field"><label>Tarih</label>
                        <input class="input" type="date" name="contributed_on" value="<?= date('Y-m-d') ?>"></div>
                </div>
                <div class="field"><label>Not (ops.)</label>
                    <input class="input" name="note" placeholder="ör. maaştan ayrılan"></div>
                <p class="muted" style="font-size:12px;margin:0">İpucu: Para çekmek için eksi (-) tutar girebilirsiniz.</p>
            </div>
            <div class="modal-foot">
                <div class="grow"></div>
                <button type="button" class="btn btn-ghost" onclick="closeModal('contribModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
$delUrl = url('actions/goal_delete.php');
$csrf = csrf_token();
$inline_script = <<<JS
function newGoal(){
    const f=document.getElementById('goalForm'); f.reset();
    f.querySelector('[name=id]').value='';
    f.querySelector('[name=icon]').value='🎯';
    f.querySelector('[name=color]').value='#14452F';
    document.getElementById('goalTitle').textContent='Yeni Hedef';
    document.getElementById('goalDeleteWrap').innerHTML='';
    openModal('goalModal');
}
function editGoal(d){
    const f=document.getElementById('goalForm'); f.reset();
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=name]').value=d.name||'';
    f.querySelector('[name=target_amount]').value=d.target_amount||'';
    f.querySelector('[name=target_date]').value=d.target_date||'';
    f.querySelector('[name=icon]').value=d.icon||'🎯';
    f.querySelector('[name=color]').value=d.color||'#14452F';
    document.getElementById('goalTitle').textContent='Hedefi Düzenle';
    // Not: Silme butonu, düzenleme formunun KENDİSİNİ silme ucuna yönlendirir
    // (formaction). İç içe <form> HTML'de geçersizdir; tarayıcı iç formu yok
    // saydığı için eski sürümde "Sil" yanlışlıkla kaydet formunu gönderiyordu.
    document.getElementById('goalDeleteWrap').innerHTML=
        '<button class="btn btn-danger btn-sm" type="submit" formaction="$delUrl" formnovalidate '+
        'onclick="return confirmDelete(\\'Hedef ve tüm birikim kayıtları silinsin mi?\\')">Sil</button>';
    openModal('goalModal');
}
function contributeGoal(id,name){
    const f=document.getElementById('contribForm'); f.reset();
    f.querySelector('[name=goal_id]').value=id;
    document.getElementById('contribTitle').textContent=name+' · Para Ekle';
    openModal('contribModal');
}
function toggleHist(id){
    const el=document.getElementById('hist'+id);
    el.style.display = el.style.display==='none' ? 'block' : 'none';
}
JS;
require __DIR__ . '/templates/footer.php';
