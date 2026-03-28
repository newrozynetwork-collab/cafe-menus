<?php
defined('ROOT') || define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';
Auth::require('/admin/');

$pageTitle = 'Restaurants';
$action    = $_GET['action'] ?? 'list';
$id        = (int)($_GET['id'] ?? 0);
$cities    = DB::all('SELECT * FROM cities ORDER BY name');

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $data = [
            'city_id'          => (int)$_POST['city_id'] ?: null,
            'name'             => trim($_POST['name'] ?? ''),
            'slug'             => slugify(trim($_POST['slug'] ?? '')),
            'theme_color'      => $_POST['theme_color'] ?? '#910000',
            'body_bg'          => $_POST['body_bg'] ?? '#141414',
            'font'             => $_POST['font'] ?? 'Poppins',
            'default_lang'     => $_POST['default_lang'] ?? 'en',
            'has_sections'     => isset($_POST['has_sections']) ? 1 : 0,
            'social_facebook'  => trim($_POST['social_facebook'] ?? ''),
            'social_instagram' => trim($_POST['social_instagram'] ?? ''),
            'social_phone'     => trim($_POST['social_phone'] ?? ''),
            'social_location'  => trim($_POST['social_location'] ?? ''),
            'has_splash_video' => isset($_POST['has_splash_video']) ? 1 : 0,
            'splash_video_url' => trim($_POST['splash_video_url'] ?? ''),
            'has_ad'           => isset($_POST['has_ad']) ? 1 : 0,
            'is_active'        => isset($_POST['is_active']) ? 1 : 0,
        ];
        // Handle logo upload
        if (!empty($_FILES['logo']['name'])) {
            $rid = $id ?: 0;
            // For new restaurant, we'll update logo after insert
        }

        if ($id) {
            DB::update('restaurants', $data, 'id = ?', [$id]);
            flash('success', 'Restaurant updated.');
        } else {
            $id = DB::insert('restaurants', $data);
            flash('success', 'Restaurant created.');
        }
        // Handle logo upload now we have ID
        if (!empty($_FILES['logo']['name'])) {
            $up = handle_upload($_FILES['logo'], $id, 'image');
            if ($up) DB::run("UPDATE restaurants SET logo=? WHERE id=?", [$up['path'], $id]);
        }
        header('Location: /admin/restaurants.php');
        exit;
    }

    if ($act === 'delete' && $id) {
        DB::delete('restaurants','id=?',[$id]);
        flash('success','Restaurant deleted.');
        header('Location: /admin/restaurants.php');
        exit;
    }

    if ($act === 'toggle' && $id) {
        $cur = DB::val('SELECT is_active FROM restaurants WHERE id=?',[$id]);
        DB::run('UPDATE restaurants SET is_active=? WHERE id=?',[!$cur, $id]);
        header('Location: /admin/restaurants.php');
        exit;
    }
}

// ── Load data for edit ─────────────────────────────────────────
$restaurant = $id ? DB::one('SELECT * FROM restaurants WHERE id=?',[$id]) : null;

ob_start(); ?>

<?php if ($action === 'list'): ?>
<div class="card">
  <div class="card-header">
    <span class="card-title">All Restaurants</span>
    <a href="?action=new" class="btn btn-primary">+ New Restaurant</a>
  </div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Logo</th><th>Name</th><th>Slug / URL</th><th>Theme</th><th>Lang</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $rows = DB::all('SELECT r.*,c.name AS city_name FROM restaurants r LEFT JOIN cities c ON c.id=r.city_id ORDER BY r.created_at DESC');
    foreach ($rows as $r): ?>
    <tr>
      <td>
        <?php if ($r['logo']): ?>
        <img src="/<?= e($r['logo']) ?>" class="thumb" alt="">
        <?php else: ?><div class="thumb-placeholder">🏪</div><?php endif; ?>
      </td>
      <td><strong><?= e($r['name']) ?></strong><br><small style="color:var(--gray-500)"><?= e($r['city_name']??'') ?></small></td>
      <td><a href="/<?= e($r['slug']) ?>" target="_blank">/<?= e($r['slug']) ?></a></td>
      <td>
        <span class="color-dot" style="background:<?= e($r['theme_color']) ?>" title="<?= e($r['theme_color']) ?>"></span>
        <code style="font-size:.7rem"><?= e($r['theme_color']) ?></code>
      </td>
      <td><?= strtoupper($r['default_lang']) ?></td>
      <td><span class="badge <?= $r['is_active']?'badge-green':'badge-red' ?>"><?= $r['is_active']?'Active':'Inactive' ?></span></td>
      <td>
        <div class="action-btns">
          <a href="/<?= e($r['slug']) ?>/menu" target="_blank" class="btn btn-outline btn-sm">👁</a>
          <a href="?action=edit&id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
          <form method="POST" style="display:inline" onsubmit="return confirm('Toggle active status?')">
            <input type="hidden" name="_action" value="toggle">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn btn-outline btn-sm"><?= $r['is_active']?'⏸':'▶' ?></button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this restaurant and all its data?')">
            <input type="hidden" name="_action" value="delete">
            <button class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty-state"><span class="empty-icon">🏪</span><p>No restaurants yet.</p></td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
