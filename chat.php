<?php
require_once __DIR__ . '/includes/auth.php';
require_household();
require_feature('chat');

$page_title = 'Topluluk & Fiyat Paylaşımı';
$page_subtitle = 'Diğer kullanıcılarla sohbet edin, ucuz bulduğunuz ürünleri paylaşın';
$active = 'chat';
require __DIR__ . '/templates/header.php';
?>
<div class="card card-pad mt-2" style="background:var(--cream-2)">
    <p style="margin:0;font-size:13.5px;color:var(--ink-soft)">
        💡 Burada herkese açık sohbet edebilir ve <b>fiyat paylaşımı</b> yapabilirsiniz. Örneğin bir ürünü uygun fiyata bulduysanız,
        ürün adını, fiyatını ve nereden aldığınızı paylaşın; başkaları da aynı şeyi daha ucuza alabilsin. (Mesajlar tüm kullanıcılar tarafından görülür.)
    </p>
</div>

<div class="card mt-2" style="overflow:hidden">
    <div id="chatFeed" class="chat-feed">
        <div class="muted" style="text-align:center;padding:30px">Yükleniyor…</div>
    </div>

    <div class="chat-compose">
        <div class="seg" style="margin-bottom:10px">
            <label class="checked" id="segChat" onclick="chatMode('chat')">💬 Mesaj</label>
            <label id="segPrice" onclick="chatMode('price')">🏷️ Fiyat Paylaş</label>
        </div>

        <div id="modeChat">
            <div class="flex" style="gap:8px">
                <input class="input" id="chatMsg" placeholder="Bir mesaj yazın…" onkeydown="if(event.key==='Enter')sendChat()">
                <button class="btn btn-primary" onclick="sendChat()">Gönder</button>
            </div>
        </div>

        <div id="modePrice" style="display:none">
            <div class="grid grid-3">
                <input class="input" id="pProduct" placeholder="Ürün (ör. 1L Süt)">
                <input class="input tabular" id="pPrice" inputmode="decimal" placeholder="Fiyat (ör. 32,50)">
                <input class="input" id="pStore" placeholder="Nereden (ör. A101)">
            </div>
            <div class="flex" style="gap:8px;margin-top:8px">
                <input class="input" id="pNote" placeholder="Not (ops.)">
                <button class="btn btn-gold" onclick="sendPrice()">Paylaş</button>
            </div>
        </div>
    </div>
</div>

<?php
$csrf = csrf_token();
$sendUrl = url('actions/chat_send.php');
$fetchUrl = url('actions/chat_fetch.php');
$inline_script = <<<JS
let lastId = 0, curMode = 'chat', chatTimer = null;
const feed = document.getElementById('chatFeed');

function esc(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function chatMode(m){
    curMode = m;
    document.getElementById('segChat').classList.toggle('checked', m==='chat');
    document.getElementById('segPrice').classList.toggle('checked', m==='price');
    document.getElementById('modeChat').style.display = m==='chat' ? 'block':'none';
    document.getElementById('modePrice').style.display = m==='price' ? 'block':'none';
}

function bubble(m){
    if (m.kind === 'price'){
        return '<div class="cmsg '+(m.mine?'mine':'')+'">'+
            '<div class="cmsg-av" style="background:'+esc(m.color)+'">'+esc((m.name||'?')[0].toUpperCase())+'</div>'+
            '<div class="cmsg-body"><div class="cmsg-meta"><b>'+esc(m.name)+'</b> <span>'+m.time+'</span></div>'+
            '<div class="price-card"><div class="pc-top">🏷️ <b>'+esc(m.product)+'</b> · <span class="pc-price">'+esc(m.price)+'</span></div>'+
            (m.store?'<div class="pc-store">📍 '+esc(m.store)+'</div>':'')+
            (m.message?'<div class="pc-note">'+esc(m.message)+'</div>':'')+'</div></div></div>';
    }
    return '<div class="cmsg '+(m.mine?'mine':'')+'">'+
        '<div class="cmsg-av" style="background:'+esc(m.color)+'">'+esc((m.name||'?')[0].toUpperCase())+'</div>'+
        '<div class="cmsg-body"><div class="cmsg-meta"><b>'+esc(m.name)+'</b> <span>'+m.time+'</span></div>'+
        '<div class="cmsg-text">'+esc(m.message)+'</div></div></div>';
}

function fetchChat(){
    fetch('$fetchUrl'+(lastId?('?after='+lastId):''))
      .then(r=>r.json()).then(d=>{
        if (!d.messages) return;
        if (lastId===0 && d.messages.length===0){ feed.innerHTML='<div class="muted" style="text-align:center;padding:30px">Henüz mesaj yok. İlk mesajı siz gönderin!</div>'; return; }
        if (d.messages.length){
            if (lastId===0) feed.innerHTML='';
            const atBottom = feed.scrollHeight - feed.scrollTop - feed.clientHeight < 80;
            d.messages.forEach(m=>{ feed.insertAdjacentHTML('beforeend', bubble(m)); lastId=Math.max(lastId,m.id); });
            if (atBottom || d.messages.some(m=>m.mine)) feed.scrollTop = feed.scrollHeight;
        }
      }).catch(()=>{});
}

function postChat(body){
    body.append('csrf_token','$csrf');
    return fetch('$sendUrl',{method:'POST',body:body}).then(r=>r.json());
}
function sendChat(){
    const el=document.getElementById('chatMsg'); const v=el.value.trim(); if(!v)return;
    const b=new URLSearchParams(); b.append('kind','chat'); b.append('message',v);
    el.value='';
    postChat(b).then(d=>{ if(d.ok) fetchChat(); });
}
function sendPrice(){
    const p=document.getElementById('pProduct').value.trim();
    const pr=document.getElementById('pPrice').value.trim();
    if(!p||!pr){ alert('Ürün ve fiyat gerekli.'); return; }
    const b=new URLSearchParams();
    b.append('kind','price'); b.append('product',p); b.append('price',pr);
    b.append('store',document.getElementById('pStore').value.trim());
    b.append('message',document.getElementById('pNote').value.trim());
    postChat(b).then(d=>{ if(d.ok){ document.getElementById('pProduct').value='';document.getElementById('pPrice').value='';document.getElementById('pStore').value='';document.getElementById('pNote').value=''; fetchChat(); } else alert(d.err||'Hata'); });
}

fetchChat();
chatTimer = setInterval(fetchChat, 4000);
JS;
require __DIR__ . '/templates/footer.php';
