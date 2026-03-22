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
    ['Cold Appetizers',       'المقبلات الباردة',    'خواردنی سارد',      'vogue-c752.png'],
    ['Hot Appetizers',        'المقبلات الساخنة',    'خواردنی گەرم',       'vogue-ffb3.png'],
    ['Grills',                'المشويات',             'گریل',               'vogue-62bd.png'],
    ['Pizza',                 'البيتزا',              'پیتزا',              'vogue-97d6.png'],
    ['Pasta',                 'المعكرونة',            'پاستا',              'vogue-9497.png'],
    ['Sandwiches and Burger', 'السندويشات والبرغر',   'ساندویچ و برگەر',    'vogue-6d03.png'],
    ['Side Dishes',           'الأطباق الجانبية',    'خواردنی لاپەڕ',      'vogue-c118.png'],
    ['Hot Plate',             'الطبق الساخن',        'پلاتی گەرم',         'vogue-2efc.png'],
    ['Grilled Fish',          'السمك المشوي',        'ماسی گریل',          'vogue-6171.png'],
    ['Special Foods',         'الأطباق الخاصة',     'خواردنی تایبەت',     'vogue-85c9.png'],
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
    // [category_id, name_en, name_ar, name_ku, price, img]
    [$catColdApp, 'Fattoush',         'فتوش',           'فتووش',             6000,  'vogue(269).jpg'],
    [$catColdApp, 'Tabbulah',         'تبولة',           'تەبولە',            6000,  'vogue(272).jpg'],
    [$catColdApp, 'Rucola',           'روكولا',          'ڕووکولا',           6000,  'vogue(275).jpg'],
    [$catColdApp, 'Greek Salad',      'سلطة يونانية',   'سەلاتەی یونانی',    7000,  'vogue(278).jpg'],
    [$catColdApp, 'Ceasar Salad',     'سلطة سيزر',      'سەلاتەی سیزر',     8000,  'vogue(281).jpg'],
    [$catColdApp, 'Mutabal',          'متبل',            'مەتبەڵ',            6000,  'vogue(284).jpg'],
    [$catColdApp, 'Hummus',           'حمص',             'حومموس',            5000,  'vogue(287).jpg'],
    [$catColdApp, 'Jajik',            'جاجيك',           'جاجیک',             5000,  'vogue(290).jpg'],
    [$catColdApp, 'Kurdish Salad',    'سلطة كردية',     'سەلاتەی کوردی',    6000,  'vogue(293).jpg'],
    [$catColdApp, 'Meat Salad',       'سلطة لحم',       'سەلاتەی گۆشت',    10000, 'vogue(296).jpg'],
    [$catColdApp, 'Beetroot',         'شمندر',           'چووکەندەر',         5000,  'vogue(299).jpeg'],
    [$catColdApp, 'Beetroot Rucola',  'شمندر روكولا',   'چووکەندەر ڕووکولا', 7000,  'vogue(302).jpeg'],
    [$catColdApp, 'Beetroot Cabbages','شمندر ملفوف',    'چووکەندەر کەلەم',   6000,  'vogue(305).png'],
    [$catHotApp,  'Lamb Shanks',      'كراع الغنم',     'چنگاڵی مەڕ',       20000, 'vogue(320).png'],
    [$catHotApp,  'Vogue Salad',      'سلطة فوغ',       'سەلاتەی ڤۆگ',      8000,  null],
    [$catHotApp,  'Cheese Platter',   'طبق الجبن',      'پلاتی پەنیر',      12000, null],
    [$catHotApp,  'Chicken Finger',   'أصابع الدجاج',   'ئەنگوستی مریشک',    9000,  null],
    [$catHotApp,  'Grilled Halummi',  'حلومي مشوي',     'هالومی گریل',       8000,  null],
    [$catHotApp,  'Gamberi Aglio',    'جامبري أليو',    'گامبێری ئاگلیۆ',   15000, null],
    [$catHotApp,  'Hummus with Meat', 'حمص باللحم',     'حومموس بە گۆشت',    8000,  null],
    [$catHotApp,  'Chicken Liver',    'كبدة الدجاج',    'جگەری مریشک',       8000,  null],
    [$catGrills,  'Mixed Grill',      'مشاوي مشكلة',    'گریلی تێکەڵ',      25000, null],
    [$catGrills,  'Chicken Wings',    'أجنحة الدجاج',   'بازنی مریشک',      12000, null],
    [$catGrills,  'Lamb Chops',       'ضلوع الخروف',    'ئینچی مەڕ',        22000, null],
    [$catGrills,  'Beef Steak',       'ستيك لحم بقري',  'ستیکی گاوی',       20000, null],
    [$catPizza,   'Margherita',       'مارغريتا',       'مارگاریتا',        10000, null],
    [$catPizza,   'BBQ Chicken',      'دجاج بي بي كيو', 'مریشکی BBQ',       12000, null],
    [$catPizza,   'Pepperoni',        'بيبروني',         'پیپەرۆنی',         12000, null],
    [$catPizza,   'Four Cheese',      'أربع جبن',       'چوار جۆر پەنیر',   13000, null],
    [$catPasta,   'Carbonara',        'كاربونارا',      'کاربۆنارا',        10000, null],
    [$catPasta,   'Bolognese',        'بولونيز',         'بۆلۆنێز',          10000, null],
    [$catPasta,   'Arabiata',         'ارابياتا',        'ئارابیاتا',        10000, null],
    [$catSandwich,'Club Sandwich',    'كلوب سندويش',    'کلوب ساندویچ',      8000,  null],
    [$catSandwich,'Vogue Burger',     'برغر فوغ',       'برگەری ڤۆگ',       12000, null],
    [$catSandwich,'Crispy Chicken',   'دجاج مقرمش',     'مریشکی کریسپی',     9000,  null],
    [$catSide,    'French Fries',     'بطاطس مقلية',    'فریتس',             4000,  null],
    [$catSide,    'Onion Rings',      'حلقات البصل',    'مەزگەی پیاز',       4000,  null],
    [$catSide,    'Garlic Bread',     'خبز بالثوم',     'نانی سەمووق',       3000,  null],
    [$catHotPlate,'Lamb Rice',        'رز باللحم',      'برنجی گۆشت',       18000, null],
    [$catHotPlate,'Chicken Rice',     'رز بالدجاج',     'برنجی مریشک',      15000, null],
    [$catFish,    'Masgouf',          'مسقوف',           'مەسقووف',          25000, null],
    [$catFish,    'Grilled Salmon',   'سلمون مشوي',     'سالمۆنی گریل',     20000, null],
    [$catSpecial, 'Vogue Special',    'خاص فوغ',        'تایبەتی ڤۆگ',      30000, null],
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
    'has_splash_video' => 0,
    'splash_video_url' => null,
    'splash_video_thumb' => null,
    'has_ad'           => 0,
    'is_active'        => 1,
]);
echo "✓ Restaurant: Almajlees (id=$almajleesId)\n";

