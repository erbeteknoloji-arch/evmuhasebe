<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/captcha.php';

if (is_logged_in()) {
    redirect('index.php');
}

$regOpen = site_setting('registration_enabled', '1') === '1';
$errors = [];
$old = ['name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$regOpen) {
        $errors[] = 'Yeni kayıtlar şu anda kapalıdır.';
    }

    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim(mb_strtolower($_POST['email'] ?? ''));
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';
    $old = ['name' => $name, 'username' => $username, 'email' => $email];

    if (mb_strlen($name) < 2) {
        $errors[] = 'Ad Soyad en az 2 karakter olmalı.';
    }
    if (!preg_match('/^[a-zA-Z0-9_.]{3,60}$/', $username)) {
        $errors[] = 'Kullanıcı adı 3-60 karakter olmalı (harf, rakam, _ ve . kullanılabilir).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }
    if (mb_strlen($pass) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalı.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Şifreler eşleşmiyor.';
    }
    if (empty($_POST['kvkk'])) {
        $errors[] = 'Üye olabilmek için KVKK Aydınlatma ve Açık Rıza Metni\'ni onaylamanız gerekir.';
    }
    if (!captcha_verify()) {
        $errors[] = 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.';
    }

    if (!$errors) {
        // Benzersizlik kontrolü
        $stmt = db()->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($row = $stmt->fetch()) {
            if ($row['username'] === $username) $errors[] = 'Bu kullanıcı adı zaten alınmış.';
            if ($row['email'] === $email)       $errors[] = 'Bu e-posta ile bir hesap zaten var.';
        }
    }

    if (!$errors) {
        $colors = ['#13452F', '#B98A2E', '#0E7490', '#9333EA', '#C2410C', '#15803D', '#1D4ED8', '#DB2777'];
        $color = $colors[array_rand($colors)];

        $pdo = db();
        $isFirst = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO users (name, username, email, password_hash, avatar_color, is_admin, kvkk_accepted_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $ins->execute([$name, $username, $email, password_hash($pass, PASSWORD_DEFAULT), $color, $isFirst ? 1 : 0]);
            $userId = (int)$pdo->lastInsertId();

            // İlk evi otomatik oluştur (hemen kullanılabilir olsun)
            $code = random_code(8);
            $hh = $pdo->prepare(
                'INSERT INTO households (name, currency, join_code, created_by) VALUES (?, ?, ?, ?)'
            );
            $hh->execute(['Evim', 'TRY', $code, $userId]);
            $householdId = (int)$pdo->lastInsertId();

            $mem = $pdo->prepare(
                'INSERT INTO household_members (household_id, user_id, role) VALUES (?, ?, "owner")'
            );
            $mem->execute([$householdId, $userId]);

            $pdo->commit();

            // Varsayılan kategorileri ve otomatik kuralları ekle
            $_SESSION['user_id'] = $userId;
            $_SESSION['household_id'] = $householdId;
            seed_default_categories($householdId);
            log_activity($householdId, 'household_create', 'Ev oluşturuldu: Evim');

            flash('success', 'Hoş geldiniz ' . $name . '! Hesabınız ve ilk eviniz hazır.');
            redirect('index.php');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kayıt Ol · <?= e(APP_NAME) ?></title>
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
            <h2>Evin bütçesi, tek panelde.</h2>
            <p>Gelir ve giderleri birlikte yönetin, banka ekstrelerini saniyeler içinde içe aktarın.</p>
            <ul class="features">
                <li><span class="dot">●</span> Tüm ev üyeleriyle ortak hesap paylaşımı</li>
                <li><span class="dot">●</span> Banka PDF ekstrelerini otomatik işleme</li>
                <li><span class="dot">●</span> Kategori bazlı grafikler ve raporlar</li>
                <li><span class="dot">●</span> Birden fazla ev/hane desteği</li>
            </ul>
        </div>
        <div class="auth-foot">© <?= date('Y') ?> <?= e(APP_NAME) ?> · Verileriniz kendi sunucunuzda.</div>
    </div>

    <div class="auth-panel">
        <div class="auth-card">
            <h1>Hesap oluştur</h1>
            <p class="sub">Birkaç saniyede başlayın.</p>

            <?php if ($errors): ?>
                <div class="flashes">
                    <div class="flash error"><span>⚠</span>
                        <div><?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$regOpen): ?>
                <div class="flashes"><div class="flash error"><span>⚠</span> Yeni üye kayıtları şu anda kapalıdır. Lütfen daha sonra tekrar deneyin.</div></div>
                <div class="auth-switch"><a href="<?= url('auth/login.php') ?>">← Girişe dön</a></div>
            <?php else: ?>
            <form method="post" action="<?= url('auth/register.php') ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label>Ad Soyad</label>
                    <input class="input" type="text" name="name" value="<?= e($old['name']) ?>" required autofocus>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Kullanıcı Adı</label>
                        <input class="input" type="text" name="username" value="<?= e($old['username']) ?>" required>
                    </div>
                    <div class="field">
                        <label>E-posta</label>
                        <input class="input" type="email" name="email" value="<?= e($old['email']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Şifre</label>
                        <input class="input" type="password" name="password" required>
                    </div>
                    <div class="field">
                        <label>Şifre (Tekrar)</label>
                        <input class="input" type="password" name="password2" required>
                    </div>
                </div>

                <?= captcha_field_html() ?>

                <label class="check" style="margin:4px 0 14px">
                    <input type="checkbox" name="kvkk" value="1" required>
                    <span><a href="#" onclick="document.getElementById('kvkkModal').classList.add('open');return false;"><b>KVKK Aydınlatma ve Açık Rıza Metni</b></a>'ni okudum ve onaylıyorum.</span>
                </label>

                <button type="submit" class="btn btn-primary btn-block">Hesabı Oluştur</button>
            </form>
            <?php endif; ?>

            <div class="auth-switch">
                Zaten hesabınız var mı? <a href="<?= url('auth/login.php') ?>">Giriş yapın</a>
            </div>
        </div>
    </div>
</div>

<!-- KVKK Modal -->
<div class="modal-overlay" id="kvkkModal">
    <div class="modal" style="max-width:640px">
        <div class="modal-head"><h3>KVKK Aydınlatma ve Açık Rıza Metni</h3>
            <button class="x" onclick="document.getElementById('kvkkModal').classList.remove('open')">×</button></div>
        <div class="modal-body" style="max-height:60vh;overflow:auto">
            <div style="white-space:pre-wrap;font-size:13.5px;line-height:1.6;color:var(--ink-soft)"><?= e(site_setting('kvkk_text', '')) ?></div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('kvkkModal').classList.remove('open')">Kapat</button>
        </div>
    </div>
</div>
<script>
function refreshCaptcha(){ var i=document.getElementById('capImg'); if(i){ i.src='<?= url('captcha.php') ?>?r='+Date.now(); } }
document.addEventListener('click',function(e){ if(e.target.classList&&e.target.classList.contains('modal-overlay')) e.target.classList.remove('open'); });
</script>
</body>
</html>
