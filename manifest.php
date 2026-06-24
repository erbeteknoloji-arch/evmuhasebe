<?php
/**
 * PWA Web App Manifest (dinamik).
 * Site adı/temasını ayarlardan alır, alt klasör kurulumlarında da
 * doğru çalışsın diye URL'leri url() ile üretir.
 * Giriş GEREKTİRMEZ — tarayıcı bunu oturum olmadan da çekebilmeli.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/settings.php';

header('Content-Type: application/manifest+json; charset=utf-8');

$name  = site_name();
$theme = '#14452F';

echo json_encode([
    'name'             => $name,
    'short_name'       => mb_substr($name, 0, 12),
    'description'      => site_setting('seo_description', 'Hane bütçe ve gider takibi'),
    'lang'             => 'tr',
    'dir'              => 'ltr',
    'start_url'        => url('index.php'),
    'scope'            => url(''),
    'display'          => 'standalone',
    'orientation'      => 'portrait-primary',
    'background_color' => '#F6F4EE',
    'theme_color'      => $theme,
    'icons'            => [
        ['src' => url('assets/icons/icon-192.png'),          'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => url('assets/icons/icon-512.png'),          'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => url('assets/icons/icon-512-maskable.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    'shortcuts' => [
        ['name' => 'Yeni Gider', 'url' => url('transactions.php?yeni=gider')],
        ['name' => 'Yeni Gelir', 'url' => url('transactions.php?yeni=gelir')],
        ['name' => 'Raporlar',   'url' => url('reports.php')],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
