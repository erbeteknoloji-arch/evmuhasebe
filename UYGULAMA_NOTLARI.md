# Ev Muhasebe — Uygulama Notları (2026-06)

Bu sürümde 5 başlık ele alındı: kredi kartı yönetimi, birikim hedefi & takvim silme hataları, paylaşımlı/e-postalı alışveriş listeleri ve evden ayrılma. Aşağıda yapılan değişiklikler, gerekli veritabanı göçü ve geliştirme sırasında fark edilen mimari noktalar yer alır.

## 1) Veritabanı göçü (önce çalıştırın)

Tek dosya: `database/migration_2026_06_credit_cards_and_fixes.sql` (tekrar çalıştırmaya dayanıklıdır).

Eklenenler:

- `accounts` tablosuna `credit_limit`, `statement_day`, `due_day`, `min_payment_pct` (varsayılan **20.00**) sütunları.
- Yeni `cc_statements` tablosu: otomatik üretilen kredi kartı ekstrelerini izler ve aynı dönemde tekrar üretmeyi engeller.

Çalıştırma: phpMyAdmin > İçe Aktar, ya da `mysql -u KULLANICI -p VERITABANI < migration_2026_06_credit_cards_and_fixes.sql`.

## 2) Değişen ve eklenen dosyalar

Yeni dosyalar:

- `includes/credit.php` — kart bakiyesi, borç, kullanılabilir limit, asgari ödeme, ekstre/son ödeme tarihleri ve **tembel ekstre üretimi** (`cc_generate_statements`).
- `actions/shopping_poll.php` — alışveriş listesi için salt-okunur JSON; canlı senkronizasyon.
- `actions/shopping_email.php` — listeyi tüm aktif ev üyelerine e-posta ile gönderir.
- `actions/household_leave.php` — evden ayrılma (son ev sahibi koruması ile).
- `actions/member_role.php` — üyeyi ev sahibi yap / yetki devri.
- `database/migration_2026_06_credit_cards_and_fixes.sql` — SQL göçü.

Düzenlenen dosyalar:

- `accounts.php` — Kredi Kartı seçilince limit / kullanılabilir limit (otomatik) / hesap kesim günü / son ödeme günü / asgari ödeme yüzdesi alanları; kart kartında borç, kullanılabilir limit, asgari ödeme ve yaklaşan tarihler gösterimi; sayfa açılışında ekstre üretimi.
- `actions/account_save.php` — yeni kredi kartı alanlarının kaydı + log + ekstre tetikleme.
- `index.php` (Panel) — “💳 Kredi Kartları” özeti: Toplam Kart Borcu, Toplam Asgari Ödeme, Kalan Kullanılabilir Limit, Sonraki Hesap Kesim ve Son Ödeme tarihleri + kart kart döküm tablosu.
- `goals.php` ve `calendar.php` — **silme hatası düzeltmesi** (aşağıda).
- `actions/goal_delete.php` — sahiplik doğrulaması, ilişkili birikim kayıtlarının da silinmesi, doğru başarı/hata bildirimi, loglama.
- `actions/scheduled_delete.php` — ilişkili kredi kartı ekstre bağlantısının temizlenmesi, doğru bildirim, loglama.
- `shopping.php` — “✉️ E-posta ile Gönder” butonu + ~8 sn’de bir canlı senkronizasyon (sekme görünürken).
- `households.php` — “★ Ev sahibi yap” (yetki devri) ve herkese görünür “Evden Ayrıl” bölümü (tek ev sahibine uyarı).

## 3) Silme hatalarının kök nedeni (Madde 2 ve 3)

Hem birikim hedeflerinde hem takvimde silme “çalışmıyordu”. Sebep aynıydı: silme `<form>`’u, düzenleme modalının **dış formunun içine** JavaScript ile yerleştiriliyordu. İç içe `<form>` HTML’de geçersizdir; tarayıcı iç formu yok sayar, dolayısıyla “Sil” butonu aslında dıştaki **kaydet** formunu gönderiyordu (silme yerine kayıt güncelleniyordu).

Çözüm: İç form yerine, dış formun kendisini silme ucuna yönlendiren bir `formaction` + `formnovalidate` gönder butonu kullanıldı. Arka uçtaki silme betikleri zaten doğruydu; yine de bildirim/loglama açısından sağlamlaştırıldı. Onay açılır penceresi (“… silinsin mi?”) korunmaktadır.

## 4) Kredi kartı mantığı (Madde 1)

