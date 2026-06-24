<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (is_logged_in()) {
    redirect('index.php');
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$token = preg_replace('/[^a-f0-9]/', '', (string)$token);
$error = '';
$ok = false;

/** Geçerli, süresi dolmamış, kullanılmamış jetonu getirir. */
function fetch_reset(string $token): ?array
{
    if ($token === '') return null;
    $stmt = db()->prepare(
        'SELECT pr.*, u.email, u.name FROM password_resets pr
           JOIN users u ON u.id = pr.user_id
          WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW() LIMIT 1'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

$reset = fetch_reset($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$reset) {
        $error = 'Bağlantı geçersiz veya süresi dolmuş. Lütfen yeniden talep edin.';
    } else {
        $p1 = $_POST['password'] ?? '';
        $p2 = $_POST['password2'] ?? '';
        if (strlen($p1) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        } elseif ($p1 !== $p2) {
            $error = 'Şifreler eşleşmiyor.';
        } else {
            $hash = password_hash($p1, PASSWORD_DEFAULT);
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([$hash, $reset['user_id']]);
            db()->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')
                ->execute([$reset['id']]);
            // Bu kullanıcının diğer bekleyen jetonlarını da geçersiz kıl
            db()->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')
                ->execute([$reset['user_id']]);
            $ok = true;
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Yeni Şifre · <?= e(APP_NAME) ?></title>
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
            <h2>Yeni bir şifre belirleyin.</h2>
            <p>Güçlü ve size özel bir şifre seçin.</p>
        </div>
        <div class="auth-foot">© <?= date('Y') ?> <?= e(APP_NAME) ?></div>
    </div>

    <div class="auth-panel">
        <div class="auth-card">
            <h1>Şifre yenileme</h1>

            <?php if ($ok): ?>
                <div class="flashes"><div class="flash success"><span>✓</span>
                    Şifreniz güncellendi. Artık yeni şifrenizle giriş yapabilirsiniz.
                </div></div>
                <a class="btn btn-primary btn-block" href="<?= url('auth/login.php') ?>">Girişe Git</a>
            <?php elseif (!$reset): ?>
                <div class="flashes"><div class="flash error"><span>⚠</span>
                    <?= e($error ?: 'Bu sıfırlama bağlantısı geçersiz veya süresi dolmuş.') ?>
                </div></div>
                <a class="btn btn-primary btn-block" href="<?= url('auth/forgot.php') ?>">Yeni Bağlantı İste</a>
            <?php else: ?>
                <p class="sub"><?= e($reset['name']) ?> için yeni şifre.</p>
                <?php if ($error): ?>
                    <div class="flashes"><div class="flash error"><span>⚠</span> <?= e($error) ?></div></div>
                <?php endif; ?>
                <form method="post" action="<?= url('auth/reset.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="field">
                        <label>Yeni Şifre</label>
                        <input class="input" type="password" name="password" required autofocus minlength="6">
                    </div>
                    <div class="field">
                        <label>Yeni Şifre (Tekrar)</label>
                        <input class="input" type="password" name="password2" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Şifreyi Güncelle</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
