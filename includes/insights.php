<?php
/**
 * Harcama içgörüleri / tasarruf önerileri.
 * Bu ayın işlemlerini geçen ayla ve bütçelerle karşılaştırarak
 * kullanıcıya somut, yargılayıcı olmayan öneriler üretir.
 *
 * Dönüş: her biri ['level','icon','title','text'] olan dizi.
 *   level: warn | tip | good | info
 */

require_once __DIR__ . '/functions.php';

function spending_insights(int $householdId): array
{
    $pdo = db();
    $tips = [];

    $thisStart = date('Y-m-01');
    $thisEnd   = date('Y-m-t');
    $lastStart = date('Y-m-01', strtotime('first day of last month'));
    $lastEnd    = date('Y-m-t', strtotime('last day of last month'));

    // Toplamlar
    $sumQ = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN type='income'  THEN amount END),0) AS inc,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) AS exp
         FROM transactions
         WHERE household_id=? AND transaction_date BETWEEN ? AND ?"
    );
    $sumQ->execute([$householdId, $thisStart, $thisEnd]);
    $cur = $sumQ->fetch();
    $income = (float)$cur['inc'];
    $expense = (float)$cur['exp'];

    // Kategori bazında bu ay & geçen ay (gider)
    $catQ = $pdo->prepare(
        "SELECT c.id, c.name,
                COALESCE(SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? THEN t.amount END),0) AS cur_amt,
                COALESCE(SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? THEN t.amount END),0) AS prev_amt
           FROM categories c
           LEFT JOIN transactions t
                  ON t.category_id=c.id AND t.household_id=c.household_id AND t.type='expense'
          WHERE c.household_id=? AND c.type='expense'
          GROUP BY c.id, c.name"
    );
    $catQ->execute([$thisStart, $thisEnd, $lastStart, $lastEnd, $householdId]);
    $cats = $catQ->fetchAll();

    // Keyfi/azaltılabilir kategoriler (ada göre)
    $discretionaryNames = ['Eğlence', 'Restoran & Kafe', 'Abonelikler', 'Giyim'];
    $discTotal = 0.0;
    $byName = [];
    foreach ($cats as $c) {
        $byName[$c['name']] = $c;
        if (in_array($c['name'], $discretionaryNames, true)) {
            $discTotal += (float)$c['cur_amt'];
        }
    }

    // 1) Gider geliri aştı / tasarruf oranı
    if ($expense > 0 && $income > 0) {
        $net = $income - $expense;
        $rate = $net / $income;
        if ($net < 0) {
            $tips[] = ['level' => 'warn', 'icon' => '⚠️', 'title' => 'Bu ay gideriniz gelirinizi aştı',
                'text' => 'Bu ay ' . money(abs($net)) . ' açık veriyorsunuz. Aşağıdaki keyfi kategorilerden kısmak dengeyi sağlayabilir.'];
        } elseif ($rate < 0.10) {
            $tips[] = ['level' => 'tip', 'icon' => '💡', 'title' => 'Tasarruf oranınız düşük',
                'text' => 'Gelirinizin yalnızca %' . number_format($rate * 100, 0) . '\'ini biriktiriyorsunuz. %20 hedeflemek için aylık yaklaşık ' . money($income * 0.20 - $net) . ' daha ayırmanız gerekir.'];
        } elseif ($rate >= 0.20) {
            $tips[] = ['level' => 'good', 'icon' => '✅', 'title' => 'Tasarruf alışkanlığınız güçlü',
                'text' => 'Bu ay gelirinizin %' . number_format($rate * 100, 0) . '\'ini (' . money($net) . ') biriktirdiniz. Bunu bir birikim hedefine yönlendirebilirsiniz.'];
        }
    }

    // 2) Keyfi harcama payı yüksekse
    if ($expense > 0 && $discTotal > 0) {
        $share = $discTotal / $expense;
        if ($share >= 0.30) {
            $tips[] = ['level' => 'warn', 'icon' => '🎯', 'title' => 'Keyfi harcamalar toplamın büyük kısmı',
                'text' => 'Eğlence, restoran/kafe, abonelik ve giyim birlikte giderlerinizin %' . number_format($share * 100, 0) . '\'ini (' . money($discTotal) . ') oluşturuyor. Bunların yarısını kısmak ayda ' . money($discTotal / 2) . ' tasarruf demek.'];
        }
    }

    // 3) Kategori bazında belirgin artış (geçen aya göre)
    foreach ($cats as $c) {
        $cu = (float)$c['cur_amt'];
        $pr = (float)$c['prev_amt'];
        if ($pr >= 200 && $cu > $pr * 1.4) {
            $artis = $cu - $pr;
            $tips[] = ['level' => 'tip', 'icon' => '📈', 'title' => $c['name'] . ' harcaması arttı',
                'text' => e($c['name']) . ' kategorisinde geçen aya göre %' . number_format((($cu / $pr) - 1) * 100, 0) . ' artış var (+' . money($artis) . '). Sebebini gözden geçirmek isteyebilirsiniz.'];
        }
    }

    // 4) En büyük tek gider kategorisi (kira hariç bilgi amaçlı)
    $topName = null; $topAmt = 0.0;
    foreach ($cats as $c) {
        if ((float)$c['cur_amt'] > $topAmt) { $topAmt = (float)$c['cur_amt']; $topName = $c['name']; }
    }
    if ($topName && $expense > 0 && ($topAmt / $expense) >= 0.35 && $topName !== 'Kira') {
        $tips[] = ['level' => 'info', 'icon' => '🔍', 'title' => 'Tek kategori öne çıkıyor',
            'text' => 'Bu ayki giderlerinizin %' . number_format(($topAmt / $expense) * 100, 0) . '\'i ' . e($topName) . ' kategorisinde (' . money($topAmt) . ').'];
    }

    // 5) Bütçe aşımları
    try {
        $budQ = $pdo->prepare(
            "SELECT c.name, b.monthly_limit,
                    COALESCE(SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? THEN t.amount END),0) AS spent
               FROM budgets b
               JOIN categories c ON c.id=b.category_id
               LEFT JOIN transactions t ON t.category_id=b.category_id AND t.household_id=b.household_id AND t.type='expense'
              WHERE b.household_id=? AND b.monthly_limit>0
              GROUP BY c.name, b.monthly_limit"
        );
        $budQ->execute([$thisStart, $thisEnd, $householdId]);
        foreach ($budQ->fetchAll() as $b) {
            $spent = (float)$b['spent']; $limit = (float)$b['monthly_limit'];
            if ($limit > 0 && $spent > $limit) {
                $tips[] = ['level' => 'warn', 'icon' => '🚧', 'title' => $b['name'] . ' bütçesi aşıldı',
                    'text' => e($b['name']) . ' için ' . money($limit) . ' bütçe koymuştunuz; bu ay ' . money($spent) . ' harcandı (' . money($spent - $limit) . ' aşım).'];
            }
        }
    } catch (Throwable $e) { /* yoksay */ }

    // 6) Şans oyunu / bahis harcaması (destekleyici uyarı)
    $gambleQ = $pdo->prepare(
        "SELECT COALESCE(SUM(amount),0) AS amt, COUNT(*) AS cnt
           FROM transactions
          WHERE household_id=? AND type='expense'
            AND transaction_date BETWEEN ? AND ?
            AND (description LIKE '%nesine%' OR description LIKE '%iddaa%' OR description LIKE '%bilyoner%'
              OR description LIKE '%bahis%' OR description LIKE '%misli%' OR description LIKE '%şans oyun%'
              OR description LIKE '%sans oyun%' OR description LIKE '%bet%')"
    );
    $gambleQ->execute([$householdId, $thisStart, $thisEnd]);
    $g = $gambleQ->fetch();
    if ((float)$g['amt'] > 0) {
        $tips[] = ['level' => 'warn', 'icon' => '🎲', 'title' => 'Şans oyunu / bahis harcaması',
            'text' => 'Bu ay şans oyunu/bahis olarak görünen ' . (int)$g['cnt'] . ' işlemde ' . money((float)$g['amt']) . ' harcanmış. Bu kalemler hızla birikir; aylık bir limit belirlemek bütçenizi korur.'];
    }

    // 7) Abonelik birikmesi
    if (!empty($byName['Abonelikler']) && (float)$byName['Abonelikler']['cur_amt'] >= 300) {
        $tips[] = ['level' => 'tip', 'icon' => '🔁', 'title' => 'Abonelikleri gözden geçirin',
            'text' => 'Bu ay aboneliklere ' . money((float)$byName['Abonelikler']['cur_amt']) . ' ödediniz. Kullanmadıklarınızı iptal etmek doğrudan tasarruf sağlar.'];
    }

    // Hiç uyarı yoksa olumlu kapanış
    if (empty($tips)) {
        $tips[] = ['level' => 'good', 'icon' => '🌿', 'title' => 'Harcamalarınız dengeli görünüyor',
            'text' => 'Bu ay öne çıkan bir israf kalemi tespit edilmedi. Böyle devam!'];
    }

    return $tips;
}
