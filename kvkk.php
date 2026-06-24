<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/settings.php';
$text = site_setting('kvkk_text', '');
$name = site_name();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>KVKK Aydınlatma Metni · <?= e($name) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body style="padding:24px">
<div style="max-width:760px;margin:0 auto">
    <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:14px">
        <h1 style="margin:0">₺ <?= e($name) ?></h1>
        <a class="btn btn-ghost btn-sm" href="<?= url('auth/register.php') ?>">← Kayıt sayfasına dön</a>
    </div>
    <div class="card card-pad">
        <h2 style="margin-top:0">KVKK Aydınlatma ve Açık Rıza Metni</h2>
        <div style="white-space:pre-wrap;font-size:14px;line-height:1.7;color:var(--ink-soft)"><?= e($text) ?></div>
    </div>
    <p class="muted" style="text-align:center;margin-top:18px;font-size:12.5px">© <?= date('Y') ?> <?= e($name) ?></p>
</div>
</body>
</html>
