<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/captcha.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim($_POST['identifier'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!captcha_verify()) {
        $error = 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.';
    } elseif ($identifier === '' || $pass === '') {
        $error = 'Kullanıcı adı/e-posta ve şifre gereklidir.';
    } else {
        $stmt = db()->prepare(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$identifier, mb_strtolower($identifier)]);
        $user = $stmt->fetch();

        if ($user && (int)($user['is_active'] ?? 1) !== 1) {
            $error = 'Hesabınız devre dışı bırakılmış. Lütfen yönetici ile iletişime geçin.';
        } elseif ($user && password_verify($pass, $user['password_hash'])) {
            // Oturum sabitleme saldırısına karşı oturum ID'sini yenile
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            unset($_SESSION['household_id']); // aktif ev otomatik seçilecek
            db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
            flash('success', 'Tekrar hoş geldiniz, ' . $user['name'] . '!');
            redirect('index.php');
        } else {
            $error = 'Kullanıcı adı/e-posta veya şifre hatalı.';
        }
    }
}
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(lang_dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= te('auth.login_title') ?> · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?php if (is_rtl()): ?><link rel="stylesheet" href="<?= url('assets/css/rtl.css') ?>"><?php endif; ?>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-brand">
        <div class="logo"><span class="mark">₺</span> <?= e(APP_NAME) ?></div>
        <div class="pitch">
            <h2>Hesabınıza giriş yapın.</h2>
            <p>Evinizin gelir, gider ve raporlarına kaldığınız yerden devam edin.</p>
            <ul class="features">
                <li><span class="dot">●</span> Ortak ev bütçesi</li>
                <li><span class="dot">●</span> PDF ekstre içe aktarma</li>
                <li><span class="dot">●</span> Anlık grafikler</li>
            </ul>
        </div>
        <div class="auth-foot">© <?= date('Y') ?> <?= e(APP_NAME) ?></div>
    </div>

    <div class="auth-panel">
        <div class="auth-card">
            <h1><?= te('auth.login_title') ?></h1>
            <p class="sub"><?= te('auth.login_title') ?></p>

            <?php if ($error): ?>
                <div class="flashes"><div class="flash error"><span>⚠</span> <?= e($error) ?></div></div>
            <?php endif; ?>

            <form method="post" action="<?= url('auth/login.php') ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label><?= te('auth.username') ?> / <?= te('auth.email') ?></label>
                    <input class="input" type="text" name="identifier" value="<?= e($identifier) ?>" required autofocus>
                </div>
                <div class="field">
                    <label><?= te('auth.password') ?></label>
                    <input class="input" type="password" name="password" required>
                </div>
                <?= captcha_field_html() ?>
                <button type="submit" class="btn btn-primary btn-block"><?= te('auth.sign_in') ?></button>
            </form>
            <script>function refreshCaptcha(){var i=document.getElementById('capImg');if(i){i.src='<?= url('captcha.php') ?>?r='+Date.now();}}</script>

            <div class="auth-switch">
                <?= te('auth.no_account') ?> <a href="<?= url('auth/register.php') ?>"><?= te('auth.sign_up') ?></a>
                <br><a href="<?= url('auth/forgot.php') ?>"><?= te('auth.forgot') ?></a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
