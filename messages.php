<?php
require_once __DIR__ . '/includes/auth.php';
require_household();
require_feature('messages');

$me = (int)current_user()['id'];
$pdo = db();

// Görüşülen kişiler + son mesaj + okunmamış sayısı
$partners = $pdo->prepare(
    "SELECT u.id, u.name, u.username, u.avatar_color,
            (SELECT message FROM direct_messages d WHERE (d.from_user=u.id AND d.to_user=:me) OR (d.from_user=:me AND d.to_user=u.id) ORDER BY d.id DESC LIMIT 1) AS last_msg,
            (SELECT MAX(created_at) FROM direct_messages d WHERE (d.from_user=u.id AND d.to_user=:me) OR (d.from_user=:me AND d.to_user=u.id)) AS last_time,
            (SELECT COUNT(*) FROM direct_messages d WHERE d.from_user=u.id AND d.to_user=:me AND d.read_at IS NULL) AS unread
       FROM users u
      WHERE u.id <> :me AND u.id IN (
            SELECT CASE WHEN from_user=:me THEN to_user ELSE from_user END
              FROM direct_messages WHERE from_user=:me OR to_user=:me)
      ORDER BY last_time DESC"
);
$partners->execute([':me' => $me]);
$convs = $partners->fetchAll();

// Yeni mesaj için tüm kullanıcılar (kendisi hariç)
$allUsers = $pdo->prepare('SELECT id, name, username FROM users WHERE id <> ? AND is_active=1 ORDER BY name');
$allUsers->execute([$me]);
$users = $allUsers->fetchAll();

$with = (int)($_GET['with'] ?? 0);
$withUser = null;
if ($with > 0) {
    $w = $pdo->prepare('SELECT id, name, username, avatar_color FROM users WHERE id=? AND is_active=1');
    $w->execute([$with]);
    $withUser = $w->fetch();
}

$page_title = 'Mesajlar';
$page_subtitle = 'Diğer kullanıcılarla birebir yazışın';
$active = 'messages';
require __DIR__ . '/templates/header.php';
?>
<div class="grid cols-2-1 mt-2 dm-wrap">
    <!-- Konuşma -->
    <div class="card" style="display:flex;flex-direction:column;min-height:520px">
        <?php if (!$withUser): ?>
            <div class="card-pad" style="margin:auto;text-align:center">
                <div style="font-size:42px">✉️</div>
                <h3>Bir konuşma seçin</h3>
                <p class="muted">Sağdaki listeden bir kişi seçin ya da yeni bir mesaj başlatın.</p>
            </div>
        <?php else: ?>
            <div class="card-head">
                <div class="flex" style="gap:10px">
                    <div class="msg-av" style="background:<?= e($withUser['avatar_color']) ?>"><?= e(mb_strtoupper(mb_substr($withUser['name'],0,1))) ?></div>
                    <div><b><?= e($withUser['name']) ?></b><div class="muted" style="font-size:12px">@<?= e($withUser['username']) ?></div></div>
                </div>
            </div>
            <div id="dmFeed" class="chat-feed" style="flex:1"></div>
            <div class="chat-compose">
                <div class="flex" style="gap:8px">
                    <input class="input" id="dmMsg" placeholder="Mesaj yazın…" onkeydown="if(event.key==='Enter')sendDM()">
                    <button class="btn btn-primary" onclick="sendDM()">Gönder</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Konuşma listesi + yeni mesaj -->
    <div class="card card-pad">
        <h3>Konuşmalar</h3>
        <form method="get" action="<?= url('messages.php') ?>" class="mt-2">
            <div class="flex" style="gap:8px">
                <select class="input" name="with" required>
                    <option value="">Yeni mesaj başlat…</option>
                    <?php foreach ($users as $usr): ?>
                        <option value="<?= (int)$usr['id'] ?>"><?= e($usr['name']) ?> (@<?= e($usr['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-ghost btn-sm">Aç</button>
            </div>
        </form>

        <div class="list" style="margin-top:12px">
            <?php if (!$convs): ?>
                <p class="muted" style="font-size:13px">Henüz konuşmanız yok.</p>
            <?php else: foreach ($convs as $c): ?>
                <a class="item dm-conv <?= $with===(int)$c['id']?'active':'' ?>" href="<?= url('messages.php?with='.(int)$c['id']) ?>" style="text-decoration:none;color:inherit">
                    <div class="msg-av" style="background:<?= e($c['avatar_color']) ?>"><?= e(mb_strtoupper(mb_substr($c['name'],0,1))) ?></div>
                    <div class="grow" style="min-width:0">
                        <b><?= e($c['name']) ?></b>
                        <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block"><?= e(mb_strimwidth($c['last_msg'] ?? '', 0, 38, '…')) ?></span>
                    </div>
                    <?php if ((int)$c['unread'] > 0): ?><span class="badge"><?= (int)$c['unread'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php
$csrf = csrf_token();
$sendUrl = url('actions/dm_send.php');
$fetchUrl = url('actions/dm_fetch.php');
$inline_script = '';
if ($withUser):
$inline_script = <<<JS
let dmLast = 0, dmWith = {$with};
const dmFeed = document.getElementById('dmFeed');
function esc(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function dmBubble(m){
    return '<div class="cmsg '+(m.mine?'mine':'')+'"><div class="cmsg-body"><div class="cmsg-text">'+esc(m.message)+'</div>'+
           '<div class="cmsg-meta" style="text-align:right"><span>'+m.time+'</span></div></div></div>';
}
function dmFetch(){
    fetch('$fetchUrl?with='+dmWith+(dmLast?('&after='+dmLast):''))
      .then(r=>r.json()).then(d=>{
        if(!d.messages) return;
        if(dmLast===0 && d.messages.length===0){ dmFeed.innerHTML='<div class="muted" style="text-align:center;padding:30px">İlk mesajı gönderin.</div>'; return; }
        if(d.messages.length){
            if(dmLast===0) dmFeed.innerHTML='';
            const atBottom = dmFeed.scrollHeight - dmFeed.scrollTop - dmFeed.clientHeight < 80;
            d.messages.forEach(m=>{ dmFeed.insertAdjacentHTML('beforeend', dmBubble(m)); dmLast=Math.max(dmLast,m.id); });
            if(atBottom || d.messages.some(m=>m.mine)) dmFeed.scrollTop = dmFeed.scrollHeight;
        }
      }).catch(()=>{});
}
function sendDM(){
    const el=document.getElementById('dmMsg'); const v=el.value.trim(); if(!v)return;
    const b=new URLSearchParams(); b.append('csrf_token','$csrf'); b.append('to',dmWith); b.append('message',v);
    el.value='';
    fetch('$sendUrl',{method:'POST',body:b}).then(r=>r.json()).then(d=>{ if(d.ok) dmFetch(); });
}
dmFetch();
setInterval(dmFetch, 4000);
JS;
endif;
require __DIR__ . '/templates/footer.php';
