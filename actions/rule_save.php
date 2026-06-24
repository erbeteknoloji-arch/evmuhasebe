<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$match = trim($_POST['match_text'] ?? '');
$catId = (int)($_POST['category_id'] ?? 0);
$back = $_POST['return'] ?? 'categories.php';
if (mb_strlen($match) < 2 || !$catId) { flash('error', 'Anahtar kelime ve kategori gerekli.'); redirect($back); }
$pdo = db();
$c = $pdo->prepare('SELECT id FROM categories WHERE id = ? AND household_id = ?');
$c->execute([$catId, hid()]);
if (!$c->fetch()) { flash('error', 'Kategori bulunamadı.'); redirect($back); }
$stmt = $pdo->prepare('INSERT INTO import_rules (household_id, match_text, category_id) VALUES (?, ?, ?)');
$stmt->execute([hid(), mb_strtoupper($match, 'UTF-8'), $catId]);
flash('success', 'Otomatik kural eklendi.');
redirect($back);
