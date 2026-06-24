<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notify.php';
require_household();
verify_csrf();

$householdId = hid();
$id          = (int)($_POST['id'] ?? 0);
$type        = $_POST['type'] ?? 'expense';
$amount      = parse_money_tr($_POST['amount'] ?? '');
$desc        = trim($_POST['description'] ?? '');
$date        = $_POST['transaction_date'] ?? '';
$categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
$accountId   = (int)($_POST['account_id'] ?? 0) ?: null;
$tags        = trim($_POST['tags'] ?? '');
$tags        = $tags !== '' ? mb_substr(preg_replace('/[,\s]+/', ' ', $tags), 0, 255) : null;

$back = $_POST['return'] ?? 'transactions.php';

// Doğrulama
if (!in_array($type, ['income', 'expense'], true)) {
    flash('error', 'Geçersiz işlem türü.');
    redirect($back);
}
if ($amount === null || $amount <= 0) {
    flash('error', 'Geçerli bir tutar girin (örn. 1.250,00).');
    redirect($back);
}
$ts = strtotime($date);
if (!$ts) {
    flash('error', 'Geçerli bir tarih seçin.');
    redirect($back);
}
$date = date('Y-m-d', $ts);
$desc = mb_substr($desc, 0, 255);

$pdo = db();

// Kategori bu eve ait mi ve tür uyuyor mu?
if ($categoryId) {
    $c = $pdo->prepare('SELECT type FROM categories WHERE id = ? AND household_id = ?');
    $c->execute([$categoryId, $householdId]);
    $cat = $c->fetch();
    if (!$cat || $cat['type'] !== $type) {
        $categoryId = null; // uyumsuzsa kategoriyi boş bırak
    }
}
// Hesap bu eve ait mi?
if ($accountId) {
    $a = $pdo->prepare('SELECT id FROM accounts WHERE id = ? AND household_id = ?');
    $a->execute([$accountId, $householdId]);
    if (!$a->fetch()) {
        $accountId = null;
    }
}

if ($id > 0) {
    // Güncelle (yalnızca bu eve ait kayıt)
    $stmt = $pdo->prepare(
        'UPDATE transactions
            SET type = ?, amount = ?, description = ?, transaction_date = ?,
                category_id = ?, account_id = ?, tags = ?
          WHERE id = ? AND household_id = ?'
    );
    $stmt->execute([$type, $amount, $desc, $date, $categoryId, $accountId, $tags, $id, $householdId]);
    log_activity($householdId, 'transaction_update', $desc . ' · ' . money($amount));
    flash('success', 'İşlem güncellendi.');
} else {
    // Yeni kayıt
    $stmt = $pdo->prepare(
        'INSERT INTO transactions
            (household_id, account_id, category_id, user_id, type, amount, description, transaction_date, source, tags)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, "manual", ?)'
    );
    $stmt->execute([$householdId, $accountId, $categoryId, $_SESSION['user_id'], $type, $amount, $desc, $date, $tags]);
    log_activity($householdId, 'transaction_create', $desc . ' · ' . money($amount));
    flash('success', ($type === 'income' ? 'Gelir' : 'Gider') . ' eklendi: ' . money($amount));

    notify_household($householdId, 'transactions',
        APP_NAME . ' · Yeni ' . ($type === 'income' ? 'gelir' : 'gider'),
        'Yeni ' . ($type === 'income' ? 'gelir' : 'gider') . ' eklendi',
        '<p><b>' . e($desc !== '' ? $desc : ($type === 'income' ? 'Gelir' : 'Gider')) . '</b> · '
        . money($amount) . ' (' . format_date($date) . ') <br>'
        . e(current_user()['name']) . ' tarafından eklendi.</p>',
        (int)$_SESSION['user_id'],
        'İşlemleri Gör', absolute_url('transactions.php'));
}

redirect($back);
