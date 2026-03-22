<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';
Auth::require('/admin/');

$pageTitle   = 'Media Library';
$restaurants = DB::all('SELECT id,name FROM restaurants ORDER BY name');
$selRestId   = (int)($_GET['restaurant_id'] ?? 0);
$mediaType   = $_GET['type'] ?? 'all';
$page        = max(1,(int)($_GET['page']??1));
$perPage     = 30;

// ── POST: Upload ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    $rid = (int)($_POST['restaurant_id'] ?? 0);

    if ($act === 'upload' && !empty($_FILES['files'])) {
        $uploaded = 0;
        $files = $_FILES['files'];
        // Handle multiple files
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $type = str_contains($files['type'][$i], 'video') ? 'video' : 'image';
            $up = handle_upload($file, $rid, $type);
            if ($up) $uploaded++;
        }
        flash('success', "Uploaded $uploaded file(s).");
    }

    if ($act === 'delete') {
        $mid  = (int)($_POST['media_id'] ?? 0);
        $file = DB::one('SELECT * FROM media WHERE id=?', [$mid]);
        if ($file && file_exists($file['path'])) @unlink($file['path']);
        DB::delete('media', 'id=?', [$mid]);
        flash('success', 'File deleted.');
    }

    header("Location: /admin/media.php?restaurant_id=$rid");
    exit;
}

// ── Load media ─────────────────────────────────────────────────
$where  = '1=1';
$params = [];
if ($selRestId) { $where .= ' AND restaurant_id=?'; $params[] = $selRestId; }
if ($mediaType !== 'all') { $where .= ' AND media_type=?'; $params[] = $mediaType; }

$pag   = paginate('media', $where, $params, $page, $perPage);
$files = DB::all("SELECT * FROM media WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET {$pag['offset']}", $params);

// Totals by type
$imgCount  = DB::val("SELECT COUNT(*) FROM media WHERE media_type='image'" . ($selRestId?" AND restaurant_id=$selRestId":''));
$vidCount  = DB::val("SELECT COUNT(*) FROM media WHERE media_type='video'" . ($selRestId?" AND restaurant_id=$selRestId":''));

ob_start(); ?>

<!-- Upload area -->
<div class="card">
  <div class="card-header"><span class="card-title">Upload Files</span></div>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="_action" value="upload">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group">
        <label>Restaurant</label>
        <select name="restaurant_id">
          <?php foreach ($restaurants as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $r['id']==$selRestId?'selected':'' ?>><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Files (images, videos)</label>
        <input type="file" name="files[]" multiple accept="image/*,video/*">
        <div class="upload-hint">
          🖼 <strong>Images:</strong> JPG / PNG / WebP &nbsp;·&nbsp; Max <strong>5 MB</strong> per image &nbsp;·&nbsp; Recommended max width <strong>1920 px</strong><br>
          🎬 <strong>Videos:</strong> MP4 (H.264) &nbsp;·&nbsp; Max <strong>100 MB</strong> per video &nbsp;·&nbsp; Max resolution <strong>1080p</strong><br>
          📂 You can select multiple files at once
        </div>
      </div>
    </div>
    <div style="margin-top:.75rem">
      <button class="btn btn-primary" type="submit">⬆ Upload</button>
    </div>
  </form>
</div>

<!-- Media library -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Library (<?= $pag['total'] ?> files)</span>
    <div style="display:flex;gap:.5rem;align-items:center">
      <span class="badge badge-blue">🖼 <?= $imgCount ?> images</span>
      <span class="badge badge-yellow">🎬 <?= $vidCount ?> videos</span>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-bar" style="margin-bottom:1rem">
    <form method="GET" style="display:contents">
      <select name="restaurant_id" onchange="this.form.submit()">
        <option value="">All Restaurants</option>
        <?php foreach ($restaurants as $r): ?>
        <option value="<?= $r['id'] ?>" <?= $r['id']==$selRestId?'selected':'' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="type" onchange="this.form.submit()">
        <option value="all"   <?= $mediaType==='all'?'selected':'' ?>>All Types</option>
        <option value="image" <?= $mediaType==='image'?'selected':'' ?>>Images</option>
        <option value="video" <?= $mediaType==='video'?'selected':'' ?>>Videos</option>
      </select>
    </form>
  </div>

  <?php if ($files): ?>
  <div class="media-grid">
    <?php foreach ($files as $f): ?>
    <div class="media-item" title="<?= e($f['original_name']) ?>">
      <?php if ($f['media_type'] === 'image'): ?>
        <img src="/<?= e($f['path']) ?>" alt="<?= e($f['original_name']) ?>" loading="lazy">
      <?php else: ?>
        <div style="background:#1a1a1a;width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem">🎬</div>
      <?php endif; ?>
      <div class="media-item-overlay">
        <span class="media-item-name"><?= e($f['original_name'] ?? $f['filename']) ?></span>
        <div style="display:flex;gap:.3rem;margin-top:.3rem">
          <button onclick="copyUrl('<?= e(asset_url($f['path'])) ?>')" class="btn btn-outline btn-sm" style="font-size:.65rem;padding:.15rem .4rem">Copy URL</button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file permanently?')">
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="media_id" value="<?= $f['id'] ?>">
            <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
            <button class="btn btn-danger btn-sm" style="font-size:.65rem;padding:.15rem .4rem">Del</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty-state"><span class="empty-icon">🖼</span><p>No media files yet. Upload some!</p></div>
  <?php endif; ?>
</div>

<script>
function copyUrl(url) {
  navigator.clipboard.writeText(url).then(() => alert('URL copied!\n' + url));
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT . '/admin/layout.php';
