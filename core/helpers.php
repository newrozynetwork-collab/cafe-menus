<?php
/**
 * Global helper functions
 */

/** HTML-escape a string */
function e(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Return JSON response and exit */
function json_response(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Redirect and exit */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** Format price with thousands separator */
function format_price(float $price, string $currency = 'IQD'): string {
    return number_format($price, 0) . ' ' . $currency;
}

/** Get public URL for an uploads/ path */
function asset_url(string $path): string {
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

/** Sanitize a slug */
function slugify(string $str): string {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\-\.]+/', '-', $str);
    return trim($str, '-');
}

/** Get current request path */
function request_path(): string {
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $base = rtrim(BASE_PATH, '/');
    if ($base && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . ltrim($path, '/');
}

/** Get localized field value */
function localized(array $row, string $field, string $lang = 'en'): string {
    $col = $field . '_' . $lang;
    return $row[$col] ?? $row[$field . '_en'] ?? '';
}

/** Upload a file to the uploads directory */
function handle_upload(array $file, int $restaurantId, string $type = 'image'): ?array {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/webp','image/gif','video/mp4','video/webm'];
    if (!in_array($file['type'], $allowed)) return null;

    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    $rest = DB::one('SELECT slug,city_id FROM restaurants r JOIN cities c ON c.id=r.city_id WHERE r.id=?', [$restaurantId]);
    $city = $rest['slug'] ?? 'uploads';
    $slug = $rest['slug'] ?? 'general';
    $dir  = ROOT . "/uploads/$city/$slug/";

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $serverPath = $dir . $name;
    if (!move_uploaded_file($file['tmp_name'], $serverPath)) return null;

    $relPath = "uploads/$city/$slug/$name";
    $id = DB::insert('media', [
        'restaurant_id' => $restaurantId,
        'filename'      => $name,
        'original_name' => $file['name'],
        'mime_type'     => $file['type'],
        'file_size'     => $file['size'],
        'media_type'    => $type,
        'path'          => $serverPath,
        'url'           => asset_url($relPath),
    ]);
    return ['id' => $id, 'path' => $relPath, 'url' => asset_url($relPath)];
}

/** CSRF token generation & verification */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_verify(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Pagination helper */
function paginate(string $table, string $where, array $params, int $page, int $perPage = 20): array {
    $total  = (int) DB::val("SELECT COUNT(*) FROM $table WHERE $where", $params);
    $pages  = max(1, (int)ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;
    return ['total'=>$total,'pages'=>$pages,'page'=>$page,'offset'=>$offset,'perPage'=>$perPage];
}

/** Flash messages */
function flash(string $key, string $msg = ''): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if ($msg) { $_SESSION['flash'][$key] = $msg; return ''; }
    $val = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $val;
}
