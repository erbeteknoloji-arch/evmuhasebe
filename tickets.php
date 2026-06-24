<?php
require_once __DIR__ . '/includes/auth.php';
require_household();
require_feature('tickets');

$u = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    "SELECT t.*,
            (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id=t.id) AS msg_count,
            (SELECT MAX(created_at) FROM ticket_messages m WHERE m.ticket_id=t.id) AS last_msg
       FROM tickets t WHERE t.user_id=? ORDER BY t.updated_at DESC"
);
$stmt->execute([$u['id']]);
$tickets = $stmt->fetchAll();

$statusLabel = ['open' => ['Açık', 'var(--info)'], 'answered' => ['Yanıtlandı', 'var(--income)'], 'closed' => ['Kapalı', 'var(--ink-faint)']];
$prioLabel = ['low' => 'Düşük', 'normal' => 'Normal', 'high' => 'Yüksek'];

$page_title = 'Destek Talepleri';
$page_subtitle = 'Sorularınız ve sorunlarınız için bizimle iletişime geçin';
$page_actions = '<button class="btn btn-primary" onclick="openModal(\'tkModal\')">+ Yeni Talep</button>';
$active = 'tickets';
require __DIR__ . '/templates/header.php';
?>

<?php if (!$tickets): ?>
<div class="card card-pad mt-2" style="text-align:center">
    <div style="font-size:42px">🎫</div>
    <h3>Henüz destek talebiniz yok</h3>
    <p class="muted">Bir sorun yaşıyorsanız veya öneriniz varsa yeni bir talep oluşturun; yöneticiler size buradan yanıt verecek.</p>
    <button class="btn btn-primary" onclick="openModal('tkModal')">+ Yeni Talep Oluştur</button>
</div>
<?php else: ?>
<div class="card mt-2">
    <table class="data">
        <thead><tr><th>Konu</th><th>Durum</th><th>Öncelik</th><th class="r">Mesaj</th><th class="r">Son Güncelleme</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): $s = $statusLabel[$t['status']]; ?>
            <tr>
                <td><b><?= e($t['subject']) ?></b><div class="muted" style="font-size:12px">#<?= (int)$t['id'] ?> · <?= format_date($t['created_at']) ?></div></td>
                <td><span class="pill" style="background:transparent;color:<?= $s[1] ?>;border:1px solid <?= $s[1] ?>"><?= $s[0] ?></span></td>
                <td><?= e($prioLabel[$t['priority']] ?? $t['priority']) ?></td>
                <td class="r"><?= (int)$t['msg_count'] ?></td>
                <td class="r"><?= $t['last_msg'] ? format_date($t['last_msg']) : '-' ?></td>
                <td class="r"><a class="btn btn-ghost btn-sm" href="<?= url('ticket_view.php?id='.(int)$t['id']) ?>">Aç →</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="modal-overlay" id="tkModal">
    <div class="modal">
        <div class="modal-head"><h3>Yeni Destek Talebi</h3><button class="x" onclick="closeModal('tkModal')">×</button></div>
        <form method="post" action="<?= url('actions/ticket_create.php') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="field"><label>Konu</label>
                    <input class="input" name="subject" maxlength="180" placeholder="Kısa bir başlık" required></div>
                <div class="field"><label>Öncelik</label>
                    <select class="input" name="priority">
                        <option value="normal">Normal</option>
                        <option value="low">Düşük</option>
                        <option value="high">Yüksek</option>
                    </select></div>
                <div class="field"><label>Mesajınız</label>
                    <textarea class="input" name="message" rows="5" placeholder="Sorununuzu veya talebinizi ayrıntılı yazın" required></textarea></div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('tkModal')">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Talebi Gönder</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php';