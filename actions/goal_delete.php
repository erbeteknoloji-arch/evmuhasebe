<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$householdId = hid();
$id = (int)($_POST['id'] ?? 0);
$pdo = db();

// Yalnızca bu eve ait hedefi sil; önce sahipliği doğrula
$own = $pdo->prepare('SELECT id FROM savings_goals WHERE id=? AND household_id=? LIMIT 1');
$own->execute([$id, $householdId]);

if ($own->fetch()) {
    // İlişkili birikim kayıtlarını da temizle
    $pdo->prepare('DELETE FROM goal_contributions WHERE goal_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM savings_goals WHERE id=? AND household_id=?')->execute([$id, $householdId]);
    log_activity($householdId, 'goal_delete', 'Hedef silindi #' . $id);
    flash('success', 'Hedef silindi.');
} else {
    flash('error', 'Hedef bulunamadı veya bu işleme yetkiniz yok.');
}
redirect('goals.php');
