<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rates.php';
require_household();
verify_csrf();
try {
    $updated = refresh_rates();
    if ($updated) {
        flash('success', count($updated) . ' kur güncellendi.');
    } else {
        flash('error', 'Kurlar çekilemedi. Sunucunuzun internet erişimi olmayabilir; kurları elle de girebilirsiniz.');
    }
} catch (Throwable $e) {
    flash('error', 'Kur güncellenirken hata: ' . $e->getMessage());
}
redirect('assets.php');
