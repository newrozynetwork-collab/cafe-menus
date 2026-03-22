<?php
/**
 * TiranaMenu Clone - Database Seeder
 * Run once: php database/seed.php
 */

define('ROOT', dirname(__DIR__));
$dbPath = ROOT . '/database/tirana.db';

// Ensure database directory exists
if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0755, true);

// Create DB from schema
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$schema = file_get_contents(ROOT . '/database/schema.sql');
// Execute each statement
foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
    if ($stmt) $pdo->exec($stmt . ';');
}
echo "✓ Schema applied\n";

// ── Idempotency check — skip if already seeded ────────────────
$alreadySeeded = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($alreadySeeded > 0) {
    echo "✓ Database already seeded ($alreadySeeded users found). Skipping.\n";
    exit(0);
}

// ── Helpers ──────────────────────────────────────────────────
function insert(PDO $pdo, string $table, array $data): int {
    $cols = implode(',', array_keys($data));
    $phs  = implode(',', array_fill(0, count($data), '?'));
    $stmt = $pdo->prepare("INSERT INTO $table ($cols) VALUES ($phs)");
    $stmt->execute(array_values($data));
    return (int)$pdo->lastInsertId();
}

// ── Super Admin User ─────────────────────────────────────────
$adminId = insert($pdo, 'users', [
    'name'     => 'Super Admin',
    'email'    => 'admin@tirana.local',
    'password' => password_hash('admin123', PASSWORD_BCRYPT),
    'role'     => 'superadmin',
]);
echo "✓ Admin user created (admin@tirana.local / admin123)\n";

// ── City ─────────────────────────────────────────────────────
$cityId = insert($pdo, 'cities', ['name' => 'Sulaymaniyah', 'slug' => 'Slemani']);
echo "✓ City: Sulaymaniyah\n";

// ════════════════════════════════════════════════════════════
//  RESTAURANT 1: Vogue Cafe & Lounge
// ════════════════════════════════════════════════════════════
$vogueId = insert($pdo, 'restaurants', [
    'city_id'       => $cityId,
    'name'          => 'Vogue Cafe & Lounge',
    'slug'          => 'vogue',
    'logo'          => 'uploads/Slemani/vogue/vogue-33d0.png',
    'theme_color'   => '#5073B5',
    'body_bg'       => '#ffffff',
    'font'          => 'Poppins',
    'default_lang'  => 'en',
    'has_sections'  => 0,
    'social_facebook'  => 'https://facebook.com/voguecafe',
    'social_instagram' => 'https://instagram.com/voguecafe',
    'social_phone'     => '+9647501234567',
    'social_location'  => 'https://maps.google.com/?q=Vogue+Cafe+Sulaymaniyah',
    'has_splash_video' => 0,
    'has_ad'           => 0,
    'is_active'        => 1,
]);
echo "✓ Restaurant: Vogue (id=$vogueId)\n";

// ── Vogue Categories ─────────────────────────────────────────
$vogueBase = 'uploads/Slemani/vogue/c/i/l/';
$vogueCats = [
    ['Cold Appetizers', 'المقبلات الباردة', 'خواردنی سارد', 'vogue-c752.png'],
    ['Hot Appetizers',  'المقبلات الساخنة', 'خواردنی گەرم',  'vogue-ffb3.png'],
    ['Grills',          'المشويات',          'گریل',           'vogue-62bd.png'],
    ['Pizza',           'البيتزا',           'پیتزا',          'vogue-97d6.png'],
    ['Pasta',           'المعكرونة',         'پاستا',          'vogue-9497.png'],
    ['Sandwiches and Burger', 'السندويشات والبرغر', 'ساندویچ و برگەر', 'vogue-6d03.png'],
    ['Side Dishes',     'الأطباق الجانبية', 'خواردنی لاپەڕ',  'vogue-c118.png'],
    ['Hot Plate',       'الطبق الساخن',     'پلاتی گەرم',     'vogue-2efc.png'],
    ['Grilled Fish',    'السمك المشوي',     'ماسی گریل',      'vogue-6171.png'],
    ['Special Foods',   'الأطباق الخاصة',  'خواردنی تایبەت', 'vogue-85c9.png'],
];
$vogueCatIds = [];
foreach ($vogueCats as $i => [$en, $ar, $ku, $icon]) {
    $vogueCatIds[$en] = insert($pdo, 'categories', [
        'restaurant_id' => $vogueId,
        'section_id'    => null,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'icon'          => $vogueBase . $icon,
        'sort_order'    => $i,
        'is_active'     => 1,
    ]);
}
echo "✓ Vogue categories: " . count($vogueCatIds) . "\n";

