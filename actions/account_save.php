<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$id   = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$type = $_POST['type'] ?? 'bank';
$bank = trim($_POST['bank_name'] ?? '');
$open = parse_money_tr($_POST['opening_balance'] ?? '0') ?? 0;
if (!in_array($type, ['cash','bank','credit_card'], true)) $type = 'bank';
if (mb_strlen($name) < 1) { flash('error', 'Hesap adı gerekli.'); redirect('accounts.php'); }

/* ---- Kredi kartı alanları (yalnızca tür credit_card ise) ---- */
$creditLimit = null; $statementDay = null; $dueDay = null; $minPct = 20.00;
if ($type === 'credit_card') {
    $cl = parse_money_tr($_POST['credit_limit'] ?? '');
    $creditLimit = ($cl !== null && $cl >= 0) ? $cl : null;

    $sd = (int)($_POST['statement_day'] ?? 0);
    $statementDay = ($sd >= 1 && $sd <= 31) ? $sd : null;

    $dd = (int)($_POST['due_day'] ?? 0);
    $dueDay = ($dd >= 1 && $dd <= 31) ? $dd : null;

    $mp = parse_money_tr($_POST['min_payment_pct'] ?? '');
    if ($mp === null) { $mp = (float)($_POST['min_payment_pct'] ?? 20); }
    $minPct = ($mp >= 0 && $mp <= 100) ? round($mp, 2) : 20.00;
}

$pdo = db();
if ($id > 0) {
    $stmt = $pdo->prepare(
        'UPDATE accounts
            SET name=?, type=?, bank_name=?, opening_balance=?,
                credit_limit=?, statement_day=?, due_day=?, min_payment_pct=?
          WHERE id=? AND household_id=?'
    );
    $stmt->execute([$name, $type, $bank ?: null, $open,
        $creditLimit, $statementDay, $dueDay, $minPct, $id, hid()]);
    log_activity(hid(), 'account_update', $name);
    flash('success', 'Hesap güncellendi.');
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO accounts (household_id, name, type, bank_name, opening_balance, credit_limit, statement_day, due_day, min_payment_pct)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([hid(), $name, $type, $bank ?: null, $open,
        $creditLimit, $statementDay, $dueDay, $minPct]);
    log_activity(hid(), 'account_create', $name);
    flash('success', 'Hesap eklendi: ' . $name);
}

/* Kredi kartı eklenip/güncellenince eksik ekstreleri tembel üret */
if ($type === 'credit_card') {
    require_once __DIR__ . '/../includes/credit.php';
    cc_generate_statements(hid());
}

redirect('accounts.php');
