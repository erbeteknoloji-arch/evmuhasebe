<?php
require_once __DIR__ . '/includes/auth.php';
require_household();

$u = current_user();
$themes = theme_list();
$cur = current_theme();

$vapidPub = '';
if (is_file(__DIR__ . '/config/vapid.php')) {
    require_once __DIR__ . '/config/vapid.php';
    $vapidPub = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';
}

$page_title = t('page.settings.title');
$page_subtitle = t('page.settings.sub');
$active = 'settings';
require __DIR__ . '/templates/header.php';
?>

<div class="grid cols-2-1 mt-2">
    <div class="stack">
        <!-- PROFİL & BİLDİRİMLER -->
        <div class="card card-pad">
            <h3>Profil & Bildirim Tercihleri</h3>
            <form method="post" action="<?= url('actions/profile_save.php') ?>">
                <?= csrf_field() ?>
                <div class="grid grid-2">
                    <div class="field"><label>Ad Soyad</label>
                        <input class="input" name="name" value="<?= e($u['name']) ?>" required></div>
                    <div class="field"><label>E-posta</label>
                        <input class="input" value="<?= e($u['email']) ?>" disabled></div>
                </div>

                <h4 style="margin:14px 0 8px">E-posta Bildirimleri</h4>
                <label class="check"><input type="checkbox" name="notify_email" <?= $u['notify_email']?'checked':'' ?>>
                    <span><b>E-posta bildirimleri açık</b> — kapatırsanız hiç e-posta almazsınız (ana anahtar)</span></label>
                <label class="check"><input type="checkbox" name="notify_transactions" <?= $u['notify_transactions']?'checked':'' ?>>
                    <span>Yeni gelir/gider işlemleri ve ödemeler</span></label>
                <label class="check"><input type="checkbox" name="notify_imports" <?= $u['notify_imports']?'checked':'' ?>>
                    <span>PDF/ekstre içe aktarmaları</span></label>
                <label class="check"><input type="checkbox" name="notify_upcoming" <?= $u['notify_upcoming']?'checked':'' ?>>
                    <span>Yaklaşan ödeme hatırlatmaları</span></label>
                <label class="check"><input type="checkbox" name="notify_goals" <?= $u['notify_goals']?'checked':'' ?>>
                    <span>Birikim hedefi gelişmeleri</span></label>

                <div class="mt-2"><button class="btn btn-primary">Kaydet</button></div>
            </form>
        </div>

        <!-- DİL -->
        <div class="card card-pad">
            <h3>🌐 <?= te('settings.language') ?></h3>
            <p class="muted" style="margin-top:0;font-size:12.5px"><?= te('settings.language_desc') ?></p>
            <form method="post" action="<?= url('actions/lang_set.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e(url('settings.php')) ?>">
                <select name="lang" class="input" onchange="this.form.submit()">
                    <?php foreach (available_languages() as $code => $li): ?>
                        <option value="<?= e($code) ?>" <?= current_lang() === $code ? 'selected' : '' ?>><?= e($li[1] . '  ' . $li[0]) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- TEMA -->
        <div class="card card-pad">
            <h3><?= te('settings.theme') ?></h3>
            <p class="muted" style="margin-top:0">Bir tema seçin; anında uygulanır ve hesabınıza kaydedilir.</p>
            <form method="post" action="<?= url('actions/theme_set.php') ?>" id="themeForm">
                <?= csrf_field() ?>
                <input type="hidden" name="theme" id="themeInput" value="<?= e($cur) ?>">
                <div class="theme-grid">
                    <?php foreach ($themes as $key => $t): ?>
                        <button type="button" class="theme-swatch <?= $cur===$key?'active':'' ?>" data-theme="<?= e($key) ?>"
                                onclick="pickTheme('<?= e($key) ?>')">
                            <span class="sw" style="background:<?= e($t[1]) ?>"></span>
                            <span class="sw2" style="background:<?= e($t[2]) ?>"></span>
                            <span class="tn"><?= e($t[0]) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="stack">
        <!-- TELEFON/TARAYICI BİLDİRİMLERİ (Web Push) -->
        <div class="card card-pad">
            <h3>📲 Telefon / Tarayıcı Bildirimleri</h3>
            <p class="muted" style="margin-top:0;font-size:12.5px">
                Bu cihazda anlık bildirim alın (yeni işlem, yaklaşan/otomatik ödeme, bütçe aşımı).
                <b>iPhone'da</b> önce uygulamayı <b>Ana Ekrana ekleyin</b> ve oradan açın; ardından bildirimi etkinleştirin.
            </p>
            <div class="kv"><div><span>Bu cihazda durum</span><b id="pushState">—</b></div></div>
            <div class="flex" style="gap:8px;flex-wrap:wrap;margin-top:10px">
                <button type="button" class="btn btn-primary btn-sm" onclick="pushEnable()">Bildirimleri Aç</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="pushDisable()">Kapat</button>
                <button type="button" class="btn btn-gold btn-sm" onclick="pushTest()">Test Gönder</button>
            </div>
            <p class="muted" id="pushHint" style="font-size:12px;margin:10px 0 0"></p>
        </div>

        <!-- E-POSTA / SMTP -->
        <div class="card card-pad">
            <h3>E-posta (SMTP) Durumu</h3>
            <div class="kv">
                <div><span>Durum</span><b style="color:<?= EMAIL_ENABLED?'var(--income)':'var(--expense)' ?>">
                    <?= EMAIL_ENABLED ? 'Açık' : 'Kapalı' ?></b></div>
                <div><span>SMTP Sunucu</span><b><?= e(SMTP_HOST) ?>:<?= (int)SMTP_PORT ?> (<?= e(SMTP_SECURE ?: 'yok') ?>)</b></div>
                <div><span>Gönderen</span><b><?= e(MAIL_FROM) ?></b></div>
            </div>
            <?php if (EMAIL_ENABLED): ?>
            <form method="post" action="<?= url('actions/mail_test.php') ?>" class="mt-2">
                <?= csrf_field() ?>
                <div class="field"><label>Test e-postası gönder</label>
                    <input class="input" type="email" name="to" value="<?= e($u['email']) ?>"></div>
                <button class="btn btn-gold btn-sm">Test E-postası Gönder</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- KURLAR -->
        <div class="card card-pad">
            <h3>Döviz/Altın Kurları</h3>
            <div class="kv">
                <div><span>Otomatik çekme</span><b><?= RATES_AUTO_FETCH?'Açık':'Kapalı' ?></b></div>
                <div><span>Yenileme aralığı</span><b><?= (int)RATES_TTL_HOURS ?> saat</b></div>
            </div>
            <a class="btn btn-ghost btn-sm mt-2" href="<?= url('assets.php') ?>">Varlıklar & Kurlar →</a>
        </div>
    </div>
