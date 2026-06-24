<?php
/**
 * Evden ayrıl.
 * Kullanıcıyı aktif evin üyeliğinden çıkarır.
 * Kural: Son ev sahibi (owner), başka bir ev sahibi atanmadıkça veya ev
 * silinmedikçe evden ayrılamaz.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_household();
verify_csrf();

$pdo = db();
$me  = current_user();
$householdId = hid();
$userId = (int)$me['id'];

$mem = membership($householdId);
if (!$mem) {
    flash('error', 'Bu evin üyesi değilsiniz.');
    redirect('households.php');
}

// Son ev sahibi koruması
if (($mem['role'] ?? '') === 'owner') {
    $cnt = $pdo->prepare('SELECT COUNT(*) c FROM household_members WHERE household_id = ? AND role = "owner"');
    $cnt->execute([$householdId]);
    $ownerCount = (int)$cnt->fetch()['c'];
    if ($ownerCount <= 1) {
        flash('error', 'Tek ev sahibi olduğunuz için ayrılamazsınız. Önce başka bir üyeyi "Ev sahibi yap" ile yetkilendirin ya da evi silin.');
        redirect('households.php');
    }
}

// Üyelikten çıkar
$del = $pdo->prepare('DELETE FROM household_members WHERE household_id = ? AND user_id = ?');
$del->execute([$householdId, $userId]);

log_activity($householdId, 'member_leave', ($me['name'] ?? 'Kullanıcı') . ' evden ayrıldı');

// Aktif ev seçimini temizle -> sonraki sayfada başka eve geçilir veya kurulum açılır
unset($_SESSION['household_id']);

$name = $pdo->prepare('SELECT name FROM households WHERE id = ?');
$name->execute([$householdId]);
$h = $name->fetch();
flash('success', 'Evden ayrıldınız' . ($h ? ': ' . $h['name'] : '') . '.');

// Başka evi varsa yönetim sayfasına, yoksa kurulum akışına git
redirect('households.php');
