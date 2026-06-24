<?php
/**
 * Kimlik doğrulama ve yetkilendirme.
 * - Oturum açmış kullanıcıyı yönetir.
 * - Aktif evi (household) yönetir; tüm veriler aktif eve göre filtrelenir.
 * - Kullanıcının yalnızca üyesi olduğu evlere erişebilmesini garanti eder.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';

/* -------------------------------------------------------------------
 *  Oturum açmış kullanıcı
 * ----------------------------------------------------------------- */
function current_user(): ?array
{
    static $user = null;
    if ($user !== null) {
        return $user ?: null;
    }
    if (empty($_SESSION['user_id'])) {
        $user = false;
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $user = $row ?: false;
    return $row ?: null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Devam etmek için giriş yapın.');
        redirect('auth/login.php');
    }
    $u = current_user();
    // Hesap pasifleştirildiyse oturumu kapat
    if ($u && (int)($u['is_active'] ?? 1) !== 1) {
        $_SESSION = [];
        session_destroy();
        @session_start();
        flash('error', 'Hesabınız devre dışı bırakılmış. Lütfen yönetici ile iletişime geçin.');
        redirect('auth/login.php');
    }
    // Bakım modu: yöneticiler hariç erişim kapalı
    if (site_setting('maintenance_mode', '0') === '1' && !is_admin_user()) {
        http_response_code(503);
        $name = e(site_name());
        echo '<!doctype html><html lang="tr"><head><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<title>Bakım · ' . $name . '</title>'
           . '<link rel="stylesheet" href="' . e(url('assets/css/style.css')) . '"></head>'
           . '<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center">'
           . '<div style="max-width:460px;padding:30px"><div style="font-size:44px">🛠️</div>'
           . '<h1>Bakım Çalışması</h1>'
           . '<p class="muted">' . $name . ' şu anda bakımda. Lütfen kısa süre sonra tekrar deneyin.</p>'
           . '<a class="btn btn-ghost" href="' . e(url('auth/logout.php')) . '">Çıkış</a></div></body></html>';
        exit;
    }
}

/* -------------------------------------------------------------------
 *  Kullanıcının üyesi olduğu evler
 * ----------------------------------------------------------------- */
function user_households(): array
{
    $u = current_user();
    if (!$u) {
        return [];
    }
    $stmt = db()->prepare(
        'SELECT h.*, hm.role
           FROM households h
           JOIN household_members hm ON hm.household_id = h.id
          WHERE hm.user_id = ?
       ORDER BY h.name ASC'
    );
    $stmt->execute([$u['id']]);
    return $stmt->fetchAll();
}

/* -------------------------------------------------------------------
 *  Kullanıcı belirli bir evin üyesi mi?  (yetkilendirme kontrolü)
 * ----------------------------------------------------------------- */
function membership(int $householdId): ?array
{
    $u = current_user();
    if (!$u) {
        return null;
    }
    $stmt = db()->prepare(
        'SELECT * FROM household_members WHERE household_id = ? AND user_id = ? LIMIT 1'
    );
    $stmt->execute([$householdId, $u['id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* -------------------------------------------------------------------
 *  Aktif ev seçimi
 * ----------------------------------------------------------------- */
function set_active_household(int $householdId): bool
{
    if (membership($householdId)) {
        $_SESSION['household_id'] = $householdId;
        return true;
    }
    return false;
}

function current_household(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached ?: null;
    }

    $u = current_user();
    if (!$u) {
        $cached = false;
        return null;
    }

    $hid = $_SESSION['household_id'] ?? null;

    // Seçili ev yoksa veya üyelik kaybolduysa ilk eve geç
    if (!$hid || !membership((int)$hid)) {
        $list = user_households();
        if (!$list) {
            $cached = false;
            return null;
        }
        $hid = (int)$list[0]['id'];
        $_SESSION['household_id'] = $hid;
    }

    $stmt = db()->prepare('SELECT * FROM households WHERE id = ? LIMIT 1');
    $stmt->execute([$hid]);
    $row = $stmt->fetch();
    $cached = $row ?: false;
    return $row ?: null;
}

/** Aktif evin ID'si (sorgularda kullanmak için) */
function hid(): int
{
    $h = current_household();
    return $h ? (int)$h['id'] : 0;
}

/** Kullanıcının aktif evdeki rolü: owner | member | null */
function current_role(): ?string
{
    $h = current_household();
    if (!$h) {
        return null;
    }
    $m = membership((int)$h['id']);
    return $m['role'] ?? null;
}

function is_owner(): bool
{
    return current_role() === 'owner';
}

/* -------------------------------------------------------------------
 *  Aktif ev gerektiren sayfalar için kapı
 * ----------------------------------------------------------------- */
function require_household(): void
{
    require_login();
    if (!current_household()) {
        // Kullanıcının hiç evi yoksa kurulum sayfasına yönlendir
        redirect('households.php?ilk=1');
    }
}

function require_owner(): void
{
    require_household();
    if (!is_owner()) {
        flash('error', 'Bu işlem için ev sahibi (owner) yetkisi gerekir.');
        redirect('households.php');
    }
}

/* -------------------------------------------------------------------
 *  Site yöneticisi (global admin) — ev sahibi (owner) rolünden farklıdır
 * ----------------------------------------------------------------- */
function is_admin_user(): bool
{
    $u = current_user();
    return $u && (int)($u['is_admin'] ?? 0) === 1;
}

function require_admin(): void
{
    require_login();
    if (!is_admin_user()) {
        flash('error', 'Bu alana erişim için yönetici yetkisi gerekir.');
        redirect('index.php');
    }
}
