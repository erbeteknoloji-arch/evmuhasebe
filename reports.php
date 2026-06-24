<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/insights.php';
require_household();

$householdId = hid();
$pdo = db();

$insights = spending_insights($householdId);

/* ---- Dönem seçimi ---- */
$period = $_GET['period'] ?? 'year';
$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

if ($from && $to && strtotime($from) && strtotime($to)) {
    $period = 'custom';
    $start = date('Y-m-d', strtotime($from));
    $end   = date('Y-m-d', strtotime($to));
} else {
    switch ($period) {
        case 'month':
            $start = date('Y-m-01'); $end = date('Y-m-t'); break;
        case 'last_month':
            $start = date('Y-m-01', strtotime('first day of last month'));
            $end   = date('Y-m-t', strtotime('last day of last month')); break;
        case 'all':
            $min = $pdo->prepare('SELECT MIN(transaction_date) m FROM transactions WHERE household_id = ?');
            $min->execute([$householdId]);
            $start = $min->fetch()['m'] ?: date('Y-m-01');
            $end   = date('Y-m-d'); break;
        case 'year':
        default:
            $period = 'year';
            $start = date('Y-01-01'); $end = date('Y-12-31'); break;
    }
}

$periodNames = ['month'=>'Bu Ay','last_month'=>'Geçen Ay','year'=>'Bu Yıl','all'=>'Tüm Zamanlar','custom'=>'Özel Aralık'];

/* ---- Toplamlar ---- */
$t = $pdo->prepare("SELECT type, COALESCE(SUM(amount),0) s, COUNT(*) c FROM transactions WHERE household_id=? AND transaction_date BETWEEN ? AND ? AND transfer_id IS NULL GROUP BY type");
$t->execute([$householdId, $start, $end]);
$inc=0; $exp=0; $cnt=0;
foreach ($t->fetchAll() as $r){ if($r['type']==='income')$inc=(float)$r['s']; else $exp=(float)$r['s']; $cnt+=(int)$r['c']; }
$net = $inc - $exp;
$savingRate = $inc > 0 ? round($net / $inc * 100) : 0;

/* ---- Aylık trend (dönem içi) ---- */
$tr = $pdo->prepare("SELECT DATE_FORMAT(transaction_date,'%Y-%m') ym, type, COALESCE(SUM(amount),0) s
                       FROM transactions WHERE household_id=? AND transaction_date BETWEEN ? AND ? AND transfer_id IS NULL
                    GROUP BY ym, type");
$tr->execute([$householdId, $start, $end]);
$map=[];
foreach ($tr->fetchAll() as $r){ $map[$r['ym']][$r['type']]=(float)$r['s']; }
$labels=[]; $dInc=[]; $dExp=[];
$cur = strtotime(date('Y-m-01', strtotime($start)));
$last = strtotime(date('Y-m-01', strtotime($end)));
$guard = 0;
while ($cur <= $last && $guard < 36){
    $k = date('Y-m', $cur);
    $labels[] = tr_month((int)date('n',$cur)) . ' ' . date('y',$cur);
    $dInc[] = $map[$k]['income'] ?? 0;
    $dExp[] = $map[$k]['expense'] ?? 0;
    $cur = strtotime('+1 month', $cur);
    $guard++;
}

/* ---- Kategori dağılımları ---- */
function catBreakdown(PDO $pdo, int $hid, string $start, string $end, string $type): array {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(c.name,'Kategorisiz') name, COALESCE(c.color,'#9CA3AF') color, COALESCE(c.icon,'❔') icon,
                COALESCE(SUM(t.amount),0) s, COUNT(*) c
           FROM transactions t LEFT JOIN categories c ON c.id=t.category_id
          WHERE t.household_id=? AND t.type=? AND t.transaction_date BETWEEN ? AND ? AND t.transfer_id IS NULL
       GROUP BY c.id ORDER BY s DESC"
    );
    $stmt->execute([$hid, $type, $start, $end]);
    return $stmt->fetchAll();
}
$expCats = catBreakdown($pdo, $householdId, $start, $end, 'expense');
$incCats = catBreakdown($pdo, $householdId, $start, $end, 'income');

/* ---- Üye katkıları ---- */
$mc = $pdo->prepare(
    "SELECT COALESCE(u.name,'Bilinmiyor') name, u.avatar_color,
            COALESCE(SUM(CASE WHEN t.type='income' THEN t.amount END),0) inc,
            COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount END),0) exp,
            COUNT(*) c
       FROM transactions t LEFT JOIN users u ON u.id=t.user_id
      WHERE t.household_id=? AND t.transaction_date BETWEEN ? AND ?
   GROUP BY t.user_id ORDER BY c DESC"
);
$mc->execute([$householdId, $start, $end]);
$members = $mc->fetchAll();

