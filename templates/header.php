<?php
/**
 * Ortak başlık / iskelet.
 * Sayfa, bu dosyayı dahil etmeden önce şunları ayarlayabilir:
 *   $page_title    (zorunlu)  - üstteki başlık
 *   $page_subtitle (ops.)     - başlık altı açıklama
 *   $active        (ops.)     - aktif menü anahtarı
 *   $page_actions  (ops.)     - üst sağdaki buton HTML'i
 * Bu dosyadan önce require_household() çağrılmış olmalıdır.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$user      = current_user();
$household = current_household();
$households = user_households();
$active    = $active ?? '';
$page_subtitle = $page_subtitle ?? '';
$page_actions  = $page_actions ?? '';

$nav = ['dashboard' => ['index.php', '📊', t('nav.dashboard')],
        'transactions' => ['transactions.php', '💸', t('nav.transactions')]];
if (feature_enabled('calendar'))  $nav['calendar']  = ['calendar.php', '📅', t('nav.calendar')];
if (feature_enabled('shopping'))  $nav['shopping']  = ['shopping.php', '🛒', t('nav.shopping')];
$nav['categories'] = ['categories.php', '🏷️', t('nav.categories')];
$nav['accounts']   = ['accounts.php', '🏦', t('nav.accounts')];
if (feature_enabled('goals'))     $nav['goals']     = ['goals.php', '🎯', t('nav.goals')];
if (feature_enabled('assets'))    $nav['assets']    = ['assets.php', '💱', t('nav.assets')];
if (feature_enabled('reports'))   $nav['reports']   = ['reports.php', '📈', t('nav.reports')];
if (feature_enabled('import'))    $nav['import']    = ['import.php', '📥', t('nav.import')];
if (feature_enabled('chat'))      $nav['chat']      = ['chat.php', '💬', t('nav.chat')];
if (feature_enabled('messages'))  $nav['messages']  = ['messages.php', '✉️', t('nav.messages')];
if (feature_enabled('tickets'))   $nav['tickets']   = ['tickets.php', '🎫', t('nav.tickets')];
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(lang_dir()) ?>" data-theme="<?= e(current_theme()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? site_name()) ?> · <?= e(site_name()) ?></title>
<meta name="description" content="<?= e(site_setting('seo_description', '')) ?>">
<meta name="keywords" content="<?= e(site_setting('seo_keywords', '')) ?>">
<?php $fav = site_setting('favicon_path', ''); if ($fav): ?>
<link rel="icon" href="<?= e(url($fav)) ?>">
<?php else: ?>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/icons/favicon-32.png')) ?>">
<?php endif; ?>
<!-- PWA -->
<link rel="manifest" href="<?= e(url('manifest.php')) ?>">
<meta name="theme-color" content="#14452F">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(site_name()) ?>">
<link rel="apple-touch-icon" href="<?= e(url('assets/icons/apple-touch-icon.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?php if (is_rtl()): ?><link rel="stylesheet" href="<?= url('assets/css/rtl.css') ?>"><?php endif; ?>
</head>
<body>
<script>
/* Kenar çubuğu daraltma durumunu (yalnızca masaüstü) anında uygula - titreme olmasın */
(function(){try{
    if (localStorage.getItem('navCollapsed') === '1' &&
        window.matchMedia('(min-width:921px)').matches) {
        document.body.classList.add('nav-collapsed');
    }
}catch(e){}})();
</script>
<div class="scrim" onclick="toggleNav()"></div>
<div class="app">

    <aside class="sidebar">
        <div class="brand">
            <span class="brand-label">
            <?php $logo = site_setting('logo_path', ''); if ($logo): ?>
                <img src="<?= e(url($logo)) ?>" alt="<?= e(site_name()) ?>" style="max-height:30px;max-width:170px;vertical-align:middle">
            <?php else: ?>
                <span class="mark">₺</span> <span class="brand-text"><?= e(site_name()) ?></span>
            <?php endif; ?>
            </span>
            <button type="button" class="nav-collapse-btn" onclick="toggleSidebar()" title="Menüyü daralt/genişlet" aria-label="Menüyü daralt veya genişlet">«</button>
        </div>

        <div class="house-switch">
            <label><?= te('app.active_home') ?></label>
            <form method="post" action="<?= url('actions/household_switch.php') ?>" id="houseForm">
                <?= csrf_field() ?>
                <select name="household_id" class="input" onchange="document.getElementById('houseForm').submit()">
                    <?php foreach ($households as $h): ?>
                        <option value="<?= (int)$h['id'] ?>" <?= $h['id'] == $household['id'] ? 'selected' : '' ?>>
                            <?= e($h['name']) ?><?= $h['role'] === 'owner' ? ' ★' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <nav class="nav">
            <?php foreach ($nav as $key => $item): ?>
                <a href="<?= url($item[0]) ?>" class="<?= $active === $key ? 'active' : '' ?>" title="<?= e($item[2]) ?>">
                    <span class="ic"><?= $item[1] ?></span> <span class="label"><?= e($item[2]) ?></span>
                </a>
            <?php endforeach; ?>
            <div class="sep"></div>
            <a href="<?= url('households.php') ?>" class="<?= $active === 'households' ? 'active' : '' ?>" title="<?= te('app.members') ?>">
                <span class="ic">👥</span> <span class="label"><?= te('app.members') ?></span>
            </a>
            <a href="<?= url('settings.php') ?>" class="<?= $active === 'settings' ? 'active' : '' ?>" title="<?= te('app.settings') ?>">
                <span class="ic">⚙️</span> <span class="label"><?= te('app.settings') ?></span>
            </a>
            <?php if (is_admin_user()): ?>
            <a href="<?= url('admin/index.php') ?>" class="<?= $active === 'admin' ? 'active' : '' ?>" style="color:var(--gold)" title="<?= te('app.admin') ?>">
                <span class="ic">🛡️</span> <span class="label"><?= te('app.admin') ?></span>
            </a>
            <?php endif; ?>

            <!-- Dil seçici -->
            <form method="post" action="<?= url('actions/lang_set.php') ?>" id="langForm" style="margin-top:8px;padding:0 4px">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="">
                <label class="label" style="display:block;font-size:11px;opacity:.7;margin:0 0 4px"><?= te('app.language') ?></label>
                <select name="lang" class="input" onchange="document.getElementById('langForm').querySelector('[name=return]').value=location.href; document.getElementById('langForm').submit()">
                    <?php foreach (available_languages() as $code => $li): ?>
                        <option value="<?= e($code) ?>" <?= current_lang() === $code ? 'selected' : '' ?>><?= e($li[1] . ' ' . $li[0]) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </nav>

        <div class="me">
            <div class="av" style="background: <?= e($user['avatar_color']) ?>">
                <?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?>
            </div>
            <div class="info">
                <b><?= e($user['name']) ?></b>
                <span>@<?= e($user['username']) ?></span>
            </div>
            <a href="<?= url('auth/logout.php') ?>" class="logout" title="<?= te('app.logout') ?>">⏻</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="flex">
                <button class="menu-toggle" onclick="toggleNav()" aria-label="Menü">☰</button>
                <div class="page-title">
                    <h1><?= e($page_title ?? '') ?></h1>
                    <?php if ($page_subtitle): ?><p><?= e($page_subtitle) ?></p><?php endif; ?>
                </div>
            </div>
            <?php if ($page_actions): ?>
                <div class="actions"><?= $page_actions ?></div>
            <?php endif; ?>
        </div>

        <?php $flashes = get_flashes(); if ($flashes): ?>
            <div class="flashes">
                <?php foreach ($flashes as $f):
                    $icon = $f['type'] === 'success' ? '✓' : ($f['type'] === 'error' ? '⚠' : 'ℹ'); ?>
                    <div class="flash <?= e($f['type']) ?>"><span><?= $icon ?></span> <?= e($f['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
