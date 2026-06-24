<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notify.php';
require_household();
verify_csrf();

$householdId = hid();
$staging = $_SESSION['import_staging'] ?? null;

if (!$staging || empty($staging['rows'])) {
    flash('error', 'İçe aktarılacak veri bulunamadı. Lütfen tekrar PDF yükleyin.');
    redirect('import.php');
}

$include    = $_POST['include']     ?? [];     // işaretlenen satır indeksleri
$amounts    = $_POST['amount']      ?? [];
$types      = $_POST['type']        ?? [];
$cats       = $_POST['category_id'] ?? [];
$descs      = $_POST['description'] ?? [];
$dates      = $_POST['date']        ?? [];
$accountId  = (int)($_POST['account_id'] ?? 0) ?: null;
$learn      = !empty($_POST['learn_rules']);   // yeni kategori kurallarını öğren

if (empty($include)) {
    flash('error', 'Hiç satır seçmediniz.');
    redirect('import.php');
}

$pdo = db();

// Hesabın bu eve ait olduğunu doğrula
if ($accountId) {
    $a = $pdo->prepare('SELECT id FROM accounts WHERE id = ? AND household_id = ?');
    $a->execute([$accountId, $householdId]);
    if (!$a->fetch()) $accountId = null;
}

// Geçerli kategori kümesi (tür kontrolü için)
$catStmt = $pdo->prepare('SELECT id, type FROM categories WHERE household_id = ?');
$catStmt->execute([$householdId]);
$catTypes = [];
foreach ($catStmt->fetchAll() as $c) {
    $catTypes[(int)$c['id']] = $c['type'];
}

$pdo->beginTransaction();
try {
    // İçe aktarma partisi kaydı
    $batch = $pdo->prepare(
        'INSERT INTO import_batches (household_id, user_id, filename, bank_name, row_count) VALUES (?, ?, ?, ?, ?)'
    );
    $batch->execute([
        $householdId, $_SESSION['user_id'],
        $staging['filename'], $staging['bank'], count($staging['rows'])
    ]);
    $batchId = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare(
        'INSERT INTO transactions
            (household_id, account_id, category_id, user_id, type, amount, description, transaction_date, source, import_batch_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, "import", ?)'
    );
    $ruleIns = $pdo->prepare(
        'INSERT INTO import_rules (household_id, match_text, category_id) VALUES (?, ?, ?)'
    );

    $asciiFold = function (string $s): string {
        $map = ['ç'=>'c','Ç'=>'C','ğ'=>'g','Ğ'=>'G','ı'=>'i','İ'=>'I','ö'=>'o','Ö'=>'O','ş'=>'s','Ş'=>'S','ü'=>'u','Ü'=>'U'];
        return mb_strtoupper(strtr($s, $map), 'UTF-8');
    };
    // Mevcut kuralları (öğrenme tekrarını önlemek için) topla
    $existing = [];
    if ($learn) {
        $er = $pdo->prepare('SELECT match_text FROM import_rules WHERE household_id = ?');
        $er->execute([$householdId]);
        foreach ($er->fetchAll() as $r) $existing[$asciiFold($r['match_text'])] = true;
    }

    $count = 0;
    foreach ($include as $i) {
        $i = (int)$i;
        $type = ($types[$i] ?? 'expense') === 'income' ? 'income' : 'expense';
        $amount = parse_money_tr($amounts[$i] ?? '');
        if ($amount === null || $amount <= 0) continue;
        $desc = mb_substr(trim($descs[$i] ?? ''), 0, 255);
        $ts = strtotime($dates[$i] ?? '');
        $date = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
        $catId = (int)($cats[$i] ?? 0) ?: null;
        // Kategori bu eve ait ve tür uyumlu mu?
        if ($catId && (!isset($catTypes[$catId]) || $catTypes[$catId] !== $type)) {
            $catId = null;
        }

        $ins->execute([$householdId, $accountId, $catId, $_SESSION['user_id'], $type, $amount, $desc, $date, $batchId]);
        $count++;

        // Yeni kural öğren: bu açıklama için ilk kelimeyi -> kategori eşle
        if ($learn && $catId && $desc !== '') {
            $key = $asciiFold(preg_split('/\s+/', $desc)[0] ?? '');
            if (mb_strlen($key) >= 3 && !isset($existing[$key])) {
                $ruleIns->execute([$householdId, $key, $catId]);
                $existing[$key] = true;
            }
        }
    }

    $pdo->prepare('UPDATE import_batches SET imported_count = ? WHERE id = ?')->execute([$count, $batchId]);
    $pdo->commit();

    log_activity($householdId, 'import_commit', $count . ' işlem içe aktarıldı (' . $staging['filename'] . ')');

    // Geçici dosyayı ve hazırlık verisini temizle
    if (!empty($staging['stored'])) {
        @unlink(UPLOAD_DIR . '/' . $staging['stored']);
    }
    unset($_SESSION['import_staging']);

    flash('success', $count . ' işlem başarıyla içe aktarıldı.');
    redirect('transactions.php');

} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'İçe aktarma sırasında hata: ' . $e->getMessage());
    redirect('import.php');
}