// ── Vogue Items (Cold Appetizers) ─────────────────────────────
$vogueItems  = ROOT . '/uploads/Slemani/vogue/i/i/m/';
$catColdApp  = $vogueCatIds['Cold Appetizers'];
$catHotApp   = $vogueCatIds['Hot Appetizers'];
$catGrills   = $vogueCatIds['Grills'];
$catPizza    = $vogueCatIds['Pizza'];
$catPasta    = $vogueCatIds['Pasta'];
$catSandwich = $vogueCatIds['Sandwiches and Burger'];
$catSide     = $vogueCatIds['Side Dishes'];
$catHotPlate = $vogueCatIds['Hot Plate'];
$catFish     = $vogueCatIds['Grilled Fish'];
$catSpecial  = $vogueCatIds['Special Foods'];

$imgBase = 'uploads/Slemani/vogue/i/i/m/';
$vogueItemData = [
    // [category_key, name_en, name_ar, name_ku, price, img]
    [$catColdApp, 'Fattoush',        'فتوش',          'فتووش',          6000,  'vogue(269).jpg'],
    [$catColdApp, 'Tabbulah',        'تبولة',          'تەبولە',         6000,  'vogue(272).jpg'],
    [$catColdApp, 'Rucola',          'روكولا',         'ڕووکولا',        6000,  'vogue(275).jpg'],
    [$catColdApp, 'Greek Salad',     'سلطة يونانية',  'سەلاتەی یونانی', 7000,  'vogue(278).jpg'],
    [$catColdApp, 'Ceasar Salad',    'سلطة سيزر',     'سەلاتەی سیزر',  8000,  'vogue(281).jpg'],
    [$catColdApp, 'Mutabal',         'متبل',           'مەتبەڵ',        6000,  'vogue(284).jpg'],
    [$catColdApp, 'Hummus',          'حمص',            'حومموس',         5000,  'vogue(287).jpg'],
    [$catColdApp, 'Jajik',           'جاجيك',          'جاجیک',          5000,  'vogue(290).jpg'],
    [$catColdApp, 'Kurdish Salad',   'سلطة كردية',    'سەلاتەی کوردی', 6000,  'vogue(293).jpg'],
    [$catColdApp, 'Meat Salad',      'سلطة لحم',      'سەلاتەی گۆشت', 10000, 'vogue(296).jpg'],
    [$catColdApp, 'Beetroot',        'شمندر',          'چووکەندەر',      5000,  'vogue(299).jpeg'],
    [$catColdApp, 'Beetroot Rucola', 'شمندر روكولا',  'چووکەندەر ڕووکولا', 7000, 'vogue(302).jpeg'],
    [$catColdApp, 'Beetroot Cabbages','شمندر ملفوف',  'چووکەندەر کەلەم', 6000, 'vogue(305).png'],
    [$catHotApp,  'Lamb Shanks',     'كراع الغنم',    'چنگاڵی مەڕ',   20000, 'vogue(320).png'],
    [$catHotApp,  'Vogue Salad',     'سلطة فوغ',      'سەلاتەی ڤۆگ',  8000,  null],
    [$catHotApp,  'Cheese Platter',  'طبق الجبن',     'پلاتی پەنیر',  12000, null],
    [$catHotApp,  'Chicken Finger',  'أصابع الدجاج',  'ئەنگوستی مریشک', 9000, null],
    [$catHotApp,  'Grilled Halummi', 'حلومي مشوي',    'هالومی گریل',   8000,  null],
    [$catHotApp,  'Gamberi Aglio',   'جامبري أليو',   'گامبێری ئاگلیۆ', 15000, null],
    [$catHotApp,  'Hummus with Meat','حمص باللحم',    'حومموس بە گۆشت', 8000, null],
    [$catHotApp,  'Chicken Liver',   'كبدة الدجاج',   'جگەری مریشک',   8000,  null],
    [$catGrills,  'Mixed Grill',     'مشاوي مشكلة',   'گریلی تێکەڵ',  25000, null],
    [$catGrills,  'Chicken Wings',   'أجنحة الدجاج',  'بازنی مریشک',  12000, null],
    [$catGrills,  'Lamb Chops',      'ضلوع الخروف',   'ئینچی مەڕ',    22000, null],
    [$catGrills,  'Beef Steak',      'ستيك لحم بقري', 'ستیکی گاوی',   20000, null],
    [$catPizza,   'Margherita',      'مارغريتا',      'مارگاریتا',    10000, null],
    [$catPizza,   'BBQ Chicken',     'دجاج بي بي كيو','مریشکی BBQ',   12000, null],
    [$catPizza,   'Pepperoni',       'بيبروني',        'پیپەرۆنی',     12000, null],
    [$catPizza,   'Four Cheese',     'أربع جبن',      'چوار جۆر پەنیر', 13000, null],
    [$catPasta,   'Carbonara',       'كاربونارا',     'کاربۆنارا',    10000, null],
    [$catPasta,   'Bolognese',       'بولونيز',        'بۆلۆنێز',      10000, null],
    [$catPasta,   'Arabiata',        'ارابياتا',       'ئارابیاتا',    10000, null],
    [$catSandwich,'Club Sandwich',   'كلوب سندويش',   'کلوب ساندویچ', 8000,  null],
    [$catSandwich,'Vogue Burger',    'برغر فوغ',      'برگەری ڤۆگ',  12000, null],
    [$catSandwich,'Crispy Chicken',  'دجاج مقرمش',    'مریشکی کریسپی', 9000, null],
    [$catSide,    'French Fries',    'بطاطس مقلية',   'فریتس',         4000,  null],
    [$catSide,    'Onion Rings',     'حلقات البصل',   'مەزگەی پیاز',  4000,  null],
    [$catSide,    'Garlic Bread',    'خبز بالثوم',    'نانی سەمووق',  3000,  null],
    [$catHotPlate,'Lamb Rice',       'رز باللحم',     'برنجی گۆشت',  18000, null],
    [$catHotPlate,'Chicken Rice',    'رز بالدجاج',    'برنجی مریشک', 15000, null],
    [$catFish,    'Masgouf',         'مسقوف',          'مەسقووف',      25000, null],
    [$catFish,    'Grilled Salmon',  'سلمون مشوي',    'سالمۆنی گریل', 20000, null],
    [$catSpecial, 'Vogue Special',   'خاص فوغ',       'تایبەتی ڤۆگ',  30000, null],
];