- **Kullanılabilir limit = Kart limiti − Güncel borç** (otomatik). Modalda canlı önizleme vardır.
- **Borç türetilir:** kart bakiyesi (açılış + gelir − gider) negatife düştüğünde borç olarak yansır. Harcama yapıldıkça borç artar, kullanılabilir limit düşer. Tüm değerler işlemlerden türetildiği için sayfa yenileme, çıkış/giriş ve veritabanı kalıcılığından sonra tutarlı kalır.
- **Asgari ödeme = borç × yüzde** (varsayılan %20 — Türkiye bankalarında tipik asgari oran; yüksek limitlerde %40 olabilir, kart bazında değiştirilebilir).
- **Hesap kesim tarihinde** otomatik olarak: bir takvim etkinliği (planlı ödeme) ve ödenecek borç kaydı oluşur; panelde yaklaşan ödeme yükümlülüğü gösterilir. Arka planda zamanlayıcı (cron) bulunmadığından bu üretim **panel/hesaplar sayfası açıldığında** yapılır (tembel üretim).

## 5) Paylaşımlı alışveriş (Madde 4)

Listeler veritabanı düzeyinde **zaten eve göre paylaşımlıydı** (kullanıcıya değil eve bağlı). Eklenenler: bir üyenin ekleme/işaretleme/silme işlemini diğerlerinin sayfayı yenilemeden görmesi için hafif JS yoklaması ve listeyi tüm aktif üyelere gönderen e-posta özelliği (mevcut SMTP yapılandırmasını kullanır; Liste adı, tarih, alınan ve kalan ürünler dahil).

## 6) Mimari notlar / fark edilen noktalar

- **Cron yokluğu:** Otomasyonlar (ekstre üretimi, listelerin canlı görünümü) sayfa açılışı/JS yoklaması ile çalışır. Daha kesin zamanlama isterseniz aynı `cc_generate_statements()` çağrısını bir `cron.php` ile zamanlanmış göreve bağlamak yeterlidir.
- **Para transferi modeli yok:** Uygulamada hesaplar arası transfer kavramı bulunmadığından, otomatik ekstre planlı ödemesi belirli bir hesaba (`account_id`) bağlanmaz; “İşle” dendiğinde gerçek nakit gideriniz kaydedilir, kartın borcu ise kart işlemlerinden türetilmeye devam eder. Böylece çift kayıt karmaşası ve borcun yanlış yönde değişmesi önlenir.
- **Silme akışı yönlendirmelidir:** Uygulamanın tamamı “form gönder → yönlendir → flash bildirim” desenini kullanır; silme düzeltmeleri bu deseni korur. Tam “sayfa yenilemeden” silme istenirse AJAX’a geçiş ayrı bir iyileştirme olur.
- **Yabancı anahtar bütünlüğü:** Şemada her yerde ON DELETE CASCADE tanımlı olmadığından, hedef silmede birikim kayıtları uygulama tarafından temizleniyor; takvim silmede ekstre bağlantısı NULL’lanıyor.
- **Geriye dönük uyumluluk:** `cc_statements` yoksa kredi kartı yardımcıları sessizce devre dışı kalır; göç uygulanmadan da site çökmeden çalışır (ancak otomasyon için göç gereklidir).

## 7) PDF ekstre içe aktarma — tarayıcı tarafı okuma (pdftotext gerektirmez)

Sorun: Paylaşımlı hostingde `pdftotext` (poppler-utils) yok ve banka PDF'leri gömülü fontlar yüzünden saf PHP yedeğiyle de okunamıyordu; yalnızca metni elle kopyalayıp yapıştırınca çalışıyordu.

Çözüm: İçe aktarma sayfasındaki PDF yükleme kutusu artık dosyayı sunucuya göndermek yerine **tarayıcıda PDF.js (Mozilla)** ile okuyup metni çıkarıyor; çıkarılan metin, zaten var olan ve her sunucuda çalışan `actions/import_paste.php` akışına gönderiliyor. Böylece:

- Sunucuya hiçbir araç kurmaya gerek yok (paylaşımlı hosting için ideal).
- Fontlar/CMap'ler tarayıcı motoruyla çözüldüğü için banka PDF'leri sorunsuz okunuyor.
- PDF'in kendisi sunucuya yüklenmez; yalnızca çıkarılan metin işlenir (gizlilik artısı).

