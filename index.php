<?php
/**
 * TiranaMenu Clone — Main Router
 * Handles all frontend + API routes.
 * Admin routes are handled inside /admin/
 */

define('ROOT',      __DIR__);
// Auto-detect base URL — works on localhost and Railway/production
$_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
define('BASE_URL',  $_scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('BASE_PATH', '/');

// ── PHP built-in server: serve static files directly ─────────
// Without this, the router intercepts every .css/.js/.png request → 503
if (PHP_SAPI === 'cli-server') {
    $uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $filepath = ROOT . $uri;
    // Serve non-PHP static files (css, js, images, fonts, svg…) directly
    if ($uri !== '/' && is_file($filepath) && !str_ends_with($uri, '.php')) {
        // For video files: serve with range request support so browsers can stream
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'webm', 'mov', 'ogg'])) {
            $mimes = ['mp4'=>'video/mp4','webm'=>'video/webm','mov'=>'video/quicktime','ogg'=>'video/ogg'];
            $size  = filesize($filepath);
            header('Accept-Ranges: bytes');
            header('Content-Type: ' . ($mimes[$ext] ?? 'video/mp4'));
            if (isset($_SERVER['HTTP_RANGE'])) {
                preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m);
                $start = (int)$m[1];
                $end   = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : $size - 1;
            } else {
                // No Range header: serve first 1 MB only so PHP workers aren't
                // blocked streaming the full file before range requests arrive.
                $start = 0;
                $end   = min(1048575, $size - 1);
            }
            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");
            header('Content-Length: ' . ($end - $start + 1));
            // Disable output buffering so chunks stream immediately
            while (ob_get_level()) ob_end_clean();
            $fp = fopen($filepath, 'rb');
            fseek($fp, $start);
            $remaining = $end - $start + 1;
            while ($remaining > 0 && !feof($fp)) {
                $chunk = fread($fp, min(65536, $remaining));
                echo $chunk;
                flush();
                $remaining -= strlen($chunk);
            }
            fclose($fp);
            exit;
        }
        return false;
    }
}

require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';

// ── Parse route ──────────────────────────────────────────────
$path     = request_path();                    // e.g. /vogue/menu
$segments = array_values(array_filter(explode('/', $path)));
// segments: [] | ['vogue'] | ['vogue','menu'] | ['api','...']

// ── API routes ───────────────────────────────────────────────
if (($segments[0] ?? '') === 'api') {
    require_once ROOT . '/api/index.php';
    exit;
}

// ── Admin routes ─────────────────────────────────────────────
if (($segments[0] ?? '') === 'admin') {
    $uri       = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $adminFile = ROOT . $uri;

    // Route to the specific admin PHP file requested (dashboard.php, items.php, etc.)
    if (is_file($adminFile) && str_ends_with($adminFile, '.php')) {
        $_SERVER['PHP_SELF']    = $uri;
        $_SERVER['SCRIPT_NAME'] = $uri;
        require_once $adminFile;
    } else {
        // Default: login / index
        $_SERVER['PHP_SELF']    = '/admin/index.php';
        $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
        require_once ROOT . '/admin/index.php';
    }
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
