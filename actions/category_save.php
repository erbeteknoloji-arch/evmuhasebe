<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$id    = (int)($_POST['id'] ?? 0);
$name  = trim($_POST['name'] ?? '');
$type  = $_POST['type'] ?? 'expense';
$color = $_POST['color'] ?? '#6B7280';
$icon  = trim($_POST['icon'] ?? '📁');
if (!in_array($type, ['income','expense'], true)) $type = 'expense';
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) $color = '#6B7280';
$icon = mb_substr($icon, 0, 4) ?: '📁';
if (mb_strlen($name) < 1) { flash('error', 'Kategori adı gerekli.'); redirect('categories.php'); }
$pdo = db();
if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE categories SET name=?, type=?, color=?, icon=? WHERE id=? AND household_id=?');
    $stmt->execute([$name, $type, $color, $icon, $id, hid()]);
    flash('success', 'Kategori güncellendi.');
} else {
    $stmt = $pdo->prepare('INSERT INTO categories (household_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([hid(), $name, $type, $color, $icon]);
    flash('success', 'Kategori eklendi: ' . $name);
}
redirect('categories.php');
