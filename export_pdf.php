<?php
/**
 * PDF dışa aktarma (kütüphanesiz).
 * Yazdırmaya hazır temiz bir HTML sayfası üretir ve tarayıcının
 * "PDF olarak kaydet" özelliğini kullanır (otomatik yazdırma diyaloğu).
 * Paylaşımlı hostingde ek bileşen gerektirmez.
 */
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

$from = $_GET['from'] ?? date('Y-01-01');
$to   = $_GET['to']   ?? date('Y-12-31');
$from = date('Y-m-d', strtotime($from) ?: time());
$to   = date('Y-m-d', strtotime($to) ?: time());
$num  = fn($n) => number_format((float)$n, 2, ',', '.');

// Toplamlar (transfer hariç)
$ts = $pdo->prepare("SELECT type, COALESCE(SUM(amount),0) s FROM transactions
                      WHERE household_id=? AND transaction_date BETWEEN ? AND ? AND transfer_id IS NULL GROUP BY type");
$ts->execute([$householdId, $from, $to]);
$inc=0; $exp=0;
foreach ($ts->fetchAll() as $r){ if($r['type']==='income')$inc=(float)$r['s']; else $exp=(float)$r['s']; }

// Kategori dağılımı (gider)
$cb = $pdo->prepare("SELECT COALESCE(c.name,'Kategorisiz') name, COALESCE(SUM(t.amount),0) s
                       FROM transactions t LEFT JOIN categories c ON c.id=t.category_id
                      WHERE t.household_id=? AND t.type='expense' AND t.transfer_id IS NULL AND t.transaction_date BETWEEN ? AND ?
                   GROUP BY c.id ORDER BY s DESC");
$cb->execute([$householdId, $from, $to]);
$cats = $cb->fetchAll();

// İşlemler
$lt = $pdo->prepare(
    "SELECT t.transaction_date, t.type, t.amount, t.description, t.transfer_id,
            c.name cat_name, a.name acc_name
       FROM transactions t
       LEFT JOIN categories c ON c.id=t.category_id
       LEFT JOIN accounts a ON a.id=t.account_id
      WHERE t.household_id=? AND t.transaction_date BETWEEN ? AND ?
   ORDER BY t.transaction_date ASC, t.id ASC"
);
$lt->execute([$householdId, $from, $to]);
$rows = $lt->fetchAll();

$hName = current_household()['name'];
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8">
<title>Rapor · <?= e($hName) ?> · <?= e($from) ?>–<?= e($to) ?></title>
<style>
  *{box-sizing:border-box}
  body{font-family:'Segoe UI',Arial,sans-serif;color:#1A1D23;margin:28px;font-size:13px}
  h1{font-size:20px;margin:0 0 2px}
  .muted{color:#777}
  .cards{display:flex;gap:12px;margin:16px 0}
  .card{flex:1;border:1px solid #e3ddcf;border-radius:10px;padding:12px}
  .card .lbl{font-size:11px;color:#777}
  .card .val{font-size:18px;font-weight:700;margin-top:4px}
  table{width:100%;border-collapse:collapse;margin-top:10px}
  th,td{border:1px solid #e3ddcf;padding:6px 8px;text-align:left;font-size:12px}
  th{background:#f3efe6}
  td.r,th.r{text-align:right}
  .inc{color:#15803D}.exp{color:#C2410C}
  .toolbar{margin:0 0 14px}
  .btn{background:#14452F;color:#fff;border:0;padding:9px 16px;border-radius:8px;cursor:pointer;font-size:13px}
  @media print{.toolbar{display:none}body{margin:0}}
</style></head>
<body>
<div class="toolbar">
  <button class="btn" onclick="window.print()">🖨️ Yazdır / PDF olarak kaydet</button>
</div>
<h1><?= e($hName) ?> — Mali Rapor</h1>
<div class="muted"><?= date('d.m.Y', strtotime($from)) ?> – <?= date('d.m.Y', strtotime($to)) ?></div>

<div class="cards">
  <div class="card"><div class="lbl">Toplam Gelir</div><div class="val inc"><?= $num($inc) ?> ₺</div></div>
  <div class="card"><div class="lbl">Toplam Gider</div><div class="val exp"><?= $num($exp) ?> ₺</div></div>
  <div class="card"><div class="lbl">Net</div><div class="val"><?= $num($inc-$exp) ?> ₺</div></div>
</div>

<?php if ($cats): ?>
<h3>Gider Dağılımı (Kategori)</h3>
<table>
  <tr><th>Kategori</th><th class="r">Tutar</th><th class="r">Pay</th></tr>
  <?php foreach ($cats as $c): $p = $exp>0 ? round($c['s']/$exp*100) : 0; ?>
  <tr><td><?= e($c['name']) ?></td><td class="r"><?= $num($c['s']) ?> ₺</td><td class="r">%<?= $p ?></td></tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<h3>İşlemler (<?= count($rows) ?>)</h3>
<table>
  <tr><th>Tarih</th><th>Tür</th><th>Kategori</th><th>Hesap</th><th>Açıklama</th><th class="r">Tutar</th></tr>
  <?php foreach ($rows as $r): $isT = !empty($r['transfer_id']); ?>
  <tr>
    <td><?= date('d.m.Y', strtotime($r['transaction_date'])) ?></td>
    <td><?= $isT?'Transfer':($r['type']==='income'?'Gelir':'Gider') ?></td>
    <td><?= e($r['cat_name'] ?: '—') ?></td>
    <td><?= e($r['acc_name'] ?: '—') ?></td>
    <td><?= e($r['description']) ?></td>
    <td class="r <?= $isT?'':($r['type']==='income'?'inc':'exp') ?>"><?= ($r['type']==='income'?'+':'−').$num($r['amount']) ?> ₺</td>
  </tr>
  <?php endforeach; ?>
</table>
<p class="muted" style="margin-top:10px;font-size:11px">Transferler gelir/gider toplamına dahil değildir. Ev Muhasebe ile oluşturuldu.</p>

<script>window.addEventListener('load',function(){setTimeout(function(){window.print();},400);});</script>
</body></html>
<?php exit;
