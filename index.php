<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credit.php';
require_once __DIR__ . '/includes/budget_alerts.php';
require_once __DIR__ . '/includes/scheduled.php';
require_household();

$householdId = hid();
$pdo = db();

/* Vadesi gelen otomatik planlı ödemeleri işle (tembel) */
process_due_scheduled($householdId);

/* Kredi kartı hesap kesim tarihi geçtiyse eksik ekstreleri tembel üret */
cc_generate_statements($householdId);
$cc = cc_summary($householdId);

/* Bütçe aşım uyarıları (eşik geçilince e-posta + panelde rozet) */
$budgetAlerts = check_budget_alerts($householdId);

$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');
$monthLabel = tr_month((int)date('n')) . ' ' . date('Y');

/* ---- Bu ayın gelir / gider toplamı ---- */
$mt = $pdo->prepare(
    'SELECT type, COALESCE(SUM(amount),0) s, COUNT(*) c
       FROM transactions
      WHERE household_id = ? AND transaction_date BETWEEN ? AND ? AND transfer_id IS NULL
   GROUP BY type'
);
$mt->execute([$householdId, $monthStart, $monthEnd]);
$monthIncome = 0; $monthExpense = 0; $monthCount = 0;
foreach ($mt->fetchAll() as $r) {
    if ($r['type'] === 'income') $monthIncome = (float)$r['s'];
    else $monthExpense = (float)$r['s'];
    $monthCount += (int)$r['c'];
}
$monthNet = $monthIncome - $monthExpense;

/* ---- Tüm zamanların bakiyesi ---- */
$at = $pdo->prepare('SELECT type, COALESCE(SUM(amount),0) s FROM transactions WHERE household_id = ? GROUP BY type');
$at->execute([$householdId]);
$allIncome = 0; $allExpense = 0;
foreach ($at->fetchAll() as $r) {
    if ($r['type'] === 'income') $allIncome = (float)$r['s']; else $allExpense = (float)$r['s'];
}
$openStmt = $pdo->prepare('SELECT COALESCE(SUM(opening_balance),0) s FROM accounts WHERE household_id = ?');
$openStmt->execute([$householdId]);
$openingTotal = (float)$openStmt->fetch()['s'];
$balance = $openingTotal + $allIncome - $allExpense;

/* ---- Son 6 ay trendi ---- */
$trendStart = date('Y-m-01', strtotime('-5 months'));
$tr = $pdo->prepare(
    "SELECT DATE_FORMAT(transaction_date,'%Y-%m') ym, type, COALESCE(SUM(amount),0) s
       FROM transactions
      WHERE household_id = ? AND transaction_date >= ? AND transfer_id IS NULL
   GROUP BY ym, type"
);
$tr->execute([$householdId, $trendStart]);
$trendMap = [];
foreach ($tr->fetchAll() as $r) {
    $trendMap[$r['ym']][$r['type']] = (float)$r['s'];
}
$trendLabels = []; $trendIncome = []; $trendExpense = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $trendLabels[]  = tr_month((int)date('n', strtotime("-$i months"))) . ' ' . date('y', strtotime("-$i months"));
    $trendIncome[]  = $trendMap[$key]['income']  ?? 0;
    $trendExpense[] = $trendMap[$key]['expense'] ?? 0;
}

/* ---- Bu ayki kategori dağılımı (gider) ---- */
$cb = $pdo->prepare(
    "SELECT COALESCE(c.name,'Kategorisiz') name, COALESCE(c.color,'#9CA3AF') color,
            COALESCE(SUM(t.amount),0) s
       FROM transactions t
       LEFT JOIN categories c ON c.id = t.category_id
      WHERE t.household_id = ? AND t.type = 'expense' AND t.transaction_date BETWEEN ? AND ? AND t.transfer_id IS NULL
   GROUP BY c.id
   ORDER BY s DESC
      LIMIT 8"
);
$cb->execute([$householdId, $monthStart, $monthEnd]);
$catRows = $cb->fetchAll();

