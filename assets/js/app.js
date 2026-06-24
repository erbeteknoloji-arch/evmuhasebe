/* =====================================================================
   EV MUHASEBE  -  İstemci Tarafı Etkileşimler
   ===================================================================== */

/* ----- Mobil menü ------------------------------------------------- */
function toggleNav() {
    document.body.classList.toggle('nav-open');
    _syncMoreBtn();
}

/* Alt gezinme "Menü" butonunu kenar çubuğu durumuyla senkronize et */
function _syncMoreBtn() {
    var btn  = document.getElementById('bnMoreBtn');
    var icon = document.getElementById('bnMoreIcon');
    if (!btn) return;
    var open = document.body.classList.contains('nav-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (icon) icon.textContent = open ? '✕' : '☰';
    btn.classList.toggle('active', open);
}

/* ----- Masaüstü kenar çubuğu daralt/genişlet ---------------------- */
function toggleSidebar() {
    document.body.classList.toggle('nav-collapsed');
    try {
        localStorage.setItem('navCollapsed',
            document.body.classList.contains('nav-collapsed') ? '1' : '0');
    } catch (e) {}
}

/* Mobilde bir menü bağlantısına tıklayınca menüyü kapat */
document.addEventListener('click', function (e) {
    var link = e.target.closest ? e.target.closest('.sidebar .nav a') : null;
    if (link && window.matchMedia('(max-width: 920px)').matches) {
        document.body.classList.remove('nav-open');
        _syncMoreBtn();
    }
});
/* Masaüstüne geçilince açık mobil menüyü kapat */
window.addEventListener('resize', function () {
    if (window.innerWidth > 920) {
        document.body.classList.remove('nav-open');
        _syncMoreBtn();
    }
});

/* ----- Modal yönetimi --------------------------------------------- */
function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
// Arka plana tıklayınca kapat — ANCAK basma ve bırakma ikisi de arka planda
// olmalı. Böylece metin seçerken (içeride basıp dışarıda bırakınca) menü kapanmaz.
var __modalDownTarget = null;
document.addEventListener('mousedown', function (e) {
    __modalDownTarget = e.target;
});
document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('modal-overlay')
        && __modalDownTarget === e.target) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});
// ESC ile kapat
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(function (m) {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

/* ----- İşlem düzenleme modalını ön-doldur ------------------------- */
function editTransaction(data) {
    const f = document.getElementById('txForm');
    if (!f) return;
    f.querySelector('[name=id]').value = data.id || '';
    f.querySelector('[name=description]').value = data.description || '';
    f.querySelector('[name=amount]').value = data.amount || '';
    f.querySelector('[name=transaction_date]').value = data.date || '';
    if (f.querySelector('[name=tags]')) f.querySelector('[name=tags]').value = data.tags || '';
    if (f.querySelector('[name=account_id]')) f.querySelector('[name=account_id]').value = data.account_id || '';
    if (f.querySelector('[name=category_id]')) f.querySelector('[name=category_id]').value = data.category_id || '';
    setType(data.type || 'expense');
    document.getElementById('txModalTitle').textContent = 'İşlemi Düzenle';
    openModal('txModal');
}
function newTransaction(type) {
    const f = document.getElementById('txForm');
    if (!f) return;
    f.reset();
    f.querySelector('[name=id]').value = '';
    const today = new Date().toISOString().slice(0, 10);
    f.querySelector('[name=transaction_date]').value = today;
    setType(type || 'expense');
    document.getElementById('txModalTitle').textContent = 'Yeni İşlem';
    openModal('txModal');
}

/* ----- Gelir/Gider segment seçici --------------------------------- */
function setType(type) {
    document.querySelectorAll('.seg label').forEach(function (l) { l.classList.remove('checked'); });
    const radio = document.querySelector('.seg input[value="' + type + '"]');
    if (radio) {
        radio.checked = true;
        radio.closest('label').classList.add('checked');
    }
    filterCategoriesByType(type);
}
function filterCategoriesByType(type) {
    const sel = document.querySelector('#txForm [name=category_id]');
    if (!sel) return;
    let firstVisible = null;
    Array.from(sel.options).forEach(function (opt) {
        if (!opt.dataset.type) return; // boş seçenek
        const show = opt.dataset.type === type;
        opt.hidden = !show;
        opt.disabled = !show;
        if (show && firstVisible === null) firstVisible = opt.value;
    });
    // Seçili seçenek artık görünmüyorsa ilk uygun olana geç
    const cur = sel.options[sel.selectedIndex];
    if (!cur || cur.dataset.type !== type) {
        if (firstVisible !== null) sel.value = firstVisible;
    }
}
document.addEventListener('change', function (e) {
    if (e.target.matches('.seg input')) {
        setType(e.target.value);
    }
});

/* ----- Silme onayı (form gönderimi) ------------------------------- */
function confirmDelete(message) {
    return confirm(message || 'Bu kaydı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');
}

/* ----- Para biçimi (anlık önizleme) ------------------------------- */
function formatTRY(n) {
    return Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
}

/* ----- İçe aktarma: toplu seçim & canlı toplam -------------------- */
function toggleAllRows(master) {
    document.querySelectorAll('.import-row-check').forEach(function (c) { c.checked = master.checked; });
    recalcImportTotals();
}
function recalcImportTotals() {
    let inc = 0, exp = 0, cnt = 0;
    document.querySelectorAll('tr[data-import-row]').forEach(function (tr) {
        const chk = tr.querySelector('.import-row-check');
        if (!chk || !chk.checked) return;
        cnt++;
        const amount = parseFloat(tr.querySelector('[name^="amount"]').value.replace(/\./g, '').replace(',', '.')) || 0;
        const type = tr.querySelector('[name^="type"]').value;
        if (type === 'income') inc += amount; else exp += amount;
    });
    const eInc = document.getElementById('sumIncome');
    const eExp = document.getElementById('sumExpense');
    const eCnt = document.getElementById('sumCount');
    if (eInc) eInc.textContent = formatTRY(inc);
    if (eExp) eExp.textContent = formatTRY(exp);
    if (eCnt) eCnt.textContent = cnt;
}
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('import-row-check') || e.target.matches('tr[data-import-row] select')) {
        recalcImportTotals();
    }
});
document.addEventListener('input', function (e) {
    if (e.target.matches('tr[data-import-row] [name^="amount"]')) {
        recalcImportTotals();
    }
});

