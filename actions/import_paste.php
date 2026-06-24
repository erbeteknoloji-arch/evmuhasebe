<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/PdfStatementParser.php';
require_household();
verify_csrf();

$householdId = hid();

$text = (string)($_POST['statement_text'] ?? '');
if (trim($text) === '' || mb_strlen($text) < 15) {
    flash('error', 'Lütfen ekstre metnini yapıştırın.');
    redirect('import.php');
}
// Aşırı büyük girişleri sınırla (güvenlik)
if (strlen($text) > 2_000_000) {
    $text = substr($text, 0, 2_000_000);
}

$parser = new PdfStatementParser();
try {
    [$rows, $method] = $parser->parseText($text);
} catch (Throwable $e) {
    flash('error', 'Metin işlenirken hata oluştu: ' . $e->getMessage());
    redirect('import.php');
}

if (empty($rows)) {
    flash('error', 'Yapıştırılan metinde işlem satırı bulunamadı. Tarih ve tutar içeren satırların eksiksiz kopyalandığından emin olun (örn. "04.06.2026 ... -144,90 TL").');
    redirect('import.php');
}

// Otomatik kategorileme — yükleme akışıyla birebir aynı mantık
$rstmt = db()->prepare(
    'SELECT r.match_text, r.category_id, c.type
       FROM import_rules r
       JOIN categories c ON c.id = r.category_id
      WHERE r.household_id = ?'
);
$rstmt->execute([$householdId]);
$rules = $rstmt->fetchAll();

$asciiFold = function (string $s): string {
    $map = ['ç'=>'c','Ç'=>'C','ğ'=>'g','Ğ'=>'G','ı'=>'i','İ'=>'I','ö'=>'o','Ö'=>'O','ş'=>'s','Ş'=>'S','ü'=>'u','Ü'=>'U'];
    return mb_strtoupper(strtr($s, $map), 'UTF-8');
};

$matched = 0;
foreach ($rows as &$row) {
    $row['category_id'] = null;
    $haystack = $asciiFold($row['description']);
    foreach ($rules as $rule) {
        if (strpos($haystack, $asciiFold($rule['match_text'])) !== false) {
            $row['category_id'] = (int)$rule['category_id'];
            $row['type'] = $rule['type'];
            $matched++;
            break;
        }
    }
}
unset($row);

$sourceName = trim((string)($_POST['source_name'] ?? ''));
if ($sourceName !== '') {
    $sourceName = mb_substr(preg_replace('/[\r\n\t]+/', ' ', $sourceName), 0, 160);
}

$_SESSION['import_staging'] = [
    'filename'  => $sourceName !== '' ? $sourceName : 'Yapıştırılan metin',
    'stored'    => null,                 // dosya yok
    'bank'      => $parser->bankName,
    'method'    => $sourceName !== '' ? 'pdf-tarayıcı' : 'paste',
    'rows'      => $rows,
    'matched'   => $matched,
];

flash('success', count($rows) . ' işlem bulundu' . ($parser->bankName ? ' (' . $parser->bankName . ')' : '') .
    '. ' . $matched . ' tanesi otomatik kategorilendi. Lütfen aşağıdan kontrol edip içe aktarın.');
redirect('import.php');