$itemCount = 0;
foreach ($vogueItemData as $i => [$catId, $en, $ar, $ku, $price, $img]) {
    insert($pdo, 'items', [
        'restaurant_id' => $vogueId,
        'category_id'   => $catId,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'price'         => $price,
        'image'         => $img ? $imgBase . $img : null,
        'is_active'     => 1,
        'sort_order'    => $i,
    ]);
    $itemCount++;
}
echo "✓ Vogue items: $itemCount\n";

// ════════════════════════════════════════════════════════════
//  RESTAURANT 2: Almajlees Cafe
// ════════════════════════════════════════════════════════════
$almajleesId = insert($pdo, 'restaurants', [
    'city_id'          => $cityId,
    'name'             => 'Almajlees Cafe',
    'slug'             => 'almajlees',
    'logo'             => 'uploads/Slemani/almajlees/almajlees-0cfe.png',
    'theme_color'      => '#643A1B',
    'body_bg'          => '#643A1B',
    'font'             => 'rabar',
    'default_lang'     => 'ku',
    'has_sections'     => 0,
    'social_facebook'  => 'https://facebook.com/almajleescafe',
    'social_instagram' => 'https://instagram.com/almajleescafe',
    'social_phone'     => '+9647501111222',
    'social_location'  => 'https://maps.google.com/?q=Almajlees+Cafe+Sulaymaniyah',
    'has_splash_video' => 1,
    'splash_video_url' => 'uploads/Slemani/almajlees/th/v/almajlees-5e90.mp4',
    'splash_video_thumb' => 'uploads/Slemani/almajlees/th/v/almajlees-5e90.png',
    'has_ad'           => 0,
    'is_active'        => 1,
]);
echo "✓ Restaurant: Almajlees (id=$almajleesId)\n";

