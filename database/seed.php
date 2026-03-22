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
    'logo'          => null,
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
$vogueCats = [
    ['Cold Appetizers',       'المقبلات الباردة',    'خواردنی سارد'],
    ['Hot Appetizers',        'المقبلات الساخنة',    'خواردنی گەرم'],
    ['Grills',                'المشويات',             'گریل'],
    ['Pizza',                 'البيتزا',              'پیتزا'],
    ['Pasta',                 'المعكرونة',            'پاستا'],
    ['Sandwiches and Burger', 'السندويشات والبرغر',   'ساندویچ و برگەر'],
    ['Side Dishes',           'الأطباق الجانبية',    'خواردنی لاپەڕ'],
    ['Hot Plate',             'الطبق الساخن',        'پلاتی گەرم'],
    ['Grilled Fish',          'السمك المشوي',        'ماسی گریل'],
    ['Special Foods',         'الأطباق الخاصة',     'خواردنی تایبەت'],
];
$vogueCatIds = [];
foreach ($vogueCats as $i => [$en, $ar, $ku]) {
    $vogueCatIds[$en] = insert($pdo, 'categories', [
        'restaurant_id' => $vogueId,
        'section_id'    => null,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'icon'          => null,
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

$vogueItemData = [
    // [category_id, name_en, name_ar, name_ku, price]
    [$catColdApp, 'Fattoush',         'فتوش',           'فتووش',             6000],
    [$catColdApp, 'Tabbulah',         'تبولة',           'تەبولە',            6000],
    [$catColdApp, 'Rucola',           'روكولا',          'ڕووکولا',           6000],
    [$catColdApp, 'Greek Salad',      'سلطة يونانية',   'سەلاتەی یونانی',    7000],
    [$catColdApp, 'Ceasar Salad',     'سلطة سيزر',      'سەلاتەی سیزر',     8000],
    [$catColdApp, 'Mutabal',          'متبل',            'مەتبەڵ',            6000],
    [$catColdApp, 'Hummus',           'حمص',             'حومموس',            5000],
    [$catColdApp, 'Jajik',            'جاجيك',           'جاجیک',             5000],
    [$catColdApp, 'Kurdish Salad',    'سلطة كردية',     'سەلاتەی کوردی',    6000],
    [$catColdApp, 'Meat Salad',       'سلطة لحم',       'سەلاتەی گۆشت',    10000],
    [$catColdApp, 'Beetroot',         'شمندر',           'چووکەندەر',         5000],
    [$catColdApp, 'Beetroot Rucola',  'شمندر روكولا',   'چووکەندەر ڕووکولا', 7000],
    [$catColdApp, 'Beetroot Cabbages','شمندر ملفوف',    'چووکەندەر کەلەم',   6000],
    [$catHotApp,  'Lamb Shanks',      'كراع الغنم',     'چنگاڵی مەڕ',       20000],
    [$catHotApp,  'Vogue Salad',      'سلطة فوغ',       'سەلاتەی ڤۆگ',      8000],
    [$catHotApp,  'Cheese Platter',   'طبق الجبن',      'پلاتی پەنیر',      12000],
    [$catHotApp,  'Chicken Finger',   'أصابع الدجاج',   'ئەنگوستی مریشک',    9000],
    [$catHotApp,  'Grilled Halummi',  'حلومي مشوي',     'هالومی گریل',       8000],
    [$catHotApp,  'Gamberi Aglio',    'جامبري أليو',    'گامبێری ئاگلیۆ',   15000],
    [$catHotApp,  'Hummus with Meat', 'حمص باللحم',     'حومموس بە گۆشت',    8000],
    [$catHotApp,  'Chicken Liver',    'كبدة الدجاج',    'جگەری مریشک',       8000],
    [$catGrills,  'Mixed Grill',      'مشاوي مشكلة',    'گریلی تێکەڵ',      25000],
    [$catGrills,  'Chicken Wings',    'أجنحة الدجاج',   'بازنی مریشک',      12000],
    [$catGrills,  'Lamb Chops',       'ضلوع الخروف',    'ئینچی مەڕ',        22000],
    [$catGrills,  'Beef Steak',       'ستيك لحم بقري',  'ستیکی گاوی',       20000],
    [$catPizza,   'Margherita',       'مارغريتا',       'مارگاریتا',        10000],
    [$catPizza,   'BBQ Chicken',      'دجاج بي بي كيو', 'مریشکی BBQ',       12000],
    [$catPizza,   'Pepperoni',        'بيبروني',         'پیپەرۆنی',         12000],
    [$catPizza,   'Four Cheese',      'أربع جبن',       'چوار جۆر پەنیر',   13000],
    [$catPasta,   'Carbonara',        'كاربونارا',      'کاربۆنارا',        10000],
    [$catPasta,   'Bolognese',        'بولونيز',         'بۆلۆنێز',          10000],
    [$catPasta,   'Arabiata',         'ارابياتا',        'ئارابیاتا',        10000],
    [$catSandwich,'Club Sandwich',    'كلوب سندويش',    'کلوب ساندویچ',      8000],
    [$catSandwich,'Vogue Burger',     'برغر فوغ',       'برگەری ڤۆگ',       12000],
    [$catSandwich,'Crispy Chicken',   'دجاج مقرمش',     'مریشکی کریسپی',     9000],
    [$catSide,    'French Fries',     'بطاطس مقلية',    'فریتس',             4000],
    [$catSide,    'Onion Rings',      'حلقات البصل',    'مەزگەی پیاز',       4000],
    [$catSide,    'Garlic Bread',     'خبز بالثوم',     'نانی سەمووق',       3000],
    [$catHotPlate,'Lamb Rice',        'رز باللحم',      'برنجی گۆشت',       18000],
    [$catHotPlate,'Chicken Rice',     'رز بالدجاج',     'برنجی مریشک',      15000],
    [$catFish,    'Masgouf',          'مسقوف',           'مەسقووف',          25000],
    [$catFish,    'Grilled Salmon',   'سلمون مشوي',     'سالمۆنی گریل',     20000],
    [$catSpecial, 'Vogue Special',    'خاص فوغ',        'تایبەتی ڤۆگ',      30000],
];

$itemCount = 0;
foreach ($vogueItemData as $i => [$catId, $en, $ar, $ku, $price]) {
    insert($pdo, 'items', [
        'restaurant_id' => $vogueId,
        'category_id'   => $catId,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'price'         => $price,
        'image'         => null,
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
    'logo'             => null,
    'theme_color'      => '#643A1B',
    'body_bg'          => '#643A1B',
    'font'             => 'rabar',
    'default_lang'     => 'ku',
    'has_sections'     => 0,
    'social_facebook'  => 'https://facebook.com/almajleescafe',
    'social_instagram' => 'https://instagram.com/almajleescafe',
    'social_phone'     => '+9647501111222',
    'social_location'  => 'https://maps.google.com/?q=Almajlees+Cafe+Sulaymaniyah',
    'has_splash_video' => 0,
    'splash_video_url' => null,
    'splash_video_thumb' => null,
    'has_ad'           => 0,
    'is_active'        => 1,
]);
echo "✓ Restaurant: Almajlees (id=$almajleesId)\n";

// ── Almajlees Categories ──────────────────────────────────────
$almCats = [
    ['Breakfast Set',   'طقم الإفطار', 'ژەمی بەیانیان'],
    ['Breakfast Bread', 'خبز الإفطار', 'نانی بەیانی'],
];
$almCatIds = [];
foreach ($almCats as $i => [$en, $ar, $ku]) {
    $almCatIds[$en] = insert($pdo, 'categories', [
        'restaurant_id' => $almajleesId,
        'section_id'    => null,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'icon'          => null,
        'sort_order'    => $i,
        'is_active'     => 1,
    ]);
}
echo "✓ Almajlees categories: " . count($almCatIds) . "\n";

// ── Almajlees Items ───────────────────────────────────────────
$catBreakSet   = $almCatIds['Breakfast Set'];
$catBreakBread = $almCatIds['Breakfast Bread'];
$almItems = [
    [$catBreakSet,   'Almajlees Breakfast Set', 'طقم فطور المجالس', 'ژەمی بەیانیانی ئەلمەجلیس', 36000],
    [$catBreakSet,   'Two Person Set',           'طقم شخصين',        'ژەمی دوو کەسی',            15500],
    [$catBreakSet,   'Four Person Set',          'طقم أربعة أشخاص',  'ژەمی چوار کەسی',           27000],
    [$catBreakSet,   'One Person Set',           'طقم شخص واحد',     'ژەمی یەک کەسی',             7000],
    [$catBreakSet,   'English Breakfast',        'فطور إنجليزي',     'ژەمی بەیانی ئینگلیزی',      8500],
    [$catBreakBread, 'Samoon Bread',             'خبز السمون',       'نانی سەمووق',               1500],
    [$catBreakBread, 'Toast Bread',              'خبز التوست',       'نانی تووس',                 1000],
    [$catBreakBread, 'Lavash Bread',             'خبز اللاواش',      'نانی لاڤاش',               1000],
];

$almCount = 0;
foreach ($almItems as $i => [$catId, $en, $ar, $ku, $price]) {
    insert($pdo, 'items', [
        'restaurant_id' => $almajleesId,
        'category_id'   => $catId,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'price'         => $price,
        'image'         => null,
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
    'logo'             => null,
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
