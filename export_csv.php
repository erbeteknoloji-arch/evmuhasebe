<?php
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

$from = $_GET['from'] ?? date('Y-01-01');
$to   = $_GET['to']   ?? date('Y-12-31');
$from = date('Y-m-d', strtotime($from) ?: time());
$to   = date('Y-m-d', strtotime($to) ?: time());

$stmt = $pdo->prepare(
    "SELECT t.transaction_date, t.type, t.amount, t.description, t.source,
            c.name cat_name, a.name acc_name, u.name user_name
       FROM transactions t
       LEFT JOIN categories c ON c.id = t.category_id
       LEFT JOIN accounts a ON a.id = t.account_id
       LEFT JOIN users u ON u.id = t.user_id
      WHERE t.household_id = ? AND t.transaction_date BETWEEN ? AND ?
   ORDER BY t.transaction_date ASC, t.id ASC"
);
$stmt->execute([$householdId, $from, $to]);

$houseName = preg_replace('/[^A-Za-z0-9_]+/', '_', current_household()['name']);
$filename = 'ev_muhasebe_' . $houseName . '_' . $from . '_' . $to . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel için)

// Türkçe Excel ';' ayracı kullanır
fputcsv($out, ['Tarih','Tür','Kategori','Hesap','Açıklama','Tutar','Ekleyen','Kaynak'], ';');

while ($r = $stmt->fetch()) {
    fputcsv($out, [
        date('d.m.Y', strtotime($r['transaction_date'])),
        $r['type'] === 'income' ? 'Gelir' : 'Gider',
        $r['cat_name'] ?: 'Kategorisiz',
        $r['acc_name'] ?: '',
        $r['description'],
        number_format((float)$r['amount'], 2, ',', '.'),
        $r['user_name'] ?: '',
        $r['source'] === 'import' ? 'PDF İçe Aktarma' : 'Manuel',
    ], ';');
}
fclose($out);
exit;