Değişen dosyalar: `import.php` (yükleme kutusu + PDF.js betiği, satırları y/x konumuna göre yeniden kurar), `actions/import_paste.php` (gerçek dosya adını ve “pdf-tarayıcı” yöntemini gösterir). PDF.js, footer'daki Chart.js gibi cdnjs üzerinden yüklenir.

Sınırlar / yedekler:
- **Taranmış (fotoğraf) PDF'ler** metin içermediğinden okunamaz; uygulama bu durumu algılayıp kullanıcıyı “Ekstre Metnini Yapıştır” seçeneğine yönlendirir (OCR gerekir, ayrı bir iş).
- CDN engelliyse PDF.js yüklenemez; yine elle yapıştırma yedeği devrede kalır.
- Eski sunucu tarafı `actions/import_upload.php` ve `includes/PdfStatementParser.php` (pdftotext + saf PHP) kodda duruyor; ileride sunucuya pdftotext kurarsanız `config.php` içinde `PDFTOTEXT_PATH` tanımlayıp o yolu da kullanabilirsiniz.

## 8) Yönetim paneli yenilendi (üst düzey panel)

**“Array to string conversion” hatası (kök neden):** `templates/header.php`, dahil edildiğinde `$households = user_households();` (bir DİZİ) ve foreach içinde `$h` değişkenlerini tanımlıyor. Şablonlar aynı kapsamda include edildiği için, `admin/index.php` içinde aynı adla tuttuğumuz **hane sayısı** (`$households`) header dahil edildikten sonra bir diziyle eziliyor ve sayfada basıldığında “Array” uyarısı çıkıyordu (eski sürümde satır 32, yeni sürümde 78 — ikisi de aynı sebep). Çözüm: değişken adları çakışmayacak şekilde değiştirildi — `admin/index.php`’de `$households → $householdCount`, `admin/household_view.php`’de görüntülenen hane `$h → $house`. Genel kural: header’dan sonra basılan değişkenlerde `$households`, `$h`, `$household`, `$active`, `$page_actions`, `$nav` adlarını kullanmaktan kaçının.

**Modüller eklenebilir/çıkarılabilir:** Zaten mevcut olan `feat_*` anahtarlarıyla (admin → Site Ayarları → Özellikler) her modül açılıp kapatılabiliyor; kapatılan modül menüden kalkar ve erişime kapanır. Bu sistem korundu.

**Toplam hane + sahibi:** Panel üstündeki **“Toplam Hane”** kartı tıklanabilir; `admin/households.php` sayfasına götürür. Burada her hane; **sahibi (owner)**, üye sayısı, hesap/işlem sayısı, net bakiye ve oluşturma tarihiyle listelenir. Hane adı veya üye adına göre arama vardır.

**Başka hanelerin işlemlerini görüntüleme/düzenleme/silme:** `admin/household_view.php?id=` sayfası seçilen hanenin üyelerini, hesaplarını ve **işlemlerini (sayfalı)** gösterir. Yönetici her işlemi düzenleyebilir (modal) veya silebilir. Bu işlemler hane sınırından bağımsız, yalnızca yöneticiye açık `actions/admin_tx_save.php` ve `actions/admin_tx_delete.php` uçlarıyla yapılır ve ilgili hanenin etkinlik günlüğüne `[admin]` etiketiyle yazılır.

**Panele eklenen diğer iyileştirmeler:** Aktif/yönetici kullanıcı kırılımı ve son 7 gün yeni kayıt/işlem; toplam gelir, gider ve işlem hacmi; topluluk+özel mesaj sayısı; **Sistem Durumu** (bakım modu, yeni kayıtlar, captcha, SMTP); ve **tüm haneleri kapsayan genel etkinlik akışı**.

Yeni/değişen dosyalar: `admin/index.php` (yeniden yazıldı), `admin/households.php` (yeni), `admin/household_view.php` (yeni), `actions/admin_tx_save.php` (yeni), `actions/admin_tx_delete.php` (yeni).

## 9) Yeni özellikler: transfer, bütçe uyarısı, otomatik ödeme, Excel/PDF, gelişmiş filtre

**Önce göç #2'yi çalıştırın:** `database/migration_2026_06b_transfers_alerts.sql`
(transactions.transfer_id + tags, scheduled_items.auto_post, budget_alerts tablosu).

