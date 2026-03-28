<?php
defined('ROOT') || define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';
Auth::require('/admin/');

$pageTitle   = 'Menus & Categories';
$restaurants = DB::all('SELECT id,name,slug,has_sections FROM restaurants WHERE is_active=1 ORDER BY name');
$selRestId   = (int)($_GET['restaurant_id'] ?? ($restaurants[0]['id'] ?? 0));
$selRest     = $selRestId ? DB::one('SELECT * FROM restaurants WHERE id=?', [$selRestId]) : null;

// ── POST handlers ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    $rid = (int)($_POST['restaurant_id'] ?? $selRestId);

    // ── Category actions
    if ($act === 'cat_save') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        $data  = [
            'restaurant_id' => $rid,
            'section_id'    => $_POST['section_id'] ?: null,
            'name_en'       => trim($_POST['name_en'] ?? ''),
            'name_ar'       => trim($_POST['name_ar'] ?? ''),
            'name_ku'       => trim($_POST['name_ku'] ?? ''),
            'sort_order'    => (int)($_POST['sort_order'] ?? 0),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];
        if (!empty($_FILES['icon']['name'])) {
            $up = handle_upload($_FILES['icon'], $rid, 'image');
            if ($up) $data['icon'] = $up['path'];
        }
        $catId ? DB::update('categories', $data, 'id=?', [$catId]) : DB::insert('categories', $data);
        flash('success', 'Category saved.');
    }

    if ($act === 'cat_delete') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        DB::delete('categories', 'id=?', [$catId]);
        flash('success', 'Category deleted.');
    }

    if ($act === 'cat_toggle') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        $cur   = DB::val('SELECT is_active FROM categories WHERE id=?', [$catId]);
        DB::run('UPDATE categories SET is_active=? WHERE id=?', [!$cur, $catId]);
    }

    if ($act === 'cat_sort') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        foreach ($ids as $i => $cid) {
            DB::run('UPDATE categories SET sort_order=? WHERE id=?', [$i, (int)$cid]);
        }
        json_response(['ok' => true]);
    }

    // ── Section actions
    if ($act === 'sec_save') {
        $secId = (int)($_POST['sec_id'] ?? 0);
        $data  = [
            'restaurant_id' => $rid,
            'name_en'       => trim($_POST['name_en'] ?? ''),
            'name_ar'       => trim($_POST['name_ar'] ?? ''),
            'name_ku'       => trim($_POST['name_ku'] ?? ''),
            'sort_order'    => (int)($_POST['sort_order'] ?? 0),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];
        $secId ? DB::update('sections', $data, 'id=?', [$secId]) : DB::insert('sections', $data);
        flash('success', 'Section saved.');
    }
    if ($act === 'sec_delete') {
        $secId = (int)($_POST['sec_id'] ?? 0);
        DB::delete('sections','id=?',[$secId]);
        flash('success','Section deleted.');
    }

    header("Location: /admin/menus.php?restaurant_id=$rid");
    exit;
}

// ── Load data ──────────────────────────────────────────────────
$sections   = $selRestId ? DB::all('SELECT * FROM sections WHERE restaurant_id=? ORDER BY sort_order', [$selRestId]) : [];
$categories = $selRestId ? DB::all('SELECT c.*,s.name_en AS section_name FROM categories c LEFT JOIN sections s ON s.id=c.section_id WHERE c.restaurant_id=? ORDER BY c.sort_order', [$selRestId]) : [];

ob_start(); ?>

<!-- Restaurant selector -->
<div class="filter-bar" style="margin-bottom:1rem">
  <form method="GET">
    <div style="display:flex;gap:.5rem;align-items:center">
      <select name="restaurant_id" onchange="this.form.submit()" style="min-width:200px">
        <?php foreach ($restaurants as $r): ?>
        <option value="<?= $r['id'] ?>" <?= $r['id']==$selRestId?'selected':'' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <a href="/<?= e($selRest['slug']??'') ?>/menu" target="_blank" class="btn btn-outline btn-sm">👁 View Menu</a>
    </div>
  </form>
</div>

<?php if ($selRest && $selRest['has_sections']): ?>
<!-- ── Sections ──────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Menu Sections</span>
    <button class="btn btn-primary btn-sm" onclick="openModal('sec-modal')">+ Add Section</button>
  </div>
  <?php if ($sections): ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>#</th><th>Name (EN)</th><th>Arabic</th><th>Kurdish</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($sections as $sec): ?>
    <tr>
      <td><?= $sec['sort_order'] ?></td>
      <td><strong><?= e($sec['name_en']) ?></strong></td>
      <td dir="rtl"><?= e($sec['name_ar']) ?></td>
      <td dir="rtl"><?= e($sec['name_ku']) ?></td>
      <td><span class="badge <?= $sec['is_active']?'badge-green':'badge-red' ?>"><?= $sec['is_active']?'Active':'Hidden' ?></span></td>
      <td>
        <button class="btn btn-outline btn-sm" onclick='editSection(<?= json_encode($sec) ?>)'>Edit</button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete section?')">
          <input type="hidden" name="_action" value="sec_delete">
          <input type="hidden" name="sec_id" value="<?= $sec['id'] ?>">
          <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
          <button class="btn btn-danger btn-sm">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <div class="empty-state"><span class="empty-icon">📑</span><p>No sections yet. Add Food, Drinks, Hookah, etc.</p></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Categories ─────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Categories (<?= count($categories) ?>)</span>
    <button class="btn btn-primary btn-sm" onclick="openModal('cat-modal')">+ Add Category</button>
  </div>
  <?php if ($categories): ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>⠿</th><th>Icon</th><th>Name (EN)</th><th>Arabic</th><th>Kurdish</th><th>Section</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody id="catSortable">
    <?php foreach ($categories as $cat): ?>
    <tr data-id="<?= $cat['id'] ?>">
      <td class="drag-handle">⠿</td>
      <td>
        <?php if ($cat['icon']): ?>
        <img src="/<?= e($cat['icon']) ?>" class="thumb" alt="">
        <?php else: ?><div class="thumb-placeholder">📂</div><?php endif; ?>
      </td>
      <td><strong><?= e($cat['name_en']) ?></strong></td>
      <td dir="rtl"><?= e($cat['name_ar']) ?></td>
      <td dir="rtl"><?= e($cat['name_ku']) ?></td>
      <td><?= e($cat['section_name'] ?? '—') ?></td>
      <td><span class="badge <?= $cat['is_active']?'badge-green':'badge-red' ?>"><?= $cat['is_active']?'Active':'Hidden' ?></span></td>
      <td>
        <div class="action-btns">
          <button class="btn btn-outline btn-sm" onclick='editCategory(<?= json_encode($cat) ?>)'>Edit</button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete category? Items will also be removed.')">
            <input type="hidden" name="_action" value="cat_delete">
            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
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
  <div class="empty-state"><span class="empty-icon">📂</span><p>No categories yet.</p></div>
  <?php endif; ?>
