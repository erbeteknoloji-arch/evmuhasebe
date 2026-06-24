# Ev Muhasebe

Hane halkı için tasarlanmış, **PHP + MySQL/MariaDB** tabanlı kapsamlı bir gelir-gider ve bütçe takip uygulaması. Aynı evi paylaşan kişiler tek bir hesabı birlikte yönetebilir, banka/kredi kartı PDF ekstrelerini içe aktarıp işlemleri otomatik kategorilere ayırabilir ve detaylı raporlar alabilir.

---

## ✨ Özellikler

- **Gelir & Gider Takibi** – Tüm işlemleri tarih, kategori, hesap ve açıklamayla kaydedin; anlık net bakiye hesabı.
- **Hazır Kategoriler** – Market, Kira, Faturalar, Ulaşım, Sağlık, Abonelikler, Maaş ve daha fazlası (21 hazır kategori); istediğinizi ekleyip düzenleyebilirsiniz (renk + emoji ikon).
- **Çoklu Hane Desteği** – Birden çok ev/hane oluşturun (ör. "Evim", "Yazlık Ev") ve aralarında tek tıkla geçiş yapın. Her hanenin verisi tamamen ayrıdır.
- **Hesap Paylaşımı** – Hane üyelerini **davet kodu** veya **e-posta davet bağlantısı** ile çağırın; herkes aynı işlemleri görüp ekleyebilir. Sahip (owner) / üye (member) rolleri.
- **PDF Ekstre İçe Aktarma** – Bankadan gelen kredi kartı / hesap ekstresi PDF'ini yükleyin; uygulama metni okur, bankayı tanır, satırları çıkarır ve mağaza adına göre **otomatik kategorilendirir**. Onaylamadan önce her satırı düzenleyebilirsiniz. Sık kullandığınız mağazalar için kuralları **otomatik öğrenir**.
- **Bütçeler** – Kategori bazında aylık harcama limiti belirleyin; panoda ilerleme çubuklarıyla takip edin.
- **Raporlar** – Aylık/yıllık/özel dönem; gelir-gider dağılımı, tasarruf oranı, kategori kırılımı ve hane üyesi katkı tablosu. Grafiklerle görselleştirme.
- **CSV Dışa Aktarma** – Tüm işlemleri Türkçe Excel uyumlu (noktalı virgül + UTF-8) CSV olarak indirin.
- **Çoklu Para Birimi** – Hane bazında TRY ₺ / USD $ / EUR € / GBP £.
- **Güvenli Giriş** – Kullanıcı adı veya e-posta + parola; parolalar `password_hash` ile saklanır, oturumlar güvenli çerezle yönetilir, tüm formlarda CSRF koruması.

### 🆕 v2 ile gelen özellikler
- **📅 Takvim & Planlı Ödemeler** – Yaklaşan ödemeleri ve gelirleri takvim üzerinde görün. Tek seferlik veya **tekrarlayan** (haftalık/aylık/yıllık) kayıtlar ekleyin; "İşle" ile tek tıkta gerçek işleme dönüştürün (tekrar edenler otomatik sonraki tarihe ilerler). Geciken ve yaklaşan ödemeler ayrı listelenir.
- **🎯 Birikim Hedefleri** – Araba, tatil, acil durum fonu gibi hedefler koyun; ne kadar biriktirdiğinizi, kalan tutarı, ilerleme yüzdesini ve hedefe yetişmek için **aylık ne kadar** ayırmanız gerektiğini görün. Para ekleyip çıkarabilir, geçmişi izleyebilirsiniz.
- **💱 Varlıklar & Güncel Kurlar** – Döviz (USD/EUR/GBP) ve altın/gümüş (gram, çeyrek, yarım, tam) birikimlerinizi girin; **güncel kurdan TL değeri** ve maliyetinize göre **kâr/zarar** otomatik hesaplanır. Kurlar internetten otomatik çekilir ve önbelleğe alınır; gerekirse elle de güncellenebilir.
- **💡 Akıllı Öneriler** – Raporlar sayfasının altında, bu ayki harcamalarınıza göre **tasarruf önerileri**: keyfi/gereksiz görünen kategoriler, ani artışlar, bütçe aşımları, abonelik birikmesi, şans oyunu uyarısı ve tasarruf oranı geri bildirimi.
- **📧 E-posta Bildirimleri (SMTP)** – Yeni işlem, içe aktarma, planlı ödeme ve hedef gelişmeleri ev üyelerine e-posta ile bildirilir. Her kullanıcı hangi bildirimleri alacağını **Ayarlar**'dan seçer. (PHPMailer gerektirmez; saf PHP SMTP.)
- **🔑 Şifremi Unuttum** – Giriş ekranından e-posta ile şifre sıfırlama (güvenli, süreli jeton).
- **🎨 Tema Modları** – Standart, **Koyu (Dark)**, Pembe, Okyanus, Mor Gece, Orman Gecesi ve Kahve temaları. Seçim anında uygulanır ve hesabınıza kaydedilir.
- **🧮 Açılır Hesap Makinesi** – Her sayfada sağ alttaki butondan (veya **Alt+C**) açılan, sonucu kopyalanabilen hesap makinesi.

