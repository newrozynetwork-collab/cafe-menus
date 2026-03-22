<?php
/**
 * TiranaMenu Clone — Main Router
 * Handles all frontend + API routes.
 * Admin routes are handled inside /admin/
 */

define('ROOT',      __DIR__);
define('BASE_URL',  'http://localhost:8000');
define('BASE_PATH', '/');

require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';

// ── Parse route ──────────────────────────────────────────────
$path    = request_path();                     // e.g. /vogue/menu
$segments = array_values(array_filter(explode('/', $path)));
// segments: [] | ['vogue'] | ['vogue','menu'] | ['api','...']

// ── API routes ───────────────────────────────────────────────
if (($segments[0] ?? '') === 'api') {
    require_once ROOT . '/api/index.php';
    exit;
}

// ── Admin routes ─────────────────────────────────────────────
if (($segments[0] ?? '') === 'admin') {
    // PHP built-in server quirk: re-route to admin/index.php
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
    require_once ROOT . '/admin/index.php';
    exit;
}

// ── Home / landing ────────────────────────────────────────────
if (empty($segments)) {
    require_once ROOT . '/templates/landing.php';
    exit;
}

// ── Restaurant routes ─────────────────────────────────────────
$slug    = $segments[0];                       // e.g. 'vogue'
$subpage = $segments[1] ?? '';                 // '' | 'menu'

// Load restaurant from DB
$restaurant = DB::one(
    'SELECT r.*, c.slug AS city_slug FROM restaurants r
     LEFT JOIN cities c ON c.id = r.city_id
     WHERE r.slug = ? AND r.is_active = 1',
    [$slug]
);

if (!$restaurant) {
    http_response_code(404);
    require_once ROOT . '/templates/404.php';
    exit;
}

if ($subpage === 'menu') {
    // Load all sections, categories and items
    $sections = DB::all(
        'SELECT * FROM sections WHERE restaurant_id = ? AND is_active = 1 ORDER BY sort_order',
        [$restaurant['id']]
    );
    $categories = DB::all(
        'SELECT * FROM categories WHERE restaurant_id = ? AND is_active = 1 ORDER BY sort_order',
        [$restaurant['id']]
    );
    $items = DB::all(
        'SELECT * FROM items WHERE restaurant_id = ? AND is_active = 1 ORDER BY category_id, sort_order',
        [$restaurant['id']]
    );
    $ads = DB::all(
        "SELECT * FROM ads WHERE restaurant_id = ? AND is_active = 1 AND position IN ('in-menu','pre-menu') ORDER BY sort_order",
        [$restaurant['id']]
    );
    require_once ROOT . '/templates/menu.php';
} else {
    // Splash / language-select page
    $ads = DB::all(
        "SELECT * FROM ads WHERE restaurant_id = ? AND is_active = 1 AND position = 'splash' ORDER BY sort_order",
        [$restaurant['id']]
    );
    require_once ROOT . '/templates/splash.php';
}
