<?php
require_once __DIR__ . '/includes/auth.php';
require_household();
require_feature('tickets');

$u = current_user();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$admin = is_admin_user();

// Talebi getir (sahibi veya admin görebilir)
$stmt = $pdo->prepare('SELECT t.*, us.name AS owner_name, us.email AS owner_email FROM tickets t JOIN users us ON us.id=t.user_id WHERE t.id=? LIMIT 1');
$stmt->execute([$id]);
$ticket = $stmt->fetch();
if (!$ticket || (!$admin && (int)$ticket['user_id'] !== (int)$u['id'])) {
    flash('error', 'Talep bulunamadı.');
    redirect('tickets.php');
}

$mStmt = $pdo->prepare('SELECT m.*, us.name AS uname, us.avatar_color FROM ticket_messages m LEFT JOIN users us ON us.id=m.user_id WHERE m.ticket_id=? ORDER BY m.created_at ASC');
$mStmt->execute([$id]);
$messages = $mStmt->fetchAll();

$statusLabel = ['open' => 'Açık', 'answered' => 'Yanıtlandı', 'closed' => 'Kapalı'];

$page_title = 'Talep #' . $id;
$page_subtitle = $ticket['subject'];
$active = 'tickets';
require __DIR__ . '/templates/header.php';
?>
<div class="flex" style="gap:8px;margin:6px 0 14px">
    <a class="btn btn-ghost btn-sm" href="<?= url($admin ? 'admin/tickets.php' : 'tickets.php') ?>">← Geri</a>
    <span class="pill"><?= e($statusLabel[$ticket['status']] ?? $ticket['status']) ?></span>
    <?php if ($admin): ?><span class="muted" style="font-size:12.5px;align-self:center">Sahibi: <?= e($ticket['owner_name']) ?> (<?= e($ticket['owner_email']) ?>)</span><?php endif; ?>
</div>

<div class="card card-pad">
    <div class="chat-thread">
        <?php foreach ($messages as $m): $mine = (int)$m['user_id'] === (int)$u['id']; ?>
            <div class="msg <?= $m['is_admin'] ? 'msg-admin' : 'msg-user' ?> <?= $mine ? 'msg-mine' : '' ?>">
                <div class="msg-av" style="background:<?= e($m['avatar_color'] ?? '#14452F') ?>"><?= e(mb_strtoupper(mb_substr($m['uname'] ?? '?', 0, 1))) ?></div>
                <div class="msg-body">
                    <div class="msg-meta"><b><?= e($m['uname'] ?? 'Kullanıcı') ?></b>
                        <?= $m['is_admin'] ? '<span class="pill" style="background:var(--gold-soft);color:var(--gold)">Yönetici</span>' : '' ?>
                        <span><?= format_date($m['created_at']) ?> <?= date('H:i', strtotime($m['created_at'])) ?></span></div>
                    <div class="msg-text"><?= nl2br(e($m['message'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
    <form method="post" action="<?= url('actions/ticket_reply.php') ?>" style="margin-top:16px">
        <?= csrf_field() ?>
        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <div class="field"><label>Yanıt yaz</label>
            <textarea class="input" name="message" rows="3" placeholder="Mesajınızı yazın..." required></textarea></div>
        <div class="flex" style="gap:8px">
            <button class="btn btn-primary" type="submit">Gönder</button>
            <button class="btn btn-ghost" type="submit" formaction="<?= url('actions/ticket_status.php') ?>" name="status" value="closed" onclick="return confirm('Talep kapatılsın mı?')">Talebi Kapat</button>
        </div>
    </form>
    <?php else: ?>
    <div class="flash info" style="margin-top:14px"><span>ℹ</span> Bu talep kapatıldı.
        <form method="post" action="<?= url('actions/ticket_status.php') ?>" style="display:inline;margin-left:8px">
            <?= csrf_field() ?><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
            <button class="btn btn-ghost btn-sm" name="status" value="open">Yeniden Aç</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php';