<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$catId = (int)($_POST['category_id'] ?? 0);
$limit = parse_money_tr($_POST['monthly_limit'] ?? '0') ?? 0;
$pdo = db();
$c = $pdo->prepare('SELECT id FROM categories WHERE id = ? AND household_id = ?');
$c->execute([$catId, hid()]);
if (!$c->fetch()) { flash('error', 'Kategori bulunamadı.'); redirect('categories.php'); }
if ($limit <= 0) {
    $d = $pdo->prepare('DELETE FROM budgets WHERE household_id = ? AND category_id = ?');
    $d->execute([hid(), $catId]);
    flash('success', 'Bütçe limiti kaldırıldı.');
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO budgets (household_id, category_id, monthly_limit) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit)'
    );
    $stmt->execute([hid(), $catId, $limit]);
    flash('success', 'Aylık bütçe limiti kaydedildi: ' . money($limit));
}
redirect('categories.php');
