<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/mailer.php';
require_login();
verify_csrf();

$u = current_user();
if (!EMAIL_ENABLED) {
    flash('error', 'E-posta gönderimi kapalı. config.php → EMAIL_ENABLED değerini açın ve SMTP bilgilerini girin.');
    redirect('settings.php');
}
$to = filter_var($_POST['to'] ?? '', FILTER_VALIDATE_EMAIL) ? $_POST['to'] : $u['email'];
$html = email_layout('Test e-postası',
    '<p>Bu bir test mesajıdır. Bu e-postayı aldıysanız SMTP ayarlarınız doğru çalışıyor demektir. 🎉</p>',
    'Uygulamaya Dön', absolute_url('index.php'));

$err = null;
if (send_app_mail($to, APP_NAME . ' · SMTP Testi', $html, $u['name'], $err)) {
    flash('success', 'Test e-postası gönderildi: ' . e($to) . '. Gelen kutunuzu kontrol edin.');
} else {
    flash('error', 'Gönderilemedi: ' . e($err ?? 'bilinmeyen hata'));
}
redirect('settings.php');
