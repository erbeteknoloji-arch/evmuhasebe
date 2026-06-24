<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/mailer.php';

if (is_logged_in()) {
    redirect('index.php');
}

$done = false;
$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim($_POST['identifier'] ?? '');
    if ($identifier === '') {
        $error = 'Lütfen e-posta veya kullanıcı adınızı girin.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$identifier, mb_strtolower($identifier)]);
        $user = $stmt->fetch();

        if ($user) {
            // Eski kullanılmamış jetonları temizle
            $del = db()->prepare('DELETE FROM password_resets WHERE user_id = ? AND used = 0');
            $del->execute([$user['id']]);

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 saat geçerli
            $ins = db()->prepare(
                'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
            );
            $ins->execute([$user['id'], $token, $expires]);

            $link = absolute_url('auth/reset.php?token=' . $token);
            $content = '<p>Merhaba ' . e($user['name']) . ',</p>'
                . '<p>Hesabınız için şifre sıfırlama talebinde bulunuldu. Aşağıdaki butona tıklayarak '
                . 'yeni bir şifre belirleyebilirsiniz. Bağlantı <b>1 saat</b> geçerlidir.</p>'
                . '<p style="font-size:12px;color:#8A8F99">Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz; şifreniz değişmez.</p>';
            $html = email_layout('Şifre sıfırlama', $content, 'Şifremi Sıfırla', $link);

            $mailErr = null;
            @send_app_mail($user['email'], APP_NAME . ' · Şifre Sıfırlama', $html, $user['name'], $mailErr);
            // E-posta yapılandırılmamışsa hata sızdırmadan bilgilendir (güvenlik)
        }
        // Kullanıcı bulunsa da bulunmasa da aynı mesaj (e-posta sayımı sızmasın)
        $done = true;
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Şifremi Unuttum · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-brand">
        <div class="logo"><span class="mark">₺</span> <?= e(APP_NAME) ?></div>
        <div class="pitch">
            <h2>Şifrenizi mi unuttunuz?</h2>
            <p>Sorun değil. E-posta adresinize bir sıfırlama bağlantısı gönderelim.</p>
        </div>
        <div class="auth-foot">© <?= date('Y') ?> <?= e(APP_NAME) ?></div>
    </div>

    <div class="auth-panel">
        <div class="auth-card">
            <h1>Şifre sıfırlama</h1>
            <p class="sub">Hesabınıza bağlı e-posta veya kullanıcı adınızı girin.</p>

            <?php if ($done): ?>
                <div class="flashes"><div class="flash success"><span>✓</span>
                    Eğer bu bilgiyle bir hesap varsa, şifre sıfırlama bağlantısı e-posta adresine gönderildi. Lütfen gelen kutunuzu (ve spam klasörünü) kontrol edin.
                </div></div>
                <?php if (!EMAIL_ENABLED): ?>
                    <div class="flashes"><div class="flash info"><span>ℹ</span>
                        Not: Sunucuda e-posta gönderimi henüz açılmamış. Yönetici, <code>config.php</code> içinden SMTP ayarlarını yapıp <code>EMAIL_ENABLED</code> değerini açtığında bağlantılar gönderilir.
                    </div></div>
                <?php endif; ?>
                <div class="auth-switch"><a href="<?= url('auth/login.php') ?>">← Girişe dön</a></div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="flashes"><div class="flash error"><span>⚠</span> <?= e($error) ?></div></div>
                <?php endif; ?>
                <form method="post" action="<?= url('auth/forgot.php') ?>">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label>Kullanıcı Adı veya E-posta</label>
                        <input class="input" type="text" name="identifier" value="<?= e($identifier) ?>" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Sıfırlama Bağlantısı Gönder</button>
                </form>
                <div class="auth-switch"><a href="<?= url('auth/login.php') ?>">← Girişe dön</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
