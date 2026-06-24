<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/PdfStatementParser.php';
require_household();
verify_csrf();

$householdId = hid();

// Dosya kontrolü
if (empty($_FILES['statement']) || $_FILES['statement']['error'] !== UPLOAD_ERR_OK) {
    flash('error', 'Lütfen geçerli bir PDF dosyası seçin.');
    redirect('import.php');
}

$file = $_FILES['statement'];
$sizeMb = $file['size'] / (1024 * 1024);
if ($sizeMb > MAX_UPLOAD_MB) {
    flash('error', 'Dosya çok büyük (en fazla ' . MAX_UPLOAD_MB . ' MB).');
    redirect('import.php');
}

// Uzantı ve içerik türü kontrolü
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$head = @file_get_contents($file['tmp_name'], false, null, 0, 5);
if ($ext !== 'pdf' || strpos((string)$head, '%PDF') !== 0) {
    flash('error', 'Yalnızca PDF dosyaları desteklenir.');
    redirect('import.php');
}

// Yükleme klasörünü hazırla
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0775, true);
}
$safeName = 'ekstre_' . $householdId . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.pdf';
$destPath = UPLOAD_DIR . '/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    // Bazı ortamlarda move_uploaded_file kısıtlı olabilir
    if (!@copy($file['tmp_name'], $destPath)) {
        flash('error', 'Dosya kaydedilemedi. Klasör yazma izinlerini kontrol edin (assets/uploads).');
        redirect('import.php');
    }
}

// PDF'yi ayrıştır
$parser = new PdfStatementParser();
try {
    [$rows, $method] = $parser->parse($destPath);
} catch (Throwable $e) {
    flash('error', 'PDF okunurken hata oluştu: ' . $e->getMessage());
    redirect('import.php');
}

if (empty($rows)) {
    $hint = ($method === 'php')
        ? 'Sunucunuzda otomatik PDF okuma aracı (pdftotext) bulunmuyor olabilir. '
        : '';
    flash('error', 'Bu PDF içinde işlem satırı bulunamadı. ' . $hint .
        'Lütfen aşağıdaki "Ekstre Metnini Yapıştır" seçeneğini kullanın: PDF\'i açıp Tümünü Seç (Ctrl+A), Kopyala (Ctrl+C) ve metni yapıştırın.');
    @unlink($destPath);
    redirect('import.php');
}

// Otomatik kategorileme: açıklamayı kurallarla eşleştir
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
            // Eşleşen kural güçlü bir sinyaldir: türü de kategorinin türüne hizala
            $row['type'] = $rule['type'];
            $matched++;
            break;
        }
    }
}
unset($row);

// Oturumda hazırlık verisini sakla (kullanıcı gözden geçirecek)
$_SESSION['import_staging'] = [
    'filename'  => $file['name'],
    'stored'    => $safeName,
    'bank'      => $parser->bankName,
    'method'    => $method,
    'rows'      => $rows,
    'matched'   => $matched,
];

flash('success', count($rows) . ' işlem bulundu' . ($parser->bankName ? ' (' . $parser->bankName . ')' : '') .
    '. ' . $matched . ' tanesi otomatik kategorilendi. Lütfen aşağıdan kontrol edip içe aktarın.');
redirect('import.php');
