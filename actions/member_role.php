<?php
/**
 * Üye rolünü değiştir (ev sahibi yap / üye yap).
 * Yalnızca mevcut ev sahibi (owner) çalıştırabilir.
 * Bu sayede son ev sahibi, ayrılmadan önce yetkiyi devredebilir.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_owner();
verify_csrf();

$pdo = db();
$householdId = hid();
$userId = (int)($_POST['user_id'] ?? 0);
$role   = ($_POST['role'] ?? 'owner') === 'member' ? 'member' : 'owner';

if ($userId <= 0) {
    flash('error', 'Geçersiz üye.');
    redirect('households.php');
}

// Hedef kullanıcı bu evin üyesi mi?
$chk = $pdo->prepare('SELECT role FROM household_members WHERE household_id = ? AND user_id = ? LIMIT 1');
$chk->execute([$householdId, $userId]);
$target = $chk->fetch();
if (!$target) {
    flash('error', 'Üye bu evde bulunamadı.');
    redirect('households.php');
}

// "Üye yap"a düşürmede son ev sahibini koru
if ($role === 'member') {
    $cnt = $pdo->prepare('SELECT COUNT(*) c FROM household_members WHERE household_id = ? AND role = "owner"');
    $cnt->execute([$householdId]);
    if ((int)$cnt->fetch()['c'] <= 1 && ($target['role'] === 'owner')) {
        flash('error', 'Son ev sahibinin yetkisi kaldırılamaz.');
        redirect('households.php');
    }
}

$upd = $pdo->prepare('UPDATE household_members SET role = ? WHERE household_id = ? AND user_id = ?');
$upd->execute([$role, $householdId, $userId]);

log_activity($householdId, 'member_role', 'Üye #' . $userId . ' → ' . ($role === 'owner' ? 'Ev sahibi' : 'Üye'));
flash('success', $role === 'owner' ? 'Üye artık ev sahibi.' : 'Üyenin yetkisi güncellendi.');
redirect('households.php');
