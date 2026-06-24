<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();
verify_csrf();

// Metin ayarları
foreach (['site_name','seo_title','seo_description','seo_keywords','kvkk_text'] as $k) {
    if (isset($_POST[$k])) set_site_setting($k, trim($_POST[$k]));
}
// Açma/kapama ayarları (checkbox)
$bools = ['registration_enabled','captcha_enabled','maintenance_mode',
          'feat_calendar','feat_goals','feat_assets','feat_import','feat_reports',
          'feat_calculator','feat_tickets','feat_chat','feat_messages','feat_shopping'];
foreach ($bools as $k) {
    set_site_setting($k, isset($_POST[$k]) ? '1' : '0');
}

// Logo / favicon yükleme
$brandDir = UPLOAD_DIR . '/brand';
if (!is_dir($brandDir)) @mkdir($brandDir, 0775, true);
$allowed = ['png','jpg','jpeg','gif','webp','svg','ico'];

function save_brand_file(string $field, array $allowed, string $brandDir): ?string
{
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? 1) !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return null;
    if (($_FILES[$field]['size'] ?? 0) > 3 * 1024 * 1024) return null; // 3MB
    // Görsel doğrulama (svg/ico hariç getimagesize ile)
    if (!in_array($ext, ['svg','ico'], true) && @getimagesize($_FILES[$field]['tmp_name']) === false) return null;
    $name = $field . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $brandDir . '/' . $name;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) return null;
    return 'assets/uploads/brand/' . $name;
}

$logo = save_brand_file('logo', $allowed, $brandDir);
if ($logo) set_site_setting('logo_path', $logo);
$fav = save_brand_file('favicon', $allowed, $brandDir);
if ($fav) set_site_setting('favicon_path', $fav);

flash('success', 'Site ayarları kaydedildi.');
redirect('admin/settings.php');