</div>

<!-- Category Modal -->
<div class="modal-backdrop" id="cat-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="cat-modal-title">Add Category</h3>
      <button onclick="closeModal('cat-modal')" style="background:none;border:none;cursor:pointer;font-size:1.3rem">×</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="_action" value="cat_save">
        <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
        <input type="hidden" name="cat_id" id="cat-id-input" value="">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group">
            <label>Name (English) *</label>
            <input type="text" name="name_en" id="cat-name-en" required placeholder="e.g. Cold Appetizers">
          </div>
          <div class="form-group">
            <label>Name (Arabic)</label>
            <input type="text" name="name_ar" id="cat-name-ar" placeholder="المقبلات الباردة" dir="rtl">
          </div>
          <div class="form-group">
            <label>Name (Kurdish)</label>
            <input type="text" name="name_ku" id="cat-name-ku" placeholder="خواردنی سارد" dir="rtl">
          </div>
          <?php if ($sections): ?>
          <div class="form-group">
            <label>Section</label>
            <select name="section_id" id="cat-section">
              <option value="">— None —</option>
              <?php foreach ($sections as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['name_en']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" id="cat-sort" value="0" min="0">
          </div>
          <div class="form-group">
            <label>Category Icon Image</label>
            <input type="file" name="icon" accept="image/*">
            <div class="upload-hint">
              📐 Recommended: <strong>200 × 200 px</strong> (square) &nbsp;·&nbsp;
              📁 Max size: <strong>500 KB</strong> &nbsp;·&nbsp;
              🖼 Format: <strong>PNG</strong> with transparent background preferred
            </div>
            <div id="cat-icon-preview" class="img-preview-wrap"></div>
          </div>
          <div class="form-group">
            <label style="display:flex;gap:.5rem;align-items:center">
              <input type="checkbox" name="is_active" id="cat-active" value="1" checked> Active
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('cat-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Section Modal -->
<div class="modal-backdrop" id="sec-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="sec-modal-title">Add Section</h3>
      <button onclick="closeModal('sec-modal')" style="background:none;border:none;cursor:pointer;font-size:1.3rem">×</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="_action" value="sec_save">
        <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
        <input type="hidden" name="sec_id" id="sec-id-input" value="">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group">
            <label>Name (English) *</label>
            <input type="text" name="name_en" id="sec-name-en" required placeholder="Food">
          </div>
          <div class="form-group">
            <label>Name (Arabic)</label>
            <input type="text" name="name_ar" id="sec-name-ar" placeholder="طعام" dir="rtl">
          </div>
          <div class="form-group">
            <label>Name (Kurdish)</label>
            <input type="text" name="name_ku" id="sec-name-ku" placeholder="خواردن" dir="rtl">
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" id="sec-sort" value="0" min="0">
          </div>
          <div class="form-group">
            <label style="display:flex;gap:.5rem;align-items:center">
              <input type="checkbox" name="is_active" id="sec-active" value="1" checked> Active
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('sec-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Section</button>
      </div>
    </form>
  </div>
</div>

<script>
function editCategory(cat) {
  document.getElementById('cat-modal-title').textContent = 'Edit Category';
  document.getElementById('cat-id-input').value  = cat.id;
  document.getElementById('cat-name-en').value   = cat.name_en || '';
  document.getElementById('cat-name-ar').value   = cat.name_ar || '';
  document.getElementById('cat-name-ku').value   = cat.name_ku || '';
  document.getElementById('cat-sort').value      = cat.sort_order || 0;
  document.getElementById('cat-active').checked  = !!cat.is_active;
  const secEl = document.getElementById('cat-section');
  if (secEl) secEl.value = cat.section_id || '';
  const prev = document.getElementById('cat-icon-preview');
  prev.innerHTML = cat.icon ? `<img src="/${cat.icon}" class="img-preview" alt="">` : '';
  openModal('cat-modal');
}
function editSection(sec) {
  document.getElementById('sec-modal-title').textContent = 'Edit Section';
  document.getElementById('sec-id-input').value  = sec.id;
  document.getElementById('sec-name-en').value   = sec.name_en || '';
  document.getElementById('sec-name-ar').value   = sec.name_ar || '';
  document.getElementById('sec-name-ku').value   = sec.name_ku || '';
  document.getElementById('sec-sort').value      = sec.sort_order || 0;
  document.getElementById('sec-active').checked  = !!sec.is_active;
  openModal('sec-modal');
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT . '/admin/layout.php';