$expParams = http_build_query(['from'=>$start,'to'=>$end]);
$csvUrl   = url('export_csv.php?' . $expParams);
$xlsUrl   = url('export_excel.php?' . $expParams);
$pdfUrl   = url('export_pdf.php?' . $expParams);

$page_title    = t('page.reports.title');
$page_subtitle = ($periodNames[$period] ?? '') . ' · ' . format_date($start) . ' – ' . format_date($end);
$active        = 'reports';
$page_actions  = '<a href="' . $xlsUrl . '" class="btn btn-ghost">⬇ Excel</a>'
               . '<a href="' . $pdfUrl . '" target="_blank" class="btn btn-ghost">⬇ PDF</a>'
               . '<a href="' . $csvUrl . '" class="btn btn-gold">⬇ CSV</a>';

require __DIR__ . '/templates/header.php';
?>

<!-- Dönem seçici -->
<form method="get" class="filterbar">
    <div class="field">
        <label>Dönem</label>
        <select name="period" class="input" onchange="this.form.from.value='';this.form.to.value='';this.form.submit()">
            <?php foreach (['month','last_month','year','all'] as $p): ?>
                <option value="<?= $p ?>" <?= $period===$p?'selected':'' ?>><?= $periodNames[$p] ?></option>
            <?php endforeach; ?>
            <?php if ($period==='custom'): ?><option value="custom" selected>Özel Aralık</option><?php endif; ?>
        </select>
    </div>
    <div class="field"><label>Başlangıç</label><input type="date" name="from" class="input" value="<?= e($period==='custom'?$start:'') ?>"></div>
    <div class="field"><label>Bitiş</label><input type="date" name="to" class="input" value="<?= e($period==='custom'?$end:'') ?>"></div>
    <button class="btn btn-primary">Uygula</button>
</form>

<!-- Özet -->
<div class="grid grid-4">
    <div class="card stat income"><div class="label">Toplam Gelir</div><div class="value"><?= money($inc) ?></div></div>
    <div class="card stat expense"><div class="label">Toplam Gider</div><div class="value"><?= money($exp) ?></div></div>
    <div class="card stat balance"><div class="label">Net</div><div class="value" style="color:<?= $net>=0?'var(--income)':'var(--expense)' ?>"><?= ($net>=0?'+':'').money($net) ?></div></div>
    <div class="card stat balance"><div class="label">Tasarruf Oranı</div><div class="value"><?= $savingRate ?>%</div><div class="meta"><?= $cnt ?> işlem</div></div>
</div>

<!-- Akıllı Öneriler / Tasarruf Fırsatları (bu ay) -->
<div class="card mt-2">
    <div class="card-head">
        <h3>💡 Öneriler & Tasarruf Fırsatları</h3>
        <span class="pill"><?= tr_month((int)date('n')) ?> <?= date('Y') ?></span>
    </div>
    <div class="card-pad">
        <p class="muted" style="margin-top:0;font-size:12.5px">Bu ayki harcama alışkanlıklarınıza göre otomatik üretilmiştir.</p>
        <?php foreach ($insights as $t): ?>
            <div class="insight <?= e($t['level']) ?>">
                <span class="ic"><?= $t['icon'] ?></span>
                <div><b><?= e($t['title']) ?></b><p><?= $t['text'] /* zaten e() ile kaçırıldı */ ?></p></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Aylık trend -->
