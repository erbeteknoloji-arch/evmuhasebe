<?php
require_once __DIR__ . '/includes/auth.php';
require_household();

$householdId = hid();
$pdo = db();

// Gözden geçirmeyi iptal et (oturumdaki hazırlık verisini temizle)
if (isset($_GET['iptal'])) {
    if (!empty($_SESSION['import_staging']['stored'])) {
        @unlink(UPLOAD_DIR . '/' . $_SESSION['import_staging']['stored']);
    }
    unset($_SESSION['import_staging']);
    flash('info', 'İçe aktarma iptal edildi.');
    redirect('import.php');
}

$staging = $_SESSION['import_staging'] ?? null;

/* Form dropdownları için kategoriler & hesaplar */
$cstmt = $pdo->prepare('SELECT id, name, type, icon FROM categories WHERE household_id = ? AND is_archived = 0 ORDER BY type DESC, name ASC');
$cstmt->execute([$householdId]);
$allCats = $cstmt->fetchAll();
$expCats = array_values(array_filter($allCats, fn($c)=>$c['type']==='expense'));
$incCats = array_values(array_filter($allCats, fn($c)=>$c['type']==='income'));

$astmt = $pdo->prepare('SELECT id, name FROM accounts WHERE household_id = ? AND is_active = 1 ORDER BY name ASC');
$astmt->execute([$householdId]);
$allAccs = $astmt->fetchAll();

/* İçe aktarma geçmişi */
$hstmt = $pdo->prepare('SELECT * FROM import_batches WHERE household_id = ? ORDER BY created_at DESC LIMIT 8');
$hstmt->execute([$householdId]);
$history = $hstmt->fetchAll();

$page_title    = t('page.import.title');
$page_subtitle = 'Banka / kredi kartı ekstresini yükleyip işlemleri otomatik aktarın';
$active        = 'import';

require __DIR__ . '/templates/header.php';

/** Kategori <select> seçeneklerini (optgroup'lu) çizer */
function catOptions(array $expCats, array $incCats, ?int $selected): void {
    echo '<option value="">— Kategorisiz —</option>';
    echo '<optgroup label="Gider">';
    foreach ($expCats as $c) {
        $sel = ($selected == $c['id']) ? 'selected' : '';
        echo '<option value="'.(int)$c['id'].'" '.$sel.'>'.$c['icon'].' '.e($c['name']).'</option>';
    }
    echo '</optgroup><optgroup label="Gelir">';
    foreach ($incCats as $c) {
        $sel = ($selected == $c['id']) ? 'selected' : '';
        echo '<option value="'.(int)$c['id'].'" '.$sel.'>'.$c['icon'].' '.e($c['name']).'</option>';
    }
    echo '</optgroup>';
}
?>

<?php if (!$staging): ?>
<!-- ADIM 1: Yükleme -->
<div class="grid cols-2-1">
    <div class="card">
        <div class="card-head"><h3>1. Ekstre PDF'ini Yükle</h3></div>
        <div class="card-pad">
            <form method="post" action="<?= url('actions/import_paste.php') ?>" id="pdfClientForm">
                <?= csrf_field() ?>
                <input type="hidden" name="statement_text" id="pdfExtractedText">
                <input type="hidden" name="source_name" id="pdfSourceName">
                <label class="dropzone" style="display:block;cursor:pointer">
                    <div class="big">📄</div>
                    <h3 style="margin:6px 0">PDF dosyasını seçin</h3>
                    <p class="muted" style="margin:0">Banka hesap ekstresi veya kredi kartı ekstresi · En fazla <?= MAX_UPLOAD_MB ?> MB</p>
                    <input type="file" id="pdfFileInput" accept="application/pdf,.pdf">
                </label>
                <div id="pdfStatus" class="muted" style="margin-top:10px;font-size:13px"></div>
                <div class="mt-2">
                    <button type="button" id="pdfExtractBtn" class="btn btn-primary btn-block" disabled onclick="pdfExtractAndSubmit()">PDF'i Analiz Et →</button>
                </div>
            </form>
            <p class="muted" style="font-size:12px;margin:10px 0 0">🔒 PDF <b>tarayıcınızda</b> okunur; dosyanın kendisi sunucuya gönderilmez, yalnızca çıkarılan metin işlenir. Bu sayede sunucuda ek araç (pdftotext) gerekmez.</p>
        </div>
    </div>

    <div class="card card-pad">
        <h3>Nasıl çalışır?</h3>
        <div class="list" style="margin-top:6px">
            <div class="item"><div class="av" style="background:var(--forest)">1</div><div class="grow"><b>PDF'i yükleyin</b><span>Bankanızdan indirdiğiniz dönem ekstresi</span></div></div>
            <div class="item"><div class="av" style="background:var(--forest-300)">2</div><div class="grow"><b>Otomatik ayrıştırma</b><span>Tarih, açıklama ve tutarlar çıkarılır</span></div></div>
            <div class="item"><div class="av" style="background:var(--gold)">3</div><div class="grow"><b>Akıllı kategorileme</b><span>Kurallara göre kategori önerilir</span></div></div>
            <div class="item"><div class="av" style="background:var(--income)">4</div><div class="grow"><b>Kontrol & aktar</b><span>Gözden geçirip onaylayın</span></div></div>
        </div>
        <p class="muted" style="font-size:12.5px;margin-bottom:0">💡 İpucu: Ekstre <b>metin tabanlı PDF</b> olmalıdır. Taranmış (fotoğraf) ekstreler okunamaz. PDF okunamazsa veya satır bulunamazsa, aşağıdaki <b>metni yapıştır</b> seçeneğini kullanın.</p>
    </div>
