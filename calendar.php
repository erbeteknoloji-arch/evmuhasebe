<?php
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

// Görüntülenen ay
$monthParam = $_GET['ay'] ?? date('Y-m');
$baseTs = strtotime($monthParam . '-01');
if (!$baseTs) $baseTs = strtotime(date('Y-m-01'));
$year  = (int)date('Y', $baseTs);
$month = (int)date('n', $baseTs);
$monthStart = date('Y-m-01', $baseTs);
$monthEnd   = date('Y-m-t', $baseTs);
$prevMonth = date('Y-m', strtotime('-1 month', $baseTs));
$nextMonth = date('Y-m', strtotime('+1 month', $baseTs));

// Bu aydaki planlı kalemler
$mStmt = $pdo->prepare(
    "SELECT s.*, c.name AS cat_name, c.icon AS cat_icon, a.name AS acc_name
       FROM scheduled_items s
       LEFT JOIN categories c ON c.id = s.category_id
       LEFT JOIN accounts a ON a.id = s.account_id
      WHERE s.household_id = ? AND s.due_date BETWEEN ? AND ?
      ORDER BY s.due_date ASC"
);
$mStmt->execute([$householdId, $monthStart, $monthEnd]);
$byDay = [];
foreach ($mStmt->fetchAll() as $row) {
    $d = (int)date('j', strtotime($row['due_date']));
    $byDay[$d][] = $row;
}

// Yaklaşan (bugünden sonraki 45 gün) ve geciken (bugünden önce, bekliyor)
$today = date('Y-m-d');
$upQ = $pdo->prepare(
    "SELECT s.*, c.name AS cat_name FROM scheduled_items s LEFT JOIN categories c ON c.id=s.category_id
      WHERE s.household_id=? AND s.status='pending' AND s.due_date >= ? AND s.due_date <= ?
      ORDER BY s.due_date ASC LIMIT 40"
);
$upQ->execute([$householdId, $today, date('Y-m-d', strtotime('+45 days'))]);
$upcoming = $upQ->fetchAll();

$ovQ = $pdo->prepare(
    "SELECT s.*, c.name AS cat_name FROM scheduled_items s LEFT JOIN categories c ON c.id=s.category_id
      WHERE s.household_id=? AND s.status='pending' AND s.due_date < ?
      ORDER BY s.due_date ASC"
);
$ovQ->execute([$householdId, $today]);
$overdue = $ovQ->fetchAll();

// Modal için kategoriler & hesaplar
$cats = $pdo->prepare('SELECT id,name,type,icon FROM categories WHERE household_id=? ORDER BY type,name');
$cats->execute([$householdId]);
$categories = $cats->fetchAll();
$accs = $pdo->prepare('SELECT id,name FROM accounts WHERE household_id=? ORDER BY name');
$accs->execute([$householdId]);
$accounts = $accs->fetchAll();

$weekdays = ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
$firstDow = (int)date('N', strtotime($monthStart)); // 1=Pzt
$daysInMonth = (int)date('t', $baseTs);
$todayD = ((int)date('Y') === $year && (int)date('n') === $month) ? (int)date('j') : -1;

$page_title = t('page.calendar.title');
$page_subtitle = 'Yaklaşan ödemeler, gelirler ve planlı işlemler';
$page_actions = '<button class="btn btn-primary" onclick="newScheduled()">+ Planlı Ödeme</button>';
$active = 'calendar';
require __DIR__ . '/templates/header.php';
?>

