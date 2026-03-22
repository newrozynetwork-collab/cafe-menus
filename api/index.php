<?php
/**
 * TiranaMenu Clone — Public REST API
 *
 * Routes:
 *   GET /api/restaurants              → list all active restaurants
 *   GET /api/restaurants/{slug}       → restaurant info
 *   GET /api/restaurants/{slug}/menu  → full menu (sections+cats+items)
 *   GET /api/restaurants/{slug}/categories → categories
 *   GET /api/restaurants/{slug}/items → items (with optional ?category_id=)
 *   GET /api/health                   → system health check
 */

if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET')     { json_response(['error'=>'Method not allowed'],405); }

// ── Parse path segments after /api/ ───────────────────────────
$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri      = preg_replace('#^/api#', '', $uri);
$segments = array_values(array_filter(explode('/', $uri)));
// segments: [] | ['health'] | ['restaurants'] | ['restaurants','vogue'] | ['restaurants','vogue','menu']

$seg0 = $segments[0] ?? '';
$seg1 = $segments[1] ?? '';
$seg2 = $segments[2] ?? '';

// ── Health check ──────────────────────────────────────────────
if ($seg0 === 'health') {
    json_response(['status'=>'ok','time'=>date('c'),'db'=>'sqlite']);
}

// ── /api/restaurants ──────────────────────────────────────────
if ($seg0 === 'restaurants') {

    // ── GET /api/restaurants
    if (!$seg1) {
        $rows = DB::all("
            SELECT r.id,r.name,r.slug,r.logo,r.theme_color,r.body_bg,r.font,r.default_lang,
                   r.has_sections,r.has_splash_video,r.has_ad,
                   r.social_facebook,r.social_instagram,r.social_phone,r.social_location,
                   c.name AS city
            FROM restaurants r
            LEFT JOIN cities c ON c.id=r.city_id
            WHERE r.is_active=1 ORDER BY r.name
        ");
        foreach ($rows as &$r) $r['logo_url'] = $r['logo'] ? asset_url($r['logo']) : null;
        json_response(['data'=>$rows,'count'=>count($rows)]);
    }

    $slug = $seg1;
    $rest = DB::one("
        SELECT r.*,c.name AS city,c.slug AS city_slug
        FROM restaurants r LEFT JOIN cities c ON c.id=r.city_id
        WHERE r.slug=? AND r.is_active=1
    ", [$slug]);

    if (!$rest) json_response(['error'=>'Restaurant not found'],404);

    // ── GET /api/restaurants/{slug}
    if (!$seg2) {
        $rest['logo_url'] = $rest['logo'] ? asset_url($rest['logo']) : null;
        unset($rest['password']);
        json_response(['data'=>$rest]);
    }

    // ── GET /api/restaurants/{slug}/menu
    if ($seg2 === 'menu') {
        $sections   = DB::all('SELECT id,name_en,name_ar,name_ku,sort_order FROM sections WHERE restaurant_id=? AND is_active=1 ORDER BY sort_order', [$rest['id']]);
        $categories = DB::all('SELECT id,section_id,name_en,name_ar,name_ku,icon,sort_order FROM categories WHERE restaurant_id=? AND is_active=1 ORDER BY sort_order', [$rest['id']]);
        $items      = DB::all('SELECT id,category_id,name_en,name_ar,name_ku,description_en,description_ar,description_ku,price,image FROM items WHERE restaurant_id=? AND is_active=1 ORDER BY sort_order', [$rest['id']]);
        $variants   = DB::all('SELECT v.* FROM variants v JOIN items i ON i.id=v.item_id WHERE i.restaurant_id=? ORDER BY v.sort_order', [$rest['id']]);

        // Attach variants to items
        $varMap = [];
        foreach ($variants as $v) $varMap[$v['item_id']][] = $v;

        foreach ($categories as &$c) {
            $c['icon_url'] = $c['icon'] ? asset_url($c['icon']) : null;
        }
        foreach ($items as &$item) {
            $item['image_url']  = $item['image'] ? asset_url($item['image']) : null;
            $item['variants']   = $varMap[$item['id']] ?? [];
        }

        json_response([
            'data' => [
                'restaurant' => [
                    'id'           => $rest['id'],
                    'name'         => $rest['name'],
                    'slug'         => $rest['slug'],
                    'logo_url'     => $rest['logo'] ? asset_url($rest['logo']) : null,
                    'theme_color'  => $rest['theme_color'],
                    'body_bg'      => $rest['body_bg'],
                    'font'         => $rest['font'],
                    'default_lang' => $rest['default_lang'],
                    'has_sections' => (bool)$rest['has_sections'],
                ],
                'sections'   => $sections,
                'categories' => $categories,
                'items'      => $items,
            ]
        ]);
    }

    // ── GET /api/restaurants/{slug}/categories
    if ($seg2 === 'categories') {
        $cats = DB::all('SELECT c.*,s.name_en AS section_name FROM categories c LEFT JOIN sections s ON s.id=c.section_id WHERE c.restaurant_id=? AND c.is_active=1 ORDER BY c.sort_order', [$rest['id']]);
        foreach ($cats as &$c) $c['icon_url'] = $c['icon'] ? asset_url($c['icon']) : null;
        json_response(['data'=>$cats,'count'=>count($cats)]);
    }

    // ── GET /api/restaurants/{slug}/items?category_id=X
    if ($seg2 === 'items') {
        $catId  = (int)($_GET['category_id'] ?? 0);
        $params = [$rest['id']];
        $where  = 'restaurant_id=? AND is_active=1';
        if ($catId) { $where .= ' AND category_id=?'; $params[] = $catId; }
        $items = DB::all("SELECT * FROM items WHERE $where ORDER BY sort_order", $params);
        foreach ($items as &$item) {
            $item['image_url'] = $item['image'] ? asset_url($item['image']) : null;
            $item['variants']  = DB::all('SELECT * FROM variants WHERE item_id=? ORDER BY sort_order', [$item['id']]);
        }
        json_response(['data'=>$items,'count'=>count($items)]);
    }
}

// ── 404 ───────────────────────────────────────────────────────
json_response([
    'error'  => 'Not found',
    'routes' => [
        'GET /api/health',
        'GET /api/restaurants',
        'GET /api/restaurants/{slug}',
        'GET /api/restaurants/{slug}/menu',
        'GET /api/restaurants/{slug}/categories',
        'GET /api/restaurants/{slug}/items?category_id=',
    ]
], 404);
