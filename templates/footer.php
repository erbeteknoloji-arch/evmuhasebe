    </main>
</div>

<!-- =====================================================================
     MOBİL ALT NAVİGASYON  —  yalnızca ≤920 px
     $active değişkeni header.php'de ayarlanır ve burada aktif öğeyi işaretler.
     "Menü" butonu mevcut toggleNav() fonksiyonunu çağırır.
     ===================================================================== -->
<?php
/* Mevcut $active değişkenini güvenli bir şekilde oku */
$_bn_active = isset($active) ? $active : '';

/* Takvim ve alışveriş özelliklerini kontrol et */
$_bn_calendar = function_exists('feature_enabled') && feature_enabled('calendar');
$_bn_shopping  = function_exists('feature_enabled') && feature_enabled('shopping');

/* Alt gezinme öğelerini oluştur:
   Her zaman 5 slot: Dashboard · İşlemler · [Takvim/Hesaplar] · [Alışveriş/Hedefler] · Menü */
$_bn_items = [];
$_bn_items[] = ['page' => 'dashboard',    'href' => url('index.php'),        'icon' => '🏠', 'label' => t('nav.dashboard')];
$_bn_items[] = ['page' => 'transactions', 'href' => url('transactions.php'), 'icon' => '💸', 'label' => t('nav.transactions')];

if ($_bn_calendar) {
    $_bn_items[] = ['page' => 'calendar', 'href' => url('calendar.php'), 'icon' => '📅', 'label' => t('nav.calendar')];
} else {
    $_bn_items[] = ['page' => 'accounts', 'href' => url('accounts.php'), 'icon' => '🏦', 'label' => t('nav.accounts')];
}

if ($_bn_shopping) {
    $_bn_items[] = ['page' => 'shopping', 'href' => url('shopping.php'), 'icon' => '🛒', 'label' => t('nav.shopping')];
} else {
    $_bn_items[] = ['page' => 'goals', 'href' => url('goals.php'), 'icon' => '🎯', 'label' => t('nav.goals')];
}
?>
<nav class="mobile-bottom-nav" aria-label="Alt gezinme çubuğu" role="navigation">
    <div class="bn-inner">
        <?php foreach ($_bn_items as $_bn_item): ?>
        <a href="<?= e($_bn_item['href']) ?>"
           class="bn-item<?= $_bn_active === $_bn_item['page'] ? ' active' : '' ?>"
           data-page="<?= e($_bn_item['page']) ?>"
           aria-label="<?= e($_bn_item['label']) ?>"
           <?= $_bn_active === $_bn_item['page'] ? 'aria-current="page"' : '' ?>>
            <span class="bn-dot"></span>
            <span class="bn-icon"><?= $_bn_item['icon'] ?></span>
            <span class="bn-label"><?= e($_bn_item['label']) ?></span>
        </a>
        <?php endforeach; ?>
        <button type="button"
                class="bn-item"
                data-page="more"
                onclick="toggleNav()"
                aria-label="Menüyü aç/kapat"
                aria-expanded="false"
                id="bnMoreBtn">
            <span class="bn-dot"></span>
            <span class="bn-icon" id="bnMoreIcon">☰</span>
            <span class="bn-label">Menü</span>
        </button>
    </div>
</nav>

<!-- AÇILIR HESAP MAKİNESİ -->
<?php if (function_exists('feature_enabled') && feature_enabled('calculator')): ?>
<button type="button" id="calcFab" class="calc-fab" onclick="toggleCalc()" title="Hesap Makinesi (Alt+C)" aria-label="Hesap makinesi">🧮</button>
<div id="calcPop" class="calc-pop" role="dialog" aria-label="Hesap makinesi">
    <div class="calc-bar">
        <span>Hesap Makinesi</span>
        <button type="button" class="calc-x" onclick="toggleCalc(false)" aria-label="Kapat">×</button>
    </div>
    <input type="text" id="calcExpr" class="calc-expr" placeholder="0" inputmode="none" readonly>
    <div id="calcRes" class="calc-res">0</div>
    <div class="calc-keys">
        <button type="button" class="ck fn" onclick="calcClear()">C</button>
        <button type="button" class="ck fn" onclick="calcBack()">⌫</button>
        <button type="button" class="ck fn" onclick="calcOp('%')">%</button>
        <button type="button" class="ck op" onclick="calcOp('/')">÷</button>
        <button type="button" class="ck" onclick="calcKey('7')">7</button>
        <button type="button" class="ck" onclick="calcKey('8')">8</button>
        <button type="button" class="ck" onclick="calcKey('9')">9</button>
        <button type="button" class="ck op" onclick="calcOp('*')">×</button>
        <button type="button" class="ck" onclick="calcKey('4')">4</button>
        <button type="button" class="ck" onclick="calcKey('5')">5</button>
        <button type="button" class="ck" onclick="calcKey('6')">6</button>
        <button type="button" class="ck op" onclick="calcOp('-')">−</button>
        <button type="button" class="ck" onclick="calcKey('1')">1</button>
        <button type="button" class="ck" onclick="calcKey('2')">2</button>
        <button type="button" class="ck" onclick="calcKey('3')">3</button>
        <button type="button" class="ck op" onclick="calcOp('+')">+</button>
        <button type="button" class="ck" onclick="calcKey('00')">00</button>
        <button type="button" class="ck" onclick="calcKey('0')">0</button>
        <button type="button" class="ck" onclick="calcDot()">,</button>
        <button type="button" class="ck eq" onclick="calcEquals()">=</button>
    </div>
    <div class="calc-foot"><button type="button" class="btn btn-ghost btn-sm btn-block" onclick="calcCopy()">Sonucu Kopyala</button></div>
</div>
<?php endif; ?>

<!-- Grafikler için Chart.js (tarayıcıda çalışır) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (!empty($inline_script)): ?>
<script><?= $inline_script ?></script>
<?php endif; ?>

<!-- PWA: Service Worker kaydı + "Uygulamayı Yükle" istemi -->
<script>
(function(){
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('<?= e(url('sw.js')) ?>').catch(function(){});
        });
    }
    // Android/Chrome: yükleme istemini yakala, küçük bir buton göster
    var deferred = null;
    window.addEventListener('beforeinstallprompt', function (ev) {
        ev.preventDefault();
        deferred = ev;
        var btn = document.getElementById('pwaInstallBtn');
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'pwaInstallBtn';
            btn.type = 'button';
            btn.textContent = '📲 Uygulamayı Yükle';
            btn.style.cssText = 'position:fixed;left:50%;transform:translateX(-50%);bottom:18px;z-index:9999;'
                + 'background:#14452F;color:#fff;border:0;padding:11px 18px;border-radius:999px;'
                + 'box-shadow:0 8px 24px rgba(0,0,0,.18);font-size:14px;cursor:pointer';
            btn.onclick = function () {
                if (!deferred) return;
                deferred.prompt();
                deferred.userChoice.finally(function(){ deferred = null; btn.remove(); });
            };
            document.body.appendChild(btn);
        }
        btn.style.display = '';
    });
    window.addEventListener('appinstalled', function(){
        var b = document.getElementById('pwaInstallBtn'); if (b) b.remove();
    });
})();
</script>
</body>
</html>