/* ---- Son işlemler ---- */
$rt = $pdo->prepare(
    'SELECT t.*, c.name cat_name, c.color cat_color, c.icon cat_icon, u.name user_name
       FROM transactions t
       LEFT JOIN categories c ON c.id = t.category_id
       LEFT JOIN users u ON u.id = t.user_id
      WHERE t.household_id = ?
   ORDER BY t.transaction_date DESC, t.id DESC
      LIMIT 8'
);
$rt->execute([$householdId]);
$recent = $rt->fetchAll();

/* ---- Bütçe ilerlemeleri ---- */
$bg = $pdo->prepare(
    "SELECT b.monthly_limit, c.name, c.color, c.icon, c.id cat_id,
            COALESCE(spent.s,0) spent
       FROM budgets b
       JOIN categories c ON c.id = b.category_id
       LEFT JOIN (
            SELECT category_id, SUM(amount) s
              FROM transactions
             WHERE household_id = ? AND type='expense' AND transaction_date BETWEEN ? AND ? AND transfer_id IS NULL
          GROUP BY category_id
       ) spent ON spent.category_id = c.id
      WHERE b.household_id = ?
   ORDER BY (spent.s / b.monthly_limit) DESC"
);
$bg->execute([$householdId, $monthStart, $monthEnd, $householdId]);
$budgets = $bg->fetchAll();

$page_title    = t('page.dashboard.title');
$page_subtitle = current_household()['name'] . ' · ' . $monthLabel;
$active        = 'dashboard';
$page_actions  = '<a href="' . url('transactions.php?yeni=gelir') . '" class="btn btn-ghost">' . e(t('dash.add_income')) . '</a>'
               . '<a href="' . url('transactions.php?yeni=gider') . '" class="btn btn-primary">' . e(t('dash.add_expense')) . '</a>';

require __DIR__ . '/templates/header.php';
?>

