<?php
/**
 * =====================================================================
 *  PdfStatementParser
 * =====================================================================
 *  Banka / kredi kartı PDF ekstrelerini okuyup işlem satırlarını çıkarır.
 *
 *  Metin çıkarma iki yöntemle denenir:
 *    1) Sunucuda "pdftotext" (poppler-utils) varsa onunla (en doğru sonuç).
 *    2) Yoksa saf PHP ile (FlateDecode akışlarını açar, metin operatörlerini okur).
 *
 *  Çıkarılan metinden tarih + açıklama + tutar içeren satırlar tespit edilir.
 *  Türkçe (1.234,56) ve İngilizce (1,234.56) tutar biçimleri desteklenir.
 *  Sonuçlar, kullanıcı içe aktarmadan önce gözden geçirsin diye dizi olarak döner.
 * =====================================================================
 */

class PdfStatementParser
{
    /** Tespit edilen banka adı (varsa) */
    public ?string $bankName = null;

    /** Ham metin (hata ayıklama için) */
    public string $rawText = '';

    private array $bankSignatures = [
        'Garanti' => ['GARANTI', 'GARANTİ BBVA', 'BONUS'],
        'İş Bankası' => ['IS BANKASI', 'İŞ BANKASI', 'ISBANK', 'MAXIMUM'],
        'Yapı Kredi' => ['YAPI KREDI', 'YAPI VE KREDI', 'WORLD KART', 'WORLDCARD'],
        'Akbank' => ['AKBANK', 'AXESS', 'WINGS'],
        'Ziraat Bankası' => ['ZIRAAT', 'BANKKART'],
        'QNB' => ['QNB', 'FINANSBANK', 'CARDFINANS'],
        'Halkbank' => ['HALKBANK', 'PARAF'],
        'Vakıfbank' => ['VAKIFBANK', 'VAKIF', 'WORLD'],
        'Denizbank' => ['DENIZBANK', 'BONUS'],
        'TEB' => ['TURK EKONOMI', 'TEB ', 'BONUS'],
        'ING' => ['ING BANK', 'ING '],
        'Enpara' => ['ENPARA'],
    ];

    /**
     * PDF dosyasını ayrıştırır ve işlem satırlarını döndürür.
     *
     * @return array{0: array<int,array>, 1: string} [satırlar, çıkarma yöntemi]
     */
    public function parse(string $filePath): array
    {
        [$text, $method] = $this->extractText($filePath);
        $this->rawText = $text;
        $this->bankName = $this->detectBank($text);
        $rows = $this->extractTransactions($text);
        return [$rows, $method];
    }

    /**
     * Hazır metinden (kullanıcının PDF'ten kopyalayıp yapıştırdığı) işlem satırlarını çıkarır.
     * Sunucuda pdftotext bulunmadığında veya otomatik okuma başarısız olduğunda kullanılır.
     *
     * @return array{0: array<int,array>, 1: string} [satırlar, "paste"]
     */
    public function parseText(string $text): array
    {
        $this->rawText = $text;
        $this->bankName = $this->detectBank($text);
        $rows = $this->extractTransactions($text);
        return [$rows, 'paste'];
    }

    /* --------------------------------------------------------------- */
    /*  METİN ÇIKARMA                                                  */
    /* --------------------------------------------------------------- */
    private function extractText(string $filePath): array
    {
        // 1) pdftotext varsa (en güvenilir, sütun düzenini korur)
        if ($this->commandExists('pdftotext')) {
            $out = $this->runPdfToText($filePath);
            if ($out !== null && trim($out) !== '') {
                return [$out, 'pdftotext'];
            }
        }
        // 2) Saf PHP yedeği
        $out = $this->extractTextPurePhp($filePath);
        return [$out, 'php'];
    }