/* ----- Sayfa yüklenince ------------------------------------------- */
document.addEventListener('DOMContentLoaded', function () {
    // Segment ilk durumunu işaretle
    const checked = document.querySelector('.seg input:checked');
    if (checked) checked.closest('label').classList.add('checked');
    recalcImportTotals();
});

/* =====================================================================
   AÇILIR HESAP MAKİNESİ
   ===================================================================== */
let calcExpression = '';

function toggleCalc(show) {
    const pop = document.getElementById('calcPop');
    if (!pop) return;
    const willShow = (show === undefined) ? !pop.classList.contains('open') : show;
    pop.classList.toggle('open', willShow);
    if (willShow) { calcRender(); }
}
function calcRender() {
    const expr = document.getElementById('calcExpr');
    const res = document.getElementById('calcRes');
    if (!expr) return;
    const disp = calcExpression
        .replace(/\*/g, ' × ').replace(/\//g, ' ÷ ')
        .replace(/-/g, ' − ').replace(/\+/g, ' + ')
        .replace(/%/g, ' %').replace(/\./g, ',');
    expr.value = disp.trim() || '0';
    res.textContent = calcEvaluate();
}
function calcEvaluate() {
    if (!calcExpression) return '0';
    // Yüzde -> /100, yalnızca güvenli karakterler
    let raw = calcExpression.replace(/%/g, '/100');
    if (!/^[0-9+\-*/(). ]+$/.test(raw)) return '—';
    try {
        // eslint-disable-next-line no-new-func
        let val = Function('"use strict"; return (' + raw + ')')();
        if (val === Infinity || val === -Infinity || isNaN(val)) return '—';
        val = Math.round((val + Number.EPSILON) * 100000000) / 100000000;
        return new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 8 }).format(val);
    } catch (e) {
        return '—';
    }
}
function calcKey(d) { calcExpression += d; calcRender(); }
function calcDot() {
    // Aynı sayıda ikinci virgülü engelle
    const tail = calcExpression.split(/[+\-*/%]/).pop();
    if (tail.indexOf('.') === -1) { calcExpression += (calcExpression === '' || /[+\-*/%]$/.test(calcExpression)) ? '0.' : '.'; calcRender(); }
}
function calcOp(op) {
    if (calcExpression === '' && op !== '-') return;
    if (/[+\-*/%]$/.test(calcExpression)) { calcExpression = calcExpression.slice(0, -1); }
    calcExpression += op; calcRender();
}
function calcBack() { calcExpression = calcExpression.slice(0, -1); calcRender(); }
function calcClear() { calcExpression = ''; calcRender(); }
function calcEquals() {
    const r = calcEvaluate();
    if (r !== '—') { calcExpression = r.replace(/\./g, '').replace(/,/g, '.'); calcRender(); }
}
function calcCopy() {
    const r = document.getElementById('calcRes');
    if (!r) return;
    const txt = r.textContent;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(txt).then(function () { flashTiny('Kopyalandı: ' + txt); });
    }
}
function flashTiny(msg) {
    let t = document.getElementById('tinyToast');
    if (!t) { t = document.createElement('div'); t.id = 'tinyToast'; t.className = 'tiny-toast'; document.body.appendChild(t); }
    t.textContent = msg; t.classList.add('show');
    clearTimeout(window.__tinyT); window.__tinyT = setTimeout(function () { t.classList.remove('show'); }, 1600);
}

// Klavye: Alt+C aç/kapat, açıkken rakam/operatör girişi
document.addEventListener('keydown', function (e) {
    if (e.altKey && (e.key === 'c' || e.key === 'C')) { e.preventDefault(); toggleCalc(); return; }
    const pop = document.getElementById('calcPop');
    if (!pop || !pop.classList.contains('open')) return;
    if (e.key === 'Escape') { toggleCalc(false); return; }
    if (/^[0-9]$/.test(e.key)) { calcKey(e.key); e.preventDefault(); }
    else if (e.key === '.' || e.key === ',') { calcDot(); e.preventDefault(); }
    else if (['+', '-', '*', '/', '%'].includes(e.key)) { calcOp(e.key); e.preventDefault(); }
    else if (e.key === 'Enter' || e.key === '=') { calcEquals(); e.preventDefault(); }
    else if (e.key === 'Backspace') { calcBack(); e.preventDefault(); }
});