<?php if (!empty($budgetAlerts)): ?>
<div class="card card-pad" style="margin-bottom:14px;border-color:#f0cdbb;background:#fff8f3">
    <div class="flex" style="gap:10px;align-items:flex-start">
        <div style="font-size:22px">⚠️</div>
        <div class="grow">
            <b><?= te('dash.budget_warning') ?></b>
            <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap">
                <?php foreach ($budgetAlerts as $al): ?>
                    <span class="pill" style="background:<?= $al['level']>=100?'var(--expense-bg)':'var(--gold-soft)' ?>;color:<?= $al['level']>=100?'var(--expense)':'var(--gold)' ?>">
                        <?= e($al['icon'].' '.$al['category']) ?> · %<?= $al['pct'] ?> (<?= money($al['spent']) ?>/<?= money($al['limit']) ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <a href="<?= url('categories.php') ?>" class="btn btn-ghost btn-sm">Bütçeler →</a>
    </div>
</div>
<?php endif; ?>

<!-- Özet kartları -->
<div class="grid grid-4">
    <div class="card stat income">
        <div class="label"><span class="chip" style="background:var(--income-bg)">↑</span> <?= te('dash.month_income') ?></div>
        <div class="value"><?= money($monthIncome) ?></div>
        <div class="meta"><?= $monthLabel ?></div>
    </div>
    <div class="card stat expense">
        <div class="label"><span class="chip" style="background:var(--expense-bg)">↓</span> <?= te('dash.month_expense') ?></div>
        <div class="value"><?= money($monthExpense) ?></div>
        <div class="meta"><?= $monthCount ?> işlem</div>
    </div>
    <div class="card stat balance">
        <div class="label"><span class="chip" style="background:var(--gold-soft)">≈</span> <?= te('dash.month_net') ?></div>
        <div class="value" style="color:<?= $monthNet >= 0 ? 'var(--income)' : 'var(--expense)' ?>">
            <?= ($monthNet >= 0 ? '+' : '') . money($monthNet) ?>
        </div>
        <div class="meta">Gelir − Gider</div>
    </div>
    <div class="card stat balance">
        <div class="label"><span class="chip" style="background:var(--cream-2)">₺</span> <?= te('dash.total_balance') ?></div>
        <div class="value"><?= money($balance) ?></div>
        <div class="meta">Tüm hesaplar (açılış dahil)</div>
    </div>
</div>

<?php if (!empty($cc['cards'])): ?>
<!-- Kredi Kartları özeti -->
<div class="card mt-2">
    <div class="card-head">
        <h3>💳 <?= te('dash.cards') ?></h3>
        <a href="<?= url('accounts.php') ?>" class="btn btn-ghost btn-sm">Kartları Yönet →</a>
    </div>
    <div class="card-pad">
        <div class="grid grid-4">
            <div class="stat expense">
                <div class="label">Toplam Kart Borcu</div>
                <div class="value tabular" style="color:var(--expense)"><?= money($cc['total_debt']) ?></div>
            </div>
            <div class="stat balance">
                <div class="label">Toplam Asgari Ödeme</div>
                <div class="value tabular"><?= money($cc['total_min']) ?></div>
            </div>
            <div class="stat income">
                <div class="label">Kalan Kullanılabilir Limit</div>
                <div class="value tabular"><?= $cc['total_available'] !== null ? money($cc['total_available']) : '—' ?></div>
            </div>
            <div class="stat balance">
                <div class="label">Yaklaşan Tarihler</div>
                <div class="meta" style="font-size:13px;line-height:1.7">
                    📄 Hesap kesim: <b><?= $cc['next_statement'] ? format_date($cc['next_statement']) : '—' ?></b><br>
                    📅 Son ödeme: <b><?= $cc['next_due'] ? format_date($cc['next_due']) : '—' ?></b>
                </div>
            </div>
        </div>
        <div class="table-wrap mt-2">
            <table class="data">
                <thead><tr><th>Kart</th><th class="num-right">Borç</th><th class="num-right">Asgari</th><th class="num-right">Kullanılabilir</th><th>Hesap Kesim</th><th>Son Ödeme</th></tr></thead>
                <tbody>
                <?php foreach ($cc['cards'] as $c): ?>
                    <tr>
                        <td><b><?= e($c['name']) ?></b><?= $c['bank_name'] ? ' <span class="muted">· '.e($c['bank_name']).'</span>' : '' ?></td>
                        <td class="num-right amount expense tabular"><?= money($c['debt']) ?></td>
                        <td class="num-right tabular"><?= money($c['min_payment']) ?></td>
                        <td class="num-right tabular"><?= $c['available'] !== null ? money($c['available']) : '—' ?></td>
                        <td class="muted"><?= $c['next_statement'] ? format_date($c['next_statement']) : '—' ?></td>
                        <td class="muted"><?= $c['next_due'] ? format_date($c['next_due']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Grafikler -->
<div class="grid cols-2-1 mt-2">
    <div class="card">
        <div class="card-head"><h3><?= te('dash.trend') ?></h3></div>
        <div class="card-pad"><div class="chart-box"><canvas id="trendChart"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-head"><h3><?= te('dash.cat_dist') ?></h3></div>
        <div class="card-pad">
            <?php if ($catRows && array_sum(array_column($catRows, 's')) > 0): ?>
                <div class="chart-box"><canvas id="catChart"></canvas></div>
            <?php else: ?>
                <div class="empty"><div class="big">🥧</div><p class="muted">Bu ay henüz gider yok.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Son işlemler + Bütçeler -->
<div class="grid cols-2-1 mt-2">
    <div class="card">
        <div class="card-head">
            <h3><?= te('dash.recent_tx') ?></h3>
            <a href="<?= url('transactions.php') ?>" class="btn btn-ghost btn-sm"><?= te('ui.all_arrow') ?></a>
        </div>
        <?php if ($recent): ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Tarih</th><th>Açıklama</th><th>Kategori</th><th class="num-right">Tutar</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $t): ?>
                    <tr>
                        <td class="muted tabular"><?= format_date($t['transaction_date']) ?></td>
                        <td>
                            <?= e($t['description'] ?: '—') ?>
                            <?php if ($t['source'] === 'import'): ?><span class="pill import">PDF</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t['cat_name']): ?>
                                <span class="cat-badge"><span class="swatch" style="background:<?= e($t['cat_color']) ?>22"><?= $t['cat_icon'] ?></span><?= e($t['cat_name']) ?></span>
                            <?php else: ?><span class="muted">—</span><?php endif; ?>
                        </td>
                        <td class="num-right amount <?= $t['type'] ?>">
                            <?= ($t['type'] === 'income' ? '+' : '−') . money($t['amount']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty"><div class="big">📭</div><h3>Henüz işlem yok</h3>
                <p class="muted">İlk gelir veya giderinizi ekleyin ya da banka ekstrenizi içe aktarın.</p>
                <div class="flex" style="justify-content:center;margin-top:14px">
                    <a href="<?= url('transactions.php?yeni=gider') ?>" class="btn btn-primary">İşlem Ekle</a>
                    <a href="<?= url('import.php') ?>" class="btn btn-ghost">PDF İçe Aktar</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head"><h3><?= te('dash.monthly_budgets') ?></h3><a href="<?= url('categories.php') ?>" class="btn btn-ghost btn-sm"><?= te('ui.edit') ?></a></div>
        <div class="card-pad">
            <?php if ($budgets): foreach ($budgets as $b):
                $pct = $b['monthly_limit'] > 0 ? min(100, round($b['spent'] / $b['monthly_limit'] * 100)) : 0;
                $over = $b['spent'] > $b['monthly_limit']; ?>
                <div style="margin-bottom:16px">
                    <div class="flex-between" style="font-size:13.5px">
                        <span><?= $b['icon'] ?> <b><?= e($b['name']) ?></b></span>
                        <span class="tabular <?= $over ? '' : 'muted' ?>" style="<?= $over ? 'color:var(--expense);font-weight:700' : '' ?>">
                            <?= money($b['spent']) ?> / <?= money($b['monthly_limit']) ?>
                        </span>
                    </div>
                    <div class="bar"><span class="<?= $over ? 'over' : '' ?>" style="width:<?= $pct ?>%;<?= $over ? '' : 'background:'.e($b['color']) ?>"></span></div>
                </div>
            <?php endforeach; else: ?>
                <div class="empty"><div class="big">🎯</div><p class="muted">Henüz bütçe limiti yok.<br>Kategoriler sayfasından ekleyebilirsiniz.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$catLabels = array_map(fn($r) => $r['name'], $catRows);
$catData   = array_map(fn($r) => (float)$r['s'], $catRows);
$catColors = array_map(fn($r) => $r['color'], $catRows);

$inline_script = '
const fmtTRY = v => new Intl.NumberFormat("tr-TR",{style:"currency",currency:"TRY"}).format(v);
Chart.defaults.font.family = "Plus Jakarta Sans, sans-serif";
Chart.defaults.color = "#4A4F5A";

new Chart(document.getElementById("trendChart"), {
  type: "bar",
  data: {
    labels: ' . json_encode($trendLabels) . ',
    datasets: [
      { label: "Gelir", data: ' . json_encode($trendIncome) . ', backgroundColor: "#15803D", borderRadius: 6, maxBarThickness: 26 },
      { label: "Gider", data: ' . json_encode($trendExpense) . ', backgroundColor: "#C2410C", borderRadius: 6, maxBarThickness: 26 }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: "bottom", labels:{usePointStyle:true,pointStyle:"circle",padding:16} },
      tooltip: { callbacks: { label: c => c.dataset.label + ": " + fmtTRY(c.parsed.y) } } },
    scales: { y: { ticks: { callback: v => v.toLocaleString("tr-TR") }, grid: { color: "#EDE9DE" } },
              x: { grid: { display:false } } }
  }
});
';

if ($catRows && array_sum($catData) > 0) {
    $inline_script .= '
new Chart(document.getElementById("catChart"), {
  type: "doughnut",
  data: { labels: ' . json_encode($catLabels) . ',
    datasets: [{ data: ' . json_encode($catData) . ', backgroundColor: ' . json_encode($catColors) . ', borderWidth: 2, borderColor: "#fff" }] },
  options: { responsive: true, maintainAspectRatio: false, cutout: "62%",
    plugins: { legend: { position: "bottom", labels:{usePointStyle:true,pointStyle:"circle",padding:12,font:{size:11.5}} },
      tooltip: { callbacks: { label: c => c.label + ": " + fmtTRY(c.parsed) } } } }
});
';
}

require __DIR__ . '/templates/footer.php';
