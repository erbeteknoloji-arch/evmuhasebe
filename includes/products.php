<?php
/**
 * Alışveriş listesi ürün kataloğu.
 * Ağ bağlantısı gerektirmeyen, akılda kalıcı emoji "görselleri" ile.
 * Her kategori bir renk taşır; ürün döşemeleri bu renkle boyanır.
 */

/** Kategori -> [emoji, başlık, döşeme rengi] */
function product_categories(): array
{
    return [
        'meyve'     => ['🥦', 'Meyve & Sebze',            '#E7F3EB'],
        'et'        => ['🥩', 'Et, Tavuk & Balık',        '#FBEAE1'],
        'sut'       => ['🥛', 'Süt & Kahvaltılık',         '#EAF1FB'],
        'firin'     => ['🍞', 'Ekmek & Fırın',             '#F6ECD9'],
        'kiler'     => ['🍚', 'Temel Gıda & Kiler',        '#F0EDE4'],
        'icecek'    => ['🥤', 'İçecekler',                  '#E7F0F3'],
        'atistir'   => ['🍫', 'Atıştırmalık & Tatlı',      '#F7E7EE'],
        'donmus'    => ['🧊', 'Donmuş & Hazır',            '#EAF4F7'],
        'temizlik'  => ['🧼', 'Temizlik',                  '#EAF6F0'],
        'bakim'     => ['🧴', 'Kişisel Bakım',             '#F3EEF9'],
        'bebek'     => ['🍼', 'Bebek & Evcil',             '#FBEFE6'],
        'diger'     => ['🧺', 'Diğer',                     '#EFEBE0'],
    ];
}

/**
 * Her kategori için ürün listesi: [emoji, ürün adı].
 */