<?php if ($overdue): ?>
<div class="card mt-2" style="border-color:#f0cdbb">
    <div class="card-head"><h3 style="color:var(--danger)">⏰ Geciken Ödemeler (<?= count($overdue) ?>)</h3></div>
    <div class="list">
        <?php foreach ($overdue as $o): $dleft = days_until($o['due_date']); ?>
        <div class="item">
            <div class="av" style="background:var(--expense)"><?= $o['type']==='income'?'＋':'－' ?></div>
            <div class="grow">
                <b><?= e($o['title']) ?></b>
                <span><?= format_date($o['due_date']) ?> · <?= abs($dleft) ?> gün gecikti<?= $o['cat_name'] ? ' · '.e($o['cat_name']) : '' ?></span>
            </div>
            <div class="tabular" style="font-weight:700;color:<?= $o['type']==='income'?'var(--income)':'var(--expense)' ?>">
                <?= money($o['amount']) ?>
            </div>
            <form method="post" action="<?= url('actions/scheduled_pay.php') ?>" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <button class="btn btn-sm btn-primary">İşle</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid cols-2-1 mt-2">
    <!-- TAKVİM IZGARASI -->
    <div class="card">
        <div class="card-head">
            <h3><?= tr_month($month) ?> <?= $year ?></h3>
            <div class="flex" style="gap:6px">
                <a class="btn btn-ghost btn-sm" href="<?= url('calendar.php?ay='.$prevMonth) ?>">←</a>
                <a class="btn btn-ghost btn-sm" href="<?= url('calendar.php') ?>">Bugün</a>
                <a class="btn btn-ghost btn-sm" href="<?= url('calendar.php?ay='.$nextMonth) ?>">→</a>
            </div>
        </div>
        <div class="card-pad">
            <div class="cal-grid cal-head">
                <?php foreach ($weekdays as $wd): ?><div class="cal-dow"><?= $wd ?></div><?php endforeach; ?>
            </div>
            <div class="cal-grid">
                <?php for ($i = 1; $i < $firstDow; $i++): ?>
                    <div class="cal-cell empty"></div>
                <?php endfor; ?>
                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d); ?>
                    <div class="cal-cell <?= $d === $todayD ? 'today' : '' ?>" onclick="newScheduledOn('<?= $dateStr ?>')">
                        <div class="cal-num"><?= $d ?></div>
                        <?php if (!empty($byDay[$d])): foreach ($byDay[$d] as $ev): ?>
                            <div class="cal-ev <?= $ev['type'] ?> <?= $ev['status']==='paid'?'paid':'' ?>"
                                 title="<?= e($ev['title']).' · '.money($ev['amount']) ?>"
                                 onclick="event.stopPropagation(); editScheduled(<?= e(json_encode([
                                    'id'=>$ev['id'],'type'=>$ev['type'],'title'=>$ev['title'],
                                    'amount'=>number_format((float)$ev['amount'],2,',','.'),
                                    'due_date'=>$ev['due_date'],'recurrence'=>$ev['recurrence'],
                                    'category_id'=>$ev['category_id'],'account_id'=>$ev['account_id'],
                                    'notes'=>$ev['notes'],'status'=>$ev['status'],'auto_post'=>$ev['auto_post'] ?? 0
                                 ], JSON_HEX_APOS|JSON_HEX_QUOT)) ?>)">
                                <?= $ev['type']==='income'?'＋':'－' ?> <?= e(mb_strimwidth($ev['title'],0,12,'…')) ?>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <p class="muted" style="font-size:12.5px;margin:10px 0 0">Bir güne tıklayarak planlı ödeme ekleyebilir, mevcut bir kayda tıklayarak düzenleyebilirsiniz.</p>
        </div>
    </div>

    <!-- YAKLAŞAN -->
    <div class="card card-pad">
        <h3>Yaklaşan (45 gün)</h3>
        <?php if (!$upcoming): ?>
            <p class="muted">Yaklaşan planlı ödeme yok. Sağ üstten ekleyebilirsiniz.</p>
        <?php else: ?>
        <div class="list" style="margin-top:6px">
            <?php foreach ($upcoming as $u): $dleft = days_until($u['due_date']); ?>
            <div class="item">
                <div class="av" style="background:<?= $u['type']==='income'?'var(--income)':'var(--forest)' ?>">
                    <?= $dleft ?>
                </div>
                <div class="grow">
                    <b><?= e($u['title']) ?></b>
                    <span><?= format_date($u['due_date']) ?> · <?= $dleft===0?'bugün':($dleft.' gün sonra') ?>
                        <?= $u['recurrence']!=='none' ? ' · 🔁' : '' ?></span>
                </div>
                <div class="tabular" style="font-weight:700;color:<?= $u['type']==='income'?'var(--income)':'var(--expense)' ?>">
                    <?= money($u['amount']) ?>
                </div>
                <form method="post" action="<?= url('actions/scheduled_pay.php') ?>" style="display:inline">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button class="btn btn-sm btn-primary" title="Ödendi olarak işle">İşle</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: Planlı ödeme ekle/düzenle -->
