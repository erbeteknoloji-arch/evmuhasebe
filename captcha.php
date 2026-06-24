<?php
/**
 * Captcha resmi üretir (GD). Kod oturuma yazılır, captcha.php?r=... ile çağrılır.
 * GD yoksa matematik yedeği kullanıldığından bu uç nokta çağrılmaz.
 */
require_once __DIR__ . '/includes/functions.php';   // oturum + ayarlar
require_once __DIR__ . '/includes/captcha.php';

if (!captcha_gd_available()) {
    http_response_code(404);
    exit;
}

// Belirsiz karakterler hariç 5 haneli kod
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 5; $i++) {
    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}
$_SESSION['captcha_code'] = $code;
$_SESSION['captcha_mode'] = 'image';

$w = 170; $h = 52;
$img = imagecreatetruecolor($w, $h);
$bg   = imagecolorallocate($img, 246, 244, 238);   // krem
$fg   = imagecolorallocate($img, 20, 69, 47);      // orman yeşili
$noise= imagecolorallocate($img, 185, 138, 46);    // altın
imagefilledrectangle($img, 0, 0, $w, $h, $bg);

// Gürültü çizgileri
for ($i = 0; $i < 6; $i++) {
    imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $noise);
}
for ($i = 0; $i < 120; $i++) {
    imagesetpixel($img, random_int(0, $w), random_int(0, $h), $fg);
}

// Karakterleri hafif kaydırarak yaz
$x = 18;
for ($i = 0; $i < strlen($code); $i++) {
    $y = random_int(14, 26);
    imagestring($img, 5, $x, $y, $code[$i], $fg);
    $x += 28;
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
imagepng($img);
imagedestroy($img);