function product_catalog(): array
{
    return [
        'meyve' => [
            ['🍎','Elma'],['🍌','Muz'],['🍊','Portakal'],['🍋','Limon'],['🍓','Çilek'],
            ['🍇','Üzüm'],['🍉','Karpuz'],['🍈','Kavun'],['🍑','Şeftali'],['🍒','Kiraz'],
            ['🥝','Kivi'],['🍍','Ananas'],['🥑','Avokado'],['🍅','Domates'],['🥒','Salatalık'],
            ['🥕','Havuç'],['🥔','Patates'],['🧅','Soğan'],['🧄','Sarımsak'],['🫑','Biber'],
            ['🥬','Marul'],['🥦','Brokoli'],['🍆','Patlıcan'],['🌽','Mısır'],['🍄','Mantar'],
            ['🫒','Zeytin'],['🌶️','Acı Biber'],['🥜','Fındık/Fıstık'],
        ],
        'et' => [
            ['🥩','Kırmızı Et'],['🍗','Tavuk But'],['🍖','Tavuk Göğsü'],['🥓','Pastırma/Bacon'],
            ['🌭','Sosis'],['🥚','Yumurta'],['🐟','Balık'],['🦐','Karides'],['🦑','Kalamar'],
            ['🍤','Hamsi'],['🥫','Ton Balığı'],['🧆','Köfte'],
        ],
        'sut' => [
            ['🥛','Süt'],['🧀','Peynir'],['🧈','Tereyağı'],['🍳','Yumurta'],['🥣','Yoğurt'],
            ['🫙','Bal'],['🍯','Reçel'],['☕','Kahve'],['🍵','Çay'],['🥐','Kruvasan'],
            ['🥯','Simit/Poğaça'],['🧇','Gofret'],['🥜','Fıstık Ezmesi'],['🌰','Tahin/Pekmez'],
        ],
        'firin' => [
            ['🍞','Ekmek'],['🥖','Baget'],['🥐','Kruvasan'],['🥯','Simit'],['🫓','Lavaş/Pide'],
            ['🥞','Krep/Pankek'],['🧁','Kek'],['🍪','Bisküvi'],['🥨','Tuzlu Kraker'],
        ],
        'kiler' => [
            ['🍚','Pirinç'],['🌾','Bulgur'],['🍝','Makarna'],['🫘','Kuru Fasulye'],['🫛','Nohut'],
            ['🥣','Mercimek'],['🧂','Tuz'],['🍬','Şeker'],['🫗','Sıvı Yağ'],['🫒','Zeytinyağı'],
            ['🥫','Salça'],['🥫','Konserve'],['🌽','Mısır Unu'],['🍮','Un'],['🧄','Baharat'],
            ['🍜','Hazır Çorba'],['🫙','Turşu'],['🍯','Bal'],
        ],
        'icecek' => [
            ['💧','Su'],['🥤','Gazlı İçecek'],['🧃','Meyve Suyu'],['🧋','Ayran'],['☕','Kahve'],
            ['🍵','Çay'],['🍺','Bira'],['🍷','Şarap'],['🫗','Soda'],['🥛','Süt'],['🧊','Buz'],
            ['🧉','Enerji İçeceği'],
        ],
        'atistir' => [
            ['🍫','Çikolata'],['🍬','Şeker/Akide'],['🍪','Kurabiye'],['🍿','Patlamış Mısır'],
            ['🥨','Çerez'],['🥜','Fındık'],['🌰','Kuruyemiş'],['🍩','Donut'],['🍰','Pasta'],
            ['🧁','Kek'],['🍮','Puding'],['🍦','Dondurma'],['🥧','Tatlı'],['🍯','Lokum'],
            ['🥔','Cips'],
        ],
        'donmus' => [
            ['🧊','Donmuş Sebze'],['🍕','Pizza'],['🍟','Patates Kızartması'],['🥟','Mantı'],
            ['🌮','Hazır Yemek'],['🍦','Dondurma'],['🐟','Donmuş Balık'],['🍗','Donmuş Tavuk'],
        ],
        'temizlik' => [
            ['🧼','Sabun'],['🧴','Bulaşık Deterjanı'],['🧽','Sünger'],['🧹','Süpürge'],
            ['🧺','Çamaşır Deterjanı'],['🧻','Tuvalet Kağıdı'],['🧴','Yumuşatıcı'],
            ['🚿','Yüzey Temizleyici'],['🗑️','Çöp Poşeti'],['🧯','Çamaşır Suyu'],
            ['🪣','Kova/Paspas'],['🧤','Eldiven'],['📄','Kağıt Havlu'],
        ],
        'bakim' => [
            ['🪥','Diş Fırçası'],['🦷','Diş Macunu'],['🧴','Şampuan'],['🧼','Duş Jeli'],
            ['🧻','Peçete'],['🪒','Tıraş Bıçağı'],['🧴','Krem/Losyon'],['💄','Makyaj'],
            ['🧷','Ped/Hijyen'],['🩹','Yara Bandı'],['💊','İlaç/Vitamin'],['🧫','Dezenfektan'],
            ['🪮','Tarak'],['🧴','Deodorant'],
        ],
        'bebek' => [
            ['🍼','Biberon'],['🧷','Bebek Bezi'],['🧴','Bebek Şampuanı'],['🧻','Islak Mendil'],
            ['🍪','Bebek Maması'],['🐶','Köpek Maması'],['🐱','Kedi Maması'],['🦴','Evcil Ödül'],
            ['🐟','Kedi Kumu'],
        ],
        'diger' => [
            ['🛒','Genel Ürün'],['🔋','Pil'],['💡','Ampul'],['🕯️','Mum'],['🧦','Çorap'],
            ['📒','Kırtasiye'],['🎁','Hediye'],['🌷','Çiçek'],['🔌','Şarj Aleti'],['🧰','Hırdavat'],
        ],
    ];
}

/**
 * Bir ürün adına en uygun emoji + döşeme rengini bul.
 * Eşleşme yoksa kategori varsayılanına / genel sepete düşer.
 */
function guess_product_icon(string $name): array
{
    $name = mb_strtolower(trim($name), 'UTF-8');
    if ($name === '') return ['🛒', '#EFEBE0'];

    $cats = product_categories();
    foreach (product_catalog() as $catKey => $items) {
        $color = $cats[$catKey][2] ?? '#EFEBE0';
        foreach ($items as $it) {
            $pn = mb_strtolower($it[1], 'UTF-8');
            // ürün adının ilk kelimesi ya da tam ad geçiyorsa eşleştir
            $first = explode('/', $pn)[0];
            if ($name === $pn || mb_strpos($name, $first) !== false || mb_strpos($first, $name) !== false) {
                return [$it[0], $color];
            }
        }
    }
    return ['🛒', '#EFEBE0'];
}