<div class="modal-overlay" id="schedModal">
    <div class="modal">
        <div class="modal-head"><h3 id="schedTitle">Planlı Ödeme</h3><button class="x" onclick="closeModal('schedModal')">×</button></div>
        <form method="post" action="<?= url('actions/scheduled_save.php') ?>" id="schedForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="field">
                    <label>Tür</label>
                    <select class="input" name="type">
                        <option value="expense">Gider (ödeme)</option>
                        <option value="income">Gelir</option>
                    </select>
                </div>
                <div class="field"><label>Başlık</label>
                    <input class="input" name="title" placeholder="ör. Ev kirası, Kredi taksiti" required></div>
                <div class="grid grid-2">
                    <div class="field"><label>Tutar</label>
                        <input class="input tabular" name="amount" inputmode="decimal" placeholder="0,00" required></div>
                    <div class="field"><label>Tarih</label>
                        <input class="input" type="date" name="due_date" required></div>
                </div>
                <div class="grid grid-2">
                    <div class="field"><label>Tekrar</label>
                        <select class="input" name="recurrence">
                            <option value="none">Tek seferlik</option>
                            <option value="monthly">Her ay</option>
                            <option value="weekly">Her hafta</option>
                            <option value="yearly">Her yıl</option>
                        </select></div>
                    <div class="field"><label>Hesap (ops.)</label>
                        <select class="input" name="account_id">
                            <option value="">—</option>
                            <?php foreach ($accounts as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"><?= e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                </div>
                <div class="field"><label>Kategori (ops.)</label>
                    <select class="input" name="category_id">
                        <option value="">—</option>
                        <optgroup label="Giderler">
                            <?php foreach ($categories as $c): if ($c['type']==='expense'): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['icon'].' '.$c['name']) ?></option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                        <optgroup label="Gelirler">
                            <?php foreach ($categories as $c): if ($c['type']==='income'): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['icon'].' '.$c['name']) ?></option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                    </select></div>
                <div class="field"><label>Not (ops.)</label>
                    <input class="input" name="notes" placeholder="ek açıklama"></div>
                <label class="check" style="display:flex;gap:8px;align-items:center;margin-top:4px">
                    <input type="checkbox" name="auto_post" value="1">
                    <span><b>Vadesinde otomatik işle</b> — tarih gelince elle “İşle” demeden işleme alınır</span>
                </label>
            </div>
            <div class="modal-foot">
                <div id="schedDeleteWrap"></div>
                <div class="grow"></div>
                <button type="button" class="btn btn-ghost" onclick="closeModal('schedModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php
$delUrl = url('actions/scheduled_delete.php');
$csrf = csrf_token();
$inline_script = <<<JS
function newScheduled(){
    const f=document.getElementById('schedForm'); f.reset();
    f.querySelector('[name=id]').value='';
    document.getElementById('schedTitle').textContent='Yeni Planlı Ödeme';
    document.getElementById('schedDeleteWrap').innerHTML='';
    openModal('schedModal');
}
function newScheduledOn(dateStr){
    newScheduled();
    document.getElementById('schedForm').querySelector('[name=due_date]').value=dateStr;
}
function editScheduled(d){
    const f=document.getElementById('schedForm'); f.reset();
    f.querySelector('[name=id]').value=d.id||'';
    f.querySelector('[name=type]').value=d.type||'expense';
    f.querySelector('[name=title]').value=d.title||'';
    f.querySelector('[name=amount]').value=d.amount||'';
    f.querySelector('[name=due_date]').value=d.due_date||'';
    f.querySelector('[name=recurrence]').value=d.recurrence||'none';
    f.querySelector('[name=category_id]').value=d.category_id||'';
    f.querySelector('[name=account_id]').value=d.account_id||'';
    f.querySelector('[name=notes]').value=d.notes||'';
    var ap=f.querySelector('[name=auto_post]'); if(ap) ap.checked = (d.auto_post==1||d.auto_post==='1');
    document.getElementById('schedTitle').textContent='Planlı Ödemeyi Düzenle';
    // Not: Silme butonu, düzenleme formunu silme ucuna yönlendirir (formaction).
    // İç içe <form> HTML'de geçersiz olduğundan eski sürümde "Sil" aslında
    // kaydet formunu gönderiyor, etkinlik silinmiyordu.
    document.getElementById('schedDeleteWrap').innerHTML=
        '<button class="btn btn-danger btn-sm" type="submit" formaction="$delUrl" formnovalidate '+
        'onclick="return confirmDelete(\\'Bu planlı ödeme silinsin mi?\\')">Sil</button>';
    openModal('schedModal');
}
JS;
require __DIR__ . '/templates/footer.php';