### 🆕 v3 ile gelen özellikler
- **🛡️ Yönetim Paneli (Admin)** – `/admin` altında: site geneli istatistikler; **kullanıcı yönetimi** (aktif/pasif yapma, yöneticilik verme/alma, silme); **site ayarları** (site adı, logo, favicon, SEO başlık/açıklama/anahtar kelimeler); **özellik açma-kapama** (her modülü menüden ve erişimden kaldırabilirsiniz); **bakım modu**; **kayıt açma/kapama**; **KVKK metni düzenleyici**; ve **destek talepleri** yönetimi. İlk kayıt olan kullanıcı otomatik yönetici olur.
- **📜 KVKK Uyumu** – Kayıt ekranında 6698 sayılı KVKK kapsamında **aydınlatma ve açık rıza metni** ve onay kutusu; onaylamadan üye olunamaz. Onay tarihi kayıt altına alınır. Metin admin panelinden düzenlenebilir. (Şablon niteliğindedir; mevzuata tam uyum için hukuk danışmanına danışın.)
- **🤖 Bot Koruması (Captcha)** – Giriş ve kayıt ekranlarında doğrulama: GD eklentisi varsa **resim captcha**, yoksa otomatik **matematik sorusu** yedeği. Ayrıca gizli "honeypot" tuzağı. Admin panelinden açılıp kapatılabilir.
- **🎫 Destek / Ticket Modülü** – Kullanıcılar yöneticilere destek talebi açar, yazışır; yöneticiler yanıtlar ve durumu (açık/yanıtlandı/kapalı) yönetir. E-posta bildirimleri ile.
- **💬 Topluluk & Fiyat Paylaşımı** – Site geneli **genel sohbet** ve **birebir mesajlaşma**. Kullanıcılar uygun fiyat buldukları ürünleri (ürün, fiyat, mağaza, not) paylaşır; herkes daha ucuza alabilsin. (Anlık güncelleme, AJAX yoklaması ile çalışır.)

### 🆕 v4 ile gelen özellikler
- **🛒 Alışveriş Listesi** – Birden çok liste oluşturun (ör. *Haftalık Market*, *Kahvaltılık*). Her ürünün **renkli görsel ikonu** (emoji) vardır; **akılda kalıcı** olması için ~150 ürünlük hazır görsel katalogdan tek dokunuşla ekleyin ya da kendi ürününüzü yazın (otomatik ikon tahmini ile). Markette dolaşırken ürünleri **tek tıkla işaretleyin** (sayfa yenilenmeden, AJAX), miktar ve tahmini fiyat girin, **ilerleme çubuğu** ve **tahmini toplam tutarı** görün, tamamlananları tek tuşla temizleyin. Menüden açılıp kapatılabilir (admin paneli → özellikler).
- **📐 Açılır/Kapanır Menü & Kaydırma Düzeltmesi** – Kenar çubuğu masaüstünde **daraltılabilir** (yalnızca ikonlar; tercih hatırlanır) ve menü öğeleri ekrana sığmadığında **kaydırılabilir** olur; mobilde de tüm menü öğelerine erişilir.

---

## 🗂️ Klasör Yapısı

