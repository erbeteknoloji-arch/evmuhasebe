<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('DELETE FROM categories WHERE id = ? AND household_id = ?');
$stmt->execute([$id, hid()]);
if ($stmt->rowCount()) flash('success', 'Kategori silindi. İlgili işlemler “kategorisiz” oldu.');
else flash('error', 'Kategori bulunamadı.');
redirect('categories.php');