<div class="card" style="max-width:760px">
  <div class="card-header">
    <span class="card-title"><?= $restaurant ? 'Edit: '.e($restaurant['name']) : 'New Restaurant' ?></span>
    <a href="/admin/restaurants.php" class="btn btn-outline btn-sm">← Back</a>
  </div>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="_action" value="save">
    <?= csrf_field() ?>
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <!-- Basic Info -->
    <div class="tab-nav">
      <button type="button" class="tab-nav-btn active" onclick="showTab('basic',this)">Basic Info</button>
      <button type="button" class="tab-nav-btn" onclick="showTab('theme',this)">Theme</button>
      <button type="button" class="tab-nav-btn" onclick="showTab('social',this)">Social</button>
      <button type="button" class="tab-nav-btn" onclick="showTab('media',this)">Video & Ads</button>
    </div>

    <div class="tab-pane active" id="tab-basic">
      <div class="form-grid">
        <div class="form-group">
          <label>Restaurant Name *</label>
          <input type="text" name="name" value="<?= e($restaurant['name']??'') ?>" required placeholder="e.g. Vogue Cafe & Lounge">
        </div>
        <div class="form-group">
          <label>Slug (URL path) *</label>
          <input type="text" name="slug" value="<?= e($restaurant['slug']??'') ?>" required placeholder="e.g. vogue">
          <span class="form-hint">Becomes: yourdomain.com/<strong>slug</strong></span>
        </div>
        <div class="form-group">
          <label>City</label>
          <select name="city_id">
            <option value="">— None —</option>
            <?php foreach ($cities as $city): ?>
            <option value="<?= $city['id'] ?>" <?= ($restaurant['city_id']??'')==$city['id']?'selected':'' ?>><?= e($city['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Default Language</label>
          <select name="default_lang">
            <option value="en" <?= ($restaurant['default_lang']??'en')==='en'?'selected':'' ?>>English</option>
            <option value="ar" <?= ($restaurant['default_lang']??'')==='ar'?'selected':'' ?>>العربية</option>
            <option value="ku" <?= ($restaurant['default_lang']??'')==='ku'?'selected':'' ?>>کوردی</option>
          </select>
        </div>
        <div class="form-group">
          <label>Logo Image</label>
          <input type="file" name="logo" accept="image/*">
          <div class="upload-hint">
            📐 Recommended: <strong>400 × 400 px</strong> (square) &nbsp;·&nbsp;
            📁 Max size: <strong>1 MB</strong> &nbsp;·&nbsp;
            🖼 Format: <strong>PNG or WebP</strong> (transparent background ideal)
          </div>
          <?php if (!empty($restaurant['logo'])): ?>
          <div class="img-preview-wrap"><img src="/<?= e($restaurant['logo']) ?>" class="img-preview" alt=""></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Font</label>
          <select name="font">
            <option value="Poppins" <?= ($restaurant['font']??'')==='Poppins'?'selected':'' ?>>Poppins</option>
            <option value="rabar"   <?= ($restaurant['font']??'')==='rabar'?'selected':'' ?>>Rabar (Kurdish)</option>
            <option value="Almarai" <?= ($restaurant['font']??'')==='Almarai'?'selected':'' ?>>Almarai (Arabic)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Features</label>
          <div style="display:flex;flex-direction:column;gap:.4rem;margin-top:.25rem">
            <label style="display:flex;gap:.5rem;align-items:center;font-weight:400">
              <input type="checkbox" name="has_sections" value="1" <?= ($restaurant['has_sections']??0)?'checked':'' ?>>
              Show Section Tabs (Food / Drinks / Hookah)
            </label>
            <label style="display:flex;gap:.5rem;align-items:center;font-weight:400">
              <input type="checkbox" name="is_active" value="1" <?= ($restaurant['is_active']??1)?'checked':'' ?>>
              Restaurant is Active
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane" id="tab-theme">
      <div class="form-grid">
        <div class="form-group">
          <label>Brand / Theme Color</label>
          <div style="display:flex;gap:.5rem;align-items:center">
            <input type="color" name="theme_color" value="<?= e($restaurant['theme_color']??'#910000') ?>">
            <input type="text" value="<?= e($restaurant['theme_color']??'#910000') ?>" style="max-width:120px" oninput="this.previousElementSibling.value=this.value" placeholder="#910000">
          </div>
          <span class="form-hint">Header, buttons, accents, footer</span>
        </div>
        <div class="form-group">
          <label>Page Background Color</label>
          <div style="display:flex;gap:.5rem;align-items:center">
            <input type="color" name="body_bg" value="<?= e($restaurant['body_bg']??'#141414') ?>">
            <input type="text" value="<?= e($restaurant['body_bg']??'#141414') ?>" style="max-width:120px" oninput="this.previousElementSibling.value=this.value" placeholder="#141414">
          </div>
          <span class="form-hint">Use #fff for white (Vogue style) or #141414 for dark</span>
        </div>
      </div>
    </div>

    <div class="tab-pane" id="tab-social">
      <div class="form-grid">
        <div class="form-group">
          <label>Facebook URL</label>
          <input type="url" name="social_facebook" value="<?= e($restaurant['social_facebook']??'') ?>" placeholder="https://facebook.com/...">
        </div>
        <div class="form-group">
          <label>Instagram URL</label>
          <input type="url" name="social_instagram" value="<?= e($restaurant['social_instagram']??'') ?>" placeholder="https://instagram.com/...">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="social_phone" value="<?= e($restaurant['social_phone']??'') ?>" placeholder="+9647...">
        </div>
        <div class="form-group">
          <label>Google Maps URL</label>
          <input type="url" name="social_location" value="<?= e($restaurant['social_location']??'') ?>" placeholder="https://maps.google.com/...">
        </div>
      </div>
    </div>

    <div class="tab-pane" id="tab-media">
      <div class="form-grid">
        <div class="form-group">
          <label style="display:flex;gap:.5rem;align-items:center">
            <input type="checkbox" name="has_splash_video" value="1" <?= ($restaurant['has_splash_video']??0)?'checked':'' ?>>
            Show video on splash/language page
          </label>
        </div>
        <div class="form-group full">
          <label>Splash Video URL (or path)</label>
          <input type="text" name="splash_video_url" value="<?= e($restaurant['splash_video_url']??'') ?>" placeholder="uploads/Slemani/almajlees/th/v/video.mp4">
          <div class="upload-hint">
            🎬 Format: <strong>MP4 (H.264)</strong> &nbsp;·&nbsp;
            📐 Resolution: <strong>1080p or 720p</strong> &nbsp;·&nbsp;
            📁 Max size: <strong>50 MB</strong> &nbsp;·&nbsp;
            ⏱ Keep under <strong>15 seconds</strong> for fast loading — upload via Media tab first, then paste the path here
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;gap:.5rem;align-items:center">
            <input type="checkbox" name="has_ad" value="1" <?= ($restaurant['has_ad']??0)?'checked':'' ?>>
            Has Advertisement Slots
          </label>
          <span class="form-hint">Manage individual ads in the <a href="/admin/ads.php">Ads section</a></span>
        </div>
      </div>
    </div>

    <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--gray-200);display:flex;gap:.5rem">
      <button class="btn btn-primary" type="submit">💾 Save Restaurant</button>
      <a href="/admin/restaurants.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once ROOT . '/admin/layout.php';