</div>

<!-- Tarayıcı içinde PDF metin çıkarma (PDF.js) — sunucuda pdftotext gerektirmez -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function(){
    var input  = document.getElementById('pdfFileInput');
    var btn    = document.getElementById('pdfExtractBtn');
    var status = document.getElementById('pdfStatus');
    if (!input) return;

    if (window['pdfjsLib']) {
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    input.addEventListener('change', function () {
        if (input.files && input.files.length) {
            btn.disabled = false;
            status.textContent = 'Seçilen dosya: ' + input.files[0].name;
        } else {
            btn.disabled = true;
            status.textContent = '';
        }
    });

    // Aynı y konumundaki parçaları satır yapıp x'e göre sırala (pdftotext -layout benzeri)
    function reconstruct(items) {
        var lines = [];
        items.forEach(function (it) {
            if (!it.str) return;
            var y = Math.round(it.transform[5]);
            var x = it.transform[4];
            var line = null;
            for (var i = 0; i < lines.length; i++) {
                if (Math.abs(lines[i].y - y) <= 3) { line = lines[i]; break; }
            }
            if (!line) { line = { y: y, parts: [] }; lines.push(line); }
            line.parts.push({ x: x, s: it.str });
        });
        lines.sort(function (a, b) { return b.y - a.y; });
        return lines.map(function (l) {
            l.parts.sort(function (a, b) { return a.x - b.x; });
            return l.parts.map(function (p) { return p.s; }).join(' ').replace(/\s+/g, ' ').trim();
        }).filter(function (s) { return s.length; }).join('\n');
    }

    window.pdfExtractAndSubmit = async function () {
        if (!input.files || !input.files.length) return;
        if (!window['pdfjsLib']) {
            status.innerHTML = '⚠️ PDF okuyucu yüklenemedi (internet/CDN engeli olabilir). Lütfen aşağıdaki “Ekstre Metnini Yapıştır” seçeneğini kullanın.';
            return;
        }
        var file = input.files[0];
        btn.disabled = true;
        status.textContent = 'PDF okunuyor…';
        try {
            var buf = await file.arrayBuffer();
            var pdf = await pdfjsLib.getDocument({ data: buf }).promise;
            var all = [];
            for (var p = 1; p <= pdf.numPages; p++) {
                status.textContent = 'Sayfa ' + p + ' / ' + pdf.numPages + ' okunuyor…';
                var page = await pdf.getPage(p);
                var tc = await page.getTextContent();
                all.push(reconstruct(tc.items));
            }
            var text = all.join('\n');
            if (text.replace(/\s/g, '').length < 20) {
                status.innerHTML = '⚠️ Bu PDF metin içermiyor (taranmış/fotoğraf olabilir). Lütfen “Ekstre Metnini Yapıştır” seçeneğini kullanın.';
                btn.disabled = false;
                return;
            }
            document.getElementById('pdfExtractedText').value = text;
            document.getElementById('pdfSourceName').value = file.name;
            status.textContent = 'Metin çıkarıldı, analiz ediliyor…';
            document.getElementById('pdfClientForm').submit();
        } catch (e) {
            status.innerHTML = '⚠️ PDF okunamadı (' + (e && e.message ? e.message : 'bilinmeyen hata') + '). Lütfen “Ekstre Metnini Yapıştır” seçeneğini kullanın.';
            btn.disabled = false;
        }
    };
})();
</script>

<!-- ALTERNATİF: Metni yapıştır (pdftotext gerektirmez, her sunucuda çalışır) -->
<div class="card mt-2">
    <div class="card-head">
        <h3>Alternatif: Ekstre Metnini Yapıştır</h3>
        <span class="pill">Sunucuda PDF okuma yoksa veya yükleme satır bulamazsa</span>
    </div>
    <div class="card-pad">
        <p class="muted" style="margin-top:0">
            Bazı paylaşımlı sunucularda otomatik PDF okuma (pdftotext) bulunmaz ve banka PDF'leri gömülü yazı tipleri yüzünden sunucuda okunamayabilir.
            Bu durumda en kesin yöntem: <b>PDF'i açın → Tümünü Seç (Ctrl+A) → Kopyala (Ctrl+C) → aşağıya yapıştırın</b>.
            Tarih ve tutar içeren satırlar otomatik bulunur.
        </p>
        <form method="post" action="<?= url('actions/import_paste.php') ?>">
            <?= csrf_field() ?>
            <textarea name="statement_text" class="input" rows="9"
                      style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12.5px;white-space:pre"
                      placeholder="Örn.&#10;04.06.2026   BİM KAVAKLI BEYLİKDÜZÜ        -144,90 TL   -4.374,50 TL&#10;02.06.2026   GÖRKEM GELİCİ HVL-CEP ŞUBE    +5.000,00 TL    770,40 TL&#10;31.05.2026   KESİNTİ VE EKLERİ              -8,37 TL      770,40 TL"></textarea>
            <div class="mt-2">
                <button type="submit" class="btn btn-primary">Metni Analiz Et →</button>
            </div>
        </form>
    </div>