</div>

<?php
$themeSetUrl = url('actions/theme_set.php');
$subUrl   = url('actions/push_subscribe.php');
$unsubUrl = url('actions/push_unsubscribe.php');
$testUrl  = url('actions/push_test.php');
$vapidJs  = $vapidPub;
$csrf = csrf_token();
$inline_script = <<<JS
function pickTheme(key){
    document.documentElement.setAttribute('data-theme', key);
    document.getElementById('themeInput').value=key;
    document.querySelectorAll('.theme-swatch').forEach(function(b){
        b.classList.toggle('active', b.getAttribute('data-theme')===key);
    });
    // Sunucuya kaydet (AJAX)
    var fd=new URLSearchParams();
    fd.append('csrf_token','$csrf'); fd.append('theme',key); fd.append('ajax','1');
    fetch('$themeSetUrl',{method:'POST',body:fd,headers:{'Content-Type':'application/x-www-form-urlencoded'}});
}

/* ---- Web Push (bildirimler) ---- */
var VAPID_PUB='$vapidJs';
function urlB64ToUint8(base64){
    var pad='='.repeat((4 - base64.length % 4) % 4);
    var b=(base64+pad).replace(/-/g,'+').replace(/_/g,'/');
    var raw=atob(b); var arr=new Uint8Array(raw.length);
    for(var i=0;i<raw.length;i++) arr[i]=raw.charCodeAt(i);
    return arr;
}
function setPushHint(msg){ var el=document.getElementById('pushHint'); if(el) el.textContent=msg||''; }
function setPushState(s){ var el=document.getElementById('pushState'); if(el) el.textContent=s; }
async function pushEnable(){
    if(!VAPID_PUB){ setPushHint('Sunucu anahtarı (VAPID) ayarlı değil.'); return; }
    if(!('serviceWorker' in navigator) || !('PushManager' in window)){
        setPushHint('Bu tarayıcı bildirimleri desteklemiyor. iPhone icin once uygulamayi Ana Ekrana ekleyin ve oradan acin.');
        return;
    }
    try{
        var perm=await Notification.requestPermission();
        if(perm!=='granted'){ setPushHint('Bildirim izni verilmedi.'); return; }
        var reg=await navigator.serviceWorker.ready;
        var sub=await reg.pushManager.getSubscription();
        if(!sub){ sub=await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:urlB64ToUint8(VAPID_PUB)}); }
        var k=sub.toJSON().keys;
        var body=new URLSearchParams();
        body.set('csrf_token','$csrf'); body.set('endpoint',sub.endpoint);
        body.set('p256dh',k.p256dh); body.set('auth',k.auth);
        var r=await fetch('$subUrl',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body});
        var d=await r.json();
        if(d.ok){ setPushState('Açık'); setPushHint('Bu cihazda bildirimler açık.'); }
        else { setPushHint('Abonelik kaydedilemedi.'); }
    }catch(e){ setPushHint('Hata: '+(e&&e.message?e.message:e)); }
}
async function pushDisable(){
    try{
        var reg=await navigator.serviceWorker.ready;
        var sub=await reg.pushManager.getSubscription();
        var ep='';
        if(sub){ ep=sub.endpoint; await sub.unsubscribe(); }
        var body=new URLSearchParams(); body.set('csrf_token','$csrf'); body.set('endpoint',ep);
        await fetch('$unsubUrl',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body});
        setPushState('Kapalı'); setPushHint('Bu cihazda bildirimler kapatıldı.');
    }catch(e){ setPushHint('Hata: '+(e&&e.message?e.message:e)); }
}
async function pushTest(){
    try{
        var body=new URLSearchParams(); body.set('csrf_token','$csrf');
        var r=await fetch('$testUrl',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body});
        var d=await r.json();
        setPushHint(d.ok ? 'Test bildirimi gönderildi.' : 'Gönderilemedi. Önce bildirimi açın (abonelik bulunamadı).');
    }catch(e){ setPushHint('Hata: '+(e&&e.message?e.message:e)); }
}
(async function(){
    try{
        if('serviceWorker' in navigator && 'PushManager' in window){
            var reg=await navigator.serviceWorker.ready;
            var sub=await reg.pushManager.getSubscription();
            setPushState(sub ? 'Açık' : 'Kapalı');
        } else { setPushState('Desteklenmiyor'); }
    }catch(e){ setPushState('—'); }
})();
JS;
require __DIR__ . '/templates/footer.php';