```
ev-muhasebe/
├── index.php                # Panel (dashboard) – özet, grafikler, son işlemler
├── transactions.php         # İşlem listesi, filtreler, ekle/düzenle
├── categories.php           # Kategoriler + bütçeler + içe aktarma kuralları
├── accounts.php             # Hesaplar (nakit / banka / kredi kartı)
├── reports.php              # Raporlar ve grafikler
├── import.php               # PDF ekstre içe aktarma sihirbazı
├── households.php           # Hane yönetimi, üyeler, davetler
├── export_csv.php           # CSV dışa aktarma
│
├── config/
│   └── config.php           # Veritabanı bilgileri ve genel ayarlar  ← BURAYI DÜZENLEYİN
│
├── database/
│   └── schema.sql           # Veritabanı tabloları (ayrı .sql dosyası)
│
├── includes/
│   ├── db.php               # PDO veritabanı bağlantısı
│   ├── functions.php        # Yardımcı fonksiyonlar (para biçimi, tarih, vb.)
│   ├── auth.php             # Oturum / yetki / hane kapsamı
│   ├── csrf.php             # CSRF token üretimi ve doğrulama
│   └── PdfStatementParser.php  # PDF ekstre okuyucu/ayrıştırıcı
│
├── auth/
│   ├── login.php            # Giriş
│   ├── register.php         # Kayıt
│   └── logout.php           # Çıkış
│
├── actions/                 # Form işleyiciler (POST) – hepsi CSRF + yetki korumalı
│   ├── transaction_save.php / transaction_delete.php
│   ├── account_save.php / account_delete.php
│   ├── category_save.php / category_delete.php
│   ├── budget_save.php
│   ├── rule_save.php / rule_delete.php
│   ├── household_create.php / household_update.php / household_delete.php
│   ├── household_join.php / household_switch.php
│   ├── household_invite.php / member_remove.php
│   ├── import_upload.php / import_commit.php / import_paste.php
│   ├── scheduled_save.php / scheduled_delete.php / scheduled_pay.php   (takvim)
│   ├── goal_save.php / goal_delete.php / goal_contribute.php           (birikim)
│   ├── asset_save.php / asset_delete.php / rates_refresh.php / rate_manual.php (varlıklar)
│   └── profile_save.php / theme_set.php / mail_test.php               (ayarlar)
│
├── calendar.php             # Takvim & planlı ödemeler
├── goals.php                # Birikim hedefleri
├── assets.php               # Varlıklar & güncel kurlar
├── settings.php             # Profil, bildirimler, tema, SMTP testi
│
├── database/
│   ├── schema.sql           # Tüm tablolar (sıfırdan kurulum)
│   └── upgrade-v2.sql       # v1 → v2 yükseltme (mevcut kurulumlar için)
│
├── includes/  (yukarıdakilere ek olarak)
│   ├── mailer.php           # Saf PHP SMTP gönderici + e-posta şablonu
│   ├── notify.php           # Ev üyelerine bildirim
│   ├── rates.php            # Döviz/altın kuru çekme + önbellek + değerleme
│   └── insights.php         # Harcama önerileri motoru
│
├── templates/
│   ├── header.php           # Kenar menü (yeni sayfalar), tema uygulanışı
│   └── footer.php           # Açılır hesap makinesi + Chart.js
│
└── assets/
    ├── css/style.css        # Tüm stiller + 7 tema + hesap makinesi/takvim/hedef stilleri
    ├── js/app.js            # Arayüz etkileşimleri + hesap makinesi
    └── uploads/             # Yüklenen PDF'ler (yazılabilir olmalı)
```

---

## 🚀 Kurulum

### Gereksinimler
- PHP **8.1+** (eklentiler: `pdo_mysql`, `mbstring`)
- MySQL **5.7+** veya MariaDB **10.4+**
- (Önerilir) `pdftotext` – PDF okuma doğruluğunu artırır. Yoksa uygulama saf PHP ile okumayı dener.
  - Ubuntu/Debian: `sudo apt install poppler-utils`

### Adımlar

**1) Veritabanını oluşturun**
```sql
CREATE DATABASE ev_muhasebe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ev_user'@'localhost' IDENTIFIED BY 'guclu_bir_parola';
GRANT ALL PRIVILEGES ON ev_muhasebe.* TO 'ev_user'@'localhost';
FLUSH PRIVILEGES;
```

**2) Tabloları içe aktarın**
```bash
mysql -u ev_user -p ev_muhasebe < database/schema.sql
```

> **Daha önceki (v1) bir kurulumdan yükseltiyorsanız:** `schema.sql` yerine yükseltme dosyasını çalıştırın; mevcut verileriniz korunur:
> ```bash
> mysql -u ev_user -p ev_muhasebe < database/upgrade-v2.sql
> ```
> Bu dosya tema, bildirim, takvim, birikim, varlık ve şifre-sıfırlama tablolarını ekler (tekrar çalıştırmak güvenlidir).