</div>

<?php if ($history): ?>
<div class="card mt-2">
    <div class="card-head"><h3>Geçmiş İçe Aktarmalar</h3></div>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Tarih</th><th>Dosya</th><th>Banka</th><th class="num-right">Aktarılan</th></tr></thead>
        <tbody>
        <?php foreach ($history as $b): ?>
            <tr>
                <td class="muted tabular"><?= format_date($b['created_at']) ?></td>
                <td><?= e($b['filename']) ?></td>
                <td class="muted"><?= e($b['bank_name'] ?: '—') ?></td>
                <td class="num-right"><span class="pill import"><?= (int)$b['imported_count'] ?> işlem</span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ADIM 2: Gözden geçir & aktar -->
<?php $rows = $staging['rows']; ?>
<div class="card-pad card" style="margin-bottom:18px">
    <div class="flex-between flex-wrap">
        <div>
            <h3 style="margin:0">📋 <?= count($rows) ?> işlem bulundu — kontrol edin</h3>
            <p class="muted" style="margin:4px 0 0">
                Dosya: <b><?= e($staging['filename']) ?></b>
                <?php if ($staging['bank']): ?> · Banka: <b><?= e($staging['bank']) ?></b><?php endif; ?>
                · Yöntem: <?= $staging['method']==='pdftotext'?'pdftotext':'PHP' ?>
                · <?= (int)$staging['matched'] ?> otomatik kategorilendi
            </p>
        </div>
        <form method="post" action="<?= url('actions/import_upload.php') ?>" onsubmit="return false">
            <a href="<?= url('import.php?iptal=1') ?>" class="btn btn-ghost">✕ İptal Et</a>
        </form>
    </div>
</div>

<form method="post" action="<?= url('actions/import_commit.php') ?>">
    <?= csrf_field() ?>

    <div class="filterbar" style="align-items:center">
        <div class="field">
            <label>Bu işlemleri hangi hesaba ekleyelim?</label>
            <select name="account_id" class="input" style="min-width:220px">
                <option value="">— Hesap seçilmedi —</option>
                <?php foreach ($allAccs as $a): ?>
                    <option value="<?= (int)$a['id'] ?>"><?= e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <label class="flex" style="gap:8px;font-weight:500;color:var(--ink)">
                <input type="checkbox" name="learn_rules" value="1" checked> Yeni kategori kurallarını öğren
            </label>
        </div>
        <div class="spacer"></div>
        <div style="text-align:right">
            <div class="muted" style="font-size:12.5px">Seçili: <b id="sumCount">0</b> işlem</div>
            <div style="font-size:13.5px">
                <span class="amount income">+<span id="sumIncome">0,00 ₺</span></span> &nbsp;
                <span class="amount expense">−<span id="sumExpense">0,00 ₺</span></span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr>
                    <th style="width:36px"><input type="checkbox" checked onchange="toggleAllRows(this)" title="Tümünü seç"></th>
                    <th>Tarih</th><th>Açıklama</th><th>Tür</th><th>Kategori</th><th class="num-right">Tutar</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <tr data-import-row>
                        <td><input type="checkbox" class="import-row-check" name="include[]" value="<?= $i ?>" checked></td>
                        <td><input class="input btn-sm" style="width:130px;padding:6px 8px" type="date" name="date[<?= $i ?>]" value="<?= e($r['date']) ?>"></td>
                        <td><input class="input btn-sm" style="min-width:200px;padding:6px 8px" type="text" name="description[<?= $i ?>]" value="<?= e($r['description']) ?>"></td>
                        <td>
                            <select name="type[<?= $i ?>]" class="input btn-sm" style="padding:6px 8px">
                                <option value="expense" <?= $r['type']==='expense'?'selected':'' ?>>Gider</option>
                                <option value="income"  <?= $r['type']==='income'?'selected':'' ?>>Gelir</option>
                            </select>
                        </td>
                        <td>
                            <select name="category_id[<?= $i ?>]" class="input btn-sm" style="padding:6px 8px;min-width:170px">
                                <?php catOptions($expCats, $incCats, $r['category_id'] ?? null); ?>
                            </select>
                        </td>
                        <td class="num-right">
                            <input class="input btn-sm tabular num-right" style="width:120px;padding:6px 8px" type="text"
                                   name="amount[<?= $i ?>]" inputmode="decimal" value="<?= number_format((float)$r['amount'],2,',','.') ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-foot" style="border-top:1px solid var(--line-soft)">
            <a href="<?= url('import.php?iptal=1') ?>" class="btn btn-ghost">İptal</a>
            <button type="submit" class="btn btn-primary">✓ Seçili İşlemleri İçe Aktar</button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
