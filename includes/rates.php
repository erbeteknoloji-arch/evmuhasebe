<?php
/**
 * =====================================================================
 *  Rates  -  Döviz ve kıymetli maden kurları
 * =====================================================================
 *  Güncel kurları internetten çeker, exchange_rates tablosunda önbelleğe
 *  alır ve varlıkların TL değerini hesaplamada kullanılır.
 *
 *  Kaynaklar (anahtar/ücret gerektirmeyen):
 *    - Döviz (USD/EUR/GBP→TRY):  open.er-api.com
 *    - Altın/Gümüş (ons USD):    api.gold-api.com  (gram'a çevrilir)
 *    - Yedek (Türkiye, gram altın): finans.truncgil.com
 *  Hiçbir kaynağa ulaşılamazsa son önbellek korunur; kullanıcı Ayarlar/
 *  Varlıklar sayfasından kurları elle de güncelleyebilir.
 * =====================================================================
 */

require_once __DIR__ . '/functions.php';

/** Desteklenen varlıklar: code => [etiket, grup, birim] */
function asset_catalog(): array
{
    return [
        'USD'        => ['Amerikan Doları', 'doviz', '$'],
        'EUR'        => ['Euro',            'doviz', '€'],
        'GBP'        => ['İngiliz Sterlini', 'doviz', '£'],
        'XAU_GRAM'   => ['Gram Altın',      'maden', 'gr'],
        'XAG_GRAM'   => ['Gram Gümüş',      'maden', 'gr'],
        'CEYREK'     => ['Çeyrek Altın',    'maden', 'adet'],
        'YARIM'      => ['Yarım Altın',     'maden', 'adet'],
        'TAM'        => ['Tam Altın',       'maden', 'adet'],
    ];
}

function asset_label(string $code): string
{
    $c = asset_catalog();
    return $c[$code][0] ?? $code;
}

/** Önbellekteki kurları döndürür: [code => rate_try]. Gerekirse yeniler. */
function get_rates(bool $force = false): array
{
    if (RATES_AUTO_FETCH || $force) {
        maybe_refresh_rates($force);
    }
    $rows = db()->query('SELECT code, rate_try FROM exchange_rates')->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['code']] = (float)$r['rate_try'];
    }
    return $map;
}

/** En son güncellemeden bu yana TTL dolduysa kurları yeniler. */
function maybe_refresh_rates(bool $force = false): void
{
    try {
        $last = db()->query('SELECT MAX(updated_at) FROM exchange_rates')->fetchColumn();
        if (!$force && $last) {
            $age = time() - strtotime($last);
            if ($age < RATES_TTL_HOURS * 3600) {
                return; // hâlâ taze
            }
        }
        refresh_rates();
    } catch (Throwable $e) {
        error_log('[EvMuhasebe Rates] ' . $e->getMessage());
    }
}

