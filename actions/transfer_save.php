<?php
/**
 * Hesaplar arası para transferi.
 * Tek işlemde iki bağlı kayıt oluşturur:
 *   - Kaynak hesapta gider (çıkış)
 *   - Hedef hesapta gelir (giriş)
 * İkisi de aynı transfer_id ile bağlanır. Bakiyeler işlemlerden
 * türetildiği için transfer her iki hesabın bakiyesine doğru yansır.
 * Hedef bir kredi kartı ise gelir kaydı kartın borcunu azaltır
 * (kredi kartı ödemesi).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();

$householdId = hid();
$pdo = db();

$fromId = (int)($_POST['from_account'] ?? 0);
$toId   = (int)($_POST['to_account'] ?? 0);
$amount = parse_money_tr($_POST['amount'] ?? '');
$date   = $_POST['transaction_date'] ?? '';
$desc   = mb_substr(trim($_POST['description'] ?? ''), 0, 200);
$back   = $_POST['return'] ?? 'accounts.php';

if ($fromId <= 0 || $toId <= 0 || $fromId === $toId) {
    flash('error', 'Farklı bir kaynak ve hedef hesap seçin.');
    redirect($back);
}
if ($amount === null || $amount <= 0) {
    flash('error', 'Geçerli bir tutar girin.');
    redirect($back);
}
$ts = strtotime($date);
$date = $ts ? date('Y-m-d', $ts) : date('Y-m-d');

// Her iki hesap da bu eve mi ait?
$chk = $pdo->prepare('SELECT id, name, type FROM accounts WHERE id = ? AND household_id = ?');
$chk->execute([$fromId, $householdId]);
$from = $chk->fetch();
$chk->execute([$toId, $householdId]);
$to = $chk->fetch();
if (!$from || !$to) {
    flash('error', 'Hesaplardan biri bulunamadı.');
    redirect($back);
}

$label = $desc !== '' ? $desc : ('Transfer: ' . $from['name'] . ' → ' . $to['name']);
$descOut = ($to['type'] === 'credit_card' && $desc === '')
    ? ('Kart ödemesi: ' . $from['name'] . ' → ' . $to['name'])
    : $label;

$transferId = bin2hex(random_bytes(8));
$uid = $_SESSION['user_id'] ?? null;

try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare(
        'INSERT INTO transactions (household_id, account_id, category_id, user_id, type, amount, description, transaction_date, source, transfer_id)
         VALUES (?, ?, NULL, ?, ?, ?, ?, ?, "manual", ?)'
    );
    // Kaynak: gider (çıkış)
    $ins->execute([$householdId, $fromId, $uid, 'expense', $amount, $descOut, $date, $transferId]);
    // Hedef: gelir (giriş)
    $ins->execute([$householdId, $toId, $uid, 'income', $amount, $descOut, $date, $transferId]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'Transfer kaydedilemedi.');
    redirect($back);
}

log_activity($householdId, 'transfer', $from['name'] . ' → ' . $to['name'] . ' · ' . money($amount));
flash('success', 'Transfer tamamlandı: ' . money($amount) . ' · ' . $from['name'] . ' → ' . $to['name']);
redirect($back);
