<?php
/**
 * Site ayarları (anahtar-değer) + özellik anahtarları.
 * Admin panelinden düzenlenir; tüm uygulama bu değerleri okur.
 */

require_once __DIR__ . '/db.php';

/** Varsayılan ayarlar (DB'de yoksa bunlar geçerlidir). */
function default_settings(): array
{
    return [
        'site_name'        => 'Ev Muhasebe',
        'logo_path'        => '',
        'favicon_path'     => '',
        'seo_title'        => 'Ev Muhasebe · Hane Bütçe ve Gider Takibi',
        'seo_description'  => 'Ev halkı için gelir-gider takibi, bütçe, raporlar ve banka ekstresi içe aktarma.',
        'seo_keywords'     => 'ev bütçesi, gider takibi, bütçe, muhasebe, aile bütçesi',
        // Erişim / güvenlik
        'registration_enabled' => '1',
        'captcha_enabled'      => '1',
        'maintenance_mode'     => '0',
        // Özellik anahtarları (1=açık, 0=kapalı)
        'feat_calendar'    => '1',
        'feat_shopping'    => '1',
        'feat_goals'       => '1',
        'feat_assets'      => '1',
        'feat_import'      => '1',
        'feat_reports'     => '1',
        'feat_tickets'     => '1',
        'feat_chat'        => '1',
        'feat_messages'    => '1',
        'feat_calculator'  => '1',
        // KVKK aydınlatma + açık rıza metni (admin düzenleyebilir)
        'kvkk_text'        => default_kvkk_text(),
    ];
}

/** Tüm ayarları (DB + varsayılan) döndürür, önbellekli. */
function all_site_settings(bool $fresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$fresh) {
        return $cache;
    }
    $defaults = default_settings();
    try {
        $rows = db()->query('SELECT skey, svalue FROM site_settings')->fetchAll();
        foreach ($rows as $r) {
            $defaults[$r['skey']] = $r['svalue'];
        }
    } catch (Throwable $e) {
        // tablo yoksa varsayılanlar geçerli
    }
    $cache = $defaults;
    return $cache;
}

function site_setting(string $key, $default = null)
{
    $all = all_site_settings();
    return $all[$key] ?? $default;
}

function set_site_setting(string $key, $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO site_settings (skey, svalue, updated_at) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = NOW()'
    );
    $stmt->execute([$key, (string)$value]);
    all_site_settings(true); // önbelleği tazele
}

/** Bir özellik açık mı? */
function feature_enabled(string $feat): bool
{
    return site_setting('feat_' . $feat, '1') === '1';
}

/** Bir özellik kapalıysa kullanıcıyı panele yönlendirir (sayfa koruması). */
function require_feature(string $feat): void
{
    if (!feature_enabled($feat)) {
        if (function_exists('flash')) flash('error', 'Bu özellik şu anda devre dışı.');
        if (function_exists('redirect')) redirect('index.php');
        exit;
    }
}

function site_name(): string
{
    return site_setting('site_name', 'Ev Muhasebe') ?: 'Ev Muhasebe';
}

/** Varsayılan KVKK aydınlatma + açık rıza metni. */
function default_kvkk_text(): string
{
    return <<<TXT
KİŞİSEL VERİLERİN KORUNMASI HAKKINDA AYDINLATMA VE AÇIK RIZA METNİ

İşbu metin, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") kapsamında, veri sorumlusu sıfatıyla tarafımızca hazırlanmıştır.

1) İŞLENEN KİŞİSEL VERİLER
Üyelik ve hizmetin sunulması amacıyla; ad-soyad, kullanıcı adı, e-posta adresiniz ve tarafınızca sisteme girilen gelir-gider/bütçe verileri işlenmektedir.

2) İŞLEME AMAÇLARI
Kişisel verileriniz; üyelik kaydının oluşturulması, hizmetin sunulması, hesabınızın güvenliğinin sağlanması, talep ve şikayetlerinizin yönetilmesi ve yasal yükümlülüklerin yerine getirilmesi amaçlarıyla işlenir.

3) HUKUKİ SEBEP
Verileriniz, KVKK madde 5 kapsamında "bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması", "hukuki yükümlülük" ve gerektiğinde "açık rızanız" hukuki sebeplerine dayanılarak işlenir.

4) AKTARIM
Kişisel verileriniz, yasal zorunluluklar dışında üçüncü kişilerle paylaşılmaz. Veriler, hizmetin barındırıldığı sunucu/altyapı sağlayıcısı nezdinde saklanır.

5) HAKLARINIZ (KVKK m.11)
Kişisel verilerinizin işlenip işlenmediğini öğrenme, düzeltilmesini veya silinmesini isteme, işlemenin sınırlandırılmasını talep etme ve kanunda sayılan diğer haklarınızı kullanma hakkına sahipsiniz. Taleplerinizi destek/iletişim kanallarımız üzerinden iletebilirsiniz.

6) AÇIK RIZA
İşbu metni okuduğunuzu, kişisel verilerinizin yukarıda belirtilen kapsamda işlenmesine açık rıza gösterdiğinizi kabul edersiniz.

Not: Bu metin genel bir bilgilendirme şablonudur. Yürürlükteki mevzuata ve kendi işleme faaliyetlerinize tam uyum için bir hukuk danışmanından görüş almanız önerilir.
TXT;
}
