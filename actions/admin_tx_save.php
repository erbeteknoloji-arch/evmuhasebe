<?php
/**
 * Yönetici: herhangi bir hanedeki işlemi düzenle.
 * Normal transaction_save aktif haneyle sınırlıdır; bu uç yalnızca
 * site yöneticisine açıktır ve işlemi kendi hanesi üzerinden günceller.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();
verify_csrf();

$pdo = db();
$id  = (int)($_POST['id'] ?? 0);

$tx = $pdo->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
$tx->execute([$id]);
$row = $tx->fetch();
if (!$row) {
    flash('error', 'İşlem bulunamadı.');
    redirect('admin/households.php');
}
$householdId = (int)$row['household_id'];
$back = 'admin/household_view.php?id=' . $householdId;

$type   = ($_POST['type'] ?? 'expense') === 'income' ? 'income' : 'expense';
$amount = parse_money_tr($_POST['amount'] ?? '');
$desc   = mb_substr(trim($_POST['description'] ?? ''), 0, 255);
$date   = $_POST['transaction_date'] ?? '';
$categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
$accountId  = (int)($_POST['account_id'] ?? 0) ?: null;

if ($amount === null || $amount <= 0) { flash('error', 'Geçerli bir tutar girin.'); redirect($back); }
$ts = strtotime($date);
if (!$ts) { flash('error', 'Geçerli bir tarih seçin.'); redirect($back); }
$date = date('Y-m-d', $ts);

// Kategori ve hesap bu haneye mi ait?
if ($categoryId) {
    $c = $pdo->prepare('SELECT type FROM categories WHERE id=? AND household_id=?');
    $c->execute([$categoryId, $householdId]);
    $cat = $c->fetch();
    if (!$cat || $cat['type'] !== $type) $categoryId = null;
}
if ($accountId) {
    $a = $pdo->prepare('SELECT id FROM accounts WHERE id=? AND household_id=?');
    $a->execute([$accountId, $householdId]);
    if (!$a->fetch()) $accountId = null;
}

$upd = $pdo->prepare(
    'UPDATE transactions SET type=?, amount=?, description=?, transaction_date=?, category_id=?, account_id=?
      WHERE id=?'
);
$upd->execute([$type, $amount, $desc, $date, $categoryId, $accountId, $id]);

log_activity($householdId, 'transaction_update', '[admin] ' . $desc . ' · ' . money($amount));
flash('success', 'İşlem güncellendi (yönetici).');
redirect($back);