> **v2'den v3'e yükseltiyorsanız** (admin paneli, KVKK, captcha, destek talepleri, sohbet/mesajlaşma):
> ```bash
> mysql -u ev_user -p ev_muhasebe < database/upgrade-v3.sql
> ```
> İlk kullanıcı (id=1) otomatik olarak yönetici yapılır. Yeni sıfır kurulumda her iki dosyaya da gerek yoktur; `schema.sql` yeterlidir.

> **v3'ten v4'e yükseltiyorsanız** (alışveriş listesi modülü):
> ```bash
> mysql -u ev_user -p ev_muhasebe < database/upgrade-v4.sql
> ```
> Sıfırdan kurulumda gerek yoktur; tablolar `schema.sql` içinde de bulunur.

**3) Bağlantı bilgilerini girin** — `config/config.php` dosyasını açıp düzenleyin:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'ev_muhasebe');
define('DB_USER', 'ev_user');
define('DB_PASS', 'guclu_bir_parola');
```

**3b) (Opsiyonel) E-posta / SMTP ayarları** — bildirim ve şifre sıfırlama e-postaları için aynı dosyada:
```php
define('APP_URL', 'https://muhasebe.alanadiniz.com'); // e-posta bağlantıları için (boşsa otomatik algılanır)
define('EMAIL_ENABLED', true);            // e-postayı açmak için true yapın
define('SMTP_HOST',   'smtp.alanadiniz.com');
define('SMTP_PORT',   587);               // 465 = SSL, 587 = TLS
define('SMTP_SECURE', 'tls');             // 'ssl' | 'tls' | ''
define('SMTP_USER',   'no-reply@alanadiniz.com');
define('SMTP_PASS',   'smtp-parolaniz');
define('MAIL_FROM',      'no-reply@alanadiniz.com');
define('MAIL_FROM_NAME', 'Ev Muhasebe');
```
> SMTP ayarlarını yaptıktan sonra **Ayarlar** sayfasından "Test E-postası Gönder" ile doğrulayabilirsiniz. PHPMailer veya başka bir kütüphane gerekmez. Gmail için "uygulama parolası" oluşturmanız gerekir.

**3c) (Opsiyonel) Döviz/altın kurları** — varlıkların güncel değeri için (varsayılan açık):
```php
define('RATES_AUTO_FETCH', true);  // kurları internetten otomatik çek
define('RATES_TTL_HOURS',  6);     // kaç saatte bir yenilensin
```
> Kurlar `open.er-api.com` (döviz) ve `api.gold-api.com` (altın/gümüş) üzerinden çekilir; sunucunuzun dışarı internet erişimi olmalıdır. Erişim yoksa **Varlıklar** sayfasından kurları elle girebilirsiniz.

**4) `assets/uploads/` klasörünün yazılabilir olduğundan emin olun**
```bash
chmod 775 assets/uploads
```

**5) Çalıştırın**
- Paylaşımlı hosting: Tüm klasörü web kök dizinine (`public_html`) yükleyin. Alt klasörde çalıştırmak da desteklenir (yollar otomatik algılanır).
- Yerel deneme:
  ```bash
  php -S localhost:8080 -t /yol/ev-muhasebe
  ```
  Tarayıcıdan `http://localhost:8080` adresine gidin.

**6) İlk kayıt** — Açılan sayfada **Kayıt Ol**'a tıklayın. İlk hesabınız ("Evim" adıyla) ve hazır kategoriler otomatik oluşturulur.

---

## 🧾 PDF Ekstre İçe Aktarma Nasıl Kullanılır?

İçe aktarmanın **iki yolu** vardır:

### Yöntem 1 — PDF Yükleme (sunucuda `pdftotext` varsa)
1. Sol menüden **İçe Aktarma**'ya girin.
2. Bankanızdan indirdiğiniz **PDF ekstresini** sürükleyip bırakın (veya seçin) ve "PDF'i Analiz Et"e basın.
3. Uygulama bankayı tanır, işlem satırlarını çıkarır ve tanıdığı mağazaları otomatik kategorilere atar.

### Yöntem 2 — Metni Yapıştır (her sunucuda çalışır, önerilir) ⭐
Pek çok paylaşımlı hosting'de otomatik PDF okuma aracı (`pdftotext`) **bulunmaz**. Ayrıca bazı banka PDF'leri (ör. Garanti) gömülü yazı tipleri nedeniyle sunucuda doğrudan okunamaz; bu durumda "işlem satırı bulunamadı" uyarısı alırsınız. Çözüm:
1. Banka **PDF'ini herhangi bir PDF görüntüleyicide açın** (Adobe Reader, tarayıcı, telefon vb.).
2. **Tümünü Seç (Ctrl+A) → Kopyala (Ctrl+C)**.
3. İçe Aktarma sayfasındaki **"Alternatif: Ekstre Metnini Yapıştır"** kutusuna yapıştırın ve "Metni Analiz Et"e basın.
4. Tarih ve tutar içeren satırlar otomatik bulunur.

