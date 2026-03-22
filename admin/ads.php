<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';
Auth::require('/admin/');

$pageTitle   = 'Ads & Videos';
$restaurants = DB::all('SELECT id,name FROM restaurants WHERE is_active=1 ORDER BY name');
$selRestId   = (int)($_GET['restaurant_id'] ?? ($restaurants[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    $aid = (int)($_POST['ad_id'] ?? 0);
    $rid = (int)($_POST['restaurant_id'] ?? 0);

    if ($act === 'ad_save') {
        $data = [
            'restaurant_id' => $rid,
            'title'         => trim($_POST['title'] ?? ''),
            'link'          => trim($_POST['link'] ?? ''),
            'ad_type'       => $_POST['ad_type'] ?? 'banner',
            'position'      => $_POST['position'] ?? 'pre-menu',
            'sort_order'    => (int)($_POST['sort_order'] ?? 0),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];
        // Image upload
        if (!empty($_FILES['image']['name'])) {
            $up = handle_upload($_FILES['image'], $rid, 'image');
            if ($up) $data['image'] = $up['path'];
        } elseif (!empty($_POST['existing_image'])) {
            $data['image'] = $_POST['existing_image'];
        }
        // Video upload
        if (!empty($_FILES['video']['name'])) {
            $up = handle_upload($_FILES['video'], $rid, 'video');
            if ($up) $data['video'] = $up['path'];
        } elseif (!empty($_POST['existing_video'])) {
            $data['video'] = $_POST['existing_video'];
        }

        $aid ? DB::update('ads', $data, 'id=?', [$aid]) : DB::insert('ads', $data);
        flash('success', 'Ad saved.');
    }
    if ($act === 'ad_delete' && $aid) {
        DB::delete('ads','id=?',[$aid]);
        flash('success','Ad deleted.');
    }
    if ($act === 'ad_toggle' && $aid) {
        $cur = DB::val('SELECT is_active FROM ads WHERE id=?',[$aid]);
        DB::run('UPDATE ads SET is_active=? WHERE id=?',[!$cur,$aid]);
    }
    header("Location: /admin/ads.php?restaurant_id=$rid");
    exit;
}

$ads = $selRestId ? DB::all('SELECT * FROM ads WHERE restaurant_id=? ORDER BY position,sort_order',[$selRestId]) : [];

ob_start(); ?>

<div class="filter-bar">
  <form method="GET">
    <select name="restaurant_id" onchange="this.form.submit()">
      <?php foreach ($restaurants as $r): ?>
      <option value="<?= $r['id'] ?>" <?= $r['id']==$selRestId?'selected':'' ?>><?= e($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <button class="btn btn-primary" onclick="openModal('ad-modal');resetAdForm()">+ New Ad / Video</button>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Ads & Videos (<?= count($ads) ?>)</span></div>
  <?php if ($ads): ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Preview</th><th>Title</th><th>Type</th><th>Position</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($ads as $ad): ?>
    <tr>
      <td>
        <?php if ($ad['image']): ?><img src="/<?= e($ad['image']) ?>" class="thumb"><?php
        elseif ($ad['video']): ?><div class="thumb-placeholder">🎬</div><?php
        else: ?><div class="thumb-placeholder">📺</div><?php endif; ?>
      </td>
      <td><strong><?= e($ad['title'] ?: '—') ?></strong></td>
      <td><span class="badge badge-blue"><?= ucfirst($ad['ad_type']) ?></span></td>
      <td><span class="badge badge-gray"><?= e($ad['position']) ?></span></td>
      <td><span class="badge <?= $ad['is_active']?'badge-green':'badge-red' ?>"><?= $ad['is_active']?'Active':'Inactive' ?></span></td>
      <td>
        <div class="action-btns">
          <button class="btn btn-outline btn-sm" onclick='editAd(<?= json_encode($ad,JSON_UNESCAPED_UNICODE) ?>)'>Edit</button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete ad?')">
            <input type="hidden" name="_action" value="ad_delete">
            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
            <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
            <button class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <div class="empty-state"><span class="empty-icon">📺</span><p>No ads yet. Add a pre-menu video or banner image.</p></div>
  <?php endif; ?>
</div>

<div class="card" style="background:#fffbeb;border-color:#fde68a">
  <h3 style="font-size:.9rem;margin-bottom:.5rem">📺 How Ads Work</h3>
  <ul style="font-size:.82rem;color:var(--gray-700);padding-left:1.25rem;line-height:1.8">
    <li><strong>Splash</strong> — shown on the language-select splash page (e.g. Almajlees video background)</li>
    <li><strong>Pre-menu</strong> — shown before the menu loads (interstitial/intro)</li>
    <li><strong>In-menu</strong> — injected into the menu grid between items</li>
    <li><strong>Banner</strong> type = image ad | <strong>Video</strong> type = autoplay MP4 | <strong>Popup</strong> type = modal overlay</li>
  </ul>
</div>

<!-- Ad Modal -->
<div class="modal-backdrop" id="ad-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="ad-modal-title">New Ad / Video</h3>
      <button onclick="closeModal('ad-modal')" style="background:none;border:none;cursor:pointer;font-size:1.3rem">×</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="_action" value="ad_save">
        <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
        <input type="hidden" name="ad_id" id="ad-id-input" value="">
        <input type="hidden" name="existing_image" id="ad-existing-img" value="">
        <input type="hidden" name="existing_video" id="ad-existing-vid" value="">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group">
            <label>Title / Label</label>
            <input type="text" name="title" id="ad-title" placeholder="e.g. Opening Promo">
          </div>
          <div class="form-group">
            <label>Ad Type</label>
            <select name="ad_type" id="ad-type">
              <option value="banner">Banner (image)</option>
              <option value="video">Video (MP4)</option>
              <option value="popup">Popup</option>
            </select>
          </div>
          <div class="form-group">
            <label>Position</label>
            <select name="position" id="ad-position">
              <option value="splash">Splash page background</option>
              <option value="pre-menu">Pre-menu (interstitial)</option>
              <option value="in-menu">In-menu (between items)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Click Link (optional)</label>
            <input type="url" name="link" id="ad-link" placeholder="https://...">
          </div>
          <div class="form-group">
            <label>Image</label>
            <input type="file" name="image" accept="image/*">
            <div class="upload-hint">
              📐 Banner: <strong>1200 × 400 px</strong> &nbsp;·&nbsp; Popup: <strong>800 × 800 px</strong> &nbsp;·&nbsp;
              📁 Max: <strong>2 MB</strong> &nbsp;·&nbsp; Format: <strong>JPG / PNG / WebP</strong>
            </div>
            <div id="ad-img-preview" class="img-preview-wrap"></div>
          </div>
          <div class="form-group">
            <label>Video (MP4)</label>
            <input type="file" name="video" accept="video/*">
            <div class="upload-hint">
              🎬 Format: <strong>MP4 (H.264)</strong> &nbsp;·&nbsp;
              📁 Max: <strong>100 MB</strong> &nbsp;·&nbsp;
              📐 Resolution: <strong>1080p or 720p</strong> &nbsp;·&nbsp;
              ⏱ Splash videos: keep under <strong>15 seconds</strong>
            </div>
            <div id="ad-vid-preview"></div>
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" id="ad-sort" value="0" min="0">
          </div>
          <div class="form-group">
            <label style="display:flex;gap:.5rem;align-items:center">
              <input type="checkbox" name="is_active" id="ad-active" value="1" checked> Active
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('ad-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function resetAdForm() {
  document.getElementById('ad-modal-title').textContent = 'New Ad / Video';
  ['ad-id-input','ad-title','ad-link'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
  document.getElementById('ad-type').value = 'banner';
  document.getElementById('ad-position').value = 'pre-menu';
  document.getElementById('ad-sort').value = '0';
  document.getElementById('ad-active').checked = true;
  document.getElementById('ad-img-preview').innerHTML = '';
  document.getElementById('ad-existing-img').value = '';
  document.getElementById('ad-existing-vid').value = '';
}
function editAd(ad) {
  resetAdForm();
  document.getElementById('ad-modal-title').textContent = 'Edit Ad';
  document.getElementById('ad-id-input').value  = ad.id;
  document.getElementById('ad-title').value     = ad.title || '';
  document.getElementById('ad-link').value      = ad.link || '';
  document.getElementById('ad-type').value      = ad.ad_type || 'banner';
  document.getElementById('ad-position').value  = ad.position || 'pre-menu';
  document.getElementById('ad-sort').value      = ad.sort_order || 0;
  document.getElementById('ad-active').checked  = !!ad.is_active;
  document.getElementById('ad-existing-img').value = ad.image || '';
  document.getElementById('ad-existing-vid').value = ad.video || '';
  if (ad.image) document.getElementById('ad-img-preview').innerHTML = `<img src="/${ad.image}" class="img-preview">`;
  openModal('ad-modal');
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT . '/admin/layout.php';