// ── Almajlees Categories ──────────────────────────────────────
$almBase = 'uploads/Slemani/almajlees/c/i/l/';
$almCats = [
    ['Breakfast Set',        'طقم الإفطار',  'ژەمی بەیانیان',  'almajlees-9875.png'],
    ['Breakfast Bread',      'خبز الإفطار',  'نانی بەیانی',    'almajlees-e599.png'],
];
$almCatIds = [];
foreach ($almCats as $i => [$en, $ar, $ku, $icon]) {
    $almCatIds[$en] = insert($pdo, 'categories', [
        'restaurant_id' => $almajleesId,
        'section_id'    => null,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'icon'          => $almBase . $icon,
        'sort_order'    => $i,
        'is_active'     => 1,
    ]);
}
echo "✓ Almajlees categories: " . count($almCatIds) . "\n";

// ── Almajlees Items ───────────────────────────────────────────
$almImgBase = 'uploads/Slemani/almajlees/i/i/m/';
$catBreakSet  = $almCatIds['Breakfast Set'];
$catBreakBread = $almCatIds['Breakfast Bread'];
$almItems = [
    [$catBreakSet,  'Almajlees Breakfast Set', 'طقم فطور المجالس',  'ژەمی بەیانیانی ئەلمەجلیس', 36000, 'almajlees(18).png'],
    [$catBreakSet,  'Two Person Set',           'طقم شخصين',          'ژەمی دوو کەسی',            15500, 'almajlees(9).jpg'],
    [$catBreakSet,  'Four Person Set',          'طقم أربعة أشخاص',    'ژەمی چوار کەسی',           27000, 'almajlees(12).jpg'],
    [$catBreakSet,  'One Person Set',           'طقم شخص واحد',       'ژەمی یەک کەسی',             7000, 'almajlees(6).jpg'],
    [$catBreakSet,  'English Breakfast',        'فطور إنجليزي',        'ژەمی بەیانی ئینگلیزی',     8500,  'almajlees(21).png'],
    [$catBreakBread,'Samoon Bread',             'خبز السمون',          'نانی سەمووق',               1500, null],
    [$catBreakBread,'Toast Bread',              'خبز التوست',          'نانی تووس',                 1000, null],
    [$catBreakBread,'Lavash Bread',             'خبز اللاواش',         'نانی لاڤاش',               1000, null],
];

$almCount = 0;
foreach ($almItems as $i => [$catId, $en, $ar, $ku, $price, $img]) {
    insert($pdo, 'items', [
        'restaurant_id' => $almajleesId,
        'category_id'   => $catId,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'price'         => $price,
        'image'         => $img ? $almImgBase . $img : null,
        'is_active'     => 1,
        'sort_order'    => $i,
    ]);
    $almCount++;
}
echo "✓ Almajlees items: $almCount\n";

// ════════════════════════════════════════════════════════════
//  RESTAURANT 3: C.C. Rest Cafe (from previous session)
// ════════════════════════════════════════════════════════════
$ccId = insert($pdo, 'restaurants', [
    'city_id'          => $cityId,
    'name'             => 'C.C. Rest Cafe',
    'slug'             => 'c.c.rest.cafe',
    'logo'             => 'uploads/Slemani/c.c.rest.cafe/c.c.rest.cafe(1).jpg',
    'theme_color'      => '#910000',
    'body_bg'          => '#141414',
    'font'             => 'Poppins',
    'default_lang'     => 'en',
    'has_sections'     => 1,
    'social_facebook'  => '',
    'social_instagram' => '',
    'social_phone'     => '+9647500000000',
    'social_location'  => '',
    'has_splash_video' => 0,
    'has_ad'           => 0,
    'is_active'        => 1,
]);
echo "✓ Restaurant: C.C. Rest Cafe (id=$ccId)\n";

// ── C.C. Sections ─────────────────────────────────────────────
$secFood     = insert($pdo, 'sections', ['restaurant_id'=>$ccId,'name_en'=>'Food','name_ar'=>'طعام','name_ku'=>'خواردن','sort_order'=>0]);
$secDrinks   = insert($pdo, 'sections', ['restaurant_id'=>$ccId,'name_en'=>'Drinks','name_ar'=>'مشروبات','name_ku'=>'خواردنەوە','sort_order'=>1]);
$secHookah   = insert($pdo, 'sections', ['restaurant_id'=>$ccId,'name_en'=>'Hookah','name_ar'=>'نارجيلة','name_ku'=>'قەلیان','sort_order'=>2]);

echo "✓ C.C. sections: 3 (Food, Drinks, Hookah)\n";
echo "\n✅ Database seeded successfully!\n";
echo "   DB path: $dbPath\n";
echo "   Total restaurants: 3\n\n";
