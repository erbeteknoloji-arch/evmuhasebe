<?php
/**
 * Excel dışa aktarma (kütüphanesiz).
 * HTML tablo, Excel'in açabildiği .xls olarak gönderilir; paylaşımlı
 * hostingde ek bileşen gerektirmez. Türkçe karakterler için UTF-8.
 */
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

$from = $_GET['from'] ?? date('Y-01-01');
$to   = $_GET['to']   ?? date('Y-12-31');
$from = date('Y-m-d', strtotime($from) ?: time());
$to   = date('Y-m-d', strtotime($to) ?: time());

$stmt = $pdo->prepare(
    "SELECT t.transaction_date, t.type, t.amount, t.description, t.tags, t.source, t.transfer_id,
            c.name cat_name, a.name acc_name, u.name user_name
       FROM transactions t
       LEFT JOIN categories c ON c.id = t.category_id
       LEFT JOIN accounts a ON a.id = t.account_id
       LEFT JOIN users u ON u.id = t.user_id
      WHERE t.household_id = ? AND t.transaction_date BETWEEN ? AND ?
   ORDER BY t.transaction_date ASC, t.id ASC"
);
$stmt->execute([$householdId, $from, $to]);
$rows = $stmt->fetchAll();

$houseName = preg_replace('/[^A-Za-z0-9_]+/', '_', current_household()['name']);
$filename = 'ev_muhasebe_' . $houseName . '_' . $from . '_' . $to . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$totInc = 0; $totExp = 0;
foreach ($rows as $r) {
    if (!empty($r['transfer_id'])) continue;
    if ($r['type'] === 'income') $totInc += (float)$r['amount']; else $totExp += (float)$r['amount'];
}
$num = fn($n) => number_format((float)$n, 2, ',', '.');
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>
<table border="1">
    <tr><th colspan="8" style="font-size:14px;background:#14452F;color:#fff">Ev Muhasebe · <?= e(current_household()['name']) ?> · <?= e($from) ?> – <?= e($to) ?></th></tr>
    <tr>
        <th>Tarih</th><th>Tür</th><th>Kategori</th><th>Hesap</th><th>Açıklama</th><th>Etiketler</th><th>Tutar</th><th>Kaynak</th>
    </tr>
    <?php foreach ($rows as $r):
        $isTransfer = !empty($r['transfer_id']); ?>
    <tr>
        <td><?= date('d.m.Y', strtotime($r['transaction_date'])) ?></td>
        <td><?= $isTransfer ? 'Transfer' : ($r['type']==='income'?'Gelir':'Gider') ?></td>
        <td><?= e($r['cat_name'] ?: 'Kategorisiz') ?></td>
        <td><?= e($r['acc_name'] ?: '') ?></td>
        <td><?= e($r['description']) ?></td>
        <td><?= e($r['tags'] ?: '') ?></td>
        <td style="mso-number-format:'\@'"><?= ($r['type']==='income'?'':'-') . $num($r['amount']) ?></td>
        <td><?= $isTransfer ? 'Transfer' : ($r['source']==='import'?'PDF':'Manuel') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="8"></td></tr>
    <tr><th colspan="6" style="text-align:right">Toplam Gelir</th><td colspan="2"><?= $num($totInc) ?> TL</td></tr>
    <tr><th colspan="6" style="text-align:right">Toplam Gider</th><td colspan="2"><?= $num($totExp) ?> TL</td></tr>
    <tr><th colspan="6" style="text-align:right">Net</th><td colspan="2"><?= $num($totInc-$totExp) ?> TL</td></tr>
</table>
<p style="font-size:11px;color:#888">Transferler gelir/gider toplamına dahil değildir.</p>
</body></html>
<?php exit;