    /** pdftotext çalıştırılabilir yolu. config.php'de PDFTOTEXT_PATH tanımlıysa onu kullanır. */
    private function pdftotextBin(): string
    {
        if (defined('PDFTOTEXT_PATH') && PDFTOTEXT_PATH !== '') {
            return PDFTOTEXT_PATH;
        }
        return 'pdftotext';
    }

    private function isWindows(): bool
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    private function commandExists(string $cmd): bool
    {
        // Tam yol verilmişse doğrudan dosya kontrolü (Windows/XAMPP için ideal)
        if (defined('PDFTOTEXT_PATH') && PDFTOTEXT_PATH !== '') {
            return is_file(PDFTOTEXT_PATH);
        }
        if (!function_exists('shell_exec')) {
            return false;
        }
        // Windows'ta "where", Unix'te "command -v"
        if ($this->isWindows()) {
            $res = @shell_exec('where ' . $cmd . ' 2>NUL');
        } else {
            $res = @shell_exec('command -v ' . escapeshellarg($cmd) . ' 2>/dev/null');
        }
        return is_string($res) && trim($res) !== '';
    }

    private function runPdfToText(string $filePath): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }
        $bin     = escapeshellarg($this->pdftotextBin());
        $devnull = $this->isWindows() ? '2>NUL' : '2>/dev/null';
        $cmd = $bin . ' -layout -enc UTF-8 ' . escapeshellarg($filePath) . ' - ' . $devnull;
        $out = @shell_exec($cmd);
        return is_string($out) ? $out : null;
    }

    /**
     * Saf PHP PDF metin çıkarıcı.
     * Akışları (stream) bulur, FlateDecode ise açar, BT..ET blokları içindeki
     * Tj / TJ operatörlerinden metni toplar. Dijital üretilmiş PDF'lerin çoğunda çalışır.
     */
    private function extractTextPurePhp(string $filePath): string
    {
        $data = @file_get_contents($filePath);
        if ($data === false) {
            return '';
        }

        $text = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $data, $m)) {
            foreach ($m[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded === false) {
                    $decoded = @gzinflate($stream);
                }
                if ($decoded === false) {
                    $decoded = $stream; // sıkıştırılmamış olabilir
                }
                $text .= $this->extractTextFromContent($decoded) . "\n";
            }
        }
        return $text;
    }

    private function extractTextFromContent(string $content): string
    {
        $result = '';
        // (...) Tj   ve   [...] TJ   bloklarını yakala
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $arr)) {
            foreach ($arr[1] as $block) {
                if (preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/s', $block, $parts)) {
                    foreach ($parts[1] as $p) {
                        $result .= $this->unescapePdfString($p);
                    }
                }
                $result .= "\n";
            }
        }
        if (preg_match_all('/\((?:[^()\\\\]|\\\\.)*\)\s*Tj/s', $content, $single)) {
            foreach ($single[0] as $tok) {
                if (preg_match('/\((.*)\)\s*Tj/s', $tok, $mm)) {
                    $result .= $this->unescapePdfString($mm[1]) . "\n";
                }
            }
        }
        return $result;
    }

    private function unescapePdfString(string $s): string
    {
        $map = ['\\n' => "\n", '\\r' => "\r", '\\t' => "\t",
                '\\(' => '(', '\\)' => ')', '\\\\' => '\\'];
        return strtr($s, $map);
    }

    /* --------------------------------------------------------------- */
    /*  BANKA TESPİTİ                                                  */
    /* --------------------------------------------------------------- */
    private function detectBank(string $text): ?string
    {
        $upper = mb_strtoupper($this->asciiFold($text), 'UTF-8');
        foreach ($this->bankSignatures as $bank => $needles) {
            foreach ($needles as $n) {
                if (strpos($upper, $n) !== false) {
                    return $bank;
                }
            }
        }
        return null;
    }

    /* --------------------------------------------------------------- */
    /*  İŞLEM SATIRLARI                                                */
    /* --------------------------------------------------------------- */
    private function extractTransactions(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if ($line === '' || mb_strlen($line) < 8) {
                continue;
            }

            // Satır başında/içinde tarih var mı?
            $date = $this->findDate($line);
            if ($date === null) {
                continue;
            }

            // Tüm parasal değerleri konumlarıyla bul
            $tokens = $this->findMoneyTokens($line);
            // Tarihin içine düşen sahte tutarları ele (örn. 01.03.2026 -> "01.03")
            $tokens = array_values(array_filter($tokens, function ($t) use ($date) {
                $tEnd = $t['pos'] + $t['len'];
                return $t['pos'] >= $date['end'] || $tEnd <= $date['start'];
            }));
            if (empty($tokens)) {
                continue;
            }

            // Tutarı ve yönünü belirle
            [$amount, $type, $amountPos, $amountLen] = $this->pickAmount($tokens, $line);
            if ($amount === null || $amount == 0.0) {
                continue;
            }

            // Açıklama: tarihten sonra, tutardan önceki metin
            $desc = $this->buildDescription($line, $date['end'], $amountPos);
            if ($desc === '') {
                $desc = 'İşlem';
            }

            $rows[] = [
                'date'        => $date['ymd'],
                'date_display'=> $date['display'],
                'description' => $desc,
                'amount'      => round(abs($amount), 2),
                'type'        => $type,         // income | expense (tahmin)
                'raw'         => $line,
            ];
        }

        return $rows;
    }

    /** Satırdaki ilk tarihi bulur (gg.aa.yyyy / gg/aa/yyyy / gg.aa.yy / Türkçe ay adı) */
    private function findDate(string $line): ?array
    {
        // Sayısal: 01.03.2026 | 01/03/2026 | 01-03-2026 | 01.03.26
        if (preg_match('/\b(\d{1,2})[.\/\-](\d{1,2})[.\/\-](\d{2,4})\b/u', $line, $m, PREG_OFFSET_CAPTURE)) {
            $d = (int)$m[1][0];
            $mo = (int)$m[2][0];
            $y = (int)$m[3][0];
            if ($y < 100) {
                $y += 2000;
            }
            if ($this->validDate($d, $mo, $y)) {
                $end = $m[0][1] + strlen($m[0][0]);
                return [
                    'ymd'     => sprintf('%04d-%02d-%02d', $y, $mo, $d),
                    'display' => sprintf('%02d.%02d.%04d', $d, $mo, $y),
                    'start'   => $m[0][1],
                    'end'     => $end,
                ];
            }
        }
        // Türkçe ay adı: 01 Mar 2026 | 1 Mart 2026
        $months = ['oca'=>1,'şub'=>2,'sub'=>2,'mar'=>3,'nis'=>4,'may'=>5,'haz'=>6,
                   'tem'=>7,'ağu'=>8,'agu'=>8,'eyl'=>9,'eki'=>10,'kas'=>11,'ara'=>12];
        if (preg_match('/\b(\d{1,2})\s+([A-Za-zçğıöşüÇĞİÖŞÜ]{3,9})\s+(\d{4})\b/u', $line, $m, PREG_OFFSET_CAPTURE)) {
            $d = (int)$m[1][0];
            $key = mb_strtolower(mb_substr($m[2][0], 0, 3), 'UTF-8');
            $y = (int)$m[3][0];
            if (isset($months[$key]) && $this->validDate($d, $months[$key], $y)) {
                $mo = $months[$key];
                $end = $m[0][1] + strlen($m[0][0]);
                return [
                    'ymd'     => sprintf('%04d-%02d-%02d', $y, $mo, $d),
                    'display' => sprintf('%02d.%02d.%04d', $d, $mo, $y),
                    'start'   => $m[0][1],
                    'end'     => $end,
                ];
            }
        }
        return null;
    }

    private function validDate(int $d, int $m, int $y): bool
    {
        return $d >= 1 && $d <= 31 && $m >= 1 && $m <= 12 && $y >= 2000 && $y <= 2099;
    }

    /**
     * Satırdaki parasal değerleri konumlarıyla bulur.
     * Örn. -1.234,56 | 1.234,56 | 1,234.56 | 245,50 TL
     */
    private function findMoneyTokens(string $line): array
    {
        $tokens = [];
        // İşaret + binlik/ondalık ayraçlı sayı (en az bir ayraç içermeli ki tarih vb. yakalanmasın)
        $pattern = '/([+\-]?\s?)(\d{1,3}(?:[.,]\d{3})*[.,]\d{2}|\d+[.,]\d{2})\b/u';
        if (preg_match_all($pattern, $line, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $whole = $match[0][0];
                $pos   = $match[0][1];
                $sign  = trim($match[1][0]);
                $num   = $match[2][0];
                $value = parse_money_tr($num);
                if ($value === null) {
                    continue;
                }
                if ($sign === '-') {
                    $value = -$value;
                }
                $tokens[] = [
                    'value'    => $value,
                    'signed'   => $sign === '-' || $sign === '+',
                    'sign'     => $sign,
                    'pos'      => $pos,
                    'len'      => strlen($whole),
                ];
            }
        }
        return $tokens;
    }

    /**
     * İşlem tutarını ve yönünü (gelir/gider) tahmin eder.
     * Strateji:
     *   - İşaretli (+/-) bir değer varsa onu kullan; işaret yönü belirler.
     *   - Birden fazla değer ve işaret yoksa, sonuncusu bakiye sayılır -> ilkini al.
     *   - Açıklamada "alacak/iade/yatan" geçiyorsa gelir; aksi halde gider.
     * (Kullanıcı içe aktarma öncesi her satırı düzeltebilir.)
     *
     * @return array{0: ?float, 1: string, 2: int, 3: int}
     */
    private function pickAmount(array $tokens, string $line): array
    {
        $lower = mb_strtolower($this->asciiFold($line), 'UTF-8');
        $incomeHint = preg_match('/\b(alacak|iade|yatan|gelen|faiz|temettu|maas|odeme alindi|para yatirma|virman gelen)\b/u', $lower);

        // 1) İşaretli değer öncelikli
        foreach ($tokens as $t) {
            if ($t['signed']) {
                $type = $t['value'] < 0 ? 'expense' : 'income';
                return [$t['value'], $type, $t['pos'], $t['len']];
            }
        }

        // 2) İşaretsiz: birden çok token varsa sonuncusu bakiye kabul edilir
        $chosen = $tokens[0];
        if (count($tokens) >= 2) {
            $chosen = $tokens[0]; // ilk değer = işlem tutarı
        }
        $type = $incomeHint ? 'income' : 'expense';
        return [$chosen['value'], $type, $chosen['pos'], $chosen['len']];
    }

    private function buildDescription(string $line, int $afterDatePos, int $beforeAmountPos): string
    {
        $start = max(0, $afterDatePos);
        $len = $beforeAmountPos - $start;
        if ($len <= 0) {
            // Tutar tarihten önce geliyorsa tüm satırdan tarihi/tutarı temizleyerek al
            $desc = $line;
        } else {
            $desc = substr($line, $start, $len);
        }
        // Baştaki/sondaki gereksiz işaretleri temizle
        $desc = trim($desc, " \t\n\r:|-*.");
        $desc = preg_replace('/\s+/u', ' ', $desc);
        // Çok uzun açıklamaları kısalt
        return mb_substr(trim($desc), 0, 200);
    }

    /** Türkçe karakterleri ASCII'ye indirger (eşleştirme için) */
    private function asciiFold(string $s): string
    {
        $map = ['ç'=>'c','Ç'=>'C','ğ'=>'g','Ğ'=>'G','ı'=>'i','İ'=>'I',
                'ö'=>'o','Ö'=>'O','ş'=>'s','Ş'=>'S','ü'=>'u','Ü'=>'U'];
        return strtr($s, $map);
    }
}