// ── Almajlees Categories ──────────────────────────────────────
$almBase = 'uploads/Slemani/almajlees/c/i/l/';
$almCats = [
    ['Breakfast Set',   'طقم الإفطار', 'ژەمی بەیانیان', 'almajlees-9875.png'],
    ['Breakfast Bread', 'خبز الإفطار', 'نانی بەیانی',   'almajlees-e599.png'],
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
$almImgBase    = 'uploads/Slemani/almajlees/i/i/m/';
$catBreakSet   = $almCatIds['Breakfast Set'];
$catBreakBread = $almCatIds['Breakfast Bread'];
$almItems = [
    [$catBreakSet,   'Almajlees Breakfast Set', 'طقم فطور المجالس', 'ژەمی بەیانیانی ئەلمەجلیس', 36000, 'almajlees(18).png'],
    [$catBreakSet,   'Two Person Set',           'طقم شخصين',        'ژەمی دوو کەسی',            15500, 'almajlees(9).jpg'],
    [$catBreakSet,   'Four Person Set',          'طقم أربعة أشخاص',  'ژەمی چوار کەسی',           27000, 'almajlees(12).jpg'],
    [$catBreakSet,   'One Person Set',           'طقم شخص واحد',     'ژەمی یەک کەسی',             7000, 'almajlees(6).jpg'],
    [$catBreakSet,   'English Breakfast',        'فطور إنجليزي',     'ژەمی بەیانی ئینگلیزی',      8500, 'almajlees(21).png'],
    [$catBreakBread, 'Samoon Bread',             'خبز السمون',       'نانی سەمووق',               1500, null],
    [$catBreakBread, 'Toast Bread',              'خبز التوست',       'نانی تووس',                 1000, null],
    [$catBreakBread, 'Lavash Bread',             'خبز اللاواش',      'نانی لاڤاش',               1000, null],
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
//  RESTAURANT 3: C.C. Rest Cafe
// ════════════════════════════════════════════════════════════
$ccId = insert($pdo, 'restaurants', [
    'city_id'          => $cityId,
    'name'             => 'C.C. Rest Cafe',
    'slug'             => 'c.c.rest.cafe',
    'logo'             => 'uploads/Slemani/c.c.rest.cafe/c.c.rest.cafe(1).jpg',
    'theme_color'      => '#910000',
    'body_bg'          => '#141414',
    'font'             => 'rabar',
    'default_lang'     => 'ku',
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
$secFood   = insert($pdo, 'sections', ['restaurant_id'=>$ccId,'name_en'=>'Food',   'name_ar'=>'طعام',     'name_ku'=>'خواردن',    'sort_order'=>0,'is_active'=>1]);
$secDrinks = insert($pdo, 'sections', ['restaurant_id'=>$ccId,'name_en'=>'Drinks', 'name_ar'=>'مشروبات',  'name_ku'=>'خواردنەوە', 'sort_order'=>1,'is_active'=>1]);
$secHookah = insert($pdo, 'sections', ['restaurant_id'=>$ccId,'name_en'=>'Hookah', 'name_ar'=>'نارجيلة',  'name_ku'=>'قەلیان',   'sort_order'=>2,'is_active'=>1]);
echo "✓ C.C. sections: 3\n";

// ── C.C. Categories ───────────────────────────────────────────
$ccBase = 'uploads/Slemani/c.c.rest.cafe/c/i/l/';
$ccImgBase = 'uploads/Slemani/c.c.rest.cafe/i/i/m/';

// [section_id, name_en, name_ar, name_ku, icon_file, sort]
$ccCatDefs = [
    [$secFood,   'Breakfast',   'الإفطار',        'نانی بەیانی',    'c.c.rest.cafe-6299.png', 0],
    [$secFood,   'Soup',        'الشوربة',         'شۆربا',          'c.c.rest.cafe-c900.png', 1],
    [$secFood,   'Salads',      'السلطات',         'زەلاتە',         'c.c.rest.cafe-15d1.png', 2],
    [$secFood,   'Pasta',       'المعكرونة',       'پاستا',          'c.c.rest.cafe-7ba9.png', 3],
    [$secFood,   'Pizza',       'البيتزا',         'پیتزا',          'c.c.rest.cafe-fb4f.png', 4],
    [$secFood,   'Main Dishes', 'الأطباق الرئيسية','ژەمی سەرەکی',   'c.c.rest.cafe-b0fe.png', 5],
    [$secFood,   'Kids Food',   'طعام الأطفال',    'خواردنی منداڵ',  'c.c.rest.cafe-248f.png', 6],
    [$secFood,   'Fast Food',   'الوجبات السريعة', 'خواردنی خێرا',   'c.c.rest.cafe-453d.png', 7],
    [$secFood,   'Specials',    'المميزات',         'خواردنی تایبەت', 'c.c.rest.cafe-1da3.png', 8],
    [$secFood,   'Diet Food',   'الطعام الصحي',    'خۆراکی دایت',   'c.c.rest.cafe-90ca.png', 9],
    [$secFood,   'Extra Sauce', 'الصوص الإضافي',   'سۆسی زیادە',    'c.c.rest.cafe-a09a.png', 10],
    [$secFood,   'Desserts',    'الحلويات',         'شیرینی',         'c.c.rest.cafe-c415.png', 11],
];
$ccCatIds = [];
foreach ($ccCatDefs as [$secId, $en, $ar, $ku, $icon, $sort]) {
    $ccCatIds[$en] = insert($pdo, 'categories', [
        'restaurant_id' => $ccId,
        'section_id'    => $secId,
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'icon'          => $ccBase . $icon,
        'sort_order'    => $sort,
        'is_active'     => 1,
    ]);
}
echo "✓ C.C. categories: " . count($ccCatIds) . "\n";

// ── C.C. Items ────────────────────────────────────────────────
// [cat_en, name_ku, name_ar, name_en, price, img_file]
// price=0 means "price by selection"
$ccItems = [
    // نانی بەیانی  Breakfast
    ['Breakfast', 'ژه‌می پارشێو٢ نه‌فه‌ر',       'طقم إفطار شخصين',          'Breakfast Set 2 Person',  15000, 'c.c.rest.cafe-7aa0.png'],
    ['Breakfast', 'ژه‌می پارشێو تایبه‌ت',         'طقم إفطار مميز',            'Special Breakfast Set',   20000, 'c.c.rest.cafe-0a4a.png'],
    // شۆربا  Soup
    ['Soup', 'شۆربای نیسک',              'شوربة العدس',              'Lentil Soup',              5000, 'c.c.rest.cafe(6).jpg'],
    ['Soup', 'شۆربای مریشک و قارچک',     'شوربة الدجاج والفطر',      'Chicken & Mushroom Soup',  5000, 'c.c.rest.cafe(9).jpg'],
    ['Soup', 'شۆربای سەوزەوات',          'شوربة الخضار',             'Vegetable Soup',           4000, 'c.c.rest.cafe(18).jpg'],
    ['Soup', 'شۆربای برۆکلی',            'شوربة البروكلي',           'Broccoli Soup',            5000, 'c.c.rest.cafe(12).jpg'],
    // زەلاتە  Salads
    ['Salads', 'زەڵاتەی تێکەڵ',          'سلطة مشكلة',               'Mixed Salad',              6000, 'c.c.rest.cafe(23).jpg'],
    ['Salads', 'زەڵاتەی جەرجیر',         'سلطة الجرجير',             'Rocket Salad',             6000, 'c.c.rest.cafe(26).jpg'],
    ['Salads', 'زەڵاتەی سیزەر',          'سلطة سيزر',                'Caesar Salad',             8000, 'c.c.rest.cafe(29).jpg'],
    ['Salads', 'زەڵاتەی فەتوش',          'سلطة الفتوش',              'Fattoush Salad',           7000, 'c.c.rest.cafe(32).jpg'],
    ['Salads', 'زەڵاتەی ماسی',           'سلطة السمك',               'Fish Salad',               7000, 'c.c.rest.cafe(35).jpg'],
    ['Salads', 'زەڵاتەی یۆنانی',         'السلطة اليونانية',         'Greek Salad',              9000, 'c.c.rest.cafe(38).png'],
    ['Salads', 'زەڵاتەی سی.سی',          'سلطة سي سي',               'C.C. Salad',              10000, 'c.c.rest.cafe(41).jpg'],
    ['Salads', 'زەڵاتەی ڕۆبیان',         'سلطة الروبيان',            'Shrimp Salad',            13000, 'c.c.rest.cafe(44).jpg'],
    ['Salads', 'حومس',                   'حمص',                      'Hummus',                   5000, 'c.c.rest.cafe(47).jpg'],
    // پاستا  Pasta
    ['Pasta', 'سپاگێتی ناپۆلی',          'سباغيتي نابولي',           'Spaghetti Napoli',         7000, 'c.c.rest.cafe(152).jpg'],
    ['Pasta', 'پێنێ پۆلۆ',               'بيني بولو',                'Penne Polo',              10000, 'c.c.rest.cafe(155).jpg'],
    ['Pasta', 'پێنێ ئارابیاتا',           'بيني أرابياتا',            'Penne Arrabbiata',         8000, 'c.c.rest.cafe(158).jpg'],
    ['Pasta', 'سپاگێتی بۆلۆنیز',         'سباغيتي بولونيز',          'Spaghetti Bolognese',     11000, 'c.c.rest.cafe(161).jpg'],
    ['Pasta', 'پێنێ ئەلفرێدۆ',           'بيني ألفريدو',             'Penne Alfredo',           11000, 'c.c.rest.cafe(173).jpg'],
    ['Pasta', 'سپاگێتی بە ڕۆبیان',       'سباغيتي بالروبيان',        'Spaghetti with Shrimp',  14000, 'c.c.rest.cafe(176).jpg'],
    ['Pasta', 'فیتوچین ئەلفرێدۆ',        'فيتوتشيني ألفريدو',        'Fettuccine Alfredo',      11000, 'c.c.rest.cafe(191).jpg'],
    ['Pasta', 'پێنێ بۆلۆنیز',            'بيني بولونيز',             'Penne Bolognese',          8000, 'c.c.rest.cafe(146).jpg'],
    ['Pasta', 'لازانیا',                 'لازانيا',                  'Lasagna',                 10000, 'c.c.rest.cafe(149).jpg'],
    // پیتزا  Pizza
    ['Pizza', 'پیتزای مارگریتا',          'بيتزا مارغريتا',           'Margherita Pizza',         7000, 'c.c.rest.cafe(90).jpg'],
    ['Pizza', 'پیتزای سەلامی',           'بيتزا السلامي',            'Salami Pizza',             8000, 'c.c.rest.cafe(93).jpg'],
    ['Pizza', 'پیتزای هاوایی',           'بيتزا هاواي',              'Hawaiian Pizza',           8000, 'c.c.rest.cafe(96).jpg'],
    ['Pizza', 'پیتزای سەوزەوات',         'بيتزا الخضار',             'Vegetable Pizza',          8000, 'c.c.rest.cafe(99).jpg'],
    ['Pizza', 'چوار وەرزە',              'بيتزا أربعة فصول',         'Four Seasons Pizza',       9000, 'c.c.rest.cafe(105).png'],
    ['Pizza', 'پیتزای پۆلۆ',             'بيتزا بولو',               'Polo Pizza',               9000, 'c.c.rest.cafe(114).jpg'],
    ['Pizza', 'پیتزای سی.سی',            'بيتزا سي سي',              'C.C. Pizza',              12000, 'c.c.rest.cafe(129).png'],
    ['Pizza', 'پیتزای داخراو',           'بيتزا مغلقة',              'Closed Pizza',            10000, 'c.c.rest.cafe(132).jpg'],
    ['Pizza', 'نان و پەتاتە و پەنیر',    'خبز وبطاطس وجبن',          'Bread Potato & Cheese',    7000, 'c.c.rest.cafe(87).jpg'],
    ['Pizza', 'پیتزای باڕبیکیو',         'بيتزا باربيكيو',           'BBQ Pizza',                9000, 'c.c.rest.cafe(138).jpeg'],
    ['Pizza', 'پیتزای مەکسیکی',          'بيتزا مكسيكية',            'Mexican Pizza',             0,   'c.c.rest.cafe(141).png'],
    ['Pizza', 'پیتزای پیپەرۆنی',         'بيتزا بيبيروني',           'Pepperoni Pizza',         10000, 'c.c.rest.cafe(135).jpg'],
    // ژەمی سەرەکی  Main Dishes
    ['Main Dishes', 'ستیکی مریشک',       'ستيك الدجاج',              'Chicken Steak',           15000, 'c.c.rest.cafe(436).jpg'],
    ['Main Dishes', 'ستێکی گۆشت',        'ستيك اللحم',               'Beef Steak',              20000, 'c.c.rest.cafe(439).png'],
    ['Main Dishes', 'ڕۆبیان بە سیر',     'روبيان بالثوم',            'Garlic Shrimp',           22000, 'c.c.rest.cafe(451).jpeg'],
    ['Main Dishes', 'کنتاکی ڕۆبیان',     'روبيان كنتاكي',            'Kentucky Shrimp',         24000, 'c.c.rest.cafe(442).jpg'],
    ['Main Dishes', 'ماسی کارب',         'سمك الكارب',               'Carp Fish',               15000, 'c.c.rest.cafe(445).jpg'],
    ['Main Dishes', 'گۆشت بە کاری',      'لحم بالكاري',              'Beef Curry',              16000, 'c.c.rest.cafe(427).jpg'],
    ['Main Dishes', 'پرزۆڵەی بەرخ',      'ضلع الخروف',               'Lamb Chops',              21000, 'c.c.rest.cafe(430).jpg'],
    ['Main Dishes', 'مریشک بە کاری',     'دجاج بالكاري',             'Chicken Curry',           14000, 'c.c.rest.cafe(418).png'],
    ['Main Dishes', 'فاهیتای گۆشت',      'فاهيتا اللحم',             'Beef Fajita',             20000, 'c.c.rest.cafe(424).jpg'],
    ['Main Dishes', 'فاهیتای مریشک',     'فاهيتا الدجاج',            'Chicken Fajita',          18000, 'c.c.rest.cafe(421).jpg'],
    ['Main Dishes', 'ساجی مریشک',        'ساج الدجاج',               'Chicken Saj',             18000, 'c.c.rest.cafe(448).jpeg'],
    ['Main Dishes', 'کەباب هیندی',       'كباب هندي',                'Indian Kebab',            13000, 'c.c.rest.cafe(457).png'],
    ['Main Dishes', 'فەخارەی گۆشت',      'فخارة اللحم',              'Clay Pot Beef',           23000, 'c.c.rest.cafe(460).jpeg'],
    ['Main Dishes', 'ستراگانۆفی مریشک',  'ستروغانوف الدجاج',         'Chicken Stroganoff',      20000, 'c.c.rest.cafe(469).png'],
    ['Main Dishes', 'فەخارەی مریشک',     'فخارة الدجاج',             'Clay Pot Chicken',        17000, 'c.c.rest.cafe(463).webp'],
    ['Main Dishes', 'ستراگانۆفی گۆشت',   'ستروغانوف اللحم',          'Beef Stroganoff',         25000, 'c.c.rest.cafe(454).png'],
    ['Main Dishes', 'مریشک بە هەنگوین',  'دجاج بالعسل',              'Honey Chicken',           10000, 'c.c.rest.cafe(466).webp'],
    ['Main Dishes', 'ستیر فرای تێکەڵاو', 'ستير فراي مشكل',           'Mixed Stir Fry',          20000, 'c.c.rest.cafe(433).jpeg'],
    // خواردنی منداڵ  Kids Food
    ['Kids Food', 'پیتزای بێلا',         'بيتزا بيلا',               'Bella Pizza',              5000, 'c.c.rest.cafe(202).jpg'],
    ['Kids Food', 'پیتزای کارینا',       'بيتزا كارينا',             'Karina Pizza',             6000, 'c.c.rest.cafe(205).jpg'],
    ['Kids Food', 'ماش پۆتەیتۆ',        'مهروس البطاطس',            'Mashed Potato',            5000, 'c.c.rest.cafe(223).jpeg'],
    // خواردنی خێرا  Fast Food
    ['Fast Food', 'قارچکی کریسپی',       'فطر مقرمش',                'Crispy Mushroom',          6000, 'c.c.rest.cafe(253).jpg'],
    ['Fast Food', 'ویجز',                'ويدجز',                    'Wedges',                   5000, 'c.c.rest.cafe(259).jpg'],
    ['Fast Food', 'ویجز بە پەنیر',       'ويدجز بالجبن',             'Wedges with Cheese',       6000, 'c.c.rest.cafe(262).jpg'],
    ['Fast Food', 'فینگر',               'فنجر',                     'Finger',                   5000, 'c.c.rest.cafe(265).jpg'],
    ['Fast Food', 'فینگەر بە پەنیر',     'فنجر بالجبن',              'Finger with Cheese',       6000, 'c.c.rest.cafe(268).jpg'],
    ['Fast Food', 'کنتاکی مریشک',        'كنتاكي دجاج',              'Kentucky Chicken',         6500, 'c.c.rest.cafe(274).jpg'],
    ['Fast Food', 'کنتاکی بە پەنیر',     'كنتاكي بالجبن',            'Kentucky with Cheese',     6000, 'c.c.rest.cafe(286).jpg'],
    ['Fast Food', 'ناگێت',               'ناجيت',                    'Nuggets',                  6000, 'c.c.rest.cafe(289).jpg'],
    ['Fast Food', 'فیلادلفیا',           'فيلادلفيا',                'Philadelphia',             7000, 'c.c.rest.cafe(292).jpg'],
    ['Fast Food', 'ساندویچی زینگەر',     'ساندوتش زينجر',            'Zinger Sandwich',          6000, 'c.c.rest.cafe(298).jpg'],
    ['Fast Food', 'شاورمەی ساج',         'شاورما الساج',             'Saj Shawarma',              0,   'c.c.rest.cafe(310).jpg'],
    ['Fast Food', 'هەمبەرگر',            'همبرجر',                   'Hamburger',                7000, 'c.c.rest.cafe(313).jpg'],
    ['Fast Food', 'هەمبەرگر بە پەنیر',   'همبرجر بالجبن',            'Cheeseburger',             8000, 'c.c.rest.cafe(316).jpg'],
    ['Fast Food', 'هەمبەرگر بە قارچک',   'همبرجر بالفطر',            'Mushroom Burger',           0,   'c.c.rest.cafe(319).jpg'],
    ['Fast Food', 'هەمبەرگر تایبەت',     'همبرجر مميز',              'Special Burger',            0,   'c.c.rest.cafe(322).jpg'],
    ['Fast Food', 'هەمبەرگر ستیک',       'همبرجر ستيك',              'Steak Burger',              0,   'c.c.rest.cafe(325).jpg'],
    ['Fast Food', 'هەمبەرگر بە مریشک',   'همبرجر الدجاج',            'Chicken Burger',            0,   'c.c.rest.cafe(328).jpg'],
    ['Fast Food', 'چیکەن ئەنوراپ',       'رول الدجاج',               'Chicken Wrap',              0,   'c.c.rest.cafe(331).jpg'],
    ['Fast Food', 'کاسادێلا',            'كيساديلا',                 'Quesadilla',               7000, 'c.c.rest.cafe(337).png'],
    ['Fast Food', 'کرواسۆنی سیزەری مریشک','كروسان سيزر الدجاج',     'Chicken Caesar Croissant', 7000, 'c.c.rest.cafe(340).jpeg'],
    // خواردنی تایبەت  Specials
    ['Specials', 'ڕانی بەرخ (بۆ ٣ کەس)', 'ران الخروف (3 أشخاص)',    'Lamb Leg (3 persons)',    70000, 'c.c.rest.cafe(345).jpg'],
    ['Specials', 'ماینچەی بەرخ',          'قدر الخروف',               'Lamb Stew',               22000, 'c.c.rest.cafe(351).jpg'],
    ['Specials', 'گەردەمل (بۆ ٢ کەس)',    'قوزي (شخصين)',             'Quzi (2 persons)',        40000, 'c.c.rest.cafe(357).jpg'],
    ['Specials', 'پڕزۆڵەی پیشاو (١ کەس)','ضلع بيشاور (شخص)',        'Peshawar Ribs (1 person)',22000, 'c.c.rest.cafe(360).jpg'],
    ['Specials', '١ نەفەر قۆزی',          'قوزي شخص واحد',            '1 Person Quzi',           15000, 'c.c.rest.cafe(381).jpeg'],
    ['Specials', 'قاز',                   'أوزة',                     'Goose',                  140000, 'c.c.rest.cafe(387).jpeg'],
    ['Specials', 'قەلی کامل',             'خروف كامل',                'Whole Lamb',             200000, 'c.c.rest.cafe(384).jpeg'],
    // خۆراکی دایت  Diet Food
    ['Diet Food', 'ستیکی گۆشتی دایت',    'ستيك اللحم دايت',          'Diet Beef Steak',         15000, 'c.c.rest.cafe(239).jpg'],
    ['Diet Food', 'ستیکی مریشک دایت',    'ستيك الدجاج دايت',         'Diet Chicken Steak',      12000, 'c.c.rest.cafe(242).jpg'],
    ['Diet Food', 'خواردنی سەوزەوات',    'الخضار',                   'Vegetables',               8000, 'c.c.rest.cafe(245).jpg'],
    // سۆسی زیادە  Extra Sauce
    ['Extra Sauce', 'سۆسی لیمۆن',        'صوص الليمون',              'Lemon Sauce',              5000, 'c.c.rest.cafe(392).jpg'],
    ['Extra Sauce', 'سۆسی قارچک',        'صوص الفطر',                'Mushroom Sauce',           5000, 'c.c.rest.cafe(395).jpg'],
    ['Extra Sauce', 'سۆسی ڕۆزماری',      'صوص الروزماري',            'Rosemary Sauce',           5000, 'c.c.rest.cafe(398).jpg'],
    ['Extra Sauce', 'سۆسی دیمیگڵاس',     'صوص ديمي غلاس',            'Demi-Glace Sauce',         5000, 'c.c.rest.cafe(401).jpg'],
    ['Extra Sauce', 'سۆسی بیبەری ڕەش',   'صوص الفلفل الأسود',        'Black Pepper Sauce',       5000, 'c.c.rest.cafe(404).jpg'],
    ['Extra Sauce', 'سۆسی بیبەری توون',  'صوص الفلفل الحار',         'Hot Pepper Sauce',         5000, 'c.c.rest.cafe(407).jpg'],
    ['Extra Sauce', 'سی.سی سۆس',         'صوص سي سي',                'C.C. Sauce',               5000, 'c.c.rest.cafe(410).jpg'],
    // شیرینی  Desserts
    ['Desserts', 'سویت ڕۆڵ',             'سويت رول',                 'Sweet Roll',               5000, 'c.c.rest.cafe(64).jpg'],
    ['Desserts', 'کونافە بە فستق',        'كنافة بالفستق',            'Kunafa with Pistachio',    7000, 'c.c.rest.cafe(67).jpg'],
    ['Desserts', 'شیرینی سوڵتان پاشا',   'حلوى سلطان باشا',          'Sultan Pasha Dessert',     6000, 'c.c.rest.cafe(70).jpg'],
    ['Desserts', 'تیرامیسو',              'تيراميسو',                 'Tiramisu',                 5000, 'c.c.rest.cafe(73).jpg'],
    ['Desserts', 'تیرالیچا',             'تيراليتشا',                'Tiralitcha',               4000, 'c.c.rest.cafe(76).jpeg'],
    ['Desserts', 'باقلاوە بە شیر',        'بقلاوة بالحليب',           'Baklava with Milk',        5000, 'c.c.rest.cafe(79).jpeg'],
    ['Desserts', 'فۆندانت',              'فوندان',                   'Fondant',                  6000, 'c.c.rest.cafe(82).png'],
    ['Desserts', 'چوکلێت کێک',           'كيك الشوكولاتة',           'Chocolate Cake',           5000, 'c.c.rest.cafe-f91f.jpeg'],
    ['Desserts', 'کرواسۆنی پەنیر',       'كروسان الجبن',             'Cheese Croissant',         3500, 'c.c.rest.cafe-ab22.jpeg'],
    ['Desserts', 'کرواسۆنی چۆکلێت',     'كروسان الشوكولاتة',        'Chocolate Croissant',      4000, 'c.c.rest.cafe-e620.jpeg'],
];

$ccCount = 0;
foreach ($ccItems as $i => [$catEn, $ku, $ar, $en, $price, $img]) {
    insert($pdo, 'items', [
        'restaurant_id' => $ccId,
        'category_id'   => $ccCatIds[$catEn],
        'name_en'       => $en,
        'name_ar'       => $ar,
        'name_ku'       => $ku,
        'price'         => $price,
        'image'         => $ccImgBase . $img,
        'is_active'     => 1,
        'sort_order'    => $i,
    ]);
    $ccCount++;
}
echo "✓ C.C. items: $ccCount\n";

echo "\n✅ Database seeded successfully!\n";
echo "   DB path: $dbPath\n";
echo "   Total restaurants: 3\n\n";
