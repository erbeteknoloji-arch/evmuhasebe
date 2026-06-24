<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rates.php';
require_household();
verify_csrf();

$code = strtoupper(preg_replace('/[^A-Za-z_]/', '', $_POST['code'] ?? ''));
$rate = parse_money_tr($_POST['rate_try'] ?? '');
$catalog = asset_catalog();
if (!isset($catalog[$code]) || $rate === null || $rate <= 0) {
    flash('error', 'Geçerli bir varlık ve kur girin.');
    redirect('assets.php');
}
upsert_rate($code, $rate, $catalog[$code][0], 'manual');
flash('success', $catalog[$code][0] . ' kuru elle güncellendi: ' . money($rate));
redirect('assets.php');