> Bu yöntem PDF'in metin katmanını kullandığı için sunucu yazılımından bağımsızdır ve **kesin** çalışır. (Yalnızca taranmış/fotoğraf PDF'lerde metin kopyalanamaz; orada OCR gerekir.)

### Her iki yöntemde de
- Açılan tabloda her satırı kontrol edin: tarihi, açıklamayı, **gelir/gider** yönünü, kategoriyi ve tutarı düzenleyebilir; istemediğiniz satırların işaretini kaldırabilirsiniz.
- İşlemlerin ekleneceği **hesabı** seçin.
- "Yeni kategori kurallarını öğren" açıkken, atadığınız kategoriler sonraki ekstrelerde otomatik uygulanır.
- **İçe Aktar**'a basın — işlemler hesabınıza eklenir.

> **Desteklenen bankalar (otomatik tanıma):** Garanti BBVA, İş Bankası, Yapı Kredi, Akbank, Ziraat, QNB Finansbank, Halkbank, Vakıfbank, Denizbank, TEB, ING, Enpara ve benzerleri. Tanınmayan bankalarda da satır çıkarma çalışır; sadece banka adı boş kalır.

> **`pdftotext` kurmak isterseniz (opsiyonel, doğrudan yükleme için):** kendi sunucunuzda `sudo apt install poppler-utils` ile kurabilirsiniz. Kuramıyorsanız (çoğu paylaşımlı hosting) Yöntem 2'yi kullanın.

---

## 🛡️ Yönetim Paneli & İlk Yönetici

İlk kayıt olan kullanıcı **otomatik olarak yönetici** olur ve sol menüde **Yönetim Paneli** bağlantısını görür (`/admin`). Başka birini yönetici yapmak için: Yönetim Paneli → Kullanıcılar → ilgili kullanıcıda "Yönetici Yap". Mevcut bir kurulumu v3'e yükselttiyseniz id=1 kullanıcısı yönetici yapılır; dilerseniz elle de yapabilirsiniz:
```sql
UPDATE users SET is_admin = 1 WHERE username = 'kullanici_adi';
```

**Captcha (bot koruması):** Resim captcha için sunucunuzda PHP **GD eklentisi** açık olmalıdır (XAMPP'te genelde açıktır). GD yoksa otomatik olarak **matematik sorusu** captcha kullanılır; ek kurulum gerekmez. Captcha'yı Yönetim Paneli → Site Ayarları'ndan kapatabilirsiniz.

## 🔐 Güvenlik Notları

- Parolalar asla düz metin saklanmaz (`password_hash` / `password_verify`).
- Tüm form gönderimleri **CSRF token** ile korunur.
- Tüm veritabanı sorguları aktif **haneye göre kapsamlandırılır** — bir hanenin verisi diğerine sızmaz.
- `config/`, `includes/`, `database/` klasörleri ve `.sql` dosyaları `.htaccess` ile web erişimine kapalıdır (Apache).
- `assets/uploads/` içinde PHP/script çalıştırma engellenmiştir.
- Üretim ortamında `config/config.php` içindeki hata gösterimini (`display_errors`) kapatmanız önerilir.

> **Nginx kullanıyorsanız:** `.htaccess` çalışmaz. `config/`, `includes/`, `database/`, `assets/uploads/` dizinlerine doğrudan erişimi ve `.sql` uzantılı dosyaların sunulmasını sunucu yapılandırmanızda engelleyin.

---

## 🛠️ Kullanılan Teknolojiler

- **Arka uç:** PHP 8 (saf, framework'süz), PDO
- **Veritabanı:** MySQL / MariaDB (utf8mb4)
- **Ön yüz:** HTML5, CSS3 (özel tasarım), JavaScript (vanilla), [Chart.js](https://www.chartjs.org/) grafikler için
- **PDF okuma:** `pdftotext` (poppler-utils) veya saf PHP yedek çözümü

---

Tüm para birimi biçimleri Türkçe standardına göredir (1.234,56 ₺) ve tarihler `gg.aa.yyyy` olarak gösterilir. Arayüz tamamen Türkçedir.