**Hesaplar arası transfer.** Hesaplar sayfasına “↔ Para Transferi” butonu eklendi. Transfer, tek işlemde iki bağlı kayıt oluşturur: kaynakta gider, hedefte gelir (ikisi aynı `transfer_id`). Bakiyeler işlemlerden türetildiği için her iki hesaba doğru yansır; **hedef bir kredi kartıysa borç azalır** (kart ödemesi). Transferler gelir/gider raporlarına dahil edilmez (`transfer_id IS NULL` filtresi panel, raporlar, bütçe ve işlem özetlerine eklendi). Transferin bir bacağı silinince diğeri de silinir. Dosyalar: `actions/transfer_save.php` (yeni), `accounts.php`, `actions/transaction_delete.php`, `index.php`, `reports.php`, `transactions.php`.

**Bütçe aşım uyarıları.** Bir kategori bu ay bütçesinin %80 veya %100'üne ulaşınca panelde uyarı rozeti çıkar ve dönem+seviye başına **bir kez** e-posta gönderilir (`budget_alerts` tablosu tekrarı engeller). Dosyalar: `includes/budget_alerts.php` (yeni), `index.php`.

**Planlı ödemelerin otomatik işlenmesi.** Planlı ödeme modalına “Vadesinde otomatik işle” seçeneği eklendi. İşaretli ve vadesi gelmiş kalemler panel açılışında otomatik işleme alınır (tekrarlayanlar birikmiş dönemleri de yakalar). Dosyalar: `includes/scheduled.php` (yeni), `index.php`, `calendar.php`, `actions/scheduled_save.php`.

**Excel / PDF dışa aktarma.** Raporlar sayfasına “Excel” ve “PDF” butonları eklendi. Kütüphanesiz çalışır: Excel, Excel'in açtığı .xls (HTML tablo) olarak iner; PDF, yazdırmaya hazır sayfa açıp tarayıcının “PDF olarak kaydet” özelliğini kullanır. Dosyalar: `export_excel.php` (yeni), `export_pdf.php` (yeni), `reports.php`. (Mevcut CSV korundu.)

**İşlemlerde gelişmiş filtre + etiketler.** İşlemler sayfasına **min/maks tutar** ve **etiket** filtreleri eklendi; arama artık açıklama + etiketlerde çalışır. İşleme isteğe bağlı etiketler girilebilir (boşlukla ayrılır), listede `#etiket` rozetleriyle görünür. Dosyalar: `transactions.php`, `actions/transaction_save.php`, `assets/js/app.js`.

## 10) PWA — telefona kurulabilir + çevrimdışı

Uygulama artık bir **Progressive Web App**: telefonda "Ana ekrana ekle" ile kurulabiliyor, kendi simgesiyle tam ekran açılıyor ve çevrimdışıyken daha önce açılan sayfalar görüntülenebiliyor. Veritabanı göçü **gerekmez**.

