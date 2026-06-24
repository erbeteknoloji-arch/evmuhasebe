<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();
$householdId = hid();
$id = (int)($_POST['id'] ?? 0);
$pdo = db();

// İlişkili kredi kartı ekstre bağlantısını temizle (varsa)
try {
    $pdo->prepare('UPDATE cc_statements SET scheduled_item_id = NULL WHERE scheduled_item_id = ? AND household_id = ?')
        ->execute([$id, $householdId]);
} catch (Throwable $e) { /* cc_statements yoksa yok say */ }

$stmt = $pdo->prepare('DELETE FROM scheduled_items WHERE id=? AND household_id=?');
$stmt->execute([$id, $householdId]);

if ($stmt->rowCount()) {
    log_activity($householdId, 'scheduled_delete', 'Planlı ödeme silindi #' . $id);
    flash('success', 'Planlı ödeme silindi.');
} else {
    flash('error', 'Planlı ödeme bulunamadı.');
}
redirect('calendar.php');