<div class="card mt-2">
    <div class="card-head"><h3>Aylık Gelir & Gider</h3></div>
    <div class="card-pad">
        <?php if ($cnt > 0): ?>
            <div class="chart-box"><canvas id="repTrend"></canvas></div>
        <?php else: ?>
            <div class="empty"><div class="big">📊</div><p class="muted">Bu dönemde işlem bulunmuyor.</p></div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-2 mt-2">
    <!-- Gider kategorileri -->
    <div class="card">
        <div class="card-head"><h3>Gider Dağılımı</h3></div>
        <div class="card-pad">
            <?php if ($expCats && array_sum(array_column($expCats,'s'))>0): ?>
                <div class="chart-box sm"><canvas id="repExp"></canvas></div>
                <div class="table-wrap mt-2"><table class="data">
                    <tbody>
                    <?php foreach ($expCats as $c): $share = $exp>0?round($c['s']/$exp*100):0; ?>
                        <tr>
                            <td><span class="cat-badge"><span class="swatch" style="background:<?= e($c['color']) ?>22"><?= $c['icon'] ?></span><?= e($c['name']) ?></span></td>
                            <td class="muted num-right"><?= $share ?>%</td>
                            <td class="num-right amount expense"><?= money($c['s']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php else: ?><div class="empty"><div class="big">🥧</div><p class="muted">Gider yok.</p></div><?php endif; ?>
        </div>
    </div>

    <!-- Gelir kategorileri -->
    <div class="card">
        <div class="card-head"><h3>Gelir Dağılımı</h3></div>
        <div class="card-pad">
            <?php if ($incCats && array_sum(array_column($incCats,'s'))>0): ?>
                <div class="chart-box sm"><canvas id="repInc"></canvas></div>
                <div class="table-wrap mt-2"><table class="data">
                    <tbody>
                    <?php foreach ($incCats as $c): $share = $inc>0?round($c['s']/$inc*100):0; ?>
                        <tr>
                            <td><span class="cat-badge"><span class="swatch" style="background:<?= e($c['color']) ?>22"><?= $c['icon'] ?></span><?= e($c['name']) ?></span></td>
                            <td class="muted num-right"><?= $share ?>%</td>
                            <td class="num-right amount income"><?= money($c['s']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php else: ?><div class="empty"><div class="big">💰</div><p class="muted">Gelir yok.</p></div><?php endif; ?>
        </div>
    </div>
</div>

<!-- Üye katkıları -->
<?php if ($members): ?>
<div class="card mt-2">
    <div class="card-head"><h3>Ev Üyelerinin Katkısı</h3></div>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Üye</th><th class="num-right">Eklenen Gelir</th><th class="num-right">Eklenen Gider</th><th class="num-right">İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><span class="cat-badge"><span class="swatch" style="background:<?= e($m['avatar_color'] ?: '#9CA3AF') ?>;color:#fff"><?= e(mb_strtoupper(mb_substr($m['name'],0,1))) ?></span><?= e($m['name']) ?></span></td>
                <td class="num-right amount income"><?= money($m['inc']) ?></td>
                <td class="num-right amount expense"><?= money($m['exp']) ?></td>
                <td class="num-right muted"><?= (int)$m['c'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<?php
$expLabels = array_map(fn($r)=>$r['name'], $expCats);
$expData   = array_map(fn($r)=>(float)$r['s'], $expCats);
$expColors = array_map(fn($r)=>$r['color'], $expCats);
$incLabels = array_map(fn($r)=>$r['name'], $incCats);
$incData   = array_map(fn($r)=>(float)$r['s'], $incCats);
$incColors = array_map(fn($r)=>$r['color'], $incCats);

$inline_script = '
const fmtTRY = v => new Intl.NumberFormat("tr-TR",{style:"currency",currency:"TRY"}).format(v);
Chart.defaults.font.family = "Plus Jakarta Sans, sans-serif";
Chart.defaults.color = "#4A4F5A";
';
if ($cnt > 0) {
    $inline_script .= '
new Chart(document.getElementById("repTrend"),{type:"bar",
  data:{labels:'.json_encode($labels).',datasets:[
    {label:"Gelir",data:'.json_encode($dInc).',backgroundColor:"#15803D",borderRadius:6,maxBarThickness:30},
    {label:"Gider",data:'.json_encode($dExp).',backgroundColor:"#C2410C",borderRadius:6,maxBarThickness:30}]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:"bottom",labels:{usePointStyle:true,pointStyle:"circle",padding:16}},
      tooltip:{callbacks:{label:c=>c.dataset.label+": "+fmtTRY(c.parsed.y)}}},
    scales:{y:{ticks:{callback:v=>v.toLocaleString("tr-TR")},grid:{color:"#EDE9DE"}},x:{grid:{display:false}}}}});
';
}
if ($expCats && array_sum($expData) > 0) {
    $inline_script .= '
new Chart(document.getElementById("repExp"),{type:"doughnut",
  data:{labels:'.json_encode($expLabels).',datasets:[{data:'.json_encode($expData).',backgroundColor:'.json_encode($expColors).',borderWidth:2,borderColor:"#fff"}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:"60%",
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>c.label+": "+fmtTRY(c.parsed)}}}}});
';
}
if ($incCats && array_sum($incData) > 0) {
    $inline_script .= '
new Chart(document.getElementById("repInc"),{type:"doughnut",
  data:{labels:'.json_encode($incLabels).',datasets:[{data:'.json_encode($incData).',backgroundColor:'.json_encode($incColors).',borderWidth:2,borderColor:"#fff"}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:"60%",
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>c.label+": "+fmtTRY(c.parsed)}}}}});
';
}
require __DIR__ . '/templates/footer.php';
