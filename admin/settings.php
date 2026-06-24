<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_admin();

$s = all_site_settings(true);
$csrf = csrf_token();

$features = [
    'feat_calendar'   => 'Takvim & Planlı Ödemeler',
    'feat_shopping'   => 'Alışveriş Listesi',
    'feat_goals'      => 'Birikim Hedefleri',
    'feat_assets'     => 'Varlıklar & Kurlar',
    'feat_import'     => 'PDF Ekstre İçe Aktarma',
    'feat_reports'    => 'Raporlar',
    'feat_calculator' => 'Açılır Hesap Makinesi',
    'feat_tickets'    => 'Destek Talepleri',
    'feat_chat'       => 'Topluluk & Fiyat Paylaşımı',
    'feat_messages'   => 'Birebir Mesajlaşma',
];

$page_title = 'Site Ayarları';
$page_subtitle = 'Marka, SEO, özellikler ve KVKK metni';
$active = 'admin';
require __DIR__ . '/../templates/header.php';
?>
<div class="flex" style="gap:8px;margin:6px 0 14px">
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/index.php') ?>">← Panele dön</a>
</div>

<form method="post" action="<?= url('actions/admin_settings_save.php') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div class="grid cols-2-1">
        <div class="stack">
            <!-- Marka -->
            <div class="card card-pad">
                <h3>Marka</h3>
                <div class="field"><label>Site Adı</label>
                    <input class="input" name="site_name" value="<?= e($s['site_name']) ?>"></div>
                <div class="grid grid-2">
                    <div class="field"><label>Logo (PNG/JPG/SVG)</label>
                        <?php if ($s['logo_path']): ?><div class="mb-1"><img src="<?= e(url($s['logo_path'])) ?>" style="max-height:34px;background:var(--forest);padding:4px;border-radius:6px"></div><?php endif; ?>
                        <input class="input" type="file" name="logo" accept="image/*"></div>
                    <div class="field"><label>Favicon (PNG/ICO)</label>
                        <?php if ($s['favicon_path']): ?><div class="mb-1"><img src="<?= e(url($s['favicon_path'])) ?>" style="max-height:28px"></div><?php endif; ?>
                        <input class="input" type="file" name="favicon" accept="image/*,.ico"></div>
                </div>
                <p class="muted" style="font-size:12px;margin:0">Logo boş bırakılırsa "₺ Site Adı" gösterilir.</p>
            </div>

            <!-- SEO -->
            <div class="card card-pad">
                <h3>SEO Ayarları</h3>
                <div class="field"><label>Başlık (title)</label>
                    <input class="input" name="seo_title" value="<?= e($s['seo_title']) ?>"></div>
                <div class="field"><label>Açıklama (description)</label>
                    <textarea class="input" name="seo_description" rows="2"><?= e($s['seo_description']) ?></textarea></div>
                <div class="field"><label>Anahtar Kelimeler (keywords)</label>
                    <input class="input" name="seo_keywords" value="<?= e($s['seo_keywords']) ?>"></div>
            </div>

            <!-- KVKK -->
            <div class="card card-pad">
                <h3>KVKK Metni</h3>
                <p class="muted" style="margin-top:0;font-size:12.5px">Kayıt ekranında onaylatılan aydınlatma ve açık rıza metni.</p>
                <textarea class="input" name="kvkk_text" rows="12" style="font-size:13px;line-height:1.5"><?= e($s['kvkk_text']) ?></textarea>
            </div>
        </div>

        <div class="stack">
            <!-- Erişim -->
            <div class="card card-pad">
                <h3>Erişim & Güvenlik</h3>
                <label class="check"><input type="checkbox" name="registration_enabled" <?= $s['registration_enabled']==='1'?'checked':'' ?>><span><b>Yeni kayıtlara izin ver</b></span></label>
                <label class="check"><input type="checkbox" name="captcha_enabled" <?= $s['captcha_enabled']==='1'?'checked':'' ?>><span>Giriş/kayıtta captcha doğrulaması</span></label>
                <label class="check"><input type="checkbox" name="maintenance_mode" <?= $s['maintenance_mode']==='1'?'checked':'' ?>><span><b style="color:var(--expense)">Bakım modu</b> — yöneticiler hariç erişim kapanır</span></label>
            </div>

            <!-- Özellikler -->
            <div class="card card-pad">
                <h3>Özellikler</h3>
                <p class="muted" style="margin-top:0;font-size:12.5px">Kapatılan özellikler menüden kalkar ve erişime kapanır.</p>
                <?php foreach ($features as $key => $label): ?>
                    <label class="check"><input type="checkbox" name="<?= $key ?>" <?= ($s[$key] ?? '1')==='1'?'checked':'' ?>><span><?= e($label) ?></span></label>
                <?php endforeach; ?>
            </div>

            <div class="card card-pad">
                <button class="btn btn-primary btn-block">Tüm Ayarları Kaydet</button>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../templates/footer.php';