function rates_last_updated(): ?string
{
    try {
        $v = db()->query('SELECT MAX(updated_at) FROM exchange_rates')->fetchColumn();
        return $v ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Kurları kaynaklardan çeker ve veritabanına yazar.
 * @return array [code=>rate_try] güncellenenler
 */
function refresh_rates(): array
{
    $out = [];

    // 1) DÖVİZ — open.er-api.com (USD bazlı)
    $usdTry = $eurTry = $gbpTry = null;
    $fiat = http_get_json('https://open.er-api.com/v6/latest/USD');
    if ($fiat && ($fiat['result'] ?? '') === 'success' && !empty($fiat['rates']['TRY'])) {
        $r = $fiat['rates'];
        $usdTry = (float)$r['TRY'];
        if (!empty($r['EUR'])) $eurTry = $usdTry / (float)$r['EUR'];
        if (!empty($r['GBP'])) $gbpTry = $usdTry / (float)$r['GBP'];
    }

    // 2) MADEN — api.gold-api.com (ons/USD) → gram/TL
    $gramGold = $gramSilver = null;
    if ($usdTry) {
        $xau = http_get_json('https://api.gold-api.com/price/XAU');
        $xag = http_get_json('https://api.gold-api.com/price/XAG');
        $ozToGram = 31.1034768;
        if ($xau && !empty($xau['price'])) {
            $gramGold = ((float)$xau['price'] / $ozToGram) * $usdTry;
        }
        if ($xag && !empty($xag['price'])) {
            $gramSilver = ((float)$xag['price'] / $ozToGram) * $usdTry;
        }
    }

    // 3) YEDEK — truncgil (gram altın doğrudan TL); döviz/gram boşsa doldur
    if ($usdTry === null || $gramGold === null) {
        $tr = http_get_json('https://finans.truncgil.com/today.json');
        if (is_array($tr)) {
            $pick = function ($node) {
                if (!is_array($node)) return null;
                foreach (['Selling', 'Satış', 'selling', 'satis', 'Buying', 'Alış'] as $k) {
                    if (isset($node[$k])) {
                        $v = parse_money_tr((string)$node[$k]);
                        if ($v) return $v;
                    }
                }
                return null;
            };
            $find = function (array $keys) use ($tr, $pick) {
                foreach ($keys as $key) {
                    foreach ($tr as $k => $node) {
                        if (mb_strtolower(str_replace([' ', '_', '-'], '', (string)$k)) ===
                            mb_strtolower(str_replace([' ', '_', '-'], '', $key))) {
                            $v = $pick($node);
                            if ($v) return $v;
                        }
                    }
                }
                return null;
            };
            $usdTry   = $usdTry   ?: $find(['USD']);
            $eurTry   = $eurTry   ?: $find(['EUR']);
            $gbpTry   = $gbpTry   ?: $find(['GBP']);
            $gramGold = $gramGold ?: $find(['gramaltin', 'GRA', 'gram-altin']);
            $gramSilver = $gramSilver ?: $find(['gumus', 'gram-gumus']);
        }
    }

    // Yaz: yalnızca değer bulunabilenleri güncelle
    $set = [];
    if ($usdTry)     $set['USD'] = $usdTry;
    if ($eurTry)     $set['EUR'] = $eurTry;
    if ($gbpTry)     $set['GBP'] = $gbpTry;
    if ($gramGold)   $set['XAU_GRAM'] = $gramGold;
    if ($gramSilver) $set['XAG_GRAM'] = $gramSilver;
    // Türk altın türleri gram altından türetilir (yaklaşık, piyasa primi hariç)
    if ($gramGold) {
        $set['CEYREK'] = $gramGold * 1.75;
        $set['YARIM']  = $gramGold * 3.5;
        $set['TAM']    = $gramGold * 7.0;
    }

    foreach ($set as $code => $val) {
        upsert_rate($code, (float)$val, asset_label($code), 'auto');
        $out[$code] = (float)$val;
    }
    return $out;
}

/** Tek bir kuru veritabanına yazar/günceller. */
function upsert_rate(string $code, float $rateTry, ?string $label = null, string $source = 'manual'): void
{
    $stmt = db()->prepare(
        'INSERT INTO exchange_rates (code, rate_try, label, source, updated_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE rate_try = VALUES(rate_try), label = VALUES(label),
                                 source = VALUES(source), updated_at = NOW()'
    );
    $stmt->execute([$code, $rateTry, $label, $source]);
}

/** Bir varlığın TL değerini hesaplar. */
function asset_value_try(string $code, float $qty, array $rates): float
{
    $rate = $rates[$code] ?? 0.0;
    return $qty * $rate;
}

/**
 * Basit HTTP GET + JSON çözümleyici. curl varsa onu, yoksa file_get_contents kullanır.
 */
function http_get_json(string $url, int $timeout = 8): ?array
{
    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'EvMuhasebe/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            $body = null;
        }
    }
    if ($body === null && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => $timeout, 'header' => "User-Agent: EvMuhasebe/1.0\r\n"],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) $body = null;
    }
    if ($body === null) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}