- `manifest.php` (yeni): dinamik web app manifest (ad/tema ayarlardan, URL'ler `url()` ile; alt klasör kurulumlarında da doğru). Giriş gerektirmez.
- `sw.js` (yeni): service worker. Statik varlıklar (CSS/JS/font/CDN) için “önce önbellek, arkada güncelle”; sayfa gezinmelerinde “önce ağ, çevrimdışıysa son önbellek ya da `offline.html`”. `/actions/`, `ajax` ve `/auth/` istekleri asla önbeklenmez (güvenlik/gizlilik).
- `offline.html` (yeni): çevrimdışı yedek sayfa.
- `assets/icons/` (yeni): 192/512/512-maskable/apple-touch/favicon-32 PNG simgeler (orman yeşili + ₺).
- `templates/header.php`: manifest, `theme-color`, apple-touch-icon ve iOS meta etiketleri.
- `templates/footer.php`: service worker kaydı + Android/Chrome için “📲 Uygulamayı Yükle” butonu (yükleme istemi yakalanınca görünür).

Notlar: PWA kurulumu **HTTPS** gerektirir (alan adınızda SSL açık olmalı). iPhone'da kurulum Safari → Paylaş → “Ana Ekrana Ekle” ile yapılır. Service worker sürümünü yükseltmek için `sw.js` içindeki `CACHE = 'evmuhasebe-v1'` değerini değiştirmeniz yeterli. Giriş ve kurulum (auth) sayfaları kendi şablonlarını kullandığından manifest/SW etiketlerini içermez; kullanıcı giriş yaptıktan sonra (panel vb.) PWA tam devreye girer.

## 11) Mobil/tarayıcı bildirimleri (Web Push)

Artık e-postaya ek olarak **anlık push bildirimi** gönderilebiliyor (telefon/masaüstü). Kütüphanesiz, saf PHP ile uygulandı.

**Önce göç #3'ü çalıştırın:** `database/migration_2026_06c_push.sql` (`push_subscriptions` tablosu + `users.notify_push`).

Bileşenler:
- `config/vapid.php` (yeni): VAPID anahtarları (genel + özel PEM + iletişim adresi). Gizlidir (config/.htaccess kapalı). Anahtarlar üretildi; değiştirmeyin, yoksa mevcut abonelikler geçersiz olur. `VAPID_SUBJECT` şu an `mailto:berkanpekta44@gmail.com`; dilerseniz kendi alan adınızla güncelleyin.
- `includes/webpush.php` (yeni): VAPID ES256 JWT + `aes128gcm` şifreleme (RFC 8291/8188) + cURL gönderim. `push_to_user()` ve `push_household()` yardımcıları; geçersiz (404/410) abonelikleri otomatik siler.
- `actions/push_subscribe.php` / `push_unsubscribe.php` / `push_test.php` (yeni): abonelik kaydı/iptali ve test.
- `sw.js`: `push` ve `notificationclick` olayları eklendi (bildirimi gösterir, tıklayınca ilgili sayfaya götürür).
- `settings.php`: “📲 Telefon / Tarayıcı Bildirimleri” kartı — Aç / Kapat / Test düğmeleri ve cihaz durumu.
- `includes/notify.php`: mevcut tüm bildirimler (yeni işlem, içe aktarma, yaklaşan/otomatik ödeme, hedef, bütçe aşımı) artık **e-posta + push** gönderiyor. Push, e-posta kapalı olsa bile çalışır.

Kullanım: Ayarlar → Bildirimler → **Bildirimleri Aç** (izin verin) → **Test Gönder**. **iPhone'da**: iOS 16.4+ ve uygulamanın **Ana Ekrana eklenmiş** olması şarttır; Safari sekmesinde çalışmaz. Tüm platformlarda **HTTPS** gerekir.

Sunucu gereksinimi: PHP `openssl` (`openssl_pkey_derive`, ≥ 7.3), `hash_hkdf`, `ext-curl`. Yoksa özellik sessizce devre dışı kalır (uygulama çökmez). Bir kontrol için Ayarlar'da “Test Gönder” yeterli.

## 12) Çoklu dil (i18n) — TR / EN / AR / DE + RTL

Uygulamaya çok dilli altyapı eklendi. **Önce göç #4'ü çalıştırın:** `database/migration_2026_06d_i18n.sql` (`users.lang` sütunu).

Altyapı:
- `includes/i18n.php` (yeni): `t('anahtar')` / `te()` çeviri fonksiyonları, dil tespiti (oturum > kullanıcı > çerez > varsayılan `tr`), `current_lang()`, `set_lang()`, `lang_dir()`, `is_rtl()`. Eksik anahtarlar otomatik **Türkçe'ye düşer** (yarım çeviri bile siteyi bozmaz). `includes/functions.php` üzerinden global yüklenir.
- `lang/tr.php`, `lang/en.php`, `lang/ar.php`, `lang/de.php` (yeni): anahtar=>çeviri sözlükleri. Yeni metin eklemek için tr.php'ye anahtar ekleyip diğer dillere karşılığını yazmanız yeterli.
- `actions/lang_set.php` (yeni): dili değiştirir (oturum + çerez + hesaba kaydeder).
- Dil değiştirici: **kenar çubuğunda** (her sayfada) ve **Ayarlar → Dil** kartında. Seçim hesabınıza kaydedilir, tüm cihazlarda korunur.

Arapça için **RTL** (sağdan-sola):
- `<html dir="rtl">` otomatik ayarlanır; `assets/css/rtl.css` (yeni) yalnızca Arapça'da yüklenir (kenar çubuğu sağa, hizalamalar, sayıların LTR kalması vb.).

Şu an çevrili olan yüzeyler: tüm **menü/kenar çubuğu**, **dil değiştirici**, tüm sayfa **başlıkları**, **panel (dashboard)** gövdesi, **Ayarlar** ve **giriş** sayfası. Diğer sayfaların gövde metinleri şu an Türkçe görünür; altyapı hazır olduğundan bunlar `t()` anahtarlarıyla kademeli olarak çevrilebilir (istediğiniz sayfayı söyleyin, onu tamamlayayım).

Not: Çeviriler en iyi çaba ile yapıldı; özellikle Arapça/Almanca metinleri yayına almadan bir kez gözden geçirmenizi öneririm.